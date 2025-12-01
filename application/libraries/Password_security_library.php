<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Password Security Library
 * Şifre güvenliği için yardımcı fonksiyonlar
 */
class Password_security_library
{
    private $ci;
    private $min_length = 8;
    private $require_uppercase = true;
    private $require_lowercase = true;
    private $require_numbers = true;
    private $require_special = false; // Opsiyonel

    public function __construct()
    {
        $this->ci =& get_instance();
        // Cache driver'ı yükle (eğer yüklenmemişse)
        if (!isset($this->ci->cache)) {
            $this->ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
        }
    }

    /**
     * Şifre güçlülüğünü kontrol et
     *
     * @param string $password Şifre
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate_strength($password)
    {
        $errors = [];
        if (strlen($password) < $this->min_length) {
            $errors[] = 'Şifre en az ' . $this->min_length . ' karakter olmalıdır.';
        }

        if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Şifre en az bir büyük harf içermelidir.';
        }

        if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Şifre en az bir küçük harf içermelidir.';
        }

        if ($this->require_numbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Şifre en az bir rakam içermelidir.';
        }

        if ($this->require_special && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Şifre en az bir özel karakter içermelidir.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Şifre geçmişinde var mı kontrol et
     *
     * @param int $user_id Kullanıcı ID
     * @param string $new_password Yeni şifre
     * @param int $history_count Kontrol edilecek geçmiş şifre sayısı
     * @return bool True if password was used before
     */
    public function is_password_in_history($user_id, $new_password, $history_count = 5)
    {
        $this->ci->load->model('User_model');
// Password history tablosundan son N şifreyi al
        $history = $this->ci->db
            ->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->limit($history_count)
            ->get('password_history')
            ->result_array();
        foreach ($history as $old_password) {
            if (password_verify($new_password, $old_password['password_hash'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Şifre geçmişine ekle
     *
     * @param int $user_id Kullanıcı ID
     * @param string $password_hash Hash'lenmiş şifre
     */
    public function add_to_history($user_id, $password_hash)
    {
        // Eski kayıtları temizle (sadece son 10'u tut)
        $this->ci->db
            ->where('user_id', $user_id)
            ->order_by('created_at', 'DESC')
            ->limit(10, 10)
            ->delete('password_history');
// Yeni şifreyi ekle
        $this->ci->db->insert('password_history', [
            'user_id' => $user_id,
            'password_hash' => $password_hash,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Account lockout kontrolü
     *
     * @param string $identifier Email veya IP
     * @param int $max_attempts Maksimum deneme sayısı
     * @param int $lockout_duration Kilitleme süresi (dakika)
     * @return array ['locked' => bool, 'remaining_attempts' => int, 'unlock_time' => timestamp|null]
     */
    public function check_lockout($identifier, $max_attempts = 5, $lockout_duration = 15)
    {
        $key = 'login_attempts_' . md5($identifier);
        $lockout_key = 'lockout_' . md5($identifier);
// Lockout kontrolü
        $lockout_data = $this->ci->cache->get($lockout_key);
        if ($lockout_data && $lockout_data > time()) {
            return [
                'locked' => true,
                'remaining_attempts' => 0,
                'unlock_time' => $lockout_data
            ];
        }

        // Attempt sayısını al
        $attempts = $this->ci->cache->get($key) ?: 0;
        if ($attempts >= $max_attempts) {
        // Account'u kilitle
            $unlock_time = time() + ($lockout_duration * 60);
            $this->ci->cache->save($lockout_key, $unlock_time, $lockout_duration * 60);
            $this->ci->cache->delete($key);
            return [
                'locked' => true,
                'remaining_attempts' => 0,
                'unlock_time' => $unlock_time
            ];
        }

        return [
            'locked' => false,
            'remaining_attempts' => $max_attempts - $attempts,
            'unlock_time' => null
        ];
    }

    /**
     * Başarısız login denemesini kaydet
     *
     * @param string $identifier Email veya IP
     */
    public function record_failed_attempt($identifier)
    {
        $key = 'login_attempts_' . md5($identifier);
        $attempts = $this->ci->cache->get($key) ?: 0;
        $this->ci->cache->save($key, $attempts + 1, 900); // 15 dakika
    }

    /**
     * Başarılı login - attempt'leri temizle
     *
     * @param string $identifier Email veya IP
     */
    public function clear_attempts($identifier)
    {
        $key = 'login_attempts_' . md5($identifier);
        $lockout_key = 'lockout_' . md5($identifier);
        $this->ci->cache->delete($key);
        $this->ci->cache->delete($lockout_key);
    }
}
