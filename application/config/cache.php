<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Cache Configuration
|--------------------------------------------------------------------------
|
| Cache ayarları
|
*/

// Cache enabled
$config['cache_enabled'] = true;
// Cache adapter (file, redis, memcached)
$config['cache_adapter'] = 'file';
// Cache path (file adapter için)
$config['cache_path'] = APPPATH . 'cache/';
// Default TTL (Time To Live) - saniye
$config['cache_default_ttl'] = 3600;
// 1 saat

// Cache prefix
$config['cache_prefix'] = 'closet_';
// Response cache TTL
$config['response_cache_ttl'] = 300;
// 5 dakika

// Query cache TTL
$config['query_cache_ttl'] = 600;
// 10 dakika

// Redis configuration (eğer redis kullanılıyorsa)
$config['redis_host'] = '127.0.0.1';
$config['redis_port'] = 6379;
$config['redis_password'] = '';
$config['redis_database'] = 0;
// Memcached configuration (eğer memcached kullanılıyorsa)
$config['memcached_servers'] = [
    ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 1]
];
