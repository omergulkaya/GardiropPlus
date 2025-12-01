<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Optimization Helper
 * Performans optimizasyonu için yardımcı fonksiyonlar
 */

/**
 * Composer autoloader optimizasyonu
 */
if (!function_exists('optimize_autoloader')) {
    function optimize_autoloader()
    {
        $composer_path = FCPATH . 'composer.json';
        if (file_exists($composer_path)) {
            $command = 'composer dump-autoload -o --no-dev';
            exec($command, $output, $return_var);
            return $return_var === 0;
        }
        return false;
    }
}

/**
 * OPcache temizle (development için)
 */
if (!function_exists('clear_opcache')) {
    function clear_opcache()
    {
        if (function_exists('opcache_reset')) {
            return opcache_reset();
        }
        return false;
    }
}

/**
 * Query profiling başlat
 */
if (!function_exists('start_query_profiling')) {
    function start_query_profiling()
    {
        $CI =& get_instance();
        if (isset($CI->db)) {
            $CI->db->save_queries = true;
            $CI->db->query_times = [];
        }
    }
}

/**
 * Query profiling sonuçlarını al
 */
if (!function_exists('get_query_profile')) {
    function get_query_profile()
    {
        $CI =& get_instance();
        if (isset($CI->db)) {
            return [
                'queries' => $CI->db->queries ?? [],
                'query_times' => $CI->db->query_times ?? [],
                'total_time' => array_sum($CI->db->query_times ?? [])
            ];
        }
        return null;
    }
}

/**
 * Eager loading için ilişkili verileri yükle
 */
if (!function_exists('eager_load')) {
    function eager_load($model, $items, $relation, $foreign_key = null, $local_key = 'id')
    {
        if (empty($items)) {
            return $items;
        }
        
        $CI =& get_instance();
        $CI->load->model($model);
        
        $ids = array_column($items, $local_key);
        $related = $CI->{$model}->get_by_ids($ids, $foreign_key);
        
        // İlişkilendir
        $related_map = [];
        foreach ($related as $rel) {
            $key = $foreign_key ? $rel[$foreign_key] : $rel['id'];
            $related_map[$key] = $rel;
        }
        
        foreach ($items as &$item) {
            $key = $item[$local_key];
            $item[$relation] = $related_map[$key] ?? null;
        }
        
        return $items;
    }
}

/**
 * Cache key oluştur
 */
if (!function_exists('cache_key')) {
    function cache_key($prefix, $params = [])
    {
        $key = $prefix;
        if (!empty($params)) {
            $key .= '_' . md5(json_encode($params));
        }
        return $key;
    }
}

/**
 * Response time ölç
 */
if (!function_exists('measure_response_time')) {
    function measure_response_time($callback)
    {
        $start = microtime(true);
        $result = call_user_func($callback);
        $end = microtime(true);
        
        return [
            'result' => $result,
            'time' => ($end - $start) * 1000 // milliseconds
        ];
    }
}

