<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data Retention Model
 * Veri saklama politikaları ve otomatik temizleme
 */
class Data_retention_model extends CI_Model
{
    protected $policies_table = 'data_retention_policies';
    protected $cleanup_logs_table = 'data_cleanup_logs';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Tüm politikaları getir
     */
    public function get_all_policies()
    {
        return $this->db->get($this->policies_table)->result_array();
    }

    /**
     * Belirli bir veri tipi için politika getir
     */
    public function get_policy($data_type)
    {
        return $this->db->get_where($this->policies_table, ['data_type' => $data_type])->row_array();
    }

    /**
     * Politika güncelle
     */
    public function update_policy($data_type, $retention_days, $auto_delete = 1)
    {
        $existing = $this->get_policy($data_type);
        
        if ($existing) {
            $this->db->where('data_type', $data_type);
            return $this->db->update($this->policies_table, [
                'retention_days' => $retention_days,
                'auto_delete' => $auto_delete
            ]);
        } else {
            return $this->db->insert($this->policies_table, [
                'data_type' => $data_type,
                'retention_days' => $retention_days,
                'auto_delete' => $auto_delete
            ]);
        }
    }

    /**
     * Veri temizleme işlemi
     */
    public function cleanup_data($data_type)
    {
        $policy = $this->get_policy($data_type);
        
        if (!$policy || !$policy['auto_delete']) {
            return ['success' => false, 'message' => 'Politika bulunamadı veya otomatik silme kapalı'];
        }

        $start_time = microtime(true);
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $policy['retention_days'] . ' days'));
        
        $records_deleted = 0;
        $status = 'success';
        $error_message = null;

        try {
            switch ($data_type) {
                case 'api_errors':
                    $this->db->where('created_at <', $cutoff_date);
                    $records_deleted = $this->db->count_all_results('api_errors');
                    $this->db->where('created_at <', $cutoff_date);
                    $this->db->delete('api_errors');
                    break;
                    
                case 'admin_activity_logs':
                    $this->db->where('created_at <', $cutoff_date);
                    $records_deleted = $this->db->count_all_results('admin_activity_logs');
                    $this->db->where('created_at <', $cutoff_date);
                    $this->db->delete('admin_activity_logs');
                    break;
                    
                case 'user_data_access_logs':
                    $this->db->where('created_at <', $cutoff_date);
                    $records_deleted = $this->db->count_all_results('user_data_access_logs');
                    $this->db->where('created_at <', $cutoff_date);
                    $this->db->delete('user_data_access_logs');
                    break;
                    
                case '2fa_verification_codes':
                    $this->db->where('expires_at <', date('Y-m-d H:i:s'));
                    $this->db->or_where('is_used', 1);
                    $records_deleted = $this->db->count_all_results('2fa_verification_codes');
                    $this->db->where('expires_at <', date('Y-m-d H:i:s'));
                    $this->db->or_where('is_used', 1);
                    $this->db->delete('2fa_verification_codes');
                    break;
                    
                case 'email_verifications':
                    $this->db->where('created_at <', $cutoff_date);
                    $records_deleted = $this->db->count_all_results('email_verifications');
                    $this->db->where('created_at <', $cutoff_date);
                    $this->db->delete('email_verifications');
                    break;
                    
                case 'password_reset_tokens':
                    $this->db->where('expires_at <', date('Y-m-d H:i:s'));
                    $this->db->or_where('used', 1);
                    $records_deleted = $this->db->count_all_results('password_reset_tokens');
                    $this->db->where('expires_at <', date('Y-m-d H:i:s'));
                    $this->db->or_where('used', 1);
                    $this->db->delete('password_reset_tokens');
                    break;
                    
                default:
                    return ['success' => false, 'message' => 'Bilinmeyen veri tipi'];
            }
            
            // Son temizleme zamanını güncelle
            $this->db->where('data_type', $data_type);
            $this->db->update($this->policies_table, ['last_cleanup_at' => date('Y-m-d H:i:s')]);
            
        } catch (Exception $e) {
            $status = 'failed';
            $error_message = $e->getMessage();
            log_message('error', 'Data cleanup failed for ' . $data_type . ': ' . $error_message);
        }

        $execution_time = round(microtime(true) - $start_time, 2);

        // Temizleme logunu kaydet
        $this->db->insert($this->cleanup_logs_table, [
            'data_type' => $data_type,
            'records_deleted' => $records_deleted,
            'cleanup_date' => date('Y-m-d'),
            'execution_time' => $execution_time,
            'status' => $status,
            'error_message' => $error_message
        ]);

        return [
            'success' => $status === 'success',
            'records_deleted' => $records_deleted,
            'execution_time' => $execution_time,
            'message' => $error_message
        ];
    }

    /**
     * Tüm veri tipleri için temizleme yap
     */
    public function cleanup_all()
    {
        $policies = $this->get_all_policies();
        $results = [];

        foreach ($policies as $policy) {
            if ($policy['auto_delete']) {
                $results[$policy['data_type']] = $this->cleanup_data($policy['data_type']);
            }
        }

        return $results;
    }

    /**
     * Temizleme loglarını getir
     */
    public function get_cleanup_logs($data_type = null, $limit = 50)
    {
        if ($data_type) {
            $this->db->where('data_type', $data_type);
        }
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->cleanup_logs_table)->result_array();
    }

    /**
     * Güvenli veri silme (kullanıcı verileri için)
     */
    public function secure_delete_user_data($user_id)
    {
        $this->load->model('User_model');
        $this->load->model('Gdpr_model');
        
        // 1. Kullanıcı verilerini anonimleştir
        $this->db->where('id', $user_id);
        $this->db->update('users', [
            'email' => 'deleted_' . $user_id . '@deleted.local',
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'phone' => null,
            'profile_image_path' => null
        ]);
        
        // 2. Şifreyi değiştir (erişim engellensin)
        $this->db->where('id', $user_id);
        $this->db->update('users', [
            'password' => password_hash(uniqid(), PASSWORD_DEFAULT)
        ]);
        
        // 3. İlişkili verileri sil
        $this->db->where('user_id', $user_id);
        $this->db->delete('clothing_items');
        
        $this->db->where('user_id', $user_id);
        $this->db->delete('outfits');
        
        $this->db->where('user_id', $user_id);
        $this->db->delete('worn_outfits');
        
        // 4. GDPR loglarını koru (yasal zorunluluk)
        // Loglar silinmez, sadece user_id null yapılabilir
        
        // 5. Silme talebini tamamla
        $deletion_request = $this->Gdpr_model->get_user_deletion_request($user_id);
        if ($deletion_request) {
            $this->Gdpr_model->process_deletion_request(
                $deletion_request['id'],
                'completed',
                $this->session->userdata('admin_id'),
                'Kullanıcı verileri güvenli şekilde silindi'
            );
        }
        
        return true;
    }
}

