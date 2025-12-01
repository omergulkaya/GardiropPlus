<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User 2FA Model
 * İki faktörlü doğrulama yönetimi
 */
class User_2fa_model extends CI_Model
{
    protected $table = 'user_2fa';
    protected $codes_table = '2fa_verification_codes';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('data_encryption_library');
    }

    /**
     * Kullanıcı için 2FA kaydı oluştur veya getir
     */
    public function get_or_create($user_id)
    {
        $record = $this->db->get_where($this->table, ['user_id' => $user_id])->row_array();
        
        if (!$record) {
            $this->db->insert($this->table, [
                'user_id' => $user_id,
                'method' => 'totp',
                'is_enabled' => 0,
                'is_verified' => 0
            ]);
            return $this->db->get_where($this->table, ['user_id' => $user_id])->row_array();
        }
        
        return $record;
    }

    /**
     * TOTP secret key oluştur
     */
    public function generate_totp_secret()
    {
        // 16 karakterlik base32 encoded secret
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * TOTP secret kaydet (şifrelenmiş)
     */
    public function save_totp_secret($user_id, $secret)
    {
        $CI =& get_instance();
        $CI->load->library('data_encryption_library');
        $encrypted_secret = $CI->data_encryption_library->encrypt($secret);
        
        $this->db->where('user_id', $user_id);
        return $this->db->update($this->table, [
            'secret' => $encrypted_secret,
            'method' => 'totp'
        ]);
    }

    /**
     * TOTP secret al (çözülmüş)
     */
    public function get_totp_secret($user_id)
    {
        $record = $this->get_or_create($user_id);
        if (!$record || empty($record['secret'])) {
            return null;
        }
        
        $CI =& get_instance();
        $CI->load->library('data_encryption_library');
        return $CI->data_encryption_library->decrypt($record['secret']);
    }

    /**
     * TOTP kodu doğrula
     */
    public function verify_totp_code($user_id, $code)
    {
        $secret = $this->get_totp_secret($user_id);
        if (!$secret) {
            return false;
        }
        
        // TOTP kütüphanesi yükle (Google Authenticator uyumlu)
        $CI =& get_instance();
        $CI->load->library('google_authenticator');
        $ga = $CI->google_authenticator;
        
        // 30 saniyelik window (önceki ve sonraki kod da geçerli)
        $checkResult = $ga->verifyCode($secret, $code, 1);
        
        if ($checkResult) {
            // Son kullanım zamanını güncelle
            $this->db->where('user_id', $user_id);
            $this->db->update($this->table, ['last_used_at' => date('Y-m-d H:i:s')]);
        }
        
        return $checkResult;
    }

    /**
     * Yedek kodlar oluştur
     */
    public function generate_backup_codes($user_id, $count = 10)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        }
        
        // Şifrelenmiş olarak kaydet
        $CI =& get_instance();
        $CI->load->library('data_encryption_library');
        $encrypted_codes = $CI->data_encryption_library->encrypt(json_encode($codes));
        
        $this->db->where('user_id', $user_id);
        $this->db->update($this->table, ['backup_codes' => $encrypted_codes]);
        
        return $codes; // Sadece ilk oluşturulduğunda döndür
    }

    /**
     * Yedek kod doğrula
     */
    public function verify_backup_code($user_id, $code)
    {
        $record = $this->get_or_create($user_id);
        if (!$record || empty($record['backup_codes'])) {
            return false;
        }
        
        $CI =& get_instance();
        $CI->load->library('data_encryption_library');
        $codes_json = $CI->data_encryption_library->decrypt($record['backup_codes']);
        $codes = json_decode($codes_json, true);
        
        if (!is_array($codes) || !in_array(strtoupper($code), $codes)) {
            return false;
        }
        
        // Kullanılan kodu listeden çıkar
        $codes = array_values(array_diff($codes, [strtoupper($code)]));
        $CI =& get_instance();
        $CI->load->library('data_encryption_library');
        $encrypted_codes = $CI->data_encryption_library->encrypt(json_encode($codes));
        
        $this->db->where('user_id', $user_id);
        $this->db->update($this->table, ['backup_codes' => $encrypted_codes]);
        
        return true;
    }

    /**
     * Email doğrulama kodu oluştur
     */
    public function create_email_code($user_id)
    {
        // 6 haneli kod
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->db->insert($this->codes_table, [
            'user_id' => $user_id,
            'code' => $code,
            'method' => 'email',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
        ]);
        
        return $code;
    }

    /**
     * Email doğrulama kodu doğrula
     */
    public function verify_email_code($user_id, $code)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('code', $code);
        $this->db->where('method', 'email');
        $this->db->where('is_used', 0);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        
        $record = $this->db->get($this->codes_table)->row_array();
        
        if ($record) {
            // Kodu kullanıldı olarak işaretle
            $this->db->where('id', $record['id']);
            $this->db->update($this->codes_table, ['is_used' => 1]);
            return true;
        }
        
        return false;
    }

    /**
     * 2FA'yı aktif et
     */
    public function enable($user_id, $method = 'totp')
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update($this->table, [
            'is_enabled' => 1,
            'is_verified' => 1,
            'method' => $method
        ]);
    }

    /**
     * 2FA'yı devre dışı bırak
     */
    public function disable($user_id)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update($this->table, [
            'is_enabled' => 0,
            'secret' => null,
            'backup_codes' => null
        ]);
    }

    /**
     * 2FA aktif mi kontrol et
     */
    public function is_enabled($user_id)
    {
        $record = $this->get_or_create($user_id);
        return $record && $record['is_enabled'] == 1;
    }

    /**
     * Süresi dolmuş kodları temizle
     */
    public function cleanup_expired_codes()
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s'));
        $this->db->or_where('is_used', 1);
        return $this->db->delete($this->codes_table);
    }
}

