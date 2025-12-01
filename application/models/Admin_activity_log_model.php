<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin Activity Log Model
 * Admin aktivitelerini loglama (audit trail)
 */
class Admin_activity_log_model extends CI_Model
{
    protected $table = 'admin_activity_logs';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('security');
    }

    /**
     * Yeni aktivite log kaydı oluştur
     */
    public function create($data)
    {
        // Resource ID hash oluştur (mahremiyet için)
        if (isset($data['resource_id']) && $data['resource_id']) {
            $data['resource_id_hash'] = $this->hash_resource_id($data['resource_id']);
        }

        // IP adresi ve user agent
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = $this->input->ip_address();
        }
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $this->input->user_agent();
        }

        // Şüpheli aktivite kontrolü
        if (!isset($data['is_suspicious'])) {
            $data['is_suspicious'] = $this->check_suspicious_activity($data);
        }

        // Details JSON formatına çevir
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details'], JSON_UNESCAPED_UNICODE);
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Aktivite loglarını filtrele ve getir
     */
    public function get_filtered($filters = [], $limit = 50, $offset = 0)
    {
        // Admin ID filtreleme
        if (isset($filters['admin_id']) && $filters['admin_id']) {
            $this->db->where('admin_id', $filters['admin_id']);
        }

        // Action filtreleme
        if (isset($filters['action']) && $filters['action']) {
            $this->db->where('action', $filters['action']);
        }

        // Resource type filtreleme
        if (isset($filters['resource_type']) && $filters['resource_type']) {
            $this->db->where('resource_type', $filters['resource_type']);
        }

        // Resource ID hash filtreleme
        if (isset($filters['resource_id_hash']) && $filters['resource_id_hash']) {
            $this->db->where('resource_id_hash', $filters['resource_id_hash']);
        }

        // Şüpheli aktivite filtreleme
        if (isset($filters['is_suspicious']) && $filters['is_suspicious'] !== '') {
            $this->db->where('is_suspicious', $filters['is_suspicious']);
        }

        // Tarih aralığı filtreleme
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('created_at <=', $filters['date_to']);
        }

        // Arama
        if (isset($filters['search']) && $filters['search']) {
            $this->db->group_start();
            $this->db->like('action', $filters['search']);
            $this->db->or_like('resource_type', $filters['search']);
            $this->db->group_end();
        }

        // Sıralama
        $order_by = isset($filters['order_by']) ? $filters['order_by'] : 'created_at';
        $order_dir = isset($filters['order_dir']) ? $filters['order_dir'] : 'DESC';
        $this->db->order_by($order_by, $order_dir);

        // Limit ve offset
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }

        $results = $this->db->get($this->table)->result_array();

        // Details JSON'dan array'e çevir
        foreach ($results as &$result) {
            if ($result['details']) {
                $result['details'] = json_decode($result['details'], true);
            }
        }

        return $results;
    }

    /**
     * Toplam log sayısı (filtreli)
     */
    public function count_filtered($filters = [])
    {
        if (isset($filters['admin_id']) && $filters['admin_id']) {
            $this->db->where('admin_id', $filters['admin_id']);
        }
        if (isset($filters['action']) && $filters['action']) {
            $this->db->where('action', $filters['action']);
        }
        if (isset($filters['resource_type']) && $filters['resource_type']) {
            $this->db->where('resource_type', $filters['resource_type']);
        }
        if (isset($filters['is_suspicious']) && $filters['is_suspicious'] !== '') {
            $this->db->where('is_suspicious', $filters['is_suspicious']);
        }
        if (isset($filters['date_from']) && $filters['date_from']) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to']) {
            $this->db->where('created_at <=', $filters['date_to']);
        }

        return $this->db->count_all_results($this->table);
    }

    /**
     * Belirli bir kullanıcıya yapılan erişimleri getir
     */
    public function get_user_access_logs($user_id, $limit = 50)
    {
        $this->db->where('resource_type', 'user');
        $this->db->where('resource_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Admin'in son aktivitelerini getir
     */
    public function get_admin_recent_activities($admin_id, $limit = 20)
    {
        $this->db->where('admin_id', $admin_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Şüpheli aktiviteleri getir
     */
    public function get_suspicious_activities($limit = 50)
    {
        $this->db->where('is_suspicious', 1);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * İstatistikler
     */
    public function get_statistics($date_from = null, $date_to = null)
    {
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }

        // Toplam aktivite sayısı
        $total = $this->db->count_all_results($this->table);

        // Action'a göre dağılım
        $this->db->select('action, COUNT(*) as count');
        $this->db->group_by('action');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_action = $this->db->get($this->table)->result_array();

        // Admin'e göre dağılım
        $this->db->select('admin_id, COUNT(*) as count');
        $this->db->group_by('admin_id');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $by_admin = $this->db->get($this->table)->result_array();

        // Günlük trend
        $this->db->select('DATE(created_at) as date, COUNT(*) as count');
        $this->db->where('created_at >=', date('Y-m-d', strtotime('-7 days')));
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        $daily_trend = $this->db->get($this->table)->result_array();

        // Şüpheli aktivite sayısı
        $this->db->where('is_suspicious', 1);
        if ($date_from) {
            $this->db->where('created_at >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('created_at <=', $date_to);
        }
        $suspicious_count = $this->db->count_all_results($this->table);

        return [
            'total' => $total,
            'by_action' => $by_action,
            'by_admin' => $by_admin,
            'daily_trend' => $daily_trend,
            'suspicious_count' => $suspicious_count
        ];
    }

    /**
     * Resource ID hash oluştur
     */
    private function hash_resource_id($resource_id)
    {
        $salt = $this->config->item('encryption_key') ?: 'default_salt';
        return hash('sha256', $resource_id . $salt);
    }

    /**
     * Şüpheli aktivite kontrolü
     */
    private function check_suspicious_activity($data)
    {
        $is_suspicious = 0;

        // Gece saatlerinde erişim (00:00 - 05:00)
        $hour = (int)date('H');
        if ($hour >= 0 && $hour < 5) {
            $is_suspicious = 1;
        }

        // Çok sayıda kullanıcı görüntüleme (son 1 saatte 50'den fazla)
        if ($data['action'] === 'view_user') {
            $this->db->where('admin_id', $data['admin_id']);
            $this->db->where('action', 'view_user');
            $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 hour')));
            $count = $this->db->count_all_results($this->table);
            if ($count > 50) {
                $is_suspicious = 1;
            }
        }

        return $is_suspicious;
    }
}

