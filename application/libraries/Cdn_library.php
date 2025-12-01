<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CDN Library
 * Cloudflare, AWS CloudFront ve diğer CDN sağlayıcıları için entegrasyon
 */
class Cdn_library
{
    private $enabled = false;
    private $provider = 'cloudflare'; // cloudflare, cloudfront, custom
    private $base_url = '';
    private $api_key = '';
    private $zone_id = '';
    
    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->library('env_library');
        $env = $CI->env_library;
        
        $this->enabled = $env->get('CDN_ENABLED', 'false') === 'true';
        $this->provider = $env->get('CDN_PROVIDER', 'cloudflare');
        $this->base_url = $env->get('CDN_BASE_URL', '');
        $this->api_key = $env->get('CDN_API_KEY', '');
        $this->zone_id = $env->get('CDN_ZONE_ID', '');
    }

    /**
     * CDN URL'i oluştur
     */
    public function url($path, $options = [])
    {
        if (!$this->enabled || empty($this->base_url)) {
            // CDN yoksa normal base_url kullan
            return base_url($path);
        }

        // Path'i temizle
        $path = ltrim($path, '/');
        
        // Query string parametreleri (resize için)
        $query = [];
        if (isset($options['width'])) {
            $query['w'] = $options['width'];
        }
        if (isset($options['height'])) {
            $query['h'] = $options['height'];
        }
        if (isset($options['quality'])) {
            $query['q'] = $options['quality'];
        }
        if (isset($options['format'])) {
            $query['f'] = $options['format'];
        }

        $url = rtrim($this->base_url, '/') . '/' . $path;
        
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * Image URL'i oluştur (CDN ile)
     */
    public function image_url($path, $width = null, $height = null, $format = null)
    {
        $options = [];
        if ($width) $options['width'] = $width;
        if ($height) $options['height'] = $height;
        if ($format) $options['format'] = $format;
        
        return $this->url($path, $options);
    }

    /**
     * Responsive image srcset oluştur
     */
    public function srcset($path, $sizes = [150, 300, 600, 900, 1200])
    {
        $srcset = [];
        foreach ($sizes as $width) {
            $url = $this->image_url($path, $width);
            $srcset[] = $url . ' ' . $width . 'w';
        }
        return implode(', ', $srcset);
    }

    /**
     * CDN'e dosya yükle (purge cache için)
     */
    public function purge_cache($paths = [])
    {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'CDN not enabled'];
        }

        switch ($this->provider) {
            case 'cloudflare':
                return $this->purge_cloudflare($paths);
            case 'cloudfront':
                return $this->purge_cloudfront($paths);
            default:
                return ['success' => false, 'error' => 'Unsupported CDN provider'];
        }
    }

    /**
     * Cloudflare cache purge
     */
    private function purge_cloudflare($paths)
    {
        if (empty($this->api_key) || empty($this->zone_id)) {
            return ['success' => false, 'error' => 'Cloudflare credentials not configured'];
        }

        $url = "https://api.cloudflare.com/client/v4/zones/{$this->zone_id}/purge_cache";
        
        $data = [
            'files' => array_map(function($path) {
                return rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
            }, $paths)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            return ['success' => true, 'message' => 'Cache purged successfully'];
        }

        return ['success' => false, 'error' => 'Failed to purge cache', 'response' => $response];
    }

    /**
     * CloudFront cache invalidation
     */
    private function purge_cloudfront($paths)
    {
        // AWS CloudFront invalidation için AWS SDK gerekli
        // Bu basit bir implementasyon, production'da AWS SDK kullanılmalı
        return ['success' => false, 'error' => 'CloudFront invalidation requires AWS SDK'];
    }

    /**
     * CDN durumunu kontrol et
     */
    public function is_enabled()
    {
        return $this->enabled;
    }

    /**
     * CDN provider bilgisi
     */
    public function get_provider()
    {
        return $this->provider;
    }
}

