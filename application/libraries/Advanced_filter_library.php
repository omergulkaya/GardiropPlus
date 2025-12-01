<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Advanced Filter Library
 * Gelişmiş filtreleme ve arama özellikleri
 */
class Advanced_filter_library
{
    private $sensitive_fields = ['password', 'token', 'api_key', 'secret', 'access_token', 'refresh_token', 'credit_card', 'ssn'];
    
    public function __construct()
    {
        // Config'den hassas alanları oku (varsa)
        $CI =& get_instance();
        $config_sensitive = $CI->config->item('sensitive_fields');
        if ($config_sensitive && is_array($config_sensitive)) {
            $this->sensitive_fields = array_merge($this->sensitive_fields, $config_sensitive);
        }
    }

    /**
     * Hassas bilgileri filtreleyen arama
     */
    public function safe_search($query, $table, $searchable_fields = [], $exclude_sensitive = true)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        if (empty($query)) {
            return [];
        }
        
        // Hassas alanları çıkar
        if ($exclude_sensitive) {
            $searchable_fields = array_diff($searchable_fields, $this->sensitive_fields);
        }
        
        if (empty($searchable_fields)) {
            return [];
        }
        
        // Arama sorgusu oluştur
        $CI->db->group_start();
        foreach ($searchable_fields as $field) {
            $CI->db->or_like($field, $query);
        }
        $CI->db->group_end();
        
        return $CI->db->get($table)->result_array();
    }

    /**
     * Görünüm modları için alan filtreleme
     */
    public function filter_fields_by_view_mode($data, $view_mode = 'standard', $table_name = null)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        // View mode tanımları
        $view_modes = $this->get_view_mode_definitions($table_name);
        
        if (!isset($view_modes[$view_mode])) {
            return $data;
        }
        
        $allowed_fields = $view_modes[$view_mode];
        $filtered_data = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $filtered_data[$field] = $data[$field];
            }
        }
        
        return $filtered_data;
    }

    /**
     * View mode tanımları
     */
    private function get_view_mode_definitions($table_name = null)
    {
        $modes = [
            'minimal' => ['id', 'name', 'created_at'],
            'standard' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            'detailed' => ['id', 'name', 'email', 'phone', 'created_at', 'updated_at', 'status'],
            'full' => [] // Tüm alanlar (hassas alanlar hariç)
        ];
        
        // Tablo bazlı özel tanımlar
        if ($table_name === 'users') {
            $modes = [
                'minimal' => ['id', 'first_name', 'last_name', 'created_at'],
                'standard' => ['id', 'first_name', 'last_name', 'email', 'created_at'],
                'detailed' => ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'updated_at', 'email_verified'],
                'full' => []
            ];
        } elseif ($table_name === 'api_errors') {
            $modes = [
                'minimal' => ['id', 'error_code', 'status_code', 'created_at'],
                'standard' => ['id', 'error_code', 'status_code', 'message', 'endpoint', 'created_at'],
                'detailed' => ['id', 'error_code', 'status_code', 'message', 'endpoint', 'severity', 'status', 'created_at'],
                'full' => []
            ];
        }
        
        return $modes;
    }

    /**
     * Özelleştirilebilir sütun görünürlüğü
     */
    public function get_visible_columns($table_name, $user_id = null, $default_columns = null)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        // Kullanıcı tercihlerini kontrol et
        if ($user_id) {
            $preferences = $CI->db->get_where('user_column_preferences', [
                'user_id' => $user_id,
                'table_name' => $table_name
            ])->row_array();
            
            if ($preferences && !empty($preferences['visible_columns'])) {
                return json_decode($preferences['visible_columns'], true);
            }
        }
        
        // Varsayılan sütunlar
        if ($default_columns) {
            return $default_columns;
        }
        
        // Tablo için varsayılan sütunlar
        return $this->get_default_columns($table_name);
    }

    /**
     * Sütun görünürlük tercihlerini kaydet
     */
    public function save_column_preferences($user_id, $table_name, $visible_columns)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        // Mevcut tercihi kontrol et
        $existing = $CI->db->get_where('user_column_preferences', [
            'user_id' => $user_id,
            'table_name' => $table_name
        ])->row_array();
        
        $data = [
            'user_id' => $user_id,
            'table_name' => $table_name,
            'visible_columns' => json_encode($visible_columns, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($existing) {
            $CI->db->where('id', $existing['id']);
            return $CI->db->update('user_column_preferences', $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return $CI->db->insert('user_column_preferences', $data);
        }
    }

    /**
     * Varsayılan sütunlar
     */
    private function get_default_columns($table_name)
    {
        $defaults = [
            'users' => ['id', 'first_name', 'last_name', 'email', 'created_at'],
            'api_errors' => ['id', 'error_code', 'status_code', 'message', 'endpoint', 'severity', 'status', 'created_at'],
            'admin_activity_logs' => ['id', 'admin_id', 'action', 'resource_type', 'created_at'],
            'clothing_items' => ['id', 'name', 'category', 'user_id', 'created_at']
        ];
        
        return $defaults[$table_name] ?? [];
    }

    /**
     * Çoklu filtreleme
     */
    public function apply_filters($filters, $table, $base_query = null)
    {
        $CI =& get_instance();
        $CI->load->database();
        
        if ($base_query) {
            $CI->db = $base_query;
        }
        
        foreach ($filters as $field => $value) {
            if (empty($value) || in_array($field, $this->sensitive_fields)) {
                continue;
            }
            
            if (is_array($value)) {
                // Array değerler için IN sorgusu
                $CI->db->where_in($field, $value);
            } elseif (strpos($value, '%') !== false || strpos($value, '*') !== false) {
                // Wildcard arama
                $value = str_replace('*', '%', $value);
                $CI->db->like($field, $value);
            } elseif (strpos($field, '_from') !== false) {
                // Tarih aralığı başlangıç
                $real_field = str_replace('_from', '', $field);
                $CI->db->where($real_field . ' >=', $value);
            } elseif (strpos($field, '_to') !== false) {
                // Tarih aralığı bitiş
                $real_field = str_replace('_to', '', $field);
                $CI->db->where($real_field . ' <=', $value);
            } else {
                // Normal eşitlik
                $CI->db->where($field, $value);
            }
        }
        
        return $CI->db;
    }
}

