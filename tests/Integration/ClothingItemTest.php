<?php

use PHPUnit\Framework\TestCase;

/**
 * Clothing Item API Integration Test
 */
class ClothingItemTest extends TestCase
{
    private $base_url = 'http://localhost/closet/web-api/index.php/api/clothing-item';
    private $token = null;
    private $test_user_email = 'clothing_test@example.com';
    private $test_user_password = 'Test123456!';

    protected function setUp(): void
    {
        // Her test için unique email kullan
        $this->test_user_email = 'clothing_test_' . time() . '@example.com';
        
        // Register and login test user
        $this->registerTestUser();
        $this->token = $this->loginTestUser();
    }

    private function registerTestUser()
    {
        $data = [
            'email' => $this->test_user_email,
            'password' => $this->test_user_password,
            'first_name' => 'Clothing',
            'last_name' => 'Test'
        ];
        
        $response = $this->makeRequest('POST', '/api/auth/register', $data);
        
        // Registration başarısız olabilir (email zaten var), bu normal
        return $response;
    }

    private function loginTestUser()
    {
        $data = [
            'email' => $this->test_user_email,
            'password' => $this->test_user_password
        ];
        
        $response = $this->makeRequest('POST', '/api/auth/login', $data);
        
        if (isset($response['body']['success']) && $response['body']['success']) {
            return $response['body']['data']['access_token'] ?? null;
        }
        
        return null;
    }

    public function testGetClothingItems()
    {
        if (!$this->token) {
            $this->markTestSkipped('Authentication token not available');
            return;
        }

        $response = $this->makeRequest('GET', '', [], $this->token);
        
        $this->assertEquals(200, $response['status_code'], 
            'Expected 200, got ' . $response['status_code'] . '. Response: ' . json_encode($response['body']));
        
        if (isset($response['body']['success']) && $response['body']['success']) {
            if (isset($response['body']['data'])) {
                $this->assertArrayHasKey('items', $response['body']['data']);
                $this->assertArrayHasKey('pagination', $response['body']['data']);
            }
        }
    }

    public function testCreateClothingItem()
    {
        if (!$this->token) {
            $this->markTestSkipped('Authentication token not available');
            return;
        }

        $data = [
            'name' => 'Test Item ' . time(),
            'image_path' => '/uploads/test.jpg',
            'category' => 0,
            'colors' => [
                ['name' => 'Blue', 'color' => 255, 'hexCode' => '#0000FF']
            ],
            'seasons' => [0, 1],
            'styles' => [0]
        ];

        $response = $this->makeRequest('POST', '', $data, $this->token);
        
        $this->assertContains($response['status_code'], [200, 201], 
            'Expected 200 or 201, got ' . $response['status_code'] . '. Response: ' . json_encode($response['body']));
        
        if (isset($response['body']['success']) && $response['body']['success']) {
            if (isset($response['body']['data'])) {
                $this->assertArrayHasKey('id', $response['body']['data']);
            }
        }
    }

    public function testGetClothingItemById()
    {
        if (!$this->token) {
            $this->markTestSkipped('Authentication token not available');
            return;
        }

        // Create item first
        $create_data = [
            'name' => 'Get Test Item',
            'image_path' => '/uploads/test.jpg',
            'category' => 0
        ];
        $create_response = $this->makeRequest('POST', '', $create_data, $this->token);
        
        if (!isset($create_response['body']['data']['id'])) {
            $this->markTestSkipped('Failed to create test item');
            return;
        }
        
        $item_id = $create_response['body']['data']['id'];

        // Get item
        $response = $this->makeRequest('GET', '/' . $item_id, [], $this->token);
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
        if (isset($response['body']['data']['id'])) {
            $this->assertEquals($item_id, $response['body']['data']['id']);
        }
    }

    public function testUpdateClothingItem()
    {
        if (!$this->token) {
            $this->markTestSkipped('Authentication token not available');
            return;
        }

        // Create item first
        $create_data = [
            'name' => 'Update Test Item',
            'image_path' => '/uploads/test.jpg',
            'category' => 0
        ];
        $create_response = $this->makeRequest('POST', '', $create_data, $this->token);
        
        if (!isset($create_response['body']['data']['id'])) {
            $this->markTestSkipped('Failed to create test item');
            return;
        }
        
        $item_id = $create_response['body']['data']['id'];

        // Update item
        $update_data = ['name' => 'Updated Name'];
        $response = $this->makeRequest('PUT', '/' . $item_id, $update_data, $this->token);
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
    }

    public function testDeleteClothingItem()
    {
        if (!$this->token) {
            $this->markTestSkipped('Authentication token not available');
            return;
        }

        // Create item first
        $create_data = [
            'name' => 'Delete Test Item',
            'image_path' => '/uploads/test.jpg',
            'category' => 0
        ];
        $create_response = $this->makeRequest('POST', '', $create_data, $this->token);
        
        if (!isset($create_response['body']['data']['id'])) {
            $this->markTestSkipped('Failed to create test item');
            return;
        }
        
        $item_id = $create_response['body']['data']['id'];

        // Delete item
        $response = $this->makeRequest('DELETE', '/' . $item_id, [], $this->token);
        
        $this->assertEquals(200, $response['status_code']);
        $this->assertTrue($response['body']['success']);
    }

    private function makeRequest($method, $endpoint, $data = [], $token = null)
    {
        $base = 'http://localhost/closet/web-api/index.php';
        $url = $base . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        if (in_array($method, ['POST', 'PUT'])) {
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
        curl_close($ch);
        
        $body = json_decode($response, true);
        
        // If JSON decode failed, return error info
        if ($body === null && $response !== '') {
            $body = [
                'success' => false,
                'message' => 'Invalid JSON response',
                'raw_response' => substr($response, 0, 500),
                'curl_error' => $curl_error
            ];
        }
        
        return [
            'status_code' => $status_code,
            'body' => $body ?: ['success' => false, 'message' => 'Empty response']
        ];
    }
}

