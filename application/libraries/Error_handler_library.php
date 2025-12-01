<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Error Handler Library
 * Standardized error responses ve error code mapping için
 */
class Error_handler_library
{
    private $error_codes = [
        // 4xx Client Errors
        400 => 'BAD_REQUEST',
        401 => 'UNAUTHORIZED',
        403 => 'FORBIDDEN',
        404 => 'NOT_FOUND',
        405 => 'METHOD_NOT_ALLOWED',
        409 => 'CONFLICT',
        422 => 'UNPROCESSABLE_ENTITY',
        423 => 'LOCKED',
        429 => 'TOO_MANY_REQUESTS',
        
        // 5xx Server Errors
        500 => 'INTERNAL_SERVER_ERROR',
        503 => 'SERVICE_UNAVAILABLE',
        504 => 'GATEWAY_TIMEOUT'
    ];

    private $user_messages = [
        'BAD_REQUEST' => 'Geçersiz istek',
        'UNAUTHORIZED' => 'Yetkisiz erişim',
        'FORBIDDEN' => 'Erişim reddedildi',
        'NOT_FOUND' => 'Kayıt bulunamadı',
        'METHOD_NOT_ALLOWED' => 'İzin verilmeyen metod',
        'CONFLICT' => 'Çakışma hatası',
        'UNPROCESSABLE_ENTITY' => 'İşlenemeyen veri',
        'LOCKED' => 'Hesap kilitli',
        'TOO_MANY_REQUESTS' => 'Çok fazla istek',
        'INTERNAL_SERVER_ERROR' => 'Sunucu hatası',
        'SERVICE_UNAVAILABLE' => 'Servis kullanılamıyor',
        'GATEWAY_TIMEOUT' => 'Zaman aşımı'
    ];

    public function __construct()
    {
        // Logger yükle
        if (class_exists('Logger_library')) {
            $this->logger = new Logger_library();
        }
    }

