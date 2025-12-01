<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Health Check Controller
 * API sağlık durumu kontrolü için endpoint'ler
 */
class Health extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Health check endpoint'leri authentication gerektirmez
        $this->output->set_content_type('application/json');
    }

    /**
     * Health check endpoint
     * GET /api/health
     */
    public function index()
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->get_version(),
            'environment' => ENVIRONMENT
        ];

        $this->output->set_status_header(200);
        $this->output->set_output(json_encode([
            'success' => true,
            'message' => 'API is healthy',
            'data' => $health
        ]));
    }

    /**
     * Database connectivity check
     * GET /api/health/database
     */
    public function database()
    {
        $this->load->database();
        
        $start_time = microtime(true);
        try {
            $this->db->query('SELECT 1');
            $duration = microtime(true) - $start_time;
            
            $this->output->set_status_header(200);
            $this->output->set_output(json_encode([
                'success' => true,
                'message' => 'Database connection successful',
                'data' => [
                    'status' => 'ok',
                    'duration_ms' => round($duration * 1000, 2),
                    'database' => $this->db->database
                ]
            ]));
        } catch (Exception $e) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Cache connectivity check
     * GET /api/health/cache
     */
    public function cache()
    {
        $this->load->library('cache_library');
        
        $start_time = microtime(true);
        try {
            $test_key = 'health_check_' . time();
            $test_value = 'test';
            
            // Write test
            $this->cache_library->set($test_key, $test_value, 60);
            
            // Read test
            $cached = $this->cache_library->get($test_key);
            
            // Delete test
            $this->cache_library->delete($test_key);
            
            $duration = microtime(true) - $start_time;
            
            if ($cached === $test_value) {
                $this->output->set_status_header(200);
                $this->output->set_output(json_encode([
                    'success' => true,
                    'message' => 'Cache is working',
                    'data' => [
                        'status' => 'ok',
                        'duration_ms' => round($duration * 1000, 2)
                    ]
                ]));
            } else {
                $this->output->set_status_header(503);
                $this->output->set_output(json_encode([
                    'success' => false,
                    'message' => 'Cache read/write test failed'
                ]));
            }
        } catch (Exception $e) {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode([
                'success' => false,
                'message' => 'Cache connection failed: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Disk space check
     * GET /api/health/disk
     */
    public function disk()
    {
        $path = FCPATH;
        $free_space = disk_free_space($path);
        $total_space = disk_total_space($path);
        $used_space = $total_space - $free_space;
        $usage_percent = ($used_space / $total_space) * 100;

        $status = 'ok';
        $warning_threshold = 80;
        $critical_threshold = 90;

        if ($usage_percent >= $critical_threshold) {
            $status = 'critical';
        } elseif ($usage_percent >= $warning_threshold) {
            $status = 'warning';
        }

        $this->output->set_status_header(200);
        $this->output->set_output(json_encode([
            'success' => true,
            'message' => 'Disk space check completed',
            'data' => [
                'status' => $status,
                'free_space' => $this->format_bytes($free_space),
                'total_space' => $this->format_bytes($total_space),
                'used_space' => $this->format_bytes($used_space),
                'usage_percent' => round($usage_percent, 2),
                'path' => $path
            ]
        ]));
    }

    /**
     * Version endpoint
     * GET /api/health/version
     */
    public function version()
    {
        $this->output->set_status_header(200);
        $this->output->set_output(json_encode([
            'success' => true,
            'message' => 'Version information',
            'data' => [
                'version' => $this->get_version(),
                'environment' => ENVIRONMENT,
                'php_version' => PHP_VERSION,
                'codeigniter_version' => CI_VERSION,
                'server_time' => date('Y-m-d H:i:s')
            ]
        ]));
    }

    /**
     * Comprehensive health check
     * GET /api/health/check
     */
    public function check()
    {
        $checks = [
            'database' => $this->check_database(),
            'cache' => $this->check_cache(),
            'disk' => $this->check_disk()
        ];

        $all_ok = true;
        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $all_ok = false;
                break;
            }
        }

        $status_code = $all_ok ? 200 : 503;
        
        $this->output->set_status_header($status_code);
        $this->output->set_output(json_encode([
            'success' => $all_ok,
            'status' => $all_ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    /**
     * Database check helper
     */
    private function check_database()
    {
        try {
            $this->load->database();
            $this->db->query('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Cache check helper
     */
    private function check_cache()
    {
        try {
            $this->load->library('cache_library');
            $test_key = 'health_check_' . time();
            $this->cache_library->set($test_key, 'test', 60);
            $cached = $this->cache_library->get($test_key);
            $this->cache_library->delete($test_key);
            
            if ($cached === 'test') {
                return ['status' => 'ok', 'message' => 'Cache is working'];
            }
            return ['status' => 'error', 'message' => 'Cache read/write test failed'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Disk check helper
     */
    private function check_disk()
    {
        $path = FCPATH;
        $free_space = disk_free_space($path);
        $total_space = disk_total_space($path);
        $usage_percent = (($total_space - $free_space) / $total_space) * 100;

        if ($usage_percent >= 90) {
            return ['status' => 'critical', 'message' => 'Disk usage is critical: ' . round($usage_percent, 2) . '%'];
        } elseif ($usage_percent >= 80) {
            return ['status' => 'warning', 'message' => 'Disk usage is high: ' . round($usage_percent, 2) . '%'];
        }
        
        return ['status' => 'ok', 'message' => 'Disk space is sufficient'];
    }

    /**
     * Version bilgisi al
     */
    private function get_version()
    {
        // Version dosyasından oku
        $version_file = FCPATH . 'VERSION';
        if (file_exists($version_file)) {
            return trim(file_get_contents($version_file));
        }
        
        // Veya config'den
        $this->load->config('app');
        return $this->config->item('version', 'app') ?: '1.0.0';
    }

    /**
     * Bytes'ı human readable format'a çevir
     */
    private function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
