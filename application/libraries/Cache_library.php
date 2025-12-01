<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cache Library
 * Redis/Memcached ve file-based caching desteği
 */
class Cache_library
{
    private $driver = 'file'; // file, redis, memcached
    private $prefix = 'closet_';
    private $default_ttl = 3600; // 1 saat
    
    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->library('env_library');
        $env = $CI->env_library;
        
        // Cache driver'ı .env'den veya config'den al
        $this->driver = $env->get('CACHE_DRIVER') ?: $CI->config->item('cache_driver') ?: 'file';
        $this->prefix = $env->get('CACHE_PREFIX') ?: $CI->config->item('cache_prefix') ?: 'closet_';
        $this->default_ttl = (int)($env->get('CACHE_DEFAULT_TTL') ?: $CI->config->item('cache_default_ttl') ?: 3600);
    }

    /**
     * Cache'den veri al
     */
    public function get($key)
    {
        $key = $this->prefix . $key;
        
        switch ($this->driver) {
            case 'redis':
                return $this->get_redis($key);
            case 'memcached':
                return $this->get_memcached($key);
            case 'file':
            default:
                return $this->get_file($key);
        }
    }

    /**
     * Cache'e veri kaydet
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->prefix . $key;
        $ttl = $ttl ?: $this->default_ttl;
        
        switch ($this->driver) {
            case 'redis':
                return $this->set_redis($key, $value, $ttl);
            case 'memcached':
                return $this->set_memcached($key, $value, $ttl);
            case 'file':
            default:
                return $this->set_file($key, $value, $ttl);
        }
    }

    /**
     * Cache'den veri sil
     */
    public function delete($key)
    {
        $key = $this->prefix . $key;
        
        switch ($this->driver) {
            case 'redis':
                return $this->delete_redis($key);
            case 'memcached':
                return $this->delete_memcached($key);
            case 'file':
            default:
                return $this->delete_file($key);
        }
    }

    /**
     * Cache'i temizle (tüm prefix'li key'ler)
     */
    public function clear($pattern = null)
    {
        if ($pattern) {
            $pattern = $this->prefix . $pattern;
        }
        
        switch ($this->driver) {
            case 'redis':
                return $this->clear_redis($pattern);
            case 'memcached':
                return $this->clear_memcached($pattern);
            case 'file':
            default:
                return $this->clear_file($pattern);
        }
    }

    /**
     * Cache'de var mı kontrol et
     */
    public function exists($key)
    {
        return $this->get($key) !== false;
    }

    /**
     * Response cache key oluştur
     */
    public function get_response_key($uri, $params = [])
    {
        $key = 'response_' . md5($uri . json_encode($params));
        return $key;
    }

    /**
     * File-based cache
     */
    private function get_file($key)
    {
        $file = $this->get_cache_file($key);
        if (!file_exists($file)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return false;
        }
        
        return $data['value'];
    }

    private function set_file($key, $value, $ttl)
    {
        $file = $this->get_cache_file($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }

    private function delete_file($key)
    {
        $file = $this->get_cache_file($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    private function clear_file($pattern = null)
    {
        $cache_dir = APPPATH . 'cache/data/';
        if (!is_dir($cache_dir)) {
            return true;
        }
        
        $files = glob($cache_dir . ($pattern ?: $this->prefix) . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    private function get_cache_file($key)
    {
        $hash = md5($key);
        $dir = APPPATH . 'cache/data/' . substr($hash, 0, 2) . '/';
        return $dir . $hash . '.cache';
    }

    /**
     * Redis cache
     */
    private function get_redis($key)
    {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            $value = $redis->get($key);
            return $value !== false ? unserialize($value) : false;
        } catch (Exception $e) {
            log_message('error', 'Redis get error: ' . $e->getMessage());
            return false;
        }
    }

    private function set_redis($key, $value, $ttl)
    {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            return $redis->setex($key, $ttl, serialize($value));
        } catch (Exception $e) {
            log_message('error', 'Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    private function delete_redis($key)
    {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            return $redis->del($key) > 0;
        } catch (Exception $e) {
            log_message('error', 'Redis delete error: ' . $e->getMessage());
            return false;
        }
    }

    private function clear_redis($pattern = null)
    {
        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }
            $pattern = $pattern ?: $this->prefix . '*';
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                return $redis->del($keys) > 0;
            }
            return true;
        } catch (Exception $e) {
            log_message('error', 'Redis clear error: ' . $e->getMessage());
            return false;
        }
    }

    private function get_redis_connection()
    {
        static $redis = null;
        
        if ($redis !== null) {
            return $redis;
        }
        
        if (!extension_loaded('redis')) {
            return false;
        }
        
        $CI =& get_instance();
        $CI->load->library('env_library');
        $env = $CI->env_library;
        
        $host = $env->get('REDIS_HOST') ?: $CI->config->item('redis_host') ?: '127.0.0.1';
        $port = (int)($env->get('REDIS_PORT') ?: $CI->config->item('redis_port') ?: 6379);
        $password = $env->get('REDIS_PASSWORD') ?: $CI->config->item('redis_password') ?: null;
        
        try {
            $redis = new Redis();
            $redis->connect($host, $port);
            if ($password) {
                $redis->auth($password);
            }
            return $redis;
        } catch (Exception $e) {
            log_message('error', 'Redis connection error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Memcached cache
     */
    private function get_memcached($key)
    {
        try {
            $memcached = $this->get_memcached_connection();
            if (!$memcached) {
                return false;
            }
            $value = $memcached->get($key);
            return $value !== false ? $value : false;
        } catch (Exception $e) {
            log_message('error', 'Memcached get error: ' . $e->getMessage());
            return false;
        }
    }

    private function set_memcached($key, $value, $ttl)
    {
        try {
            $memcached = $this->get_memcached_connection();
            if (!$memcached) {
                return false;
            }
            return $memcached->set($key, $value, $ttl);
        } catch (Exception $e) {
            log_message('error', 'Memcached set error: ' . $e->getMessage());
            return false;
        }
    }

    private function delete_memcached($key)
    {
        try {
            $memcached = $this->get_memcached_connection();
            if (!$memcached) {
                return false;
            }
            return $memcached->delete($key);
        } catch (Exception $e) {
            log_message('error', 'Memcached delete error: ' . $e->getMessage());
            return false;
        }
    }

    private function clear_memcached($pattern = null)
    {
        try {
            $memcached = $this->get_memcached_connection();
            if (!$memcached) {
                return false;
            }
            return $memcached->flush();
        } catch (Exception $e) {
            log_message('error', 'Memcached clear error: ' . $e->getMessage());
            return false;
        }
    }

    private function get_memcached_connection()
    {
        static $memcached = null;
        
        if ($memcached !== null) {
            return $memcached;
        }
        
        if (!extension_loaded('memcached')) {
            return false;
        }
        
        $CI =& get_instance();
        $CI->load->library('env_library');
        $env = $CI->env_library;
        
        $host = $env->get('MEMCACHED_HOST') ?: $CI->config->item('memcached_host') ?: '127.0.0.1';
        $port = (int)($env->get('MEMCACHED_PORT') ?: $CI->config->item('memcached_port') ?: 11211);
        
        try {
            $memcached = new Memcached();
            $memcached->addServer($host, $port);
            return $memcached;
        } catch (Exception $e) {
            log_message('error', 'Memcached connection error: ' . $e->getMessage());
            return false;
        }
    }
}
