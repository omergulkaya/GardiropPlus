<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cache Library
 * Response ve query caching için
 */
class Cache_library
{
    private $ci;
    private $default_ttl = 3600;
// 1 saat
    private $cache_enabled = true;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->driver('cache', ['adapter' => 'file', 'backup' => 'file']);
// Cache enabled kontrolü
        $this->cache_enabled = $this->ci->config->item('cache_enabled') !== false;
    }

    /**
     * Cache'den veri al
     *
     * @param string $key Cache key
     * @return mixed|null
     */
    public function get($key)
    {
        if (!$this->cache_enabled) {
            return null;
        }

        $cache_key = $this->get_cache_key($key);
        return $this->ci->cache->get($cache_key);
    }

    /**
     * Cache'e veri kaydet
     *
     * @param string $key Cache key
     * @param mixed $data Cache edilecek veri
     * @param int $ttl Time to live (saniye)
     * @return bool
     */
    public function set($key, $data, $ttl = null)
    {
        if (!$this->cache_enabled) {
            return false;
        }

        $cache_key = $this->get_cache_key($key);
        $ttl = $ttl ?: $this->default_ttl;
        return $this->ci->cache->save($cache_key, $data, $ttl);
    }

    /**
     * Cache'den veri sil
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key)
    {
        $cache_key = $this->get_cache_key($key);
        return $this->ci->cache->delete($cache_key);
    }

    /**
     * Pattern'e göre cache temizle
     *
     * @param string $pattern Pattern (örn: 'user_*')
     * @return int Silinen kayıt sayısı
     */
    public function delete_pattern($pattern)
    {
        // File cache için pattern deletion
        $cache_path = $this->ci->config->item('cache_path') ?: APPPATH . 'cache/';
        $files = glob($cache_path . $pattern);
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Response cache key oluştur
     *
     * @param string $uri URI
     * @param array $params Query parametreleri
     * @return string
     */
    public function get_response_key($uri, $params = [])
    {
        $key = 'response_' . md5($uri . serialize($params));
        return $key;
    }

    /**
     * Query cache key oluştur
     *
     * @param string $query SQL query
     * @param array $params Query parametreleri
     * @return string
     */
    public function get_query_key($query, $params = [])
    {
        $key = 'query_' . md5($query . serialize($params));
        return $key;
    }

    /**
     * Cache key normalize et
     *
     * @param string $key
     * @return string
     */
    private function get_cache_key($key)
    {
        // Key'i normalize et (özel karakterleri temizle)
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }

    /**
     * Cache istatistikleri
     *
     * @return array
     */
    public function get_stats()
    {
        $cache_path = $this->ci->config->item('cache_path') ?: APPPATH . 'cache/';
        $files = glob($cache_path . '*');
        $total_size = 0;
        $file_count = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $total_size += filesize($file);
                $file_count++;
            }
        }

        return [
            'enabled' => $this->cache_enabled,
            'file_count' => $file_count,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2)
        ];
    }

    /**
     * Cache'i temizle
     *
     * @return bool
     */
    public function clear()
    {
        return $this->ci->cache->clean();
    }

    /**
     * Cache warming - önemli endpoint'leri önceden cache'le
     */
    public function warm_up()
    {
        // Örnek: Sık kullanılan endpoint'leri cache'le
        // Bu metod özelleştirilebilir
        log_message('info', 'Cache warming started');
    }
}
