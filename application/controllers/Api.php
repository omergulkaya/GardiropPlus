<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base API Controller
 * Tüm API controller'ları bu sınıftan türetilmelidir
 */
class Api extends CI_Controller
{
    protected $user_id;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        
        // Environment config yükle
        $this->load->library('env_library');
        
        // Logger yükle
        $this->load->library('logger_library');
        
        // Error handler yükle
        $this->load->library('error_handler_library');
        
        // Response library yükle
        $this->load->library('response_library');
        
        // API version header ekle
        $this->load->config('app');
        $api_version = method_exists($this->router, 'get_api_version') ? $this->router->get_api_version() : $this->config->item('api_version', 'app');
        if (!headers_sent() && $api_version) {
            header('X-API-Version: ' . $api_version);
        }
        
// CORS headers - Security hook tarafından yönetiliyor
        // Burada sadece fallback olarak bırakıldı
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Version');
        }

        if ($this->input->method() === 'options') {
            exit(0);
        }

        // JSON response için
        $this->output->set_content_type('application/json');
    }

    /**
     * Başarılı response döndürür
     * Field selection ve response optimization desteği
     */
    protected function success($data = null, $message = 'Success', $status_code = 200, $metadata = [])
    {
        $this->output->set_status_header($status_code);
        
        // Field selection (sparse fieldsets) - ?fields=name,email,id
        $fields = $this->input->get('fields');
        if ($fields && $data) {
            $data = $this->response_library->select_fields($data, explode(',', $fields));
        }

        // Response library ile consistent response oluştur
        $response = $this->response_library
            ->clear_metadata()
            ->success($data, $message, $status_code, $metadata);

        // Response caching (GET istekleri için)
        if ($this->input->method() === 'get' && $this->config->item('cache_enabled')) {
            $this->load->library('cache_library');
            $cache_key = $this->cache_library->get_response_key($this->uri->uri_string(), $this->input->get());
            $ttl = $this->config->item('response_cache_ttl') ?: 300;
            $this->cache_library->set($cache_key, $response, $ttl);
        }

        // Response compression (gzip)
        $this->compress_response($response);
        exit;
    }

    /**
     * Field selection - sadece istenen alanları döndür
     * @deprecated Use Response_library::select_fields() instead
     */
    private function filter_fields($data, $fields)
    {
        return $this->response_library->select_fields($data, $fields);
    }

    /**
     * Response compression (gzip)
     */
    private function compress_response($response)
    {
        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
// Accept-Encoding kontrolü
        $accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        if (strpos($accept_encoding, 'gzip') !== false && function_exists('gzencode')) {
            $compressed = gzencode($json, 6);
        // Compression level 6
            if ($compressed !== false) {
                header('Content-Encoding: gzip');
                header('Content-Length: ' . strlen($compressed));
                echo $compressed;
                return;
            }
        }

        // Compression yoksa normal output
        echo $json;
    }

    /**
     * Hata response döndürür
     * Standardized error response with error code mapping
     */
    protected function error($message = 'Error', $status_code = 400, $errors = null, $details = null)
    {
        $this->output->set_status_header($status_code);
        
        // Error handler library ile standardized error response oluştur
        $response = $this->error_handler_library->create_error_response(
            $status_code,
            $message,
            $errors,
            $details
        );

        // Response compression (gzip)
        $this->compress_response($response);
        exit;
    }

    /**
     * Kullanıcı authentication kontrolü
     * Session-based ve JWT token desteği
     */
    protected function require_auth()
    {
        $user_id = null;
// Önce session kontrolü (backward compatibility)
        $user_id = $this->session->userdata('user_id');
// Session yoksa JWT token kontrolü
        if (!$user_id) {
            $auth_header = $this->input->get_request_header('Authorization');
            if ($auth_header) {
            // Bearer token formatı: "Bearer <token>"
                if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                    $token = $matches[1];
                    $this->load->library('jwt_library');
                    $payload = $this->jwt_library->decode($token);
                    if ($payload && isset($payload['user_id']) && $payload['type'] === 'access') {
                        $user_id = $payload['user_id'];
                    // Session'a da kaydet (backward compatibility)
                        $this->session->set_userdata('user_id', $user_id);
                    }
                }
            }
        }

        if (!$user_id) {
            $this->error('Unauthorized', 401);
        }

        $this->user_id = $user_id;
        return $user_id;
    }

    /**
     * Input validation helper
     * JSON body ve form data desteği ile
     */
    protected function validate_input($rules, $use_json = false)
    {
        $this->load->library('form_validation');
        
        // JSON body validation
        if ($use_json) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON format', 400, ['json' => json_last_error_msg()]);
                return;
            }
            
            $this->form_validation->set_data($data ?: []);
        } else {
            // Form data validation
            $this->form_validation->set_data($this->input->post() ?: $this->input->get());
        }

        foreach ($rules as $field => $rule) {
            $this->form_validation->set_rules($field, $field, $rule);
        }

        if (!$this->form_validation->run()) {
            // Improved error messages
            $error_messages = method_exists($this->form_validation, 'get_error_messages') 
                ? $this->form_validation->get_error_messages() 
                : $this->form_validation->error_array();
            
            $this->error('Validation failed', 422, $error_messages);
        }
    }

    /**
     * JSON body validation
     */
    protected function validate_json($rules)
    {
        $this->validate_input($rules, true);
    }

    /**
     * File upload validation
     */
    protected function validate_upload($field_name, $allowed_types = null, $max_size = null)
    {
        $this->load->library('form_validation');
        
        if (!$this->form_validation->valid_upload($field_name, $allowed_types, $max_size)) {
            $errors = $this->form_validation->error_array();
            $this->error('File upload validation failed', 400, $errors);
        }
    }
}
