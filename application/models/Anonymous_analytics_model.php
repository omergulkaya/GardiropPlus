<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Anonymous Analytics Model
 * Anonimleştirilmiş kullanım istatistikleri ve raporlama
 */
class Anonymous_analytics_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('privacy');
    }

    /**
     * Anonimleştirilmiş kullanım istatistikleri
     */
    public function get_anonymous_statistics($date_from = null, $date_to = null)
    {
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        // Toplam kullanıcı sayısı (anonimleştirilmiş)
        $total_users = $this->db->count_all('users');
        
        // Aktif kullanıcı sayısı (anonimleştirilmiş)
        $columns = $this->db->list_fields('users');
        if (in_array('last_login', $columns)) {
            $this->db->where('last_login >=', date('Y-m-d H:i:s', strtotime('-30 days')));
            $active_users = $this->db->count_all_results('users');
        } else {
            $this->db->where('email_verified', 1);
            $active_users = $this->db->count_all_results('users');
        }
        
        // Günlük yeni kullanıcı sayıları (kişisel bilgi olmadan)
        $this->db->select('DATE(created_at) as date, COUNT(*) as count');
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where('DATE(created_at) <=', $date_to);
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        $daily_new_users = $this->db->get('users')->result_array();
        
        // Kategori dağılımı (anonimleştirilmiş)
        $this->db->select('category, COUNT(*) as count');
        $this->db->group_by('category');
        $category_distribution = $this->db->get('clothing_items')->result_array();
        
        // Stil dağılımı (anonimleştirilmiş)
        $this->db->select('style, COUNT(*) as count');
        $this->db->group_by('style');
        $style_distribution = $this->db->get('outfits')->result_array();
        
        // Yaş grupları (anonimleştirilmiş - eğer yaş bilgisi varsa)
        $age_distribution = $this->get_age_distribution();
        
        // Coğrafi dağılım (anonimleştirilmiş - sadece ülke seviyesinde)
        $geo_distribution = $this->get_geo_distribution();
        
        return [
            'total_users' => $total_users,
            'active_users' => $active_users,
            'daily_new_users' => $daily_new_users,
            'category_distribution' => $category_distribution,
            'style_distribution' => $style_distribution,
            'age_distribution' => $age_distribution,
            'geo_distribution' => $geo_distribution,
            'date_range' => [
                'from' => $date_from,
                'to' => $date_to
            ]
        ];
    }

    /**
     * Kişisel bilgi içermeyen trend analizleri
     */
    public function get_trend_analysis($metric = 'users', $period = 'daily', $days = 30)
    {
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        $date_to = date('Y-m-d');
        
        $trends = [];
        
        switch ($metric) {
            case 'users':
                $trends = $this->get_user_trends($date_from, $date_to, $period);
                break;
            case 'clothing':
                $trends = $this->get_clothing_trends($date_from, $date_to, $period);
                break;
            case 'outfits':
                $trends = $this->get_outfit_trends($date_from, $date_to, $period);
                break;
            case 'activity':
                $trends = $this->get_activity_trends($date_from, $date_to, $period);
                break;
        }
        
        return [
            'metric' => $metric,
            'period' => $period,
            'trends' => $trends,
            'date_range' => [
                'from' => $date_from,
                'to' => $date_to
            ]
        ];
    }

    /**
     * Kullanıcı trendleri (anonimleştirilmiş)
     */
    private function get_user_trends($date_from, $date_to, $period = 'daily')
    {
        if ($period === 'daily') {
            $this->db->select('DATE(created_at) as date, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE(created_at)');
            $this->db->order_by('date', 'ASC');
        } elseif ($period === 'weekly') {
            $this->db->select('YEARWEEK(created_at) as week, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('YEARWEEK(created_at)');
            $this->db->order_by('week', 'ASC');
        } else { // monthly
            $this->db->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE_FORMAT(created_at, "%Y-%m")');
            $this->db->order_by('month', 'ASC');
        }
        
        return $this->db->get('users')->result_array();
    }

    /**
     * Kıyafet trendleri
     */
    private function get_clothing_trends($date_from, $date_to, $period = 'daily')
    {
        if ($period === 'daily') {
            $this->db->select('DATE(created_at) as date, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE(created_at)');
            $this->db->order_by('date', 'ASC');
        } elseif ($period === 'weekly') {
            $this->db->select('YEARWEEK(created_at) as week, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('YEARWEEK(created_at)');
            $this->db->order_by('week', 'ASC');
        } else {
            $this->db->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE_FORMAT(created_at, "%Y-%m")');
            $this->db->order_by('month', 'ASC');
        }
        
        return $this->db->get('clothing_items')->result_array();
    }

    /**
     * Kombin trendleri
     */
    private function get_outfit_trends($date_from, $date_to, $period = 'daily')
    {
        if ($period === 'daily') {
            $this->db->select('DATE(created_at) as date, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE(created_at)');
            $this->db->order_by('date', 'ASC');
        } elseif ($period === 'weekly') {
            $this->db->select('YEARWEEK(created_at) as week, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('YEARWEEK(created_at)');
            $this->db->order_by('week', 'ASC');
        } else {
            $this->db->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE_FORMAT(created_at, "%Y-%m")');
            $this->db->order_by('month', 'ASC');
        }
        
        return $this->db->get('outfits')->result_array();
    }

    /**
     * Aktivite trendleri (anonimleştirilmiş)
     */
    private function get_activity_trends($date_from, $date_to, $period = 'daily')
    {
        // Admin aktivite loglarından (anonimleştirilmiş)
        if ($period === 'daily') {
            $this->db->select('DATE(created_at) as date, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE(created_at)');
            $this->db->order_by('date', 'ASC');
        } elseif ($period === 'weekly') {
            $this->db->select('YEARWEEK(created_at) as week, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('YEARWEEK(created_at)');
            $this->db->order_by('week', 'ASC');
        } else {
            $this->db->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count');
            $this->db->where('DATE(created_at) >=', $date_from);
            $this->db->where('DATE(created_at) <=', $date_to);
            $this->db->group_by('DATE_FORMAT(created_at, "%Y-%m")');
            $this->db->order_by('month', 'ASC');
        }
        
        return $this->db->get('admin_activity_logs')->result_array();
    }

    /**
     * Yaş dağılımı (anonimleştirilmiş - eğer yaş bilgisi varsa)
     */
    private function get_age_distribution()
    {
        // Eğer users tablosunda birth_date veya age kolonu varsa
        $columns = $this->db->list_fields('users');
        
        if (in_array('birth_date', $columns)) {
            $this->db->select('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN "0-17"
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 24 THEN "18-24"
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 25 AND 34 THEN "25-34"
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 35 AND 44 THEN "35-44"
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 45 AND 54 THEN "45-54"
                    WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 55 THEN "55+"
                    ELSE "Unknown"
                END as age_group,
                COUNT(*) as count
            ');
            $this->db->where('birth_date IS NOT NULL');
            $this->db->group_by('age_group');
            return $this->db->get('users')->result_array();
        }
        
        return [];
    }

    /**
     * Coğrafi dağılım (anonimleştirilmiş - sadece ülke seviyesinde)
     */
    private function get_geo_distribution()
    {
        // Eğer IP geolocation verisi varsa (opsiyonel)
        // Şimdilik boş döndür, gelecekte eklenebilir
        return [];
    }

    /**
     * Toplu veri raporları (bireysel kullanıcı bilgisi olmadan)
     */
    public function get_aggregate_report($report_type = 'general', $date_from = null, $date_to = null)
    {
        $date_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $date_to ?: date('Y-m-d');
        
        switch ($report_type) {
            case 'general':
                return $this->get_general_aggregate_report($date_from, $date_to);
            case 'usage':
                return $this->get_usage_aggregate_report($date_from, $date_to);
            case 'engagement':
                return $this->get_engagement_aggregate_report($date_from, $date_to);
            default:
                return $this->get_general_aggregate_report($date_from, $date_to);
        }
    }

    /**
     * Genel toplu rapor
     */
    private function get_general_aggregate_report($date_from, $date_to)
    {
        // Toplam sayılar (anonimleştirilmiş)
        $stats = [
            'total_users' => $this->db->count_all('users'),
            'total_clothing' => $this->db->count_all('clothing_items'),
            'total_outfits' => $this->db->count_all('outfits'),
        ];
        
        // Kategori bazlı toplamlar (anonimleştirilmiş)
        $this->db->select('category, COUNT(*) as total, COUNT(DISTINCT user_id) as unique_users');
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where('DATE(created_at) <=', $date_to);
        $this->db->group_by('category');
        $stats['category_totals'] = $this->db->get('clothing_items')->result_array();
        
        // Stil bazlı toplamlar (anonimleştirilmiş)
        $this->db->select('style, COUNT(*) as total, COUNT(DISTINCT user_id) as unique_users');
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where('DATE(created_at) <=', $date_to);
        $this->db->group_by('style');
        $stats['style_totals'] = $this->db->get('outfits')->result_array();
        
        return $stats;
    }

    /**
     * Kullanım toplu raporu
     */
    private function get_usage_aggregate_report($date_from, $date_to)
    {
        // Günlük aktif kullanıcı sayıları (anonimleştirilmiş)
        $this->db->select('DATE(created_at) as date, COUNT(DISTINCT user_id) as daily_active_users');
        $this->db->where('DATE(created_at) >=', $date_from);
        $this->db->where('DATE(created_at) <=', $date_to);
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        $daily_active = $this->db->get('clothing_items')->result_array();
        
        // Ortalama kıyafet sayısı (anonimleştirilmiş)
        $this->db->select('AVG(item_count) as avg_clothing_per_user');
        $this->db->from('(SELECT user_id, COUNT(*) as item_count FROM clothing_items GROUP BY user_id) as subquery');
        $avg_clothing = $this->db->get()->row()->avg_clothing_per_user ?? 0;
        
        // Ortalama kombin sayısı (anonimleştirilmiş)
        $this->db->select('AVG(item_count) as avg_outfits_per_user');
        $this->db->from('(SELECT user_id, COUNT(*) as item_count FROM outfits GROUP BY user_id) as subquery');
        $avg_outfits = $this->db->get()->row()->avg_outfits_per_user ?? 0;
        
        return [
            'daily_active_users' => $daily_active,
            'avg_clothing_per_user' => round($avg_clothing, 2),
            'avg_outfits_per_user' => round($avg_outfits, 2)
        ];
    }

    /**
     * Etkileşim toplu raporu
     */
    private function get_engagement_aggregate_report($date_from, $date_to)
    {
        // Günlük kombin kullanımı (anonimleştirilmiş)
        $this->db->select('DATE(date_worn) as date, COUNT(*) as count');
        $this->db->where('DATE(date_worn) >=', $date_from);
        $this->db->where('DATE(date_worn) <=', $date_to);
        $this->db->group_by('DATE(date_worn)');
        $this->db->order_by('date', 'ASC');
        $daily_outfit_usage = $this->db->get('worn_outfits')->result_array();
        
        // En popüler kategoriler (anonimleştirilmiş)
        $this->db->select('ci.category, COUNT(wo.id) as usage_count');
        $this->db->from('worn_outfits wo');
        $this->db->join('outfit_items oi', 'wo.outfit_id = oi.outfit_id', 'left');
        $this->db->join('clothing_items ci', 'oi.clothing_item_id = ci.id', 'left');
        $this->db->where('DATE(wo.date_worn) >=', $date_from);
        $this->db->where('DATE(wo.date_worn) <=', $date_to);
        $this->db->where('ci.category IS NOT NULL');
        $this->db->group_by('ci.category');
        $this->db->order_by('usage_count', 'DESC');
        $this->db->limit(10);
        $popular_categories = $this->db->get()->result_array();
        
        return [
            'daily_outfit_usage' => $daily_outfit_usage,
            'popular_categories' => $popular_categories
        ];
    }

    /**
     * Export için anonimleştirilmiş veri
     */
    public function export_anonymous_data($format = 'json', $report_type = 'general')
    {
        $data = $this->get_aggregate_report($report_type);
        
        if ($format === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'csv') {
            return $this->convert_to_csv($data);
        }
        
        return $data;
    }

    /**
     * CSV formatına çevir
     */
    private function convert_to_csv($data)
    {
        $output = fopen('php://temp', 'r+');
        
        // Basit CSV dönüşümü (daha gelişmiş implementasyon gerekebilir)
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                fputcsv($output, array_merge([$key], $value));
            } else {
                fputcsv($output, [$key, $value]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}

