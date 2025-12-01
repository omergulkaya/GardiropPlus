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
        
        $data['users'] = $this->User_model->get_all_paginated($per_page, $offset);
        $data['total_users'] = $this->User_model->count_all();
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_users'] / $per_page);
        
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
        $data['user'] = $this->User_model->get_by_id($id);
        
        if (!$data['user']) {
            show_404();
        }
        
        // Kullanıcının kıyafetleri
        $data['user_clothing'] = $this->Clothing_item_model->get_all($id, []);
        $data['user_outfits'] = $this->Outfit_model->get_all($id, []);
        
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

        $data['logs'] = $this->Admin_activity_log_model->get_filtered($filters, $per_page, $offset);
        $data['total_logs'] = $this->Admin_activity_log_model->count_filtered($filters);
        $data['current_page'] = $page;
        $data['per_page'] = $per_page;
        $data['total_pages'] = ceil($data['total_logs'] / $per_page);
        $data['filters'] = $filters;

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/activity_logs/index', $data);
        $this->load->view('admin/layout/footer', $data);
    }
}

