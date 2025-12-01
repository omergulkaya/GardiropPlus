<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|| -------------------------------------------------------------------------
|| Hooks
|| -------------------------------------------------------------------------
|| This file lets you define "hooks" to extend CI without hacking the core
|| files.  Please see the user guide for info:
||
||  https://codeigniter.com/userguide3/general/hooks.html
||
*/

// Security hooks
$hook['pre_system'] = array(
    'class'    => 'Security_hook',
    'function' => 'enforce_https',
    'filename' => 'Security_hook.php',
    'filepath' => 'hooks'
);

$hook['pre_controller'] = array(
    array(
        'class'    => 'Security_hook',
        'function' => 'cors_policy',
        'filename' => 'Security_hook.php',
        'filepath' => 'hooks'
    ),
    array(
        'class'    => 'Security_hook',
        'function' => 'rate_limit',
        'filename' => 'Security_hook.php',
        'filepath' => 'hooks'
    ),
    array(
        'class'    => 'Security_hook',
        'function' => 'sanitize_input',
        'filename' => 'Security_hook.php',
        'filepath' => 'hooks'
    ),
    array(
        'class'    => 'Security_hook',
        'function' => 'check_sql_injection',
        'filename' => 'Security_hook.php',
        'filepath' => 'hooks'
    )
);

// Response compression hook (API için)
$hook['post_controller_constructor'] = array(
    'class'    => 'Response_compression_hook',
    'function' => 'compress_output',
    'filename' => 'Response_compression_hook.php',
    'filepath' => 'hooks'
);
