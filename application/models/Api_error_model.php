<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Error Model
 * API hatalarını veritabanında saklama ve yönetim
 */
class Api_error_model extends CI_Model
{
    protected $table = 'api_errors';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('security');
    }

    /**
     * Yeni hata kaydı oluştur
     */
    public function create($data)
    {
        // User ID hash oluştur (mahremiyet için)
        if (isset($data['user_id']) && $data['user_id']) {
            $data['user_id_hash'] = $this->hash_user_id($data['user_id']);
        }

        // Error group ID oluştur (benzer hataları gruplamak için)
        if (!isset($data['error_group_id'])) {
            $data['error_group_id'] = $this->generate_error_group_id($data);
        }

        // İlk oluşma zamanı
        if (!isset($data['first_occurred_at'])) {
            $data['first_occurred_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table, $data);
        $error_id = $this->db->insert_id();

        // Benzer hataları grupla ve occurrence count'u güncelle
        $this->update_error_group($data['error_group_id']);

        return $error_id;
    }

    /**
     * Hata ID'ye göre getir
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    /**
     * Hataları filtrele ve getir
     */
    public function get_filtered($filters = [], $limit = 50, $offset = 0)
    {
        // Status code filtreleme
        if (isset($filters['status_code']) && $filters['status_code']) {
            $this->db->where('status_code', $filters['status_code']);
        }

        // Error code filtreleme
        if (isset($filters['error_code']) && $filters['error_code']) {
            $this->db->where('error_code', $filters['error_code']);
        }

        // Endpoint filtreleme
        if (isset($filters['endpoint']) && $filters['endpoint']) {
            $this->db->like('endpoint', $filters['endpoint']);
        }

        // User ID hash filtreleme (anonimleştirilmiş)
        if (isset($filters['user_id_hash']) && $filters['user_id_hash']) {
            $this->db->where('user_id_hash', $filters['user_id_hash']);
        }

        // Status filtreleme
        if (isset($filters['status']) && $filters['status']) {
            $this->db->where('status', $filters['status']);
        }

        // Severity filtreleme
        if (isset($filters['severity']) && $filters['severity']) {
            $this->db->where('severity', $filters['severity']);
        }

        // Tarih aralığı filtreleme
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('created_at <=', $filters['date_to']);
        }

        // Mesaj arama
        if (isset($filters['search']) && $filters['search']) {
            $this->db->group_start();
            $this->db->like('message', $filters['search']);
            $this->db->or_like('endpoint', $filters['search']);
            $this->db->or_like('error_code', $filters['search']);
            $this->db->group_end();
        }

        // Sıralama
        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'last_occurred_at';
        $order_dir = isset($filters['order_dir']) ? $filters['order_dir'] : 'DESC';
        $this->db->order_by($order_by, $order_dir);

        // Limit ve offset
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Toplam hata sayısı (filtreli)
     */
    public function count_filtered($filters = [])
    {
        // Aynı filtreleri uygula
        if (isset($filters['status_code']) && $filters['status_code']) {
            $this->db->where('status_code', $filters['status_code']);
        }
        if (isset($filters['error_code']) && $filters['error_code']) {
            $this->db->where('error_code', $filters['error_code']);
        }
        if (isset($filters['endpoint']) && $filters['endpoint']) {
            $this->db->like('endpoint', $filters['endpoint']);
        }
        if (isset($filters['status']) && $filters['status']) {
            $this->db->where('status', $filters['status']);
        }
        if (isset($filters['severity']) && $filters['severity']) {
            $this->db->where('severity', $filters['severity']);
        }
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('created_at <=', $filters['date_to']);
        }
        if (isset($filters['search']) && $filters['search']) {
            $this->db->group_start();
            $this->db->like('message', $filters['search']);
            $this->db->or_like('endpoint', $filters['search']);
            $this->db->or_like('error_code', $filters['search']);
            $this->db->group_end();
        }

        return $this->db->count_all_results($this->table);
    }

    /**
     * Hata güncelle
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hata durumunu güncelle
     */
    public function update_status($id, $status, $resolved_by = null, $notes = null)
    {
        $data = ['status' => $status];
        
        if ($status === 'resolved' || $status === 'closed') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
            if ($resolved_by) {
                $data['resolved_by'] = $resolved_by;
            }
        }
        
        if ($notes) {
            $data['resolution_notes'] = $notes;
        }

        return $this->update($id, $data);
    }

    /**
     * Hata atama
     */
    public function assign($id, $assigned_to)
    {
        return $this->update($id, [
            'assigned_to' => $assigned_to,
            'status' => 'investigating'
        ]);
    }

    /**
     * Hata istatistikleri
     */
    public function get_statistics($date_from = null, $date_to = null)
    {
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }

        // Toplam hata sayısı
        $total = $this->db->count_all_results($this->table);

        // Status code'a göre dağılım
        $this->db->select('status_code, COUNT(*) as count');
        $this->db->group_by('status_code');
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_status_code = $this->db->get($this->table)->result_array();

        // Error code'a göre dağılım
        $this->db->select('error_code, COUNT(*) as count');
        $this->db->group_by('error_code');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_error_code = $this->db->get($this->table)->result_array();

        // Endpoint'e göre dağılım (en çok hata veren)
        $this->db->select('endpoint, COUNT(*) as count');
        $this->db->where('endpoint IS NOT NULL');
        $this->db->group_by('endpoint');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_endpoint = $this->db->get($this->table)->result_array();

        // Günlük trend (son 7 gün)
        $this->db->select('DATE(created_at) as date, COUNT(*) as count');
        $this->db->where('created_at >=', date('Y-m-d', strtotime('-7 days')));
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        $daily_trend = $this->db->get($this->table)->result_array();

        // Status'a göre dağılım
        $this->db->select('status, COUNT(*) as count');
        $this->db->group_by('status');
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_status = $this->db->get($this->table)->result_array();

        return [
            'total' => $total,
            'by_status_code' => $by_status_code,
            'by_error_code' => $by_error_code,
            'by_endpoint' => $by_endpoint,
            'daily_trend' => $daily_trend,
            'by_status' => $by_status
        ];
    }

    /**
     * User ID hash oluştur (mahremiyet için)
     */
    private function hash_user_id($user_id)
    {
        // Salt ile hash'le (aynı user_id her zaman aynı hash'i üretir)
        $salt = $this->config->item('encryption_key') ?: 'default_salt';
        return hash('sha256', $user_id . $salt);
    }

    /**
     * Error group ID oluştur (benzer hataları gruplamak için)
     */
    private function generate_error_group_id($data)
    {
        // Aynı error_code, endpoint ve message pattern'ine sahip hataları grupla
        $group_string = ($data['error_code'] ?? '') . '|' . 
                       ($data['endpoint'] ?? '') . '|' . 
                       substr($data['message'] ?? '', 0, 100);
        return hash('md5', $group_string);
    }

    /**
     * Error group occurrence count'u güncelle
     */
    private function update_error_group($error_group_id)
    {
        $this->db->set('occurrence_count', 'occurrence_count + 1', false);
        $this->db->set('last_occurred_at', date('Y-m-d H:i:s'));
        $this->db->where('error_group_id', $error_group_id);
        $this->db->update($this->table);
    }

    /**
     * Kritik hataları getir (bildirim için)
     */
    public function get_critical_errors($limit = 10)
    {
        $this->db->where('severity', 'critical');
        $this->db->where('status', 'new');
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Son 24 saatteki hata sayısı
     */
    public function count_last_24h()
    {
        $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
        return $this->db->count_all_results($this->table);
    }
}

