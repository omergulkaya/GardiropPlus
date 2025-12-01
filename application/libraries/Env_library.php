<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Environment Configuration Library
 * .env file support ve environment-based config için
 */
class Env_library
{
    private $env_file;
    private $env_data = [];
    private $required_vars = [];
    private $validated = false;

    public function __construct()
    {
        $this->env_file = FCPATH . '.env';
        $this->load_env_file();
    }

    /**
     * .env dosyasını yükle
     */
    private function load_env_file()
    {
        if (!file_exists($this->env_file)) {
            // .env dosyası yoksa, environment variable'lardan yükle
            return;
        }

        $lines = file($this->env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Yorum satırlarını atla
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // KEY=VALUE formatını parse et
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Tırnak işaretlerini kaldır
                $value = trim($value, '"\'');
                
                // Environment variable olarak set et
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
                
                $this->env_data[$key] = $value;
            }
        }
    }

    /**
     * Environment variable al
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Önce getenv kontrol et
        $value = getenv($key);
        if ($value !== false) {
            return $this->parse_value($value);
        }

        // Sonra $_ENV kontrol et
        if (isset($_ENV[$key])) {
            return $this->parse_value($_ENV[$key]);
        }

        // Son olarak env_data kontrol et
        if (isset($this->env_data[$key])) {
            return $this->parse_value($this->env_data[$key]);
        }

        return $default;
    }

    /**
     * Value'yu parse et (boolean, integer, null)
     */
    private function parse_value($value)
    {
        if ($value === 'true' || $value === 'TRUE') {
            return true;
        }
        if ($value === 'false' || $value === 'FALSE') {
            return false;
        }
        if ($value === 'null' || $value === 'NULL' || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        return $value;
    }

    /**
     * Required environment variables belirle
     * 
     * @param array $vars
     */
    public function set_required($vars)
    {
        $this->required_vars = $vars;
    }

    /**
     * Config validation
     * 
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate()
    {
        $errors = [];
        
        foreach ($this->required_vars as $var) {
            if ($this->get($var) === null) {
                $errors[] = "Required environment variable '{$var}' is not set";
            }
        }

        $this->validated = empty($errors);
        
        return [
            'valid' => $this->validated,
            'errors' => $errors
        ];
    }

    /**
     * Tüm environment variables'ı al
     * 
     * @return array
     */
    public function all()
    {
        return array_merge($this->env_data, $_ENV);
    }

    /**
     * Environment-based config dosyası yükle
     * 
     * @param string $config_file
     * @return array
     */
    public function load_config($config_file)
    {
        $env = ENVIRONMENT;
        $env_config_file = APPPATH . "config/environments/{$env}/{$config_file}.php";
        
        if (file_exists($env_config_file)) {
            return include $env_config_file;
        }

        // Fallback to default config
        $default_config_file = APPPATH . "config/{$config_file}.php";
        if (file_exists($default_config_file)) {
            return include $default_config_file;
        }

        return [];
    }
}

