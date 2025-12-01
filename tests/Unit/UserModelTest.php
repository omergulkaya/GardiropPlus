<?php

require_once __DIR__ . '/BaseTestCase.php';

/**
 * User Model Test
 */
class UserModelTest extends BaseTestCase
{
    private $user_model;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!$this->ci) {
            return; // Already skipped in parent
        }
        
        try {
            $this->ci->load->model('User_model');
            $this->user_model = $this->ci->User_model;
        } catch (Exception $e) {
            $this->markTestSkipped('Failed to load User_model: ' . $e->getMessage());
        }
    }

    public function testCreateUser()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User'
        ];

        $user_id = $this->user_model->create($data);
        
        $this->assertIsInt($user_id);
        $this->assertGreaterThan(0, $user_id);
    }

    public function testGetUserById()
    {
        // Create test user
        $data = [
            'email' => 'gettest@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        $user_id = $this->user_model->create($data);

        // Get user
        $user = $this->user_model->get_by_id($user_id);
        
        $this->assertIsArray($user);
        $this->assertEquals($user_id, $user['id']);
        $this->assertEquals('gettest@example.com', $user['email']);
    }

    public function testGetUserByEmail()
    {
        // Create test user
        $email = 'emailtest@example.com';
        $data = [
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        $this->user_model->create($data);

        // Get user by email
        $user = $this->user_model->get_by_email($email);
        
        $this->assertIsArray($user);
        $this->assertEquals($email, $user['email']);
    }

    public function testUpdateUser()
    {
        // Create test user
        $data = [
            'email' => 'updatetest@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT)
        ];
        $user_id = $this->user_model->create($data);

        // Update user
        $update_data = ['first_name' => 'Updated'];
        $result = $this->user_model->update($user_id, $update_data);
        
        $this->assertTrue($result);
        
        // Verify update
        $user = $this->user_model->get_by_id($user_id);
        $this->assertEquals('Updated', $user['first_name']);
    }

    protected function tearDown(): void
    {
        // Cleanup test data if needed
    }
}

