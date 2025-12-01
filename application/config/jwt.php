<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| JWT Configuration
|--------------------------------------------------------------------------
|
| JWT (JSON Web Token) yapılandırma ayarları
|
*/

// JWT Secret Key
// Production'da mutlaka environment variable (JWT_SECRET_KEY) kullanın
$config['jwt_secret_key'] = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-in-production';
// Token expiration süreleri (saniye)
$config['access_token_expiry'] = 3600;
// 1 saat
$config['refresh_token_expiry'] = 604800;
// 7 gün

// Algorithm
$config['algorithm'] = 'HS256';
// Issuer
$config['issuer'] = base_url();
