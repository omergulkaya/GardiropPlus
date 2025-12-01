<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security Hook
 * API güvenlik kontrolleri
 */
class Security_hook
{
    private $ci;

    public function __construct()
    {
        // get_instance() henüz tanımlı olmayabilir (hook'lar bootstrap'tan önce yüklenebilir)
        // Lazy loading yapacağız
    }

    /**
     * CodeIgniter instance'ı al (lazy load)
     */
    private function get_ci()
    {
        if ($this->ci === null) {
            // get_instance() fonksiyonunun tanımlı olduğundan emin ol
            if (function_exists('get_instance')) {
                try {
                    $this->ci =& get_instance();
                } catch (Exception $e) {
                    // get_instance() çağrısı başarısız oldu
                    return null;
                } catch (Error $e) {
                    // Fatal error (CI_Controller bulunamadı gibi)
                    return null;
                }
            } elseif (class_exists('CI_Controller', false) && method_exists('CI_Controller', 'get_instance')) {
                // get_instance() henüz tanımlı değilse, CI_Controller::get_instance() kullan
                try {
                    $this->ci = CI_Controller::get_instance();
                } catch (Exception $e) {
                    return null;
                } catch (Error $e) {
                    return null;
                }
            } else {
                // CodeIgniter henüz yüklenmemiş
                return null;
            }
        }
        return $this->ci;
    }

    /**
     * HTTPS zorunluluğu kontrolü
     */
    public function enforce_https()
    {
        // Sadece production'da kontrol et
        if (ENVIRONMENT === 'production') {
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            // HTTPS olmadan istekleri reddet
                $ci = $this->get_ci();
                if ($ci) {
                    $ci->output->set_status_header(403);
                } else {
                    http_response_code(403);
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'HTTPS connection required'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    /**
     * CORS policy kontrolü
     */
    public function cors_policy()
    {
        $allowed_origins = [
            'http://localhost:3000',
            'http://localhost:8080',
            'http://localhost:5000',
            'http://localhost:58942', // Flutter Web default port
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:5000',
            'http://127.0.0.1:58942',
        ];
        // Environment variable'dan allowed origins al
        $env_origins = getenv('CORS_ALLOWED_ORIGINS');
        if ($env_origins) {
            $env_origins_array = array_map('trim', explode(',', $env_origins));
            $allowed_origins = array_merge($allowed_origins, $env_origins_array);
        }

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Development'ta localhost için geniş izin ver
        if (ENVIRONMENT !== 'production') {
            // Localhost veya 127.0.0.1 ile başlayan tüm origin'lere izin ver (port numarası olabilir)
            if ($origin && preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?(\/.*)?$/', $origin)) {
                // Localhost origin'i kabul et - origin'i olduğu gibi kullan
            } elseif ($origin && !in_array($origin, $allowed_origins)) {
                // Eğer origin varsa ama listede yoksa, development'ta yine de izin ver
                if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
                    // Localhost origin'leri kabul et
                } else {
                    // Development'ta origin yoksa wildcard kullan
                    $origin = '';
                }
            }
        } else {
            // Production'da sadece whitelist'teki origin'lere izin ver
            if ($origin && !in_array($origin, $allowed_origins)) {
                $origin = '';
            }
        }

        // CORS headers - her zaman set et
        $allow_credentials = false;
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            $allow_credentials = true;
        } else {
            // Development'ta wildcard kullan
            if (ENVIRONMENT !== 'production') {
                header('Access-Control-Allow-Origin: *');
                $allow_credentials = false; // Wildcard ile credentials kullanılamaz
            } else {
                // Production'da origin yoksa boş bırak
                header('Access-Control-Allow-Origin: ' . ($allowed_origins[0] ?? '*'));
                $allow_credentials = false;
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key, X-Timestamp, X-Signature, X-API-Version, Accept');
        if ($allow_credentials) {
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Max-Age: 86400');
        
        // Preflight request - OPTIONS metodunu handle et
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            header('Content-Length: 0');
            exit(0);
        }
    }

    /**
     * Rate limiting kontrolü
     * Per-user ve per-endpoint rate limiting desteği
     */
    public function rate_limit()
    {
        $ci = $this->get_ci();
        if (!$ci) {
            // CodeIgniter henüz yüklenmemiş, rate limiting yapamayız
            return;
        }
        
        // API endpoint'leri için rate limiting
        $uri = $ci->uri->uri_string();
// Per-endpoint limits
        $endpoint_limits = [
            'api/auth/login' => 5,      // Login için çok düşük limit
            'api/auth/register' => 3,   // Register için çok düşük limit
            'api/auth/forgot-password' => 3, // Password reset için düşük limit
        ];
// Endpoint'e özel limit var mı kontrol et
        $limit = 100;
// Default limit
        $window = 60;
// 60 saniye

        foreach ($endpoint_limits as $endpoint => $endpoint_limit) {
            if (strpos($uri, $endpoint) === 0) {
                $limit = $endpoint_limit;
                break;
            }
        }

        // Auth endpoint'leri için genel kontrol
        if (strpos($uri, 'api/auth/') === 0 && !isset($endpoint_limits[$uri])) {
            $limit = 10;
// Auth için daha düşük limit
        }

        $ip = $this->get_client_ip();
        $ci->load->library('rate_limit_library');
// Per-user rate limiting (eğer authenticated ise)
        $user_id = $ci->session->userdata('user_id');
        $identifier = $user_id ? 'user_' . $user_id : $ip;
// User-based limit (authenticated users için daha yüksek limit)
        if ($user_id) {
            $limit = min($limit * 2, 200);
// Authenticated users için 2x limit
        }

        $rate_limit_info = $ci->rate_limit_library->get_info($identifier);
        if (!$ci->rate_limit_library->check($identifier, $limit, $window)) {
            $reset_time = $rate_limit_info['reset'] ?? (time() + $window);
            $retry_after = max(1, $reset_time - time());
            $ci->output->set_status_header(429);
            header('Retry-After: ' . $retry_after);
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retry_after,
                'rate_limit' => [
                    'limit' => $limit,
                    'remaining' => 0,
                    'reset' => $reset_time
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Input sanitization
     */
    public function sanitize_input()
    {
        // XSS koruması
        $_GET = $this->sanitize_array($_GET);
        $_POST = $this->sanitize_array($_POST);
    }

    /**
     * Array sanitization
     */
    private function sanitize_array($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize_array($value);
            }
        } else {
        // HTML tag'lerini temizle
            $data = strip_tags($data);
        // Özel karakterleri escape et
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    /**
     * SQL injection koruması kontrolü
     */
    public function check_sql_injection()
    {
        $dangerous_keywords = [
            'DROP', 'DELETE', 'INSERT', 'UPDATE', 'SELECT',
            'UNION', 'EXEC', 'EXECUTE', 'SCRIPT', '--', ';',
            '/*', '*/', 'OR 1=1', 'OR \'1\'=\'1\''
        ];
        $input = array_merge($_GET, $_POST);
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $upper_value = strtoupper($value);
                foreach ($dangerous_keywords as $keyword) {
                    if (strpos($upper_value, $keyword) !== false) {
                        log_message('warning', 'Potential SQL injection attempt: ' . $key . ' = ' . $value);
                        // Production'da daha sıkı kontrol yapılabilir
                    }
                }
            }
        }
    }

    /**
     * Client IP adresini al
     */
    private function get_client_ip()
    {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
}
