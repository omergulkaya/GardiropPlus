<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate Limit Library
 * API rate limiting için
 */
class Rate_limit_library {

    private $ci;
    private $limit = 100; // Dakikada maksimum istek
    private $window = 60; // 60 saniye
    private $cache_dir;

    public function __construct() {
        $this->ci =& get_instance();
        $this->cache_dir = APPPATH . 'cache/rate_limit/';
        
        // Cache dizinini oluştur
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Rate limit kontrolü
     * 
     * @param string $identifier IP veya user_id
     * @param int $limit İstek limiti
     * @param int $window Zaman penceresi (saniye)
     * @return bool True if allowed, false if rate limited
     */
    public function check($identifier, $limit = null, $window = null) {
        $limit = $limit ?: $this->limit;
        $window = $window ?: $this->window;
        
        $key = md5($identifier);
        $file = $this->cache_dir . $key . '.json';
        
        $now = time();
        $requests = [];
        
        // Mevcut istekleri oku
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['requests'])) {
                $requests = $data['requests'];
            }
        }
        
        // Eski istekleri temizle (window dışında kalanlar)
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Limit kontrolü
        if (count($requests) >= $limit) {
            // Rate limit aşıldı
            $this->set_headers($limit, count($requests), $window);
            return false;
        }
        
        // Yeni isteği ekle
        $requests[] = $now;
        
        // Kaydet
        file_put_contents($file, json_encode([
            'requests' => array_values($requests),
            'last_update' => $now
        ]));
        
        // Headers set et
        $this->set_headers($limit, count($requests), $window);
        
        return true;
    }

    /**
     * Rate limit headers set et
     */
    private function set_headers($limit, $remaining, $window) {
        if (!headers_sent()) {
            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . max(0, $limit - $remaining));
            header('X-RateLimit-Reset: ' . (time() + $window));
        }
    }

    /**
     * Rate limit bilgilerini al
     */
    public function get_info($identifier) {
        $key = md5($identifier);
        $file = $this->cache_dir . $key . '.json';
        
        if (!file_exists($file)) {
            return [
                'limit' => $this->limit,
                'remaining' => $this->limit,
                'reset' => time() + $this->window
            ];
        }
        
        $data = json_decode(file_get_contents($file), true);
        $now = time();
        $window = $this->window;
        $requests = array_filter($data['requests'] ?? [], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        return [
            'limit' => $this->limit,
            'remaining' => max(0, $this->limit - count($requests)),
            'reset' => time() + $this->window
        ];
    }

    /**
     * Rate limit'i temizle
     */
    public function clear($identifier) {
        $key = md5($identifier);
        $file = $this->cache_dir . $key . '.json';
        
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Eski cache dosyalarını temizle
     */
    public function cleanup() {
        $files = glob($this->cache_dir . '*.json');
        $now = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['last_update'])) {
                // 1 saatten eski dosyaları sil
                if (($now - $data['last_update']) > 3600) {
                    unlink($file);
                }
            }
        }
    }
}

