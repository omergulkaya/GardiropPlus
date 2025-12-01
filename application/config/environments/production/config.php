<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Production Environment Configuration
|--------------------------------------------------------------------------
|
| Production ortamı için özel ayarlar
|
*/

// Error reporting
$config['log_threshold'] = 1; // ERROR level only
$config['db_debug'] = false;

// Caching
$config['cache_on'] = true;

// Security
$config['force_https'] = true;
$config['session_cookie_secure'] = true;
$config['session_cookie_httponly'] = true;

// Performance
$config['enable_profiler'] = false;

