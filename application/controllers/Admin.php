<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin Panel Controller
 * GardıropPlus Admin Paneli
 */
class Admin extends CI_Controller
{
    protected $admin_id;
    protected $admin_data;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        
        // Lazy loading: Modeller ve kütüphaneler sadece gerektiğinde yüklenecek
        // Bu sayede login sayfası gibi basit sayfalarda gereksiz yüklemeler önlenir
        
        // Admin authentication kontrolü
        $this->check_admin_auth();
    }
    
    /**
     * Lazy loading: Model yükleme helper'ı
     * Model daha önce yüklenmemişse yükler
     */
    protected function load_model($model)
    {
        $model_var = strtolower($model);
        if (!isset($this->$model_var)) {
            $this->load->model($model);
        }
    }
    
    /**
     * Lazy loading: Library yükleme helper'ı
     * Library daha önce yüklenmemişse yükler
     */
    protected function load_library($library)
    {
        $library_var = strtolower($library);
        if (!isset($this->$library_var)) {
            $this->load->library($library);
        }
    }
    
    /**
     * Flash message gönder ve yönlendir
     * Session flash data optimizasyonu için helper metod
     * 
     * @param string $message Flash mesajı
     * @param string $type Mesaj tipi (success, error, warning, info)
     * @param string|null $url Yönlendirilecek URL (null ise mevcut URI)
     */
    protected function flash_and_redirect($message, $type = 'success', $url = null)
    {
        $this->session->set_flashdata($type, $message);
        redirect($url ?: $this->uri->uri_string());
    }
    
    /**
     * Kayıt varlığını kontrol et, yoksa 404 göster
     * Error handling standardizasyonu için helper metod
     * 
     * @param mixed $record Kontrol edilecek kayıt
     * @param string $message Hata mesajı
     * @return mixed Kayıt varsa kaydı döndürür
     */
    protected function require_record($record, $message = 'Kayıt bulunamadı')
    {
        if (!$record) {
            $this->session->set_flashdata('error', $message);
            show_404();
        }
        return $record;
    }
    
    /**
     * Pagination bilgilerini al
     * Pagination helper metodu - kod tekrarını önler
     * 
     * @param int $per_page Sayfa başına kayıt sayısı
     * @return array Pagination bilgileri (page, per_page, offset)
     */
    protected function get_pagination($per_page = 20)
    {
        $page = (int)($this->input->get('page') ?: 1);
        $offset = ($page - 1) * $per_page;
        
        return [
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset
        ];
    }

    /**
     * Admin authentication kontrolü
     * Optimize edildi: Admin data session'da saklanıyor
     */
    protected function check_admin_auth()
    {
        $current_uri = $this->uri->uri_string();
        
        // Login sayfası hariç tüm sayfalar için auth kontrolü
        if ($current_uri !== 'admin/login' && $current_uri !== 'admin/do_login') {
            if (!$this->session->userdata('admin_logged_in')) {
                redirect('admin/login');
            }
            
            $this->admin_id = $this->session->userdata('admin_id');
            
            // Admin data'yı session'dan oku (cache)
            $admin_data = $this->session->userdata('admin_data');
            
            // Eğer session'da yoksa veya eskiyse DB'den çek
            if (!$admin_data || !isset($admin_data['id']) || $admin_data['id'] != $this->admin_id) {
                // User_model'i lazy load et
                $this->load_model('User_model');
                $admin_data = $this->user_model->get_by_id($this->admin_id);
                
                if ($admin_data) {
                    // Session'a kaydet (30 dakika TTL - session config'de ayarlanabilir)
                    $this->session->set_userdata('admin_data', $admin_data);
                }
            }
            
            $this->admin_data = $admin_data;
            
            // Admin yetkisi kontrolü (double check)
            if ($this->admin_data) {
                $is_admin = false;
                if (isset($this->admin_data['role']) && $this->admin_data['role'] === 'admin') {
                    $is_admin = true;
                } elseif (isset($this->admin_data['is_admin']) && ($this->admin_data['is_admin'] == 1 || $this->admin_data['is_admin'] === true)) {
                    $is_admin = true;
                }
                
                if (!$is_admin) {
                    $this->session->unset_userdata(['admin_logged_in', 'admin_id', 'admin_email', 'admin_name', 'admin_data']);
                    $this->session->set_flashdata('error', 'Admin yetkiniz kaldırıldı. Lütfen sistem yöneticisi ile iletişime geçin.');
                    redirect('admin/login');
                }
            }
        }
    }

    /**
     * Admin giriş sayfası
     */
    public function login()
    {
        if ($this->session->userdata('admin_logged_in')) {
            redirect('admin/dashboard');
        }
        
        $data['title'] = 'Admin Girişi - GardıropPlus';
        $this->load->view('admin/login', $data);
    }

    /**
     * Admin giriş işlemi
     */
    public function do_login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        if (empty($email) || empty($password)) {
            $this->flash_and_redirect('Email ve şifre gereklidir.', 'error', 'admin/login');
        }

        // User_model'i lazy load et
        $this->load_model('User_model');
        $user = $this->user_model->get_by_email($email);
        
        if (!$user) {
            $this->flash_and_redirect('Geçersiz email veya şifre.', 'error', 'admin/login');
        }

        // Şifre kontrolü
        if (!password_verify($password, $user['password'])) {
            $this->flash_and_redirect('Geçersiz email veya şifre.', 'error', 'admin/login');
        }

        // Admin kontrolü - role veya is_admin field'ı kontrol et
        $is_admin = false;
        
        // Önce role field'ını kontrol et
        if (isset($user['role']) && $user['role'] === 'admin') {
            $is_admin = true;
        }
        // Eğer role yoksa is_admin field'ını kontrol et
        elseif (isset($user['is_admin']) && ($user['is_admin'] == 1 || $user['is_admin'] === true)) {
            $is_admin = true;
        }
        
        // Admin değilse erişim reddedilir
        if (!$is_admin) {
            $this->flash_and_redirect('Bu sayfaya erişim yetkiniz yok. Sadece admin kullanıcılar giriş yapabilir.', 'error', 'admin/login');
        }

        // 2FA kontrolü
        $this->load_model('User_2fa_model');
        if ($this->user_2fa_model->is_enabled($user['id'])) {
            $twofa = $this->user_2fa_model->get_or_create($user['id']);
            
            // Email ile 2FA ise kod gönder
            if ($twofa['method'] === 'email') {
                $code = $this->user_2fa_model->create_email_code($user['id']);
                $this->send_2fa_email($user['email'], $code);
            }
            
            // 2FA kodu girilmesi gerekiyor
            $this->session->set_userdata([
                'admin_pending_2fa' => true,
                'admin_pending_user_id' => $user['id'],
                'admin_pending_email' => $user['email'],
                'admin_pending_2fa_method' => $twofa['method']
            ]);
            redirect('admin/verify_2fa');
        }

        // Admin session oluştur (admin_data'yı da session'a kaydet)
        $this->session->set_userdata([
            'admin_logged_in' => true,
            'admin_id' => $user['id'],
            'admin_email' => $user['email'],
            'admin_name' => $user['first_name'] . ' ' . $user['last_name'],
            'admin_data' => $user // Admin data'yı session'a kaydet (cache)
        ]);

        redirect('admin/dashboard');
    }

    /**
     * Admin çıkış
     */
    public function logout()
    {
        // Admin data'yı da session'dan temizle
        $this->session->unset_userdata(['admin_logged_in', 'admin_id', 'admin_email', 'admin_name', 'admin_data']);
        redirect('admin/login');
    }

    /**
     * Dashboard
     * Optimize edildi: Lazy loading ve cache kullanımı
     */
    public function dashboard()
    {
        $data['title'] = 'Dashboard - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Cache library'yi yükle
        $this->load_library('cache_library');
        
        // Cache key oluştur (admin_id'ye göre)
        $cache_key = 'admin_dashboard_' . $this->admin_id;
        
        // Cache'den oku
        $cached_data = $this->cache_library->get($cache_key);
        
        if ($cached_data === false) {
            // Modelleri lazy load et
            $this->load_model('Analytics_model');
            $this->load_model('User_model');
            $this->load_model('Clothing_item_model');
            $this->load_model('Api_error_model');
            $this->load_model('Error_statistics_model');
            
            // İstatistikler
            $stats = $this->analytics_model->get_all_statistics();
            
            // Son kullanıcılar
            $recent_users = $this->user_model->get_recent(5);
            
            // Son kıyafetler
            $recent_clothing = $this->clothing_item_model->get_recent(5);
            
            // API Hata özeti widget için
            $error_widget = [
                'last_24h' => $this->api_error_model->count_last_24h(),
                'critical' => $this->api_error_model->count_critical(),
                'top_endpoints' => $this->error_statistics_model->get_top_error_endpoints(5),
                'trend' => $this->error_statistics_model->get_error_trend('daily', 7)
            ];
            
            // Cache'e kaydet (5 dakika)
            $cached_data = [
                'stats' => $stats,
                'recent_users' => $recent_users,
                'recent_clothing' => $recent_clothing,
                'error_widget' => $error_widget
            ];
            
            $this->cache_library->set($cache_key, $cached_data, 300);
        }
        
        // Cache'den gelen verileri data'ya aktar
        $data['stats'] = $cached_data['stats'];
        $data['recent_users'] = $cached_data['recent_users'];
        $data['recent_clothing'] = $cached_data['recent_clothing'];
        $data['error_widget'] = $cached_data['error_widget'];
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/dashboard', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Global Search
     */
    public function search()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $query = $this->input->get('q');
        if (empty($query) || strlen($query) < 2) {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode(['results' => []]));
            return;
        }
        
        // Cache library'yi yükle
        $this->load_library('cache_library');
        
        // Cache key oluştur
        $cache_key = 'admin_search_' . md5($query);
        
        // Cache'den oku
        $results = $this->cache_library->get($cache_key);
        
        if ($results === false) {
            $results = [
                'users' => [],
                'clothing' => [],
                'outfits' => [],
                'errors' => []
            ];
            
            // Modelleri lazy load et
            $this->load_model('User_model');
            $this->load_model('Clothing_item_model');
            $this->load_model('Api_error_model');
            
            // Kullanıcı arama
            $users = $this->user_model->search($query, 5);
            foreach ($users as $user) {
                $results['users'][] = [
                    'title' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                    'url' => base_url('admin/users/detail/' . $user['id']),
                    'icon' => 'bi-person'
                ];
            }
            
            // Kıyafet arama
            $clothing = $this->clothing_item_model->search($query, 5);
            foreach ($clothing as $item) {
                $results['clothing'][] = [
                    'title' => $item['name'] ?? 'İsimsiz',
                    'url' => base_url('admin/clothing'),
                    'icon' => 'bi-bag'
                ];
            }
            
            // Hata arama
            $errors = $this->api_error_model->get_filtered(['search' => $query], 5);
            foreach ($errors as $error) {
                $results['errors'][] = [
                    'title' => $error['error_code'] . ' - ' . substr($error['message'] ?? '', 0, 50),
                    'url' => base_url('admin/errors/detail/' . $error['id']),
                    'icon' => 'bi-bug'
                ];
            }
            
            // Cache'e kaydet (2 dakika)
            $this->cache_library->set($cache_key, $results, 120);
        }
        
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode(['results' => $results]));
    }

    /**
     * Widget Data API (Real-time updates için)
     */
    public function widget_data($widget_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        // Cache library'yi yükle
        $this->load_library('cache_library');
        
        // Cache key oluştur (1 dakika cache)
        $cache_key = 'widget_' . $widget_id . '_' . date('Y-m-d-H-i');
        
        // Cache'den oku
        $data = $this->cache_library->get($cache_key);
        
        if ($data === false) {
            $data = [];
            
            switch ($widget_id) {
                case 'error-summary':
                    // Modelleri lazy load et
                    $this->load_model('Api_error_model');
                    $this->load_model('Error_statistics_model');
                    
                    $data = [
                        'html' => $this->load->view('admin/components/error_widget', [
                            'last_24h' => $this->api_error_model->count_last_24h(),
                            'critical' => $this->api_error_model->count_critical(),
                            'top_endpoints' => $this->error_statistics_model->get_top_error_endpoints(5),
                            'trend' => $this->error_statistics_model->get_error_trend('daily', 7)
                        ], true)
                    ];
                    break;
                    
                case 'user-stats':
                    // Modeli lazy load et
                    $this->load_model('Analytics_model');
                    $stats = $this->analytics_model->get_all_statistics();
                    $data = [
                        'html' => '<div class="text-center"><h3>' . number_format($stats['total_users'] ?? 0) . '</h3><small>Toplam Kullanıcı</small></div>'
                    ];
                    break;
            }
            
            // Cache'e kaydet (1 dakika)
            $this->cache_library->set($cache_key, $data, 60);
        }
        
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($data));
    }



    /**
     * Kullanıcılar listesi
     */
    public function users()
    {
        $data['title'] = 'Kullanıcılar - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Pagination helper kullan
        $pagination = $this->get_pagination(20);
        $page = $pagination['page'];
        $per_page = $pagination['per_page'];
        $offset = $pagination['offset'];
        
        $users = $this->User_model->get_all_paginated($per_page, $offset);
        
        // Veri minimizasyonu ve gizlilik kontrolü
        // privacy helper autoload'da yüklü
        foreach ($users as &$user) {
            // Admin görüntüleme izni kontrolü
            if (!$this->User_privacy_model->can_admin_view($user['id'])) {
                $user['hidden'] = true;
            }
            // Hassas verileri temizle
            $user = sanitize_for_display($user);
        }
        
        $data['users'] = $users;
        $data['total_users'] = $this->User_model->count_all();
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_users'] / $per_page);
        
        // Admin aktivite logu
        log_admin_activity('view_users_list', 'user', null, ['page' => $page]);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/users/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Kullanıcı detay
     */
    public function user_detail($id)
    {
        $data['title'] = 'Kullanıcı Detayı - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Gizlilik kontrolü
        if (!$this->User_privacy_model->can_admin_view($id)) {
            $this->session->set_flashdata('error', 'Bu kullanıcı admin tarafından görüntülenmeyi engellemiş.');
            redirect('admin/users');
        }
        
        // Veri erişim logu kaydet
        // privacy helper autoload'da yüklü
        log_data_access($id, 'view', 'user', ['fields' => ['profile']], 'Admin paneli kullanıcı görüntüleme');
        
        $data['user'] = $this->User_model->get_by_id($id);
        
        if (!$data['user']) {
            show_404();
        }
        
        // Veri minimizasyonu uygula
        // privacy helper autoload'da yüklü
        $data['user'] = sanitize_for_display($data['user']);
        
        // Gizlilik ayarları
        $data['privacy_settings'] = $this->User_privacy_model->get_or_create($id);
        
        // Kullanıcının kıyafetleri
        $data['user_clothing'] = $this->Clothing_item_model->get_all($id, []);
        $data['user_outfits'] = $this->Outfit_model->get_all($id, []);
        
        // Admin aktivite logu
        log_admin_activity('view_user', 'user', $id, ['fields' => ['profile']]);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/users/detail', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Kıyafetler listesi
     */
    public function clothing()
    {
        $data['title'] = 'Kıyafetler - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Pagination helper kullan
        $pagination = $this->get_pagination(30);
        $page = $pagination['page'];
        $per_page = $pagination['per_page'];
        $offset = $pagination['offset'];
        
        $data['clothing'] = $this->Clothing_item_model->get_all_admin_paginated($per_page, $offset);
        $data['total_clothing'] = $this->Clothing_item_model->count_all();
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_clothing'] / $per_page);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/clothing/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Kombinler listesi
     */
    public function outfits()
    {
        $data['title'] = 'Kombinler - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Pagination helper kullan
        $pagination = $this->get_pagination(30);
        $page = $pagination['page'];
        $per_page = $pagination['per_page'];
        $offset = $pagination['offset'];
        
        $data['outfits'] = $this->Outfit_model->get_all_paginated($per_page, $offset);
        $data['total_outfits'] = $this->Outfit_model->count_all();
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_outfits'] / $per_page);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/outfits/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * İstatistikler
     */
    public function statistics()
    {
        $data['title'] = 'İstatistikler - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Analytics verileri
        $data['analytics'] = $this->Analytics_model->get_all_statistics();
        
        // Anonimleştirilmiş istatistikler
        $date_from = $this->input->get('date_from') ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        $data['anonymous_stats'] = $this->Anonymous_analytics_model->get_anonymous_statistics($date_from, $date_to);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/statistics', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Hata İstatistikleri
     */
    public function error_statistics()
    {
        $data['title'] = 'Hata İstatistikleri - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        $period = $this->input->get('period') ?: 'daily';
        $days = (int)($this->input->get('days') ?: 30);
        $date_from = $this->input->get('date_from') ?: date('Y-m-d', strtotime("-{$days} days"));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        
        // Cache key
        $cache_key = 'error_stats_' . md5($period . $days . $date_from . $date_to);
        
        // Cache'den oku veya hesapla
        $stats = $this->cache_library->get($cache_key);
        if ($stats === false) {
            // Hata trend grafiği
            $stats['trend'] = $this->Error_statistics_model->get_error_trend($period, $days);
            
            // Hata tipi dağılımı
            $stats['type_distribution'] = $this->Error_statistics_model->get_error_type_distribution($date_from, $date_to);
            
            // En çok hata veren endpoint'ler
            $stats['top_endpoints'] = $this->Error_statistics_model->get_top_error_endpoints(10, $date_from, $date_to);
            
            // Hata çözülme süreleri
            $stats['resolution_times'] = $this->Error_statistics_model->get_resolution_times($date_from, $date_to);
            
            // Özet istatistikler
            $stats['summary'] = $this->Error_statistics_model->get_statistics_summary($days);
            
            // Cache'e kaydet (5 dakika)
            $this->cache_library->set($cache_key, $stats, 300);
        }
        
        $data['stats'] = $stats;
        $data['period'] = $period;
        $data['days'] = $days;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/errors/statistics', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Anonimleştirilmiş Raporlar
     */
    public function reports()
    {
        $data['title'] = 'Raporlar - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        $report_type = $this->input->get('type') ?: 'general';
        $date_from = $this->input->get('date_from') ?: date('Y-m-d', strtotime('-30 days'));
        $date_to = $this->input->get('date_to') ?: date('Y-m-d');
        $format = $this->input->get('format') ?: 'json';
        
        // Trend analizi
        $metric = $this->input->get('metric') ?: 'users';
        $period = $this->input->get('period') ?: 'daily';
        $data['trends'] = $this->Anonymous_analytics_model->get_trend_analysis($metric, $period, 30);
        
        // Toplu rapor
        $data['report'] = $this->Anonymous_analytics_model->get_aggregate_report($report_type, $date_from, $date_to);
        $data['report_type'] = $report_type;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        
        // Export isteği
        if ($this->input->get('export')) {
            $export_data = $this->Anonymous_analytics_model->export_anonymous_data($format, $report_type);
            
            if ($format === 'json') {
                $this->output->set_content_type('application/json');
                $this->output->set_output($export_data);
            } elseif ($format === 'csv') {
                $filename = 'anonymous_report_' . date('Y-m-d') . '.csv';
                $this->output->set_content_type('text/csv');
                $this->output->set_header('Content-Disposition: attachment; filename="' . $filename . '"');
                $this->output->set_output($export_data);
            }
            return;
        }
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/reports/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Sütun görünürlük tercihlerini kaydet (AJAX)
     */
    public function save_column_preferences()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $table_name = $this->input->post('table_name');
        $visible_columns = $this->input->post('visible_columns');
        
        if (!$table_name || !$visible_columns) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Eksik parametreler']));
            return;
        }
        
        $result = $this->advanced_filter_library->save_column_preferences(
            $this->admin_id,
            $table_name,
            $visible_columns
        );
        
        if ($result) {
            $this->output->set_output(json_encode(['success' => true, 'message' => 'Tercihler kaydedildi']));
        } else {
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Kayıt başarısız']));
        }
    }

    /**
     * Görünüm modunu değiştir
     */
    public function set_view_mode()
    {
        $view_mode = $this->input->post('view_mode');
        $table_name = $this->input->post('table_name');
        
        if ($view_mode && $table_name) {
            $this->session->set_userdata('view_mode_' . $table_name, $view_mode);
            $this->output->set_output(json_encode(['success' => true]));
        } else {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(['success' => false]));
        }
    }

    /**
     * Sistem ayarları
     */
    public function settings()
    {
        $data['title'] = 'Sistem Ayarları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        if ($this->input->post()) {
            // Ayarları kaydet
            $this->flash_and_redirect('Ayarlar başarıyla kaydedildi.', 'success', 'admin/settings');
        }
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * API Hataları listesi
     */
    public function errors()
    {
        $data['title'] = 'API Hataları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;

        // Filtreler
        $filters = [
            'status_code' => $this->input->get('status_code'),
            'error_code' => $this->input->get('error_code'),
            'endpoint' => $this->input->get('endpoint'),
            'status' => $this->input->get('status'),
            'severity' => $this->input->get('severity'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'search' => $this->input->get('search'),
            'order_by' => $this->input->get('order_by') ?: 'last_occurred_at',
            'order_dir' => $this->input->get('order_dir') ?: 'DESC'
        ];

        // Pagination helper kullan
        $pagination = $this->get_pagination(50);
        $page = $pagination['page'];
        $per_page = $pagination['per_page'];
        $offset = $pagination['offset'];
        
        // Modeli lazy load et
        $this->load_model('Api_error_model');

        $data['errors'] = $this->api_error_model->get_filtered($filters, $per_page, $offset);
        $data['total_errors'] = $this->api_error_model->count_filtered($filters);
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_errors'] / $per_page);
        $data['filters'] = $filters;

        // İstatistikler
        $data['stats'] = [
            'total' => $this->api_error_model->count_filtered([]),
            'last_24h' => $this->api_error_model->count_last_24h(),
            'critical' => $this->api_error_model->count_filtered(['severity' => 'critical', 'status' => 'new'])
        ];

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/errors/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Hata detay
     */
    public function error_detail($id)
    {
        $data['title'] = 'Hata Detayı - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Modeli lazy load et
        $this->load_model('Api_error_model');
        
        // Kayıt kontrolü helper metodunu kullan
        $data['error'] = $this->require_record(
            $this->api_error_model->get_by_id($id),
            'Hata kaydı bulunamadı.'
        );

        // Request body ve headers JSON'dan parse et
        if ($data['error']['request_body']) {
            $data['error']['request_body_parsed'] = json_decode($data['error']['request_body'], true);
        }
        if ($data['error']['request_headers']) {
            $data['error']['request_headers_parsed'] = json_decode($data['error']['request_headers'], true);
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/errors/detail', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Hata durumu güncelle
     */
    public function update_error_status()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $id = $this->input->post('id');
        $status = $this->input->post('status');
        $notes = $this->input->post('notes');

        if (!$id || !$status) {
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Eksik parametreler']));
            return;
        }

        $result = $this->Api_error_model->update_status($id, $status, $this->admin_id, $notes);

        if ($result) {
            $this->output->set_output(json_encode(['success' => true, 'message' => 'Hata durumu güncellendi']));
        } else {
            $this->output->set_status_header(500);
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Güncelleme başarısız']));
        }
    }

    /**
     * Admin aktivite logları
     */
    public function activity_logs()
    {
        $data['title'] = 'Aktivite Logları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;

        // Filtreler
        $filters = [
            'admin_id' => $this->input->get('admin_id'),
            'action' => $this->input->get('action'),
            'resource_type' => $this->input->get('resource_type'),
            'is_suspicious' => $this->input->get('is_suspicious'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'search' => $this->input->get('search')
        ];

        // Pagination helper kullan
        $pagination = $this->get_pagination(50);
        $page = $pagination['page'];
        $per_page = $pagination['per_page'];
        $offset = $pagination['offset'];

        // Modelleri lazy load et
        $this->load_model('Admin_activity_log_model');
        $this->load_model('User_model');
        
        $logs = $this->admin_activity_log_model->get_filtered($filters, $per_page, $offset);
        
        // Admin bilgilerini loglara ekle (N+1 query problemi düzeltildi)
        $admin_ids = array_unique(array_column($logs, 'admin_id'));
        $admins = [];
        if (!empty($admin_ids)) {
            // Tek sorguda tüm adminleri çek (WHERE IN kullanarak)
            $admins = $this->user_model->get_by_ids($admin_ids);
        }
        
        // Her log için admin bilgisini ekle
        foreach ($logs as &$log) {
            $log['admin_info'] = isset($admins[$log['admin_id']]) ? $admins[$log['admin_id']] : null;
        }
        
        $data['logs'] = $logs;
        $data['total_logs'] = $this->admin_activity_log_model->count_filtered($filters);
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_logs'] / $per_page);
        $data['filters'] = $filters;

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/activity_logs/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * 2FA doğrulama sayfası
     */
    public function verify_2fa()
    {
        if (!$this->session->userdata('admin_pending_2fa')) {
            redirect('admin/login');
        }

        $data['title'] = '2FA Doğrulama - GardıropPlus Admin';
        $data['user_id'] = $this->session->userdata('admin_pending_user_id');
        $data['email'] = $this->session->userdata('admin_pending_email');
        $data['method'] = $this->session->userdata('admin_pending_2fa_method') ?: 'totp';

        // Modelleri lazy load et
        $this->load_model('User_2fa_model');
        $this->load_model('User_model');
        
        // Kod yeniden gönder isteği
        if ($this->input->post('resend_code') && $data['method'] === 'email') {
            $code = $this->user_2fa_model->create_email_code($data['user_id']);
            $this->send_2fa_email($data['email'], $code);
            $data['success'] = 'Doğrulama kodu e-posta adresinize gönderildi.';
        }

        if ($this->input->post('code')) {
            $code = $this->input->post('code');
            $user_id = $this->session->userdata('admin_pending_user_id');
            
            $user = $this->user_model->get_by_id($user_id);
            $twofa = $this->user_2fa_model->get_or_create($user_id);
            
            $verified = false;
            
            if ($twofa['method'] === 'totp') {
                $verified = $this->user_2fa_model->verify_totp_code($user_id, $code);
                
                // TOTP başarısızsa yedek kod dene
                if (!$verified) {
                    $verified = $this->user_2fa_model->verify_backup_code($user_id, $code);
                }
            } elseif ($twofa['method'] === 'email') {
                $verified = $this->user_2fa_model->verify_email_code($user_id, $code);
            }
            
            if ($verified) {
                // 2FA doğrulandı, session temizle
                $this->session->unset_userdata(['admin_pending_2fa', 'admin_pending_user_id', 'admin_pending_email', 'admin_pending_2fa_method']);
                $this->session->set_userdata([
                    'admin_logged_in' => true,
                    'admin_id' => $user['id'],
                    'admin_email' => $user['email'],
                    'admin_name' => $user['first_name'] . ' ' . $user['last_name']
                ]);
                
                redirect('admin/dashboard');
            } else {
                $data['error'] = 'Geçersiz doğrulama kodu.';
            }
        }

        $this->load->view('admin/verify_2fa', $data);
    }

    /**
     * 2FA ayarları
     */
    public function twofa_settings()
    {
        $data['title'] = '2FA Ayarları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Modeli lazy load et
        $this->load_model('User_2fa_model');
        
        $user_id = $this->admin_id;
        $twofa = $this->user_2fa_model->get_or_create($user_id);
        $data['twofa'] = $twofa;
        
        // .env dosyasından SMTP bilgilerini oku (test için)
        $this->load->library('env_library');
        $env = $this->env_library;
        $data['smtp_host'] = $env->get('SMTP_HOST', 'BULUNAMADI');
        $data['smtp_port'] = $env->get('SMTP_PORT', 'BULUNAMADI');
        $data['smtp_username'] = $env->get('SMTP_USERNAME', 'BULUNAMADI');
        $data['smtp_encryption'] = $env->get('SMTP_ENCRYPTION', 'BULUNAMADI');

        if ($this->input->post('action')) {
            $action = $this->input->post('action');
            
            if ($action === 'enable_totp') {
                // TOTP secret oluştur
                $secret = $this->user_2fa_model->generate_totp_secret();
                $this->user_2fa_model->save_totp_secret($user_id, $secret);
                
                // Yedek kodlar oluştur
                $backup_codes = $this->user_2fa_model->generate_backup_codes($user_id);
                
                $this->load->library('google_authenticator');
                $ga = $this->google_authenticator;
                
                $data['secret'] = $secret;
                $data['backup_codes'] = $backup_codes;
                $data['qr_url'] = $ga->getQRCodeUrl(
                    $this->admin_data['email'],
                    $secret,
                    'GardıropPlus Admin'
                );
                $data['show_setup'] = true;
            } elseif ($action === 'verify_totp') {
                $code = $this->input->post('code');
                if ($this->user_2fa_model->verify_totp_code($user_id, $code)) {
                    $this->user_2fa_model->enable($user_id, 'totp');
                    $this->flash_and_redirect('2FA başarıyla etkinleştirildi.', 'success', 'admin/twofa_settings');
                } else {
                    $data['error'] = 'Geçersiz doğrulama kodu.';
                }
            } elseif ($action === 'disable') {
                $this->user_2fa_model->disable($user_id);
                $this->flash_and_redirect('2FA devre dışı bırakıldı.', 'success', 'admin/twofa_settings');
            } elseif ($action === 'enable_email') {
                // Email ile 2FA etkinleştir
                $code = $this->user_2fa_model->create_email_code($user_id);
                if ($this->send_2fa_email($this->admin_data['email'], $code)) {
                    $data['email_code_sent'] = true;
                    $data['pending_email_verification'] = true;
                } else {
                    $data['error'] = 'E-posta gönderilemedi. Lütfen e-posta ayarlarını kontrol edin.';
                }
            } elseif ($action === 'verify_email') {
                $code = $this->input->post('code');
                if ($this->user_2fa_model->verify_email_code($user_id, $code)) {
                    $this->user_2fa_model->enable($user_id, 'email');
                    $this->flash_and_redirect('2FA (E-posta) başarıyla etkinleştirildi.', 'success', 'admin/twofa_settings');
                } else {
                    $data['error'] = 'Geçersiz doğrulama kodu.';
                    $data['pending_email_verification'] = true;
                }
            }
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/twofa_settings', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Veri saklama politikaları
     */
    public function data_retention()
    {
        $data['title'] = 'Veri Saklama Politikaları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Modeli lazy load et
        $this->load_model('Data_retention_model');
        
        $data['policies'] = $this->data_retention_model->get_all_policies();
        $data['cleanup_logs'] = $this->data_retention_model->get_cleanup_logs(null, 20);

        if ($this->input->post('update_policy')) {
            $data_type = $this->input->post('data_type');
            $retention_days = (int)$this->input->post('retention_days');
            $auto_delete = $this->input->post('auto_delete') ? 1 : 0;
            
            $this->data_retention_model->update_policy($data_type, $retention_days, $auto_delete);
            $this->flash_and_redirect('Politika güncellendi.', 'success', 'admin/data_retention');
        }

        if ($this->input->post('run_cleanup')) {
            $data_type = $this->input->post('data_type');
            $result = $this->data_retention_model->cleanup_data($data_type);
            
            if ($result['success']) {
                $this->flash_and_redirect($result['records_deleted'] . ' kayıt silindi.', 'success', 'admin/data_retention');
            } else {
                $this->flash_and_redirect('Temizleme başarısız: ' . $result['message'], 'error', 'admin/data_retention');
            }
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/data_retention', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Gizlilik ayarları (kullanıcılar için)
     */
    public function privacy_settings()
    {
        $data['title'] = 'Gizlilik Ayarları - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // Modelleri lazy load et
        $this->load_model('User_privacy_model');
        
        // Tüm silme taleplerini getir
        $data['deletion_requests'] = $this->user_privacy_model->get_deletion_requests('pending');

        if ($this->input->post('process_deletion')) {
            $user_id = $this->input->post('user_id');
            $action = $this->input->post('action'); // approve, reject
            
            if ($action === 'approve') {
                $this->load_model('Data_retention_model');
                $this->data_retention_model->secure_delete_user_data($user_id);
                $this->flash_and_redirect('Kullanıcı verileri güvenli şekilde silindi.', 'success', 'admin/privacy_settings');
            } elseif ($action === 'reject') {
                $reason = $this->input->post('rejection_reason');
                $this->load_model('Gdpr_model');
                $request = $this->gdpr_model->get_user_deletion_request($user_id);
                if ($request) {
                    $this->gdpr_model->process_deletion_request(
                        $request['id'],
                        'rejected',
                        $this->admin_id,
                        null,
                        $reason
                    );
                }
                $this->flash_and_redirect('Silme talebi reddedildi.', 'success', 'admin/privacy_settings');
            }
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/privacy_settings', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * 2FA email gönder (PHPMailer kullanarak)
     */
    private function send_2fa_email($email, $code)
    {
        try {
            // .env dosyasını yükle
            $this->load->library('env_library');
            $env = $this->env_library;
            
            // PHPMailer library'sini yükle
            $this->load->library('phpmailer_lib');
            $mail = $this->phpmailer_lib->load();
            
            // .env dosyasından SMTP ayarlarını oku (Env_library kullanarak)
            $smtp_host = $env->get('SMTP_HOST', 'srv.igartista.com');
            $smtp_port = (int)$env->get('SMTP_PORT', 465);
            $smtp_username = $env->get('SMTP_USERNAME', 'ig@simurgwebtasarim.com');
            $smtp_password = $env->get('SMTP_PASSWORD', '');
            $smtp_encryption = $env->get('SMTP_ENCRYPTION', 'ssl');
            
            // Debug: Okunan değerleri logla (şifre hariç)
            if (ENVIRONMENT === 'development') {
                log_message('debug', 'SMTP Ayarları: Host=' . $smtp_host . ', Port=' . $smtp_port . ', User=' . $smtp_username . ', Encryption=' . $smtp_encryption);
            }
            
            // PHPMailer ayarları
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            
            // Port 465 için SSL, 587 için TLS
            if ($smtp_port == 465) {
                $mail->SMTPSecure = 'ssl'; // Port 465 için SSL
            } elseif ($smtp_encryption == 'tls') {
                $mail->SMTPSecure = 'tls'; // Port 587 için TLS
            } else {
                $mail->SMTPSecure = $smtp_encryption; // 'ssl' veya 'tls'
            }
            
            $mail->Port = $smtp_port;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 30; // Timeout süresini artır (saniye)
            $mail->SMTPKeepAlive = false; // Her email için yeni bağlantı
            
            // SSL sertifika doğrulamasını atla (sertifika adı eşleşmese bile çalışsın)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Debug modu (development için - production'da kapatılmalı)
            if (ENVIRONMENT === 'development') {
                $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client and server
                $mail->Debugoutput = function($str, $level) {
                    log_message('debug', 'PHPMailer: ' . $str);
                };
            }
            
            // Email içeriği
            $mail->setFrom($smtp_username, 'GardıropPlus Admin');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = '2FA Doğrulama Kodu - GardıropPlus Admin';
            
            $message = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .code-box { background: #f4f4f4; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px; }
                    .warning { color: #d9534f; font-size: 14px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>2FA Doğrulama Kodu</h2>
                    <p>Admin paneline giriş için doğrulama kodunuz:</p>
                    <div class="code-box">' . htmlspecialchars($code) . '</div>
                    <p>Bu kod 10 dakika geçerlidir.</p>
                    <p class="warning">Bu kodu kimseyle paylaşmayın. Eğer bu işlemi siz yapmadıysanız, lütfen hemen sistem yöneticisi ile iletişime geçin.</p>
                    <p>İyi günler,<br>GardıropPlus Admin Ekibi</p>
                </div>
            </body>
            </html>';
            
            $mail->Body = $message;
            $mail->AltBody = '2FA Doğrulama Kodu: ' . $code . ' (10 dakika geçerlidir)';
            
            // Email gönder
            $result = $mail->send();
            
            if (!$result) {
                log_message('error', '2FA Email gönderme hatası: ' . $mail->ErrorInfo);
            }
            
            return $result;
        } catch (Exception $e) {
            log_message('error', '2FA Email gönderme hatası: ' . $e->getMessage());
            return false;
        }
    }
}

