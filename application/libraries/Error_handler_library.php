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
    }
}

