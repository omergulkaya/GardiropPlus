<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * JWT Library
 * JSON Web Token oluşturma ve doğrulama
 */
class Jwt_library
{
    private $secret_key;
    private $algorithm = 'HS256';
    private $access_token_expiry = 3600;
// 1 saat
    private $refresh_token_expiry = 604800;
// 7 gün
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
// JWT config yükle
        $this->ci->config->load('jwt', true);
// JWT secret key - environment variable'dan veya config'den al
        $this->secret_key = getenv('JWT_SECRET_KEY') ?: $this->ci->config->item('jwt_secret_key', 'jwt');
// Hala yoksa default (sadece development)
        if (!$this->secret_key || $this->secret_key === 'your-secret-key-change-in-production') {
            if (ENVIRONMENT === 'production') {
                log_message('error', 'JWT_SECRET_KEY not set in production environment');
                show_error('JWT_SECRET_KEY must be set in production', 500);
            }
            $this->secret_key = 'your-secret-key-change-in-production';
        }

        // Secret key minimum 32 karakter olmalı
        if (strlen($this->secret_key) < 32 && ENVIRONMENT === 'production') {
            log_message('error', 'JWT_SECRET_KEY is too short (minimum 32 characters)');
        }

        // Config'den expiration sürelerini al
        $this->access_token_expiry = $this->ci->config->item('access_token_expiry', 'jwt') ?: $this->access_token_expiry;
        $this->refresh_token_expiry = $this->ci->config->item('refresh_token_expiry', 'jwt') ?: $this->refresh_token_expiry;
        $this->algorithm = $this->ci->config->item('algorithm', 'jwt') ?: $this->algorithm;
    }

    /**
     * JWT token oluştur
     *
     * @param array $payload Token içeriği
     * @param int $expiry Süre (saniye)
     * @return string JWT token
     */
    public function encode($payload, $expiry = null)
    {
        if ($expiry === null) {
            $expiry = $this->access_token_expiry;
        }

        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];
        $payload['iat'] = time();
// Issued at
        $payload['exp'] = time() + $expiry;
// Expiration
        $issuer = $this->ci ? $this->ci->config->item('issuer', 'jwt') : null;
        $payload['iss'] = $issuer ?: base_url();
// Issuer

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * JWT token doğrula ve decode et
     *
     * @param string $token JWT token
     * @return array|false Decoded payload veya false
     */
    public function decode($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
// Signature doğrula
        $signature = $this->base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);
// Expiration kontrolü
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Access token oluştur
     *
     * @param int $user_id Kullanıcı ID
     * @param array $additional_data Ek veriler
     * @return string Access token
     */
    public function createAccessToken($user_id, $additional_data = [])
    {
        $payload = array_merge([
            'user_id' => $user_id,
            'type' => 'access'
        ], $additional_data);
        return $this->encode($payload, $this->access_token_expiry);
    }

    /**
     * Refresh token oluştur
     *
     * @param int $user_id Kullanıcı ID
     * @return string Refresh token
     */
    public function createRefreshToken($user_id)
    {
        $payload = [
            'user_id' => $user_id,
            'type' => 'refresh'
        ];
        return $this->encode($payload, $this->refresh_token_expiry);
    }

    /**
     * Token'dan user_id al
     *
     * @param string $token JWT token
     * @return int|false User ID veya false
     */
    public function getUserIdFromToken($token)
    {
        $payload = $this->decode($token);
        if ($payload && isset($payload['user_id'])) {
            return $payload['user_id'];
        }

        return false;
    }

    /**
     * Token'ın geçerli olup olmadığını kontrol et
     *
     * @param string $token JWT token
     * @return bool
     */
    public function isValid($token)
    {
        $payload = $this->decode($token);
        return $payload !== false;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Token expiration süresini al
     *
     * @param string $token JWT token
     * @return int|false Expiration timestamp veya false
     */
    public function getExpiration($token)
    {
        $payload = $this->decode($token);
        if ($payload && isset($payload['exp'])) {
            return $payload['exp'];
        }

        return false;
    }
}
