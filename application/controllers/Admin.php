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

        // Admin kontrolü (email'e göre veya role field'ı varsa)
        // Şimdilik tüm kullanıcılar admin olabilir, production'da role kontrolü eklenmeli
        if (!password_verify($password, $user['password'])) {
            $this->session->set_flashdata('error', 'Geçersiz email veya şifre.');
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
}

