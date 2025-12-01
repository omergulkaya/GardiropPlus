<?php

require_once __DIR__ . '/BaseTestCase.php';

/**
 * JWT Library Test
 */
class JwtLibraryTest extends BaseTestCase
{
    private $jwt_library;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!$this->ci) {
            return; // Already skipped in parent
        }
        
        try {
            $this->ci->load->library('jwt_library');
            $this->jwt_library = $this->ci->jwt_library;
        } catch (Exception $e) {
            $this->markTestSkipped('Failed to load JWT library: ' . $e->getMessage());
        }
    }

    public function testCreateAccessToken()
    {
        if (!$this->jwt_library) {
            $this->markTestSkipped('JWT library not available');
            return;
        }

        $user_id = 1;
        $token = $this->jwt_library->createAccessToken($user_id);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Token should have 3 parts (header.payload.signature)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testDecodeToken()
    {
        if (!$this->jwt_library) {
            $this->markTestSkipped('JWT library not available');
            return;
        }

        $user_id = 1;
        $token = $this->jwt_library->createAccessToken($user_id);
        
        $payload = $this->jwt_library->decode($token);
        
        $this->assertIsArray($payload);
        $this->assertEquals($user_id, $payload['user_id']);
        $this->assertEquals('access', $payload['type']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testInvalidToken()
    {
        if (!$this->jwt_library) {
            $this->markTestSkipped('JWT library not available');
            return;
        }

        $invalid_token = 'invalid.token.here';
        $payload = $this->jwt_library->decode($invalid_token);
        
        $this->assertFalse($payload);
    }

    public function testGetUserIdFromToken()
    {
        if (!$this->jwt_library) {
            $this->markTestSkipped('JWT library not available');
            return;
        }

        $user_id = 123;
        $token = $this->jwt_library->createAccessToken($user_id);
        
        $extracted_user_id = $this->jwt_library->getUserIdFromToken($token);
        
        $this->assertEquals($user_id, $extracted_user_id);
    }

    public function testIsValidToken()
    {
        if (!$this->jwt_library) {
            $this->markTestSkipped('JWT library not available');
            return;
        }

        $user_id = 1;
        $token = $this->jwt_library->createAccessToken($user_id);
        
        $is_valid = $this->jwt_library->isValid($token);
        
        $this->assertTrue($is_valid);
    }
}
