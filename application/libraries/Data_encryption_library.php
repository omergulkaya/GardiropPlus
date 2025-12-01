<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data Encryption Library
 * Hassas verilerin şifrelenmesi için
 */
class Data_encryption_library
{
    private $encryption_key;
    private $cipher = 'aes-256-gcm';
    
    public function __construct()
    {
        // Encryption key'i config'den al
        $this->encryption_key = $this->get_encryption_key();
    }

    /**
     * Encryption key al
     */
    private function get_encryption_key()
    {
        $CI =& get_instance();
        $key = $CI->config->item('encryption_key');
        
        if (empty($key)) {
            // .env dosyasından oku
            $key = $this->get_key_from_env();
        }
        
        if (empty($key)) {
            log_message('error', 'Encryption key not found!');
            throw new Exception('Encryption key not configured');
        }
        
        // Key'i 32 byte'a sabitle (aes-256 için)
        return hash('sha256', $key, true);
    }

    /**
     * .env dosyasından key al
     */
    private function get_key_from_env()
    {
        $env_files = [
            FCPATH . '.env',
            APPPATH . '../.env',
            __DIR__ . '/../../.env'
        ];

        foreach ($env_files as $file) {
            if (file_exists($file) && is_readable($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) {
                        continue;
                    }
                    
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        $value = trim($value, '"\'');
                        
                        if ($key === 'ENCRYPTION_KEY' || $key === 'APP_KEY') {
                            return $value;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Veriyi şifrele
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            return null;
        }

        // IV (Initialization Vector) oluştur
        $iv_length = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        // Şifrele
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            log_message('error', 'Encryption failed: ' . openssl_error_string());
            return false;
        }
        
        // IV, tag ve encrypted data'yı birleştir
        $result = base64_encode($iv . $tag . $encrypted);
        
        return $result;
    }

    /**
     * Şifrelenmiş veriyi çöz
     */
    public function decrypt($encrypted_data)
    {
        if (empty($encrypted_data)) {
            return null;
        }

        // Base64 decode
        $data = base64_decode($encrypted_data);
        
        if ($data === false) {
            return false;
        }
        
        // IV, tag ve encrypted data'yı ayır
        $iv_length = openssl_cipher_iv_length($this->cipher);
        $tag_length = 16; // GCM tag length
        
        $iv = substr($data, 0, $iv_length);
        $tag = substr($data, $iv_length, $tag_length);
        $encrypted = substr($data, $iv_length + $tag_length);
        
        // Çöz
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            log_message('error', 'Decryption failed: ' . openssl_error_string());
            return false;
        }
        
        return $decrypted;
    }

    /**
     * Field-level encryption - veritabanına kaydet
     */
    public function encrypt_field($table_name, $record_id, $field_name, $value)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        $encrypted_value = $this->encrypt($value);
        
        if ($encrypted_value === false) {
            return false;
        }
        
        // Mevcut kaydı kontrol et
        $existing = $CI->db->get_where('encrypted_data', [
            'table_name' => $table_name,
            'record_id' => $record_id,
            'field_name' => $field_name
        ])->row_array();
        
        if ($existing) {
            // Güncelle
            $CI->db->where('id', $existing['id']);
            return $CI->db->update('encrypted_data', [
                'encrypted_value' => $encrypted_value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Yeni kayıt
            return $CI->db->insert('encrypted_data', [
                'table_name' => $table_name,
                'record_id' => $record_id,
                'field_name' => $field_name,
                'encrypted_value' => $encrypted_value,
                'encryption_method' => $this->cipher
            ]);
        }
    }

    /**
     * Field-level decryption - veritabanından oku
     */
    public function decrypt_field($table_name, $record_id, $field_name)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        $encrypted = $CI->db->get_where('encrypted_data', [
            'table_name' => $table_name,
            'record_id' => $record_id,
            'field_name' => $field_name
        ])->row_array();
        
        if (!$encrypted) {
            return null;
        }
        
        return $this->decrypt($encrypted['encrypted_value']);
    }

    /**
     * Hassas alanları otomatik şifrele (model hook için)
     */
    public function auto_encrypt_sensitive_fields($table_name, $record_id, $data, $sensitive_fields = [])
    {
        $default_sensitive = ['password', 'token', 'secret', 'api_key', 'access_token', 'refresh_token', 'credit_card', 'ssn'];
        $fields = array_merge($default_sensitive, $sensitive_fields);
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $this->encrypt_field($table_name, $record_id, $field, $data[$field]);
                // Orijinal değeri temizle (güvenlik için)
                unset($data[$field]);
            }
        }
        
        return $data;
    }
}

