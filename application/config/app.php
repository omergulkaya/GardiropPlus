<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
|
| Genel uygulama ayarları
|
*/

// Application version
$config['version'] = '1.0.0';

// API version
$config['api_version'] = getenv('API_VERSION') ?: 'v1';

// Default API version
$config['default_api_version'] = 'v1';

// Supported API versions
$config['supported_api_versions'] = ['v1'];

// Deprecated API versions
$config['deprecated_api_versions'] = [];

// API versioning strategy: 'url' or 'header'
$config['versioning_strategy'] = getenv('API_VERSIONING_STRATEGY') ?: 'url';

// Version header name
$config['version_header'] = 'X-API-Version';

// Deprecation warning header
$config['deprecation_header'] = 'X-API-Deprecated';

