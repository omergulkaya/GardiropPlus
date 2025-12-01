<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'controllers/Api.php';

class Metadata extends Api
{
    public $metadata_model;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Metadata_model', 'metadata_model');
        // Cache driver'ı yükle (opsiyonel - hata olursa devam et)
        try {
            $this->load->driver('cache', array('adapter' => 'file'));
        } catch (Exception $e) {
            log_message('debug', 'Cache driver could not be loaded: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/metadata/categories
     * Kategorileri getir
     */
    public function categories()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'categories_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'Categories retrieved from cache');
            }

            $categories = $this->metadata_model->get_categories($language_code);
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $categories, 3600);
            }
            
            return $this->success($categories, 'Categories retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::categories() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/metadata/colors
     * Renkleri getir
     */
    public function colors()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'colors_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'Colors retrieved from cache');
            }

            $colors = $this->metadata_model->get_colors($language_code);
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $colors, 3600);
            }
            
            return $this->success($colors, 'Colors retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::colors() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/metadata/seasons
     * Sezonları getir
     */
    public function seasons()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'seasons_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'Seasons retrieved from cache');
            }

            $seasons = $this->metadata_model->get_seasons($language_code);
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $seasons, 3600);
            }
            
            return $this->success($seasons, 'Seasons retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::seasons() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/metadata/styles
     * Stilleri getir
     */
    public function styles()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'styles_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'Styles retrieved from cache');
            }

            $styles = $this->metadata_model->get_styles($language_code);
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $styles, 3600);
            }
            
            return $this->success($styles, 'Styles retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::styles() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/metadata/event-types
     * Etkinlik türlerini getir
     */
    public function event_types()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'event_types_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'Event types retrieved from cache');
            }

            $event_types = $this->metadata_model->get_event_types($language_code);
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $event_types, 3600);
            }
            
            return $this->success($event_types, 'Event types retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::event_types() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/metadata/all
     * Tüm metadata'yı tek seferde getir (optimizasyon için)
     */
    public function all()
    {
        try {
            $language_code = $this->input->get('lang') ?: 'tr';
            
            // Cache kontrolü (cache yoksa devam et)
            $cache_key = 'metadata_all_' . $language_code;
            $cached = false;
            if (isset($this->cache) && method_exists($this->cache, 'get')) {
                $cached = $this->cache->get($cache_key);
            }
            
            if ($cached !== false) {
                return $this->success($cached, 'All metadata retrieved from cache');
            }

            $metadata = [
                'categories' => $this->metadata_model->get_categories($language_code),
                'colors' => $this->metadata_model->get_colors($language_code),
                'seasons' => $this->metadata_model->get_seasons($language_code),
                'styles' => $this->metadata_model->get_styles($language_code),
                'event_types' => $this->metadata_model->get_event_types($language_code),
            ];
            
            // Cache'e kaydet (1 saat) - cache yoksa devam et
            if (isset($this->cache) && method_exists($this->cache, 'save')) {
                $this->cache->save($cache_key, $metadata, 3600);
            }
            
            return $this->success($metadata, 'All metadata retrieved successfully');
        } catch (Exception $e) {
            log_message('error', 'Metadata::all() error: ' . $e->getMessage());
            return $this->error('INTERNAL_SERVER_ERROR', $e->getMessage(), 500);
        }
    }
}