    /**
     * Standardized error response oluştur
     * 
     * @param int $status_code
     * @param string $message
     * @param array $errors
     * @param array $details
     * @return array
     */
    public function create_error_response($status_code, $message, $errors = null, $details = null)
    {
        $error_code = $this->get_error_code($status_code);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $error_code,
                'message' => $this->get_user_message($error_code, $message),
                'status_code' => $status_code
            ]
        ];

        // Validation errors
        if ($errors !== null) {
            $response['error']['errors'] = $errors;
        }

        // Development ortamında detaylı bilgi
        if (ENVIRONMENT === 'development' && $details) {
            $response['error']['details'] = $details;
        }

        // Development ortamında original message
        if (ENVIRONMENT === 'development') {
            $response['error']['original_message'] = $message;
        }

        // Error logging
        $this->log_error($status_code, $error_code, $message, $errors, $details);

        return $response;
    }

    /**
     * Exception'dan error response oluştur
     */
    public function create_exception_response($exception)
    {
        $status_code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
        $message = $exception->getMessage();
        
        $details = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => ENVIRONMENT === 'development' ? $exception->getTraceAsString() : null
        ];

        return $this->create_error_response($status_code, $message, null, $details);
    }

    /**
     * Error code al
     */
    private function get_error_code($status_code)
    {
        return $this->error_codes[$status_code] ?? 'INTERNAL_SERVER_ERROR';
    }

    /**
     * User-friendly message al
     */
    private function get_user_message($error_code, $default_message = null)
    {
        if (ENVIRONMENT === 'development' && $default_message) {
            return $default_message;
        }

        return $this->user_messages[$error_code] ?? 'Bir hata oluştu';
    }

    /**
     * Error log
     */
    private function log_error($status_code, $error_code, $message, $errors = null, $details = null)
    {
        // File logger (mevcut)
        if (isset($this->logger)) {
            $context = [
                'error_code' => $error_code,
                'status_code' => $status_code,
                'errors' => $errors,
                'details' => $details
            ];

            if ($status_code >= 500) {
                $this->logger->error($message, $context);
            } else {
                $this->logger->warning($message, $context);
            }
        }

        // Database logger (yeni - API hatalarını veritabanına kaydet)
        $this->log_error_to_database($status_code, $error_code, $message, $errors, $details);
    }

    /**
     * Error'ı veritabanına kaydet
     */
    private function log_error_to_database($status_code, $error_code, $message, $errors = null, $details = null)
    {
        try {
            // CodeIgniter instance kontrolü
            if (!function_exists('get_instance')) {
                return;
            }

            $CI =& get_instance();
            
            // Database bağlantısı kontrolü
            if (!isset($CI->db) || !$CI->db->conn_id) {
                return;
            }

            // Api_error_model yükle
            $CI->load->model('Api_error_model');

            // Request bilgileri
            $endpoint = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
            
            // User ID (eğer varsa)
            $user_id = null;
            if ($CI->session && $CI->session->userdata('user_id')) {
                $user_id = $CI->session->userdata('user_id');
            } elseif (isset($CI->jwt) && method_exists($CI->jwt, 'get_user_id')) {
                // JWT'den user ID al
                try {
                    $user_id = $CI->jwt->get_user_id();
                } catch (Exception $e) {
                    // JWT yoksa devam et
                }
            }

            // Severity belirleme
            $severity = 'medium';
            if ($status_code >= 500) {
                $severity = 'critical';
            } elseif ($status_code >= 400 && $status_code < 500) {
                $severity = 'high';
            }

            // Request body (sadece POST/PUT/PATCH)
            $request_body = null;
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $input = file_get_contents('php://input');
                if ($input) {
                    $request_body = json_decode($input, true);
                    if ($request_body) {
                        $request_body = json_encode($request_body, JSON_UNESCAPED_UNICODE);
                    } else {
                        $request_body = $input;
                    }
                }
            }

            // Request headers
            $request_headers = null;
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if ($headers) {
                    // Hassas bilgileri temizle
                    $safe_headers = [];
                    foreach ($headers as $key => $value) {
                        if (strtolower($key) === 'authorization') {
                            $safe_headers[$key] = substr($value, 0, 20) . '...';
                        } else {
                            $safe_headers[$key] = $value;
                        }
                    }
                    $request_headers = json_encode($safe_headers, JSON_UNESCAPED_UNICODE);
                }
            }

            // Stack trace
            $stack_trace = null;
            if ($details && isset($details['trace'])) {
                $stack_trace = $details['trace'];
            } elseif (ENVIRONMENT === 'development') {
                $stack_trace = (new Exception())->getTraceAsString();
            }

            // Error data
            $error_data = [
                'error_code' => $error_code,
                'status_code' => $status_code,
                'message' => substr($message, 0, 1000), // Mesaj uzunluğu sınırı
                'endpoint' => $endpoint,
                'method' => $method,
                'user_id' => $user_id,
                'ip_address' => $CI->input->ip_address(),
                'user_agent' => $CI->input->user_agent(),
                'request_body' => $request_body ? substr($request_body, 0, 5000) : null, // Body uzunluğu sınırı
                'request_headers' => $request_headers,
                'stack_trace' => $stack_trace ? substr($stack_trace, 0, 10000) : null, // Stack trace uzunluğu sınırı
                'severity' => $severity,
                'status' => 'new'
            ];

            // Exception bilgileri
            if ($details) {
                if (isset($details['exception'])) {
                    $error_data['exception_type'] = $details['exception'];
                }
                if (isset($details['file'])) {
                    $error_data['file'] = $details['file'];
                }
                if (isset($details['line'])) {
                    $error_data['line'] = $details['line'];
                }
            }

            // Veritabanına kaydet
            $CI->Api_error_model->create($error_data);

        } catch (Exception $e) {
            // Veritabanı loglama hatası olursa sessizce devam et
            // File logger zaten çalışıyor
            if (ENVIRONMENT === 'development') {
                log_message('error', 'Error database logging failed: ' . $e->getMessage());
            }
        }
    }
}

