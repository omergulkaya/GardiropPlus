<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Error Statistics Model
 * Hata istatistikleri ve grafik verileri için
 */
class Error_statistics_model extends CI_Model
{
    protected $table = 'api_errors';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Hata sayısı trend grafiği (günlük, haftalık, aylık)
     */
    public function get_error_trend($period = 'daily', $days = 30)
    {
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        if ($period === 'daily') {
            $this->db->select('DATE(created_at) as date, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->group_by('DATE(created_at)');
            $this->db->order_by('date', 'ASC');
        } elseif ($period === 'weekly') {
            $this->db->select('YEARWEEK(created_at) as week, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->group_by('YEARWEEK(created_at)');
            $this->db->order_by('week', 'ASC');
        } else { // monthly
            $this->db->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->group_by('DATE_FORMAT(created_at, "%Y-%m")');
            $this->db->order_by('month', 'ASC');
        }
        
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Hata tipi dağılımı (status code ve error code bazlı)
     */
    public function get_error_type_distribution($date_from = null, $date_to = null)
    {
        if ($date_from) {
            $this->db->where('DATE(created_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(created_at) <=', $date_to);
        }

        // Status code dağılımı
        $this->db->select('status_code, COUNT(*) as count');
        $this->db->group_by('status_code');
        $this->db->order_by('count', 'DESC');
        $by_status_code = $this->db->get($this->table)->result_array();

        // Error code dağılımı
        $this->db->select('error_code, COUNT(*) as count');
        if ($date_from) {
            $this->db->where('DATE(created_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(created_at) <=', $date_to);
        }
        $this->db->group_by('error_code');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(20); // En çok görülen 20 hata kodu
        $by_error_code = $this->db->get($this->table)->result_array();

        // Severity dağılımı
        $this->db->select('severity, COUNT(*) as count');
        if ($date_from) {
            $this->db->where('DATE(created_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(created_at) <=', $date_to);
        }
        $this->db->group_by('severity');
        $this->db->order_by('count', 'DESC');
        $by_severity = $this->db->get($this->table)->result_array();

        return [
            'by_status_code' => $by_status_code,
            'by_error_code' => $by_error_code,
            'by_severity' => $by_severity
        ];
    }

    /**
     * En çok hata veren endpoint'ler
     */
    public function get_top_error_endpoints($limit = 10, $date_from = null, $date_to = null)
    {
        $this->db->select('endpoint, COUNT(*) as error_count, COUNT(DISTINCT error_code) as unique_errors');
        $this->db->where('endpoint IS NOT NULL');
        $this->db->where('endpoint !=', '');
        
        if ($date_from) {
            $this->db->where('DATE(created_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(created_at) <=', $date_to);
        }
        
        $this->db->group_by('endpoint');
        $this->db->order_by('error_count', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Hata çözülme süreleri
     */
    public function get_resolution_times($date_from = null, $date_to = null)
    {
        $this->db->select('
            AVG(TIMESTAMPDIFF(HOUR, first_occurred_at, resolved_at)) as avg_hours,
            AVG(TIMESTAMPDIFF(MINUTE, first_occurred_at, resolved_at)) as avg_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, first_occurred_at, resolved_at)) as min_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, first_occurred_at, resolved_at)) as max_minutes,
            COUNT(*) as resolved_count
        ');
        $this->db->where('status', 'resolved');
        $this->db->where('resolved_at IS NOT NULL');
        $this->db->where('first_occurred_at IS NOT NULL');
        
        if ($date_from) {
            $this->db->where('DATE(resolved_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(resolved_at) <=', $date_to);
        }
        
        $result = $this->db->get($this->table)->row_array();
        
        // Severity bazlı çözülme süreleri
        $this->db->select('
            severity,
            AVG(TIMESTAMPDIFF(HOUR, first_occurred_at, resolved_at)) as avg_hours,
            COUNT(*) as count
        ');
        $this->db->where('status', 'resolved');
        $this->db->where('resolved_at IS NOT NULL');
        if ($date_from) {
            $this->db->where('DATE(resolved_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(resolved_at) <=', $date_to);
        }
        $this->db->group_by('severity');
        $by_severity = $this->db->get($this->table)->result_array();
        
        return [
            'overall' => $result,
            'by_severity' => $by_severity
        ];
    }

    /**
     * Hata istatistikleri özeti (dashboard için)
     */
    public function get_statistics_summary($days = 30)
    {
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        // Toplam hata sayısı
        $this->db->where('DATE(created_at) >=', $date_from);
        $total_errors = $this->db->count_all_results($this->table);
        
        // Kritik hatalar
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where('severity', 'critical');
        $critical_errors = $this->db->count_all_results($this->table);
        
        // Çözülmemiş hatalar
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where_in('status', ['new', 'investigating']);
        $unresolved_errors = $this->db->count_all_results($this->table);
        
        // Son 24 saat
        $this->db->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $last_24h = $this->db->count_all_results($this->table);
        
        // Ortalama çözülme süresi (saat)
        $this->db->select('AVG(TIMESTAMPDIFF(HOUR, first_occurred_at, resolved_at)) as avg_hours');
        $this->db->where('status', 'resolved');
        $this->db->where('resolved_at IS NOT NULL');
        $this->db->where('DATE(resolved_at) >=', $date_from);
        $avg_resolution = $this->db->get($this->table)->row()->avg_hours ?? 0;
        
        return [
            'total_errors' => $total_errors,
            'critical_errors' => $critical_errors,
            'unresolved_errors' => $unresolved_errors,
            'last_24h' => $last_24h,
            'avg_resolution_hours' => round($avg_resolution, 2)
        ];
    }
}

