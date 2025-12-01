<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Logger Library
 * Structured logging, log levels, log rotation için
 */
class Logger_library
{
    private $log_path;
    private $log_file;
    private $log_level;
    private $log_levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];
    private $max_file_size = 10485760; // 10MB
    private $max_files = 5;

    public function __construct()
    {
        $this->log_path = APPPATH . 'logs/';
        $this->log_file = $this->log_path . 'app-' . date('Y-m-d') . '.log';
        
        // Log level environment variable'dan al
        $this->log_level = strtoupper(getenv('LOG_LEVEL') ?: 'INFO');
        
        // Log dizini yoksa oluştur
        if (!is_dir($this->log_path)) {
            mkdir($this->log_path, 0755, true);
        }
    }

    /**
     * Log yaz
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, $context = [])
    {
        $level = strtoupper($level);
        
        // Log level kontrolü
        if (!isset($this->log_levels[$level]) || 
            $this->log_levels[$level] < $this->log_levels[$this->log_level]) {
            return;
        }

        // Log rotation kontrolü
        $this->rotate_if_needed();

        // Structured log format
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'environment' => ENVIRONMENT,
            'ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'CLI'
        ];

        // JSON formatında yaz
        $log_line = json_encode($log_entry) . PHP_EOL;
        
        file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
    }

    /**
     * DEBUG log
     */
    public function debug($message, $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * INFO log
     */
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * WARNING log
     */
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * ERROR log
     */
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Exception log
     */
    public function exception($exception, $context = [])
    {
        $this->error($exception->getMessage(), array_merge($context, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]));
    }

    /**
     * Log rotation
     */
    private function rotate_if_needed()
    {
        if (!file_exists($this->log_file)) {
            return;
        }

        if (filesize($this->log_file) >= $this->max_file_size) {
            // Eski log dosyalarını temizle
            $this->clean_old_logs();
            
            // Yeni log dosyası oluştur
            $this->log_file = $this->log_path . 'app-' . date('Y-m-d') . '.log';
        }
    }

    /**
     * Eski log dosyalarını temizle
     */
    private function clean_old_logs()
    {
        $files = glob($this->log_path . 'app-*.log');
        
        if (count($files) > $this->max_files) {
            // Tarihe göre sırala
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // En eski dosyaları sil
            $files_to_delete = array_slice($files, 0, count($files) - $this->max_files);
            foreach ($files_to_delete as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Client IP al
     */
    private function get_client_ip()
    {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return 'unknown';
    }

    /**
     * Performance log
     */
    public function performance($operation, $duration, $context = [])
    {
        $this->info("Performance: {$operation}", array_merge($context, [
            'duration_ms' => round($duration * 1000, 2),
            'operation' => $operation
        ]));
    }

    /**
     * API usage analytics
     */
    public function api_usage($endpoint, $method, $status_code, $duration, $user_id = null)
    {
        $this->info("API Usage: {$method} {$endpoint}", [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $status_code,
            'duration_ms' => round($duration * 1000, 2),
            'user_id' => $user_id
        ]);
    }
}

