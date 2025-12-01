<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Query Cache Library
 * Veritabanı sorgu sonuçlarını cache'leme
 */
class Query_cache_library
{
    private $enabled = true;
    private $default_ttl = 3600; // 1 saat
    private $cache_table = 'query_cache';
    
    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        
        // Config'den ayarları al
        $this->enabled = $CI->config->item('query_cache_enabled') !== false;
        $this->default_ttl = (int)($CI->config->item('query_cache_ttl') ?: 3600);
    }

    /**
     * Cache'den sorgu sonucunu al
     */
    public function get($cache_key)
    {
        if (!$this->enabled) {
            return false;
        }

        $CI =& get_instance();
        
        $query = $CI->db->get_where($this->cache_table, [
            'cache_key' => $cache_key,
            'expires_at >' => date('Y-m-d H:i:s')
        ], 1);
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            return unserialize($row->cache_value);
        }
        
        return false;
    }

    /**
     * Sorgu sonucunu cache'e kaydet
     */
    public function set($cache_key, $data, $ttl = null)
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?: $this->default_ttl;
        $expires_at = date('Y-m-d H:i:s', time() + $ttl);
        
        $CI =& get_instance();
        
        // Mevcut cache'i kontrol et
        $existing = $CI->db->get_where($this->cache_table, ['cache_key' => $cache_key])->row();
        
        $cache_data = [
            'cache_value' => serialize($data),
            'expires_at' => $expires_at
        ];
        
        if ($existing) {
            // Güncelle
            $CI->db->where('cache_key', $cache_key);
            return $CI->db->update($this->cache_table, $cache_data);
        } else {
            // Yeni kayıt
            $cache_data['cache_key'] = $cache_key;
            $cache_data['created_at'] = date('Y-m-d H:i:s');
            return $CI->db->insert($this->cache_table, $cache_data);
        }
    }

    /**
     * Cache'den sil
     */
    public function delete($cache_key)
    {
        $CI =& get_instance();
        return $CI->db->delete($this->cache_table, ['cache_key' => $cache_key]);
    }

    /**
     * Pattern'e göre cache temizle
     */
    public function clear($pattern = null)
    {
        $CI =& get_instance();
        
        if ($pattern) {
            $CI->db->like('cache_key', $pattern);
            return $CI->db->delete($this->cache_table);
        } else {
            // Tüm cache'i temizle
            return $CI->db->truncate($this->cache_table);
        }
    }

    /**
     * Süresi dolmuş cache'leri temizle
     */
    public function cleanup()
    {
        $CI =& get_instance();
        return $CI->db->delete($this->cache_table, ['expires_at <' => date('Y-m-d H:i:s')]);
    }

    /**
     * Cache key oluştur
     */
    public function create_key($query, $params = [])
    {
        $key_data = [
            'query' => $query,
            'params' => $params
        ];
        return 'query_' . md5(serialize($key_data));
    }
}

