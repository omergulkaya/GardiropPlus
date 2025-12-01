<?php

defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Yeni kullanıcı oluştur
     */
    public function create($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * ID'ye göre kullanıcı getir
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    /**
     * Email'e göre kullanıcı getir
     */
    public function get_by_email($email)
    {
        return $this->db->get_where($this->table, ['email' => $email])->row_array();
    }

    /**
     * Kullanıcı güncelle
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Kullanıcı sil
     */
    public function delete($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Email verification token kaydet
     */
    public function save_verification_token($user_id, $email, $token, $expires_at)
    {
        $this->db->insert('email_verifications', [
            'user_id' => $user_id,
            'email' => $email,
            'verification_token' => $token,
            'expires_at' => $expires_at
        ]);
    }

    /**
     * Email verification token doğrula
     */
    public function verify_email_token($token)
    {
        $verification = $this->db
            ->where('verification_token', $token)
            ->where('verified', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('email_verifications')
            ->row_array();
        if ($verification) {
        // Email'i doğrula
            $this->db->where('id', $verification['user_id'])
                ->update('users', ['email_verified' => true]);
        // Verification'ı işaretle
            $this->db->where('id', $verification['id'])
                ->update('email_verifications', ['verified' => true]);
            return true;
        }

        return false;
    }

    /**
     * Password reset token kaydet
     */
    public function save_password_reset_token($user_id, $token, $expires_at)
    {
        $this->db->insert('password_reset_tokens', [
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at
        ]);
    }

    /**
     * Password reset token doğrula
     */
    public function verify_password_reset_token($token)
    {
        $reset = $this->db
            ->where('token', $token)
            ->where('used', false)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('password_reset_tokens')
            ->row_array();
        return $reset;
    }

    /**
     * Password reset token'ı kullanıldı olarak işaretle
     */
    public function mark_password_reset_used($token)
    {
        $this->db->where('token', $token)
            ->update('password_reset_tokens', ['used' => true]);
    }

    /**
     * Failed login attempts artır
     */
    public function increment_failed_attempts($user_id)
    {
        $this->db->set('failed_login_attempts', 'failed_login_attempts + 1', false)
            ->where('id', $user_id)
            ->update($this->table);
    }

    /**
     * Failed login attempts sıfırla
     */
    public function reset_failed_attempts($user_id)
    {
        $this->db->where('id', $user_id)
            ->update($this->table, [
                'failed_login_attempts' => 0,
                'locked_until' => null
            ]);
    }

    /**
     * Account'u kilitle
     */
    public function lock_account($user_id, $lock_until)
    {
        $this->db->where('id', $user_id)
            ->update($this->table, ['locked_until' => $lock_until]);
    }

    /**
     * Account kilitli mi kontrol et
     */
    public function is_account_locked($user_id)
    {
        $user = $this->get_by_id($user_id);
        if ($user && $user['locked_until']) {
            return strtotime($user['locked_until']) > time();
        }
        return false;
    }

    /**
     * Tüm kullanıcıları say
     */
    public function count_all()
    {
        return $this->db->count_all($this->table);
    }

    /**
     * Aktif kullanıcıları say
     * Not: last_login kolonu yoksa, email_verified olan kullanıcıları aktif sayar
     */
    public function count_active_users()
    {
        // last_login kolonu varsa onu kullan, yoksa email_verified kullan
        $columns = $this->db->list_fields($this->table);
        
        if (in_array('last_login', $columns)) {
            // last_login kolonu varsa son 30 gün içinde giriş yapanları say
            $this->db->where('last_login >=', date('Y-m-d H:i:s', strtotime('-30 days')));
            return $this->db->count_all_results($this->table);
        } else {
            // last_login yoksa email_verified olan kullanıcıları aktif say
            $this->db->where('email_verified', 1);
            return $this->db->count_all_results($this->table);
        }
    }

    /**
     * Son kullanıcıları getir
     */
    public function get_recent($limit = 10)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Pagination ile tüm kullanıcıları getir
     */
    public function get_all_paginated($per_page = 20, $offset = 0)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($per_page, $offset);
        return $this->db->get($this->table)->result_array();
    }
}
