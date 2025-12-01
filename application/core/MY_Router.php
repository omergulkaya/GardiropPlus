<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custom Router
 * API versioning desteği için
 */
class MY_Router extends CI_Router
{
    protected $api_version = null;

    public function __construct($routing = null)
    {
        parent::__construct($routing);
        $this->detect_api_version();
    }

    /**
     * API version'ı tespit et
     */
    protected function detect_api_version()
    {
        // Router'da $this->load, $this->config gibi property'ler yok
        // Config dosyasını doğrudan yükle
        $config =& get_config();
        
        // app.php config dosyasını yükle
        if (file_exists(APPPATH . 'config/app.php')) {
            include(APPPATH . 'config/app.php');
        }
        
        $strategy = isset($config['versioning_strategy']) ? $config['versioning_strategy'] : 'url';
        
        if ($strategy === 'header') {
            // Header-based versioning
            $version_header = isset($config['version_header']) ? $config['version_header'] : 'X-API-Version';
            $this->api_version = isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($version_header))]) 
                ? $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($version_header))] 
                : null;
        } else {
            // URL-based versioning (default)
            $segments = $this->uri->segments;
            
            // /api/v1/... formatını kontrol et
            if (isset($segments[1]) && $segments[1] === 'api' && isset($segments[2]) && preg_match('/^v\d+$/', $segments[2])) {
                $this->api_version = $segments[2];
                // Version segment'ini URI'den kaldır
                array_splice($segments, 2, 1);
                $this->uri->segments = $segments;
            }
        }

        // Default version
        if (!$this->api_version) {
            $this->api_version = isset($config['default_api_version']) ? $config['default_api_version'] : 'v1';
        }

        // Version validation
        $supported_versions = isset($config['supported_api_versions']) ? $config['supported_api_versions'] : ['v1'];
        if (!in_array($this->api_version, $supported_versions)) {
            show_error('Unsupported API version: ' . $this->api_version, 400);
        }

        // Deprecation check - Output header'ı set etmek için CI instance gerekir
        $deprecated_versions = isset($config['deprecated_api_versions']) ? $config['deprecated_api_versions'] : [];
        if (in_array($this->api_version, $deprecated_versions)) {
            $deprecation_header = isset($config['deprecation_header']) ? $config['deprecation_header'] : 'X-API-Deprecated';
            // Header'ı set et (CI instance hazır olduğunda)
            if (function_exists('get_instance')) {
                try {
                    $ci =& get_instance();
                    if (isset($ci) && is_object($ci) && isset($ci->output)) {
                        $ci->output->set_header($deprecation_header . ': true');
                    }
                } catch (Exception $e) {
                    // CI instance henüz hazır değil, header'ı manuel set et
                    header($deprecation_header . ': true');
                }
            } else {
                // get_instance() henüz tanımlı değil, header'ı manuel set et
                header($deprecation_header . ': true');
            }
        }
    }

    /**
     * API version al
     */
    public function get_api_version()
    {
        return $this->api_version;
    }

    /**
     * Controller path'i version'a göre ayarla
     */
    protected function _set_request($segments = array())
    {
        // API request ise version klasörünü kontrol et
        if (isset($segments[0]) && $segments[0] === 'api') {
            $version = $this->get_api_version();
            $version_path = APPPATH . 'controllers/' . $version . '/';
            
            // Version-specific controller varsa kullan
            if (isset($segments[1]) && file_exists($version_path . ucfirst($segments[1]) . '.php')) {
                $this->directory = $version . '/';
            }
        }

        return parent::_set_request($segments);
    }
}

