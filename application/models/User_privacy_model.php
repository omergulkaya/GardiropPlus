<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User Privacy Model
 * Kullanıcı gizlilik ayarları yönetimi
 */
class User_privacy_model extends CI_Model
{
    protected $table = 'user_privacy_settings';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Kullanıcı gizlilik ayarlarını getir veya oluştur
     */
    public function get_or_create($user_id)
    {
        $settings = $this->db->get_where($this->table, ['user_id' => $user_id])->row_array();
        
        if (!$settings) {
            // Varsayılan ayarlarla oluştur
            $this->db->insert($this->table, [
                'user_id' => $user_id,
                'profile_visibility' => 'private',
                'allow_admin_view' => 1,
                'show_email' => 0,
                'show_phone' => 0
            ]);
            return $this->db->get_where($this->table, ['user_id' => $user_id])->row_array();
        }
        
        return $settings;
    }

    /**
     * Gizlilik ayarlarını güncelle
     */
    public function update_settings($user_id, $data)
    {
        $allowed_fields = [
            'profile_visibility',
            'allow_admin_view',
            'show_email',
            'show_phone'
        ];
        
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $this->db->where('user_id', $user_id);
        return $this->db->update($this->table, $update_data);
    }

    /**
     * Veri silme talebi oluştur
     */
    public function request_data_deletion($user_id, $reason = null)
    {
        $this->load->model('Gdpr_model');
        
        // Gizlilik ayarlarını güncelle
        $this->db->where('user_id', $user_id);
        $this->db->update($this->table, [
            'data_deletion_requested' => 1,
            'data_deletion_requested_at' => date('Y-m-d H:i:s')
        ]);
        
        // GDPR model ile silme talebi oluştur
        return $this->Gdpr_model->create_deletion_request($user_id, $reason);
    }

    /**
     * Admin görüntüleme izni var mı kontrol et
     */
    public function can_admin_view($user_id)
    {
        $settings = $this->get_or_create($user_id);
        return $settings && $settings['allow_admin_view'] == 1;
    }

    /**
     * Profil görünürlük seviyesi
     */
    public function get_profile_visibility($user_id)
    {
        $settings = $this->get_or_create($user_id);
        return $settings ? $settings['profile_visibility'] : 'private';
    }

    /**
     * Email gösterilebilir mi
     */
    public function can_show_email($user_id)
    {
        $settings = $this->get_or_create($user_id);
        return $settings && $settings['show_email'] == 1;
    }

    /**
     * Telefon gösterilebilir mi
     */
    public function can_show_phone($user_id)
    {
        $settings = $this->get_or_create($user_id);
        return $settings && $settings['show_phone'] == 1;
    }

    /**
     * Veri silme talebi var mı
     */
    public function has_deletion_request($user_id)
    {
        $settings = $this->get_or_create($user_id);
        return $settings && $settings['data_deletion_requested'] == 1;
    }

    /**
     * Tüm silme taleplerini getir
     */
    public function get_deletion_requests($status = 'pending')
    {
        $this->db->where('data_deletion_requested', 1);
        if ($status) {
            $this->load->model('Gdpr_model');
            return $this->Gdpr_model->get_deletion_requests($status);
        }
        
        $this->db->order_by('data_deletion_requested_at', 'DESC');
        return $this->db->get($this->table)->result_array();
    }
}

