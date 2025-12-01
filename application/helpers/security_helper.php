<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security Helper
 * Input sanitization ve güvenlik fonksiyonları
 */

if (!function_exists('sanitize_input')) {
/**
     * Input'u temizle (XSS koruması)
     *
     * @param mixed $data Input data
     * @return mixed Sanitized data
     */
    function sanitize_input($data)
    {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }

        // HTML tag'lerini temizle
        $data = strip_tags($data);
// Özel karakterleri escape et
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

}

if (!function_exists('sanitize_sql')) {
/**
     * SQL injection koruması için input temizle
     *
     * @param string $input Input string
     * @return string Sanitized string
     */
    function sanitize_sql($input)
    {
        // CodeIgniter'in escape fonksiyonunu kullan
        $CI =& get_instance();
        $CI->load->database();
        return $CI->db->escape_str($input);
    }

}

if (!function_exists('check_sql_injection')) {
/**
     * SQL injection potansiyelini kontrol et
     *
     * @param string $input Input string
     * @return bool True if safe, false if potential injection
     */
    function check_sql_injection($input)
    {
        $dangerous_keywords = [
            'DROP', 'DELETE', 'INSERT', 'UPDATE', 'SELECT',
            'UNION', 'EXEC', 'EXECUTE', 'SCRIPT', '--', ';',
            '/*', '*/', 'OR 1=1', 'OR \'1\'=\'1\'', 'OR "1"="1"'
        ];
        $upper_input = strtoupper($input);
        foreach ($dangerous_keywords as $keyword) {
            if (strpos($upper_input, $keyword) !== false) {
                log_message('warning', 'Potential SQL injection: ' . $input);
                return false;
            }
        }

        return true;
    }

}

if (!function_exists('sanitize_filename')) {
/**
     * Dosya adını güvenli hale getir
     *
     * @param string $filename Dosya adı
     * @return string Sanitized filename
     */
    function sanitize_filename($filename)
    {
        // Tehlikeli karakterleri kaldır
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
// Path traversal koruması
        $filename = basename($filename);
        return $filename;
    }

}

if (!function_exists('validate_email')) {
/**
     * Email validation
     *
     * @param string $email Email adresi
     * @return bool
     */
    function validate_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

}

if (!function_exists('validate_url')) {
/**
     * URL validation
     *
     * @param string $url URL
     * @return bool
     */
    function validate_url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

}

if (!function_exists('escape_xss')) {
/**
     * XSS koruması için escape
     *
     * @param string $data Input data
     * @return string Escaped data
     */
    function escape_xss($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

}
