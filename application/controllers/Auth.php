<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class Auth extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
    }

    /**
     * Kullanıcı kaydı
     * POST /api/auth/register
     */
    public function register()
    {
        // JSON body'yi parse et
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);
        
        // JSON parse edilemediyse form data'yı kullan
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            $input = $this->input->post() ?: [];
        }
        
        // Input'u form_validation için set et
        $this->load->library('form_validation');
        $this->form_validation->set_data($input);
        
        // Validation rules
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|is_unique[users.email]');
        $this->form_validation->set_rules('password', 'Password', 'required');
        $this->form_validation->set_rules('first_name', 'First Name', 'trim');
        $this->form_validation->set_rules('last_name', 'Last Name', 'trim');
        $this->form_validation->set_rules('phone', 'Phone', 'trim');
        
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $this->error('Validation failed', 422, $errors);
            return;
        }
        
        // Password strength validation
        $this->load->library('password_security_library');
        $password = $input['password'] ?? '';
        $password_validation = $this->password_security_library->validate_strength($password);
        if (!$password_validation['valid']) {
            $this->error('Password validation failed', 400, ['password' => $password_validation['errors']]);
            return;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $data = [
            'email' => $input['email'] ?? '',
            'password' => $password_hash,
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email_verified' => false // Email verification gerekli
        ];
        $user_id = $this->User_model->create($data);
        if ($user_id) {
        // Password history'ye ekle
            $this->password_security_library->add_to_history($user_id, $password_hash);
        // Email verification token oluştur
            $verification_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $this->User_model->save_verification_token($user_id, $data['email'], $verification_token, $expires_at);
        // TODO: Email gönder (verification link ile)
            // Email gönderme işlemi buraya eklenecek

            // Otomatik login
            $this->session->set_userdata('user_id', $user_id);
        // JWT token oluştur
            $this->load->library('jwt_library');
            $access_token = $this->jwt_library->createAccessToken($user_id);
            $refresh_token = $this->jwt_library->createRefreshToken($user_id);
            $user = $this->User_model->get_by_id($user_id);
            
            // User objesi null ise, temel bilgileri oluştur
            if (!$user) {
                $user = [
                    'id' => $user_id,
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'email_verified' => false
                ];
            } else {
                unset($user['password']);
            }
            
            $response_data = [
                'user' => $user,
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 saat
                'email_verification_required' => true,
                'verification_token' => $verification_token // Development için, production'da kaldırılmalı
            ];
            $this->success($response_data, 'User registered successfully. Please verify your email.', 201);
        } else {
            $this->error('Registration failed', 500);
        }
    }

    /**
     * Kullanıcı girişi
     * POST /api/auth/login
     */
    public function login()
    {
        // JSON body'yi parse et
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);
        
        // JSON parse edilemediyse form data'yı kullan
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            $input = $this->input->post() ?: [];
        }
        
        // Validation - JSON body için
        $this->load->library('form_validation');
        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $this->error('Validation failed', 422, $errors);
            return;
        }
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
// Account lockout kontrolü
        $this->load->library('password_security_library');
        $lockout = $this->password_security_library->check_lockout($email);
        if ($lockout['locked']) {
            $this->error('Account is locked. Please try again after ' . date('Y-m-d H:i:s', $lockout['unlock_time']), 423);
        }

        $user = $this->User_model->get_by_email($email);
