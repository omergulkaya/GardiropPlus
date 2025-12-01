<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Error Code Mapping
|--------------------------------------------------------------------------
|
| HTTP status code'ları için error code mapping
|
*/

// Error codes
$config['error_codes'] = [
    // 4xx Client Errors
    400 => 'BAD_REQUEST',
    401 => 'UNAUTHORIZED',
    403 => 'FORBIDDEN',
    404 => 'NOT_FOUND',
    405 => 'METHOD_NOT_ALLOWED',
    409 => 'CONFLICT',
    422 => 'UNPROCESSABLE_ENTITY',
    423 => 'LOCKED',
    429 => 'TOO_MANY_REQUESTS',
    
    // 5xx Server Errors
    500 => 'INTERNAL_SERVER_ERROR',
    503 => 'SERVICE_UNAVAILABLE',
    504 => 'GATEWAY_TIMEOUT'
];

// User-friendly error messages (Turkish)
$config['user_messages'] = [
    'BAD_REQUEST' => 'Geçersiz istek',
    'UNAUTHORIZED' => 'Yetkisiz erişim',
    'FORBIDDEN' => 'Erişim reddedildi',
    'NOT_FOUND' => 'Kayıt bulunamadı',
    'METHOD_NOT_ALLOWED' => 'İzin verilmeyen metod',
    'CONFLICT' => 'Çakışma hatası',
    'UNPROCESSABLE_ENTITY' => 'İşlenemeyen veri',
    'LOCKED' => 'Hesap kilitli',
    'TOO_MANY_REQUESTS' => 'Çok fazla istek',
    'INTERNAL_SERVER_ERROR' => 'Sunucu hatası',
    'SERVICE_UNAVAILABLE' => 'Servis kullanılamıyor',
    'GATEWAY_TIMEOUT' => 'Zaman aşımı'
];

// English messages (optional)
$config['user_messages_en'] = [
    'BAD_REQUEST' => 'Bad request',
    'UNAUTHORIZED' => 'Unauthorized',
    'FORBIDDEN' => 'Forbidden',
    'NOT_FOUND' => 'Not found',
    'METHOD_NOT_ALLOWED' => 'Method not allowed',
    'CONFLICT' => 'Conflict',
    'UNPROCESSABLE_ENTITY' => 'Unprocessable entity',
    'LOCKED' => 'Account locked',
    'TOO_MANY_REQUESTS' => 'Too many requests',
    'INTERNAL_SERVER_ERROR' => 'Internal server error',
    'SERVICE_UNAVAILABLE' => 'Service unavailable',
    'GATEWAY_TIMEOUT' => 'Gateway timeout'
];

