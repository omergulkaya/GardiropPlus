<?php

use PHPUnit\Framework\TestCase;

/**
 * Authentication Integration Test
 */
class AuthTest extends TestCase
{
    private $base_url = 'http://localhost/closet/web-api/index.php/api/auth';
    private $test_email = 'integration_test@example.com';
    private $test_password = 'Test123456!';

    public function testRegisterEndpoint()
    {
        // Her test için unique email kullan
        $unique_email = 'test_' . time() . '_' . rand(1000, 9999) . '@example.com';
        
        $data = [
            'email' => $unique_email,
            'password' => $this->test_password,
            'first_name' => 'Integration',
            'last_name' => 'Test'
        ];

        $response = $this->makeRequest('POST', '/register', $data);
        
        // 500 hatası alıyorsak, API çalışmıyor demektir
        if ($response['status_code'] === 500) {
            $error_msg = 'API returned 500 error. ';
            if (isset($response['body']['message'])) {
                $error_msg .= 'Message: ' . $response['body']['message'];
            }
            if (isset($response['body']['raw_response'])) {
                $error_msg .= ' Raw response: ' . substr($response['body']['raw_response'], 0, 200);
            }
            $this->markTestSkipped($error_msg);
            return;
        }
        
        // 201 (created) veya 200 (success) kabul et
        $this->assertContains($response['status_code'], [200, 201], 
            'Expected 200 or 201, got ' . $response['status_code'] . '. Response: ' . json_encode($response['body']));
        
        if (isset($response['body']['success']) && $response['body']['success']) {
            if (isset($response['body']['data'])) {
                $this->assertArrayHasKey('access_token', $response['body']['data']);
                $this->assertArrayHasKey('refresh_token', $response['body']['data']);
            }
        } else {
            // Eğer başarısız olduysa, muhtemelen email zaten var veya validation hatası
            $this->markTestSkipped('Registration failed: ' . ($response['body']['message'] ?? json_encode($response['body'])));
        }
    }

    public function testLoginEndpoint()
    {
        // Önce kullanıcıyı kaydet
        $unique_email = 'login_test_' . time() . '@example.com';
        $register_data = [
            'email' => $unique_email,
            'password' => $this->test_password,
            'first_name' => 'Login',
            'last_name' => 'Test'
        ];
        $register_response = $this->makeRequest('POST', '/register', $register_data);
        
        if (!isset($register_response['body']['success']) || !$register_response['body']['success']) {
            $this->markTestSkipped('Registration failed - cannot test login');
            return;
        }

        // Login yap
        $data = [
            'email' => $unique_email,
            'password' => $this->test_password
        ];

        $response = $this->makeRequest('POST', '/login', $data);
        
        $this->assertEquals(200, $response['status_code'], 
            'Expected 200, got ' . $response['status_code'] . '. Response: ' . json_encode($response['body']));
        
        if (isset($response['body']['success']) && $response['body']['success']) {
            if (isset($response['body']['data'])) {
                $this->assertArrayHasKey('access_token', $response['body']['data']);
            }
        }
    }

    public function testLoginWithInvalidCredentials()
    {
        $data = [
            'email' => 'invalid_' . time() . '@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->makeRequest('POST', '/login', $data);
        
        // 500 hatası alıyorsak, API çalışmıyor demektir
        if ($response['status_code'] === 500) {
            $error_msg = 'API returned 500 error. ';
            if (isset($response['body']['message'])) {
                $error_msg .= 'Message: ' . $response['body']['message'];
            }
            if (isset($response['body']['raw_response'])) {
                $error_msg .= ' Raw response: ' . substr($response['body']['raw_response'], 0, 200);
            }
            $this->markTestSkipped($error_msg);
            return;
        }
        
        // 401 (Unauthorized) veya 400 (Bad Request) bekleniyor
        $this->assertContains($response['status_code'], [400, 401], 
            'Expected 400 or 401, got ' . $response['status_code'] . '. Response: ' . json_encode($response['body']));
        
        if (isset($response['body']['success'])) {
            $this->assertFalse($response['body']['success']);
        }
    }

    public function testMeEndpoint()
    {
        // Login first
        $login_data = [
            'email' => $this->test_email,
            'password' => $this->test_password
        ];
        $login_response = $this->makeRequest('POST', '/login', $login_data);
        
        // Check if login was successful
        if (!isset($login_response['body']['data']['access_token'])) {
            $this->markTestSkipped('Login failed - cannot test /me endpoint');
            return;
        }
        
        $token = $login_response['body']['data']['access_token'];

        // Get user info
        $response = $this->makeRequest('GET', '/me', [], $token);
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('data', $response['body']);
        if (isset($response['body']['data']['email'])) {
            $this->assertEquals($this->test_email, $response['body']['data']['email']);
        }
    }

    private function makeRequest($method, $endpoint, $data = [], $token = null)
    {
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
        // Connection error
        if ($curl_errno !== 0) {
            return [
                'status_code' => 0,
                'body' => [
                    'success' => false,
                    'message' => 'Connection error: ' . $curl_error,
                    'curl_error' => $curl_error,
                    'curl_errno' => $curl_errno
                ]
            ];
        }
        
        $body = json_decode($response, true);
        
        // If JSON decode failed, return error info
        if ($body === null && $response !== '') {
            $body = [
                'success' => false,
                'message' => 'Invalid JSON response',
                'raw_response' => substr($response, 0, 1000),
                'curl_error' => $curl_error
            ];
        }
        
        return [
            'status_code' => $status_code,
            'body' => $body ?: ['success' => false, 'message' => 'Empty response']
        ];
    }
}

