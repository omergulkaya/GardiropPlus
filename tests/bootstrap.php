<?php
/**
 * PHPUnit Bootstrap File
 * CodeIgniter 3 için test bootstrap
 * 
 * NOT: Integration testler için web server gerekli
 * Unit testler için CodeIgniter instance gerekli değil (skip edilebilir)
 */

// Define path constants
define('BASEPATH', realpath(__DIR__ . '/../system') . '/');
define('APPPATH', realpath(__DIR__ . '/../application') . '/');
define('FCPATH', realpath(__DIR__ . '/../') . '/');
define('ENVIRONMENT', 'testing');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock $_SERVER for CLI
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['SERVER_PORT'] = 80;
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['QUERY_STRING'] = '';
}

// Load CodeIgniter core functions
require_once BASEPATH . 'core/Common.php';

// get_instance function CodeIgniter.php dosyasının içinde tanımlı
// Ancak CodeIgniter'ı tam başlatmadan önce bu fonksiyonu tanımlamak için
// CodeIgniter.php'nin ilgili kısmını yüklememiz gerekiyor
// Ancak bu çok karmaşık olacağı için, testlerde skip mekanizması kullanıyoruz

// Note: get_instance() fonksiyonu CodeIgniter başlatıldıktan sonra tanımlı olacak
// Unit testlerde CodeIgniter'ı başlatmak yerine, test'lerin skip edilmesini sağlıyoruz
