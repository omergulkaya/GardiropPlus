<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Query Profiler Library
 * Database query profiling ve slow query detection
 */
class Query_profiler_library
{
    private $ci;
    private $slow_query_threshold = 1.0;
// 1 saniye
    private $log_slow_queries = true;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * Query profiler'ı başlat
     */
    public function enable()
    {
        $this->ci->output->enable_profiler(true);
        $this->ci->db->save_queries = true;
    }

    /**
     * Query profiler'ı durdur
     */
    public function disable()
    {
        $this->ci->output->enable_profiler(false);
    }

    /**
     * Slow query'leri kontrol et ve logla
     */
    public function check_slow_queries()
    {
        if (!$this->log_slow_queries) {
            return;
        }

        $queries = $this->ci->db->queries;
        $query_times = $this->ci->db->query_times;
        foreach ($queries as $index => $query) {
            $execution_time = isset($query_times[$index]) ? $query_times[$index] : 0;
            if ($execution_time > $this->slow_query_threshold) {
                $this->log_slow_query($query, $execution_time);
            }
        }
    }

    /**
     * Slow query'yi logla
     */
    private function log_slow_query($query, $execution_time)
    {
        $log_message = sprintf("Slow Query (%.3fs): %s", $execution_time, $query);
        log_message('warning', $log_message);
    }

    /**
     * Query plan analizi (EXPLAIN)
     */
    public function explain_query($query, $bindings = [])
    {
        $explain_query = "EXPLAIN " . $query;
        if (!empty($bindings)) {
            $this->ci->db->query($explain_query, $bindings);
        } else {
            $this->ci->db->query($explain_query);
        }

        return $this->ci->db->result_array();
    }

    /**
     * Query istatistikleri
     */
    public function get_stats()
    {
        $queries = $this->ci->db->queries;
        $query_times = $this->ci->db->query_times;
        $total_queries = count($queries);
        $total_time = array_sum($query_times);
        $avg_time = $total_queries > 0 ? $total_time / $total_queries : 0;
        $max_time = !empty($query_times) ? max($query_times) : 0;
        $min_time = !empty($query_times) ? min($query_times) : 0;
        return [
            'total_queries' => $total_queries,
            'total_time' => round($total_time, 4),
            'avg_time' => round($avg_time, 4),
            'max_time' => round($max_time, 4),
            'min_time' => round($min_time, 4)
        ];
    }
}
