<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * GDPR/KVKK Model
 * Veri koruma ve mahremiyet yönetimi
 */
class Gdpr_model extends CI_Model
{
    protected $consents_table = 'gdpr_consents';
    protected $access_logs_table = 'user_data_access_logs';
    protected $deletion_requests_table = 'data_deletion_requests';
    protected $export_requests_table = 'data_export_requests';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * ============================================
     * CONSENT YÖNETİMİ
     * ============================================
     */

    /**
     * Consent kaydet
     */
    public function save_consent($user_id, $consent_type, $status = 'granted', $version = null)
    {
        $data = [
            'user_id' => $user_id,
            'consent_type' => $consent_type,
            'consent_status' => $status,
            'consent_version' => $version ?: '1.0',
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ];

        if ($status === 'granted') {
            $data['granted_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'withdrawn') {
            $data['withdrawn_at'] = date('Y-m-d H:i:s');
        }

        // Mevcut consent'i güncelle veya yeni oluştur
        $existing = $this->get_consent($user_id, $consent_type);
        if ($existing) {
            $this->db->where('user_id', $user_id);
            $this->db->where('consent_type', $consent_type);
            return $this->db->update($this->consents_table, $data);
        } else {
            return $this->db->insert($this->consents_table, $data);
        }
    }

    /**
     * Consent getir
     */
    public function get_consent($user_id, $consent_type)
    {
        return $this->db->get_where($this->consents_table, [
            'user_id' => $user_id,
            'consent_type' => $consent_type
        ])->row_array();
    }

    /**
     * Kullanıcının tüm consent'lerini getir
     */
    public function get_user_consents($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->consents_table)->result_array();
    }

    /**
     * Consent durumunu kontrol et
     */
    public function has_consent($user_id, $consent_type)
    {
        $consent = $this->get_consent($user_id, $consent_type);
        return $consent && $consent['consent_status'] === 'granted';
    }

    /**
     * ============================================
     * DATA ACCESS LOGGING
     * ============================================
     */

    /**
     * Veri erişim logu kaydet
     */
    public function log_data_access($user_id, $accessed_by, $access_type, $data_type, $data_fields = null, $purpose = null, $legal_basis = null)
    {
        $data = [
            'user_id' => $user_id,
            'accessed_by' => $accessed_by,
            'access_type' => $access_type,
            'data_type' => $data_type,
            'purpose' => $purpose,
            'legal_basis' => $legal_basis,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ];

        if ($data_fields && is_array($data_fields)) {
            $data['data_fields'] = json_encode($data_fields, JSON_UNESCAPED_UNICODE);
        }

        return $this->db->insert($this->access_logs_table, $data);
    }

    /**
     * Kullanıcının veri erişim loglarını getir
     */
    public function get_user_access_logs($user_id, $limit = 50)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->access_logs_table)->result_array();
    }

    /**
     * ============================================
     * DATA DELETION (Right to be Forgotten)
     * ============================================
     */

    /**
     * Veri silme talebi oluştur
     */
    public function create_deletion_request($user_id, $reason = null)
    {
        $data = [
            'user_id' => $user_id,
            'request_reason' => $reason,
            'status' => 'pending'
        ];

        return $this->db->insert($this->deletion_requests_table, $data);
    }

    /**
     * Silme talebi getir
     */
    public function get_deletion_request($id)
    {
        return $this->db->get_where($this->deletion_requests_table, ['id' => $id])->row_array();
    }

    /**
     * Kullanıcının silme talebini getir
     */
    public function get_user_deletion_request($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'pending');
        $this->db->order_by('requested_at', 'DESC');
        $this->db->limit(1);
        return $this->db->get($this->deletion_requests_table)->row_array();
    }

    /**
     * Silme taleplerini listele
     */
    public function get_deletion_requests($status = null, $limit = 50)
    {
        if ($status) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('requested_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->deletion_requests_table)->result_array();
    }

    /**
     * Silme talebini işle
     */
    public function process_deletion_request($id, $status, $processed_by, $notes = null, $rejection_reason = null)
    {
        $data = [
            'status' => $status,
            'processed_by' => $processed_by,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        if ($notes) {
            $data['deletion_notes'] = $notes;
        }
        if ($rejection_reason) {
            $data['rejection_reason'] = $rejection_reason;
        }

        $this->db->where('id', $id);
        return $this->db->update($this->deletion_requests_table, $data);
    }

    /**
     * ============================================
     * DATA EXPORT (Data Portability)
     * ============================================
     */

    /**
     * Veri dışa aktarma talebi oluştur
     */
    public function create_export_request($user_id, $format = 'json')
    {
        $data = [
            'user_id' => $user_id,
            'export_format' => $format,
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')) // 30 gün geçerli
        ];

        return $this->db->insert($this->export_requests_table, $data);
    }

    /**
     * Export talebi getir
     */
    public function get_export_request($id)
    {
        return $this->db->get_where($this->export_requests_table, ['id' => $id])->row_array();
    }

    /**
     * Kullanıcının export taleplerini getir
     */
    public function get_user_export_requests($user_id, $limit = 10)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('requested_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->export_requests_table)->result_array();
    }

    /**
     * Export talebini tamamla
     */
    public function complete_export_request($id, $file_path, $file_size)
    {
        $data = [
            'status' => 'completed',
            'file_path' => $file_path,
            'file_size' => $file_size,
            'completed_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        return $this->db->update($this->export_requests_table, $data);
    }

    /**
     * Export dosyası indirme sayısını artır
     */
    public function increment_export_download($id)
    {
        $this->db->set('download_count', 'download_count + 1', false);
        $this->db->set('last_downloaded_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $id);
        return $this->db->update($this->export_requests_table);
    }

    /**
     * Süresi dolmuş export dosyalarını temizle
     */
    public function cleanup_expired_exports()
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s'));
        $this->db->where('status', 'completed');
        return $this->db->delete($this->export_requests_table);
    }
}

