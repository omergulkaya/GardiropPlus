<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Privacy Helper
 * Veri minimizasyonu ve mahremiyet için helper fonksiyonlar
 */

if (!function_exists('mask_email')) {
    /**
     * Email'i maskele (örn: use***@example.com)
     */
    function mask_email($email)
    {
        if (empty($email)) {
            return '';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        // İlk 3 karakteri göster, geri kalanını maskele
        if (strlen($username) <= 3) {
            $masked_username = substr($username, 0, 1) . '***';
        } else {
            $masked_username = substr($username, 0, 3) . '***';
        }

        return $masked_username . '@' . $domain;
    }
}

if (!function_exists('mask_phone')) {
    /**
     * Telefon numarasını maskele
     */
    function mask_phone($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Son 4 haneyi göster, geri kalanını maskele
        if (strlen($phone) <= 4) {
            return '***' . $phone;
        }

        return '***' . substr($phone, -4);
    }
}

if (!function_exists('mask_user_id')) {
    /**
     * User ID'yi hash'le (görüntüleme amaçlı)
     */
    function mask_user_id($user_id)
    {
        if (empty($user_id)) {
            return '';
        }

        // İlk 4 karakteri göster
        return substr(hash('sha256', $user_id), 0, 8) . '...';
    }
}

if (!function_exists('sanitize_for_display')) {
    /**
     * Hassas verileri temizle (admin panelinde gösterim için)
     */
    function sanitize_for_display($data, $fields_to_mask = [])
    {
        if (!is_array($data)) {
            return $data;
        }

        $default_mask_fields = ['password', 'token', 'api_key', 'secret', 'access_token', 'refresh_token'];
        $mask_fields = array_merge($default_mask_fields, $fields_to_mask);

        foreach ($mask_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        // Email ve phone maskele
        if (isset($data['email'])) {
            $data['email'] = mask_email($data['email']);
        }
        if (isset($data['phone'])) {
            $data['phone'] = mask_phone($data['phone']);
        }

        return $data;
    }
}

if (!function_exists('log_admin_activity')) {
    /**
     * Admin aktivitesini logla
     */
    function log_admin_activity($action, $resource_type = null, $resource_id = null, $details = null)
    {
        $CI =& get_instance();
        
        if (!isset($CI->Admin_activity_log_model)) {
            $CI->load->model('Admin_activity_log_model');
        }

        $admin_id = $CI->session->userdata('admin_id');
        if (!$admin_id) {
            return false;
        }

        $log_data = [
            'admin_id' => $admin_id,
            'action' => $action,
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'details' => $details
        ];

        return $CI->Admin_activity_log_model->create($log_data);
    }
}

if (!function_exists('log_data_access')) {
    /**
     * Veri erişimini logla (GDPR/KVKK)
     */
    function log_data_access($user_id, $access_type, $data_type, $data_fields = null, $purpose = null, $legal_basis = null)
    {
        $CI =& get_instance();
        
        if (!isset($CI->Gdpr_model)) {
            $CI->load->model('Gdpr_model');
        }

        $accessed_by = $CI->session->userdata('admin_id') ?: $CI->session->userdata('user_id');
        if (!$accessed_by) {
            return false;
        }

        return $CI->Gdpr_model->log_data_access($user_id, $accessed_by, $access_type, $data_type, $data_fields, $purpose, $legal_basis);
    }
}

if (!function_exists('check_permission')) {
    /**
     * Yetki kontrolü (RBAC)
     */
    function check_permission($resource, $action)
    {
        $CI =& get_instance();
        
        if (!isset($CI->Role_model)) {
            $CI->load->model('Role_model');
        }

        $user_id = $CI->session->userdata('admin_id') ?: $CI->session->userdata('user_id');
        if (!$user_id) {
            return false;
        }

        return $CI->Role_model->has_permission($user_id, $resource, $action);
    }
}

