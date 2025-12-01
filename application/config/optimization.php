<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Performance Optimization Configuration
|--------------------------------------------------------------------------
*/

// Env library yükle (.env dosyasından okumak için)
if (!function_exists('get_instance')) {
    // Config dosyası doğrudan yükleniyorsa, env library'yi yükleyemeyiz
    // Bu durumda default değerler kullanılacak
    $env = null;
} else {
    $CI =& get_instance();
    if (!isset($CI->env_library)) {
        $CI->load->library('env_library');
    }
    $env = $CI->env_library;
}

// Cache Configuration (.env'den oku veya default)
$config['cache_enabled'] = true;
$config['cache_driver'] = $env ? $env->get('CACHE_DRIVER', 'file') : 'file'; // file, redis, memcached
$config['cache_prefix'] = $env ? $env->get('CACHE_PREFIX', 'closet_') : 'closet_';
$config['cache_default_ttl'] = $env ? (int)$env->get('CACHE_DEFAULT_TTL', 3600) : 3600; // 1 saat (saniye)

// Response Caching
$config['response_cache_enabled'] = true;
$config['response_cache_ttl'] = 300; // 5 dakika (saniye)

// Redis Configuration (.env'den oku veya default)
$config['redis_host'] = $env ? $env->get('REDIS_HOST', '127.0.0.1') : '127.0.0.1';
$config['redis_port'] = $env ? (int)$env->get('REDIS_PORT', 6379) : 6379;
$config['redis_password'] = $env ? $env->get('REDIS_PASSWORD', '') : '';

// Memcached Configuration (.env'den oku veya default)
$config['memcached_host'] = $env ? $env->get('MEMCACHED_HOST', '127.0.0.1') : '127.0.0.1';
$config['memcached_port'] = $env ? (int)$env->get('MEMCACHED_PORT', 11211) : 11211;

// Query Optimization
$config['enable_query_profiling'] = (ENVIRONMENT === 'development');
$config['enable_slow_query_log'] = true;
$config['slow_query_threshold'] = 1.0; // saniye

// OPcache Configuration
$config['opcache_enabled'] = true;
$config['opcache_clear_on_deploy'] = false; // Production'da false olmalı

// Autoloader Optimization
$config['optimize_autoloader'] = (ENVIRONMENT === 'production');

// Response Compression
$config['enable_response_compression'] = true;
$config['compression_level'] = 4; // 1-9 (brotli için)

// Database Connection Pooling
$config['db_persistent'] = false; // CodeIgniter'da persistent connection
$config['db_cache_on'] = false; // Query result caching

// Query Cache Configuration
$config['query_cache_enabled'] = $env ? ($env->get('QUERY_CACHE_ENABLED', 'true') === 'true') : true;
$config['query_cache_ttl'] = $env ? (int)$env->get('QUERY_CACHE_TTL', 3600) : 3600;

// API Optimization
$config['enable_field_selection'] = true; // ?fields=name,email
$config['enable_cursor_pagination'] = true; // Cursor-based pagination
$config['max_items_per_page'] = 100; // Maximum items per page
$config['default_items_per_page'] = 20;

// Eager Loading
$config['enable_eager_loading'] = true;
$config['max_eager_load_depth'] = 2; // Maximum relation depth

