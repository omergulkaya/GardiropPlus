<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custom Exception Handler
 * Standardized error responses ve error logging için
 */
class MY_Exceptions extends CI_Exceptions
{
    /**
     * Logger instance
     */
    private $logger = null;

    /**
     * Error code mapping
     */
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

    /**
     * User-friendly error messages
     */
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
        parent::__construct();
        // Logger library yükle - CodeIgniter instance'ı hazır olduğunda
        // Logger'ı lazy load yapacağız çünkü constructor'da CI instance henüz hazır olmayabilir
    }

    /**
     * Logger instance'ı al (lazy load)
     */
    private function get_logger()
    {
        if ($this->logger === null) {
            // CodeIgniter instance'ı kontrol et
            // CI_Controller sınıfının yüklü olduğundan emin ol
            // get_instance() fonksiyonunun tanımlı olduğundan emin ol
            if (class_exists('CI_Controller', false)) {
                // get_instance() fonksiyonunun tanımlı olup olmadığını kontrol et
                if (function_exists('get_instance')) {
                    try {
                        // get_instance() çağrısını güvenli şekilde yap
                        // CI_Controller::get_instance() static metodunu direkt çağır
                        // get_instance() fonksiyonu CI_Controller yüklenmeden önce çağrılırsa hata verir
                        if (method_exists('CI_Controller', 'get_instance')) {
                            $ci_ref = CI_Controller::get_instance();
                            if (isset($ci_ref) && is_object($ci_ref) && method_exists($ci_ref, 'load')) {
                                $ci_ref->load->library('logger_library');
                                if (isset($ci_ref->logger_library)) {
                                    $this->logger = $ci_ref->logger_library;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // CodeIgniter henüz hazır değilse logger'ı null bırak
                        $this->logger = false; // false = denendi ama yüklenemedi
                    } catch (Error $e) {
                        // Fatal error durumunda (CI_Controller bulunamadı gibi)
                        $this->logger = false;
                    } catch (Throwable $e) {
                        // Herhangi bir hata durumunda
                        $this->logger = false;
                    }
                } else {
                    // get_instance() henüz tanımlı değil
                    $this->logger = false;
                }
            } else {
                // CodeIgniter henüz yüklenmemiş
                $this->logger = false;
            }
        }
        // false ise null döndür (tekrar deneme yapılmasın)
        return ($this->logger === false) ? null : $this->logger;
    }

    /**
     * Log exception
     */
    public function log_exception($severity, $message, $filepath, $line)
    {
        // Logger ile log kaydet
        $logger = $this->get_logger();
        if ($logger) {
            try {
                $exception = new Exception($message, $severity);
                $logger->exception($exception, [
                    'file' => $filepath,
                    'line' => $line,
                    'severity' => $severity
                ]);
            } catch (Exception $e) {
                // Logger hatası varsa parent'a devret
            }
        }

        return parent::log_exception($severity, $message, $filepath, $line);
    }

    /**
     * Show 404 Page
     */
    public function show_404($page = '', $log_error = true)
    {
        // API request ise JSON döndür
        if ($this->is_api_request()) {
            $this->send_error_response(404, 'NOT_FOUND', 'Resource not found');
            return;
        }

        return parent::show_404($page, $log_error);
    }

    /**
     * Show PHP Error
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        // API request ise JSON döndür
        if ($this->is_api_request()) {
            $error_code = $this->get_error_code(500);
            $this->send_error_response(500, $error_code, $this->get_user_message($error_code, $message));
            return;
        }

        return parent::show_php_error($severity, $message, $filepath, $line);
    }

    /**
     * Show Error
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        // API request ise JSON döndür
        if ($this->is_api_request()) {
            $error_code = $this->get_error_code($status_code);
            $this->send_error_response($status_code, $error_code, $this->get_user_message($error_code, is_array($message) ? implode(', ', $message) : $message));
            return;
        }

        return parent::show_error($heading, $message, $template, $status_code);
    }

    /**
     * API request kontrolü
     */
    private function is_api_request()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        return strpos($uri, '/api/') !== false || 
               strpos($uri, '/index.php/api/') !== false;
    }

    /**
     * Error response gönder
     */
    private function send_error_response($status_code, $error_code, $message, $details = null)
    {
        http_response_code($status_code);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => [
                'code' => $error_code,
                'message' => $message,
                'status_code' => $status_code
            ]
        ];

        // Development ortamında detaylı hata bilgisi
        if (ENVIRONMENT === 'development' && $details) {
            $response['error']['details'] = $details;
        }

        // Production'da user-friendly message
        if (ENVIRONMENT === 'production') {
            $response['error']['message'] = $this->get_user_message($error_code, $message);
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
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
}

