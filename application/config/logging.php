<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Logging Configuration
|--------------------------------------------------------------------------
|
| Logging ayarları
|
*/

// Log level (DEBUG, INFO, WARNING, ERROR)
$config['log_level'] = getenv('LOG_LEVEL') ?: 'INFO';

// Log file ayarları
$config['log_path'] = APPPATH . 'logs/';
$config['log_file'] = 'app-' . date('Y-m-d') . '.log';

// Log rotation ayarları
$config['max_file_size'] = 10485760; // 10MB
$config['max_files'] = 5;

// Error tracking (Sentry benzeri)
$config['error_tracking_enabled'] = getenv('ERROR_TRACKING_ENABLED') ?: false;
$config['error_tracking_dsn'] = getenv('ERROR_TRACKING_DSN') ?: '';

// Performance monitoring
$config['performance_monitoring'] = getenv('PERFORMANCE_MONITORING') ?: false;
$config['slow_query_threshold'] = 1.0; // seconds

// API usage analytics
$config['api_analytics_enabled'] = getenv('API_ANALYTICS_ENABLED') ?: true;

