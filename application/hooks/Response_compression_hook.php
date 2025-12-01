<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Response Compression Hook
 * API response'larını gzip/brotli ile sıkıştır
 */
class Response_compression_hook
{
    public function compress_output()
    {
        $CI =& get_instance();
        
        // Sadece API endpoint'leri için
        if (strpos($CI->uri->uri_string(), 'api/') === false) {
            return;
        }
        
        // Zaten sıkıştırılmışsa atla
        if (ob_get_level() > 0 && ob_get_length() > 0) {
            return;
        }
        
        // Accept-Encoding header'ını kontrol et
        $accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        
        // Brotli desteği varsa (PHP 7.0+)
        if (function_exists('brotli_compress') && strpos($accept_encoding, 'br') !== false) {
            ob_start(function($buffer) {
                return brotli_compress($buffer, 4); // Compression level 4
            });
            header('Content-Encoding: br');
            header('Vary: Accept-Encoding');
            return;
        }
        
        // Gzip desteği
        if (extension_loaded('zlib') && strpos($accept_encoding, 'gzip') !== false) {
            ob_start('ob_gzhandler');
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            return;
        }
        
        // Sıkıştırma yoksa normal output buffer
        ob_start();
    }
}

