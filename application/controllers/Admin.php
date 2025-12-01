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
        $this->load->model('User_model');
        $this->load->model('Clothing_item_model');
        $this->load->model('Outfit_model');
        $this->load->model('Analytics_model');
        $this->load->model('Api_error_model');
        $this->load->model('Admin_activity_log_model');
        $this->load->model('Gdpr_model');
        $this->load->model('Role_model');
        $this->load->model('User_2fa_model');
        $this->load->model('User_privacy_model');
        $this->load->model('Data_retention_model');
        
        // Admin authentication kontrolü
        $this->check_admin_auth();
    }

    /**
     * Admin authentication kontrolü
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
            $this->admin_data = $this->User_model->get_by_id($this->admin_id);
            
            // Admin yetkisi kontrolü (double check)
            if ($this->admin_data) {
                $is_admin = false;
                if (isset($this->admin_data['role']) && $this->admin_data['role'] === 'admin') {
                    $is_admin = true;
                } elseif (isset($this->admin_data['is_admin']) && ($this->admin_data['is_admin'] == 1 || $this->admin_data['is_admin'] === true)) {
                    $is_admin = true;
                }
                
                if (!$is_admin) {
                    $this->session->unset_userdata(['admin_logged_in', 'admin_id', 'admin_email', 'admin_name']);
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
            $this->session->set_flashdata('error', 'Email ve şifre gereklidir.');
            redirect('admin/login');
        }

        $user = $this->User_model->get_by_email($email);
        
        if (!$user) {
            $this->session->set_flashdata('error', 'Geçersiz email veya şifre.');
            redirect('admin/login');
        }

        // Şifre kontrolü
        if (!password_verify($password, $user['password'])) {
            $this->session->set_flashdata('error', 'Geçersiz email veya şifre.');
            redirect('admin/login');
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
            $this->session->set_flashdata('error', 'Bu sayfaya erişim yetkiniz yok. Sadece admin kullanıcılar giriş yapabilir.');
            redirect('admin/login');
        }

        // 2FA kontrolü
        if ($this->User_2fa_model->is_enabled($user['id'])) {
            // 2FA kodu girilmesi gerekiyor
            $this->session->set_userdata([
                'admin_pending_2fa' => true,
                'admin_pending_user_id' => $user['id'],
                'admin_pending_email' => $user['email']
            ]);
            redirect('admin/verify_2fa');
        }

        // Admin session oluştur
        $this->session->set_userdata([
            'admin_logged_in' => true,
            'admin_id' => $user['id'],
            'admin_email' => $user['email'],
            'admin_name' => $user['first_name'] . ' ' . $user['last_name']
        ]);

        redirect('admin/dashboard');
    }

    /**
     * Admin çıkış
     */
    public function logout()
    {
        $this->session->unset_userdata(['admin_logged_in', 'admin_id', 'admin_email', 'admin_name']);
        redirect('admin/login');
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        $data['title'] = 'Dashboard - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        // İstatistikler
        $data['stats'] = [
            'total_users' => $this->User_model->count_all(),
            'total_clothing' => $this->Clothing_item_model->count_all(),
            'total_outfits' => $this->Outfit_model->count_all(),
            'active_users' => $this->User_model->count_active_users(),
        ];
        
        // Son aktiviteler
        $data['recent_users'] = $this->User_model->get_recent(5);
        $data['recent_clothing'] = $this->Clothing_item_model->get_recent(5);
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/dashboard', $data);
        $this->load->view('admin/layout/footer', $data);
    }

    /**
     * Kullanıcılar listesi
     */
    public function users()
    {
        $data['title'] = 'Kullanıcılar - GardıropPlus Admin';
        $data['admin'] = $this->admin_data;
        
        $page = $this->input->get('page') ?: 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $users = $this->User_model->get_all_paginated($per_page, $offset);
        
        // Veri minimizasyonu ve gizlilik kontrolü
        $this->load->helper('privacy');
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
        $this->load->helper('privacy');
        log_data_access($id, $this->admin_id, 'view', 'user', ['fields' => ['profile']], 'Admin paneli kullanıcı görüntüleme', 'legal_obligation');
        
        $data['user'] = $this->User_model->get_by_id($id);
        
        if (!$data['user']) {
            show_404();
        }
        
        // Veri minimizasyonu uygula
        $this->load->helper('privacy');
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
        
        $page = $this->input->get('page') ?: 1;
        $per_page = 30;
        $offset = ($page - 1) * $per_page;
        
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
        
        $page = $this->input->get('page') ?: 1;
        $per_page = 30;
        $offset = ($page - 1) * $per_page;
        
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
        
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/statistics', $data);
        $this->load->view('admin/layout/footer', $data);
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
            $this->session->set_flashdata('success', 'Ayarlar başarıyla kaydedildi.');
            redirect('admin/settings');
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

        // Pagination
        $page = $this->input->get('page') ?: 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $data['errors'] = $this->Api_error_model->get_filtered($filters, $per_page, $offset);
        $data['total_errors'] = $this->Api_error_model->count_filtered($filters);
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_errors'] / $per_page);
        $data['filters'] = $filters;

        // İstatistikler
        $data['stats'] = [
            'total' => $this->Api_error_model->count_filtered([]),
            'last_24h' => $this->Api_error_model->count_last_24h(),
            'critical' => $this->Api_error_model->count_filtered(['severity' => 'critical', 'status' => 'new'])
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
        $data['error'] = $this->Api_error_model->get_by_id($id);

        if (!$data['error']) {
            show_404();
        }

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

        // Pagination
        $page = $this->input->get('page') ?: 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $logs = $this->Admin_activity_log_model->get_filtered($filters, $per_page, $offset);
        
        // Admin bilgilerini loglara ekle
        $admin_ids = array_unique(array_column($logs, 'admin_id'));
        $admins = [];
        if (!empty($admin_ids)) {
            foreach ($admin_ids as $admin_id) {
                $admin = $this->User_model->get_by_id($admin_id);
                if ($admin) {
                    $admins[$admin_id] = $admin;
                }
            }
        }
        
        // Her log için admin bilgisini ekle
        foreach ($logs as &$log) {
            $log['admin_info'] = isset($admins[$log['admin_id']]) ? $admins[$log['admin_id']] : null;
        }
        
        $data['logs'] = $logs;
        $data['total_logs'] = $this->Admin_activity_log_model->count_filtered($filters);
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

        if ($this->input->post('code')) {
            $code = $this->input->post('code');
            $user_id = $this->session->userdata('admin_pending_user_id');
            
            $user = $this->User_model->get_by_id($user_id);
            $twofa = $this->User_2fa_model->get_or_create($user_id);
            
            $verified = false;
            
            if ($twofa['method'] === 'totp') {
                $verified = $this->User_2fa_model->verify_totp_code($user_id, $code);
                
                // TOTP başarısızsa yedek kod dene
                if (!$verified) {
                    $verified = $this->User_2fa_model->verify_backup_code($user_id, $code);
                }
            } elseif ($twofa['method'] === 'email') {
                $verified = $this->User_2fa_model->verify_email_code($user_id, $code);
            }
            
            if ($verified) {
                // 2FA doğrulandı, session oluştur
                $this->session->unset_userdata(['admin_pending_2fa', 'admin_pending_user_id', 'admin_pending_email']);
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
        
        $user_id = $this->admin_id;
        $twofa = $this->User_2fa_model->get_or_create($user_id);
        $data['twofa'] = $twofa;

        if ($this->input->post('action')) {
            $action = $this->input->post('action');
            
            if ($action === 'enable_totp') {
                // TOTP secret oluştur
                $secret = $this->User_2fa_model->generate_totp_secret();
                $this->User_2fa_model->save_totp_secret($user_id, $secret);
                
                // Yedek kodlar oluştur
                $backup_codes = $this->User_2fa_model->generate_backup_codes($user_id);
                
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
                if ($this->User_2fa_model->verify_totp_code($user_id, $code)) {
                    $this->User_2fa_model->enable($user_id, 'totp');
                    $this->session->set_flashdata('success', '2FA başarıyla etkinleştirildi.');
                    redirect('admin/twofa_settings');
                } else {
                    $data['error'] = 'Geçersiz doğrulama kodu.';
                }
            } elseif ($action === 'disable') {
                $this->User_2fa_model->disable($user_id);
                $this->session->set_flashdata('success', '2FA devre dışı bırakıldı.');
                redirect('admin/twofa_settings');
            } elseif ($action === 'send_email_code') {
                $code = $this->User_2fa_model->create_email_code($user_id);
                // Email gönder (PHPMailer kullanılabilir)
                $this->load->library('phpmailer_lib');
                // Email gönderme kodu buraya eklenecek
                $data['email_code_sent'] = true;
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
        
        $data['policies'] = $this->Data_retention_model->get_all_policies();
        $data['cleanup_logs'] = $this->Data_retention_model->get_cleanup_logs(null, 20);

        if ($this->input->post('update_policy')) {
            $data_type = $this->input->post('data_type');
            $retention_days = (int)$this->input->post('retention_days');
            $auto_delete = $this->input->post('auto_delete') ? 1 : 0;
            
            $this->Data_retention_model->update_policy($data_type, $retention_days, $auto_delete);
            $this->session->set_flashdata('success', 'Politika güncellendi.');
            redirect('admin/data_retention');
        }

        if ($this->input->post('run_cleanup')) {
            $data_type = $this->input->post('data_type');
            $result = $this->Data_retention_model->cleanup_data($data_type);
            
            if ($result['success']) {
                $this->session->set_flashdata('success', $result['records_deleted'] . ' kayıt silindi.');
            } else {
                $this->session->set_flashdata('error', 'Temizleme başarısız: ' . $result['message']);
            }
            redirect('admin/data_retention');
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
        
        // Tüm silme taleplerini getir
        $data['deletion_requests'] = $this->User_privacy_model->get_deletion_requests('pending');

        if ($this->input->post('process_deletion')) {
            $user_id = $this->input->post('user_id');
            $action = $this->input->post('action'); // approve, reject
            
            if ($action === 'approve') {
                $this->load->model('Data_retention_model');
                $this->Data_retention_model->secure_delete_user_data($user_id);
                $this->session->set_flashdata('success', 'Kullanıcı verileri güvenli şekilde silindi.');
            } elseif ($action === 'reject') {
                $reason = $this->input->post('rejection_reason');
                $this->load->model('Gdpr_model');
                $request = $this->Gdpr_model->get_user_deletion_request($user_id);
                if ($request) {
                    $this->Gdpr_model->process_deletion_request(
                        $request['id'],
                        'rejected',
                        $this->admin_id,
                        null,
                        $reason
                    );
                }
                $this->session->set_flashdata('success', 'Silme talebi reddedildi.');
            }
            redirect('admin/privacy_settings');
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/privacy_settings', $data);
        $this->load->view('admin/layout/footer', $data);
    }
}

