<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Image Helper
 * Lazy loading, responsive images, CDN desteği
 */

/**
 * Lazy loading için image tag oluştur
 */
if (!function_exists('lazy_image')) {
    function lazy_image($src, $alt = '', $attributes = [])
    {
        $CI =& get_instance();
        $CI->load->library('cdn_library');
        
        // CDN URL kullan
        $src = $CI->cdn_library->url($src);
        
        $default_attrs = [
            'src' => 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E',
            'data-src' => $src,
            'alt' => $alt,
            'loading' => 'lazy',
            'class' => 'lazy-load'
        ];
        
        $attrs = array_merge($default_attrs, $attributes);
        
        $html = '<img';
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';
        
        return $html;
    }
}

/**
 * Responsive image tag oluştur (srcset ile)
 */
if (!function_exists('responsive_image')) {
    function responsive_image($src, $alt = '', $sizes = [], $attributes = [])
    {
        $CI =& get_instance();
        $CI->load->library('cdn_library');
        
        // Srcset oluştur
        $srcset = $CI->cdn_library->srcset($src, $sizes);
        
        // Default src (en küçük)
        $default_src = $CI->cdn_library->image_url($src, $sizes[0] ?? 300);
        
        $default_attrs = [
            'src' => $default_src,
            'srcset' => $srcset,
            'alt' => $alt,
            'loading' => 'lazy',
            'sizes' => '(max-width: 600px) 100vw, (max-width: 1200px) 50vw, 33vw'
        ];
        
        $attrs = array_merge($default_attrs, $attributes);
        
        $html = '<img';
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';
        
        return $html;
    }
}

/**
 * WebP desteği kontrolü ve fallback
 */
if (!function_exists('webp_image')) {
    function webp_image($src, $webp_src, $alt = '', $attributes = [])
    {
        $CI =& get_instance();
        $CI->load->library('cdn_library');
        
        $src = $CI->cdn_library->url($src);
        $webp_src = $CI->cdn_library->url($webp_src);
        
        $html = '<picture>';
        $html .= '<source srcset="' . htmlspecialchars($webp_src) . '" type="image/webp">';
        $html .= '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"';
        
        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';
        $html .= '</picture>';
        
        return $html;
    }
}

/**
 * Progressive image loading (blur placeholder)
 */
if (!function_exists('progressive_image')) {
    function progressive_image($src, $placeholder = null, $alt = '', $attributes = [])
    {
        $CI =& get_instance();
        $CI->load->library('cdn_library');
        
        $src = $CI->cdn_library->url($src);
        
        // Blur placeholder oluştur (base64 encoded tiny image)
        if (!$placeholder) {
            $placeholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E';
        }
        
        $default_attrs = [
            'src' => $placeholder,
            'data-src' => $src,
            'alt' => $alt,
            'class' => 'progressive-image',
            'loading' => 'lazy'
        ];
        
        $attrs = array_merge($default_attrs, $attributes);
        
        $html = '<img';
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';
        
        return $html;
    }
}