// Account kilitli mi kontrol et
        if ($user && $this->User_model->is_account_locked($user['id'])) {
            $this->error('Account is locked. Please try again later.', 423);
        }

        if ($user && password_verify($password, $user['password'])) {
// Başarılı login - attempt'leri temizle
            $this->password_security_library->clear_attempts($email);
            $this->User_model->reset_failed_attempts($user['id']);
// Session-based auth (backward compatibility)
            $this->session->set_userdata('user_id', $user['id']);
// JWT token oluştur
            $this->load->library('jwt_library');
            $access_token = $this->jwt_library->createAccessToken($user['id']);
            $refresh_token = $this->jwt_library->createRefreshToken($user['id']);
            unset($user['password']);
            $response_data = [
                'user' => $user,
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => 3600 // 1 saat
            ];
            $this->success($response_data, 'Login successful');
        } else {
        // Başarısız login - attempt kaydet
            $this->password_security_library->record_failed_attempt($email);
            if ($user) {
                $this->User_model->increment_failed_attempts($user['id']);
        // 5 başarısız denemeden sonra kilitle
                $failed_attempts = $user['failed_login_attempts'] + 1;
                if ($failed_attempts >= 5) {
                    $lock_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $this->User_model->lock_account($user['id'], $lock_until);
                }
            }

            $this->error('Invalid email or password', 401);
        }
    }

    /**
     * Kullanıcı çıkışı
     * POST /api/auth/logout
     */
    public function logout()
    {
        $this->session->unset_userdata('user_id');
        $this->success(null, 'Logout successful');
    }

    /**
     * Mevcut kullanıcı bilgisi
     * GET /api/auth/me
     */
    public function me()
    {
        $user_id = $this->require_auth();
        $user = $this->User_model->get_by_id($user_id);
        if ($user) {
            unset($user['password']);
            $this->success($user);
        } else {
            $this->error('User not found', 404);
        }
    }

    /**
     * Token yenileme
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        // JSON body'yi parse et
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);
        
        // JSON parse edilemediyse form data'yı kullan
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            $input = $this->input->post() ?: [];
        }
        
        // Validation - JSON body için
        $this->load->library('form_validation');
        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('refresh_token', 'Refresh token', 'required|trim');
        
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $this->error('Validation failed', 422, $errors);
            return;
        }
        
        $refresh_token = $input['refresh_token'] ?? '';
        $this->load->library('jwt_library');
        
        // Refresh token'ı doğrula
        $payload = $this->jwt_library->decode($refresh_token);
        if (!$payload || !isset($payload['user_id']) || $payload['type'] !== 'refresh') {
            $this->error('Invalid refresh token', 401);
            return;
        }

        $user_id = $payload['user_id'];
// Kullanıcının varlığını kontrol et
        $user = $this->User_model->get_by_id($user_id);
        if (!$user) {
            $this->error('User not found', 404);
        }

        // Yeni access token oluştur
        $access_token = $this->jwt_library->createAccessToken($user_id);
        $response_data = [
            'access_token' => $access_token,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
        $this->success($response_data, 'Token refreshed successfully');
    }

    /**
     * Email verification
     * GET /api/auth/verify-email/{token}
     */
    public function verify_email($token)
    {
        if ($this->User_model->verify_email_token($token)) {
            $this->success(null, 'Email verified successfully');
        } else {
            $this->error('Invalid or expired verification token', 400);
        }
    }

    /**
     * Password reset request
     * POST /api/auth/forgot-password
     */
    public function forgot_password()
    {
        $this->validate_input([
            'email' => 'required|valid_email'
        ]);
        $email = $this->input->post('email');
        $user = $this->User_model->get_by_email($email);
        if ($user) {
        // Reset token oluştur
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->User_model->save_password_reset_token($user['id'], $token, $expires_at);
        // TODO: Email gönder (reset link ile)
            // Email gönderme işlemi buraya eklenecek

            $this->success(['reset_token' => $token], 'Password reset email sent', 200);
        // Development için
        } else {
        // Güvenlik için aynı mesajı döndür
            $this->success(null, 'If email exists, password reset link has been sent', 200);
        }
    }

    /**
     * Password reset
     * POST /api/auth/reset-password
     */
    public function reset_password()
    {
        $this->validate_input([
            'token' => 'required',
            'password' => 'required'
        ]);
        $token = $this->input->post('token');
        $password = $this->input->post('password');
// Token doğrula
        $reset = $this->User_model->verify_password_reset_token($token);
        if (!$reset) {
            $this->error('Invalid or expired reset token', 400);
        }

        // Password strength validation
        $this->load->library('password_security_library');
        $password_validation = $this->password_security_library->validate_strength($password);
        if (!$password_validation['valid']) {
            $this->error('Password validation failed', 400, ['password' => $password_validation['errors']]);
        }

        // Password history kontrolü
        if ($this->password_security_library->is_password_in_history($reset['user_id'], $password)) {
            $this->error('You cannot reuse your last 5 passwords', 400);
        }

        // Şifreyi güncelle
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $this->User_model->update($reset['user_id'], ['password' => $password_hash]);
// Password history'ye ekle
        $this->password_security_library->add_to_history($reset['user_id'], $password_hash);
// Token'ı kullanıldı olarak işaretle
        $this->User_model->mark_password_reset_used($token);
// Failed attempts'i sıfırla
        $this->User_model->reset_failed_attempts($reset['user_id']);
        $this->success(null, 'Password reset successfully');
    }
}
