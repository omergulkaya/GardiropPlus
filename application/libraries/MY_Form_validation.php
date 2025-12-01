<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custom Form Validation Library
 * Custom validation rules ve JSON body validation için
 */
class MY_Form_validation extends CI_Form_validation
{
    public function __construct($rules = array())
    {
        parent::__construct($rules);
        $this->set_error_delimiters('', '');
    }

    /**
     * JSON body validation
     * JSON body'den veri al ve validate et
     */
    public function validate_json($rules)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->set_error('json', 'Invalid JSON format');
            return false;
        }

        $this->set_data($data);
        return $this->run($rules);
    }

    /**
     * Custom validation: UUID format
     */
    public function uuid($str)
    {
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $str);
    }

    /**
     * Custom validation: Date format (YYYY-MM-DD)
     */
    public function date_format($str)
    {
        if (empty($str)) {
            return true; // Optional field
        }
        $date = DateTime::createFromFormat('Y-m-d', $str);
        return $date && $date->format('Y-m-d') === $str;
    }

    /**
     * Custom validation: DateTime format (YYYY-MM-DD HH:MM:SS)
     */
    public function datetime_format($str)
    {
        if (empty($str)) {
            return true; // Optional field
        }
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $str);
        return $date && $date->format('Y-m-d H:i:s') === $str;
    }

    /**
     * Custom validation: JSON string
     */
    public function valid_json($str)
    {
        if (empty($str)) {
            return true; // Optional field
        }
        json_decode($str);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Custom validation: Array
     */
    public function is_array($str)
    {
        return is_array($str);
    }

    /**
     * Custom validation: File upload validation
     */
    public function valid_upload($field_name, $allowed_types = null, $max_size = null)
    {
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            $this->set_error($field_name, 'File upload failed');
            return false;
        }

        $file = $_FILES[$field_name];

        // File type validation
        if ($allowed_types) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = is_array($allowed_types) ? $allowed_types : explode('|', $allowed_types);
            if (!in_array($ext, $allowed)) {
                $this->set_error($field_name, 'Invalid file type. Allowed: ' . implode(', ', $allowed));
                return false;
            }
        }

        // File size validation
        if ($max_size && $file['size'] > $max_size) {
            $this->set_error($field_name, 'File size exceeds maximum allowed size');
            return false;
        }

        return true;
    }

    /**
     * Custom validation: Image file
     */
    public function valid_image($field_name)
    {
        if (!isset($_FILES[$field_name])) {
            return true; // Optional
        }

        $file = $_FILES[$field_name];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return true; // Error handling başka yerde
        }

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_types)) {
            $this->set_error($field_name, 'Invalid image type. Allowed: ' . implode(', ', $allowed_types));
            return false;
        }

        // MIME type kontrolü
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            $this->set_error($field_name, 'Invalid image MIME type');
            return false;
        }

        return true;
    }

    /**
     * Custom validation: Email domain
     */
    public function valid_email_domain($email, $allowed_domains = null)
    {
        if (empty($email)) {
            return true;
        }

        if (!$this->valid_email($email)) {
            return false;
        }

        if ($allowed_domains) {
            $domain = substr(strrchr($email, "@"), 1);
            $allowed = is_array($allowed_domains) ? $allowed_domains : explode('|', $allowed_domains);
            if (!in_array($domain, $allowed)) {
                $this->set_error('email', 'Email domain not allowed');
                return false;
            }
        }

        return true;
    }

    /**
     * Custom validation: Strong password
     */
    public function strong_password($password)
    {
        if (empty($password)) {
            return true; // Required kontrolü başka yerde
        }

        // Min 8 karakter, büyük harf, küçük harf, rakam
        if (strlen($password) < 8) {
            $this->set_error('password', 'Password must be at least 8 characters');
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $this->set_error('password', 'Password must contain at least one uppercase letter');
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $this->set_error('password', 'Password must contain at least one lowercase letter');
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $this->set_error('password', 'Password must contain at least one number');
            return false;
        }

        return true;
    }

    /**
     * Custom validation: Phone number (Turkish format)
     */
    public function valid_phone($phone)
    {
        if (empty($phone)) {
            return true; // Optional
        }

        // Turkish phone format: +90XXXXXXXXXX or 0XXXXXXXXXX
        return (bool)preg_match('/^(\+90|0)?[5][0-9]{9}$/', $phone);
    }

    /**
     * Custom validation: Color hex code
     */
    public function valid_hex_color($color)
    {
        if (empty($color)) {
            return true; // Optional
        }

        return (bool)preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color);
    }

    /**
     * Error messages iyileştir
     */
    public function get_error_messages()
    {
        $errors = $this->error_array();
        $messages = [];

        foreach ($errors as $field => $error) {
            $messages[$field] = $this->format_error_message($field, $error);
        }

        return $messages;
    }

    /**
     * Error message formatla
     */
    private function format_error_message($field, $error)
    {
        // Field name'i daha okunabilir yap
        $field_name = ucfirst(str_replace('_', ' ', $field));
        
        // Error message'ı iyileştir
        $message = str_replace('The ' . $field . ' field', $field_name, $error);
        $message = str_replace('field', '', $message);
        
        return trim($message);
    }
}

