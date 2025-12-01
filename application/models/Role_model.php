<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Role Model
 * RBAC (Role-Based Access Control) sistemi
 */
class Role_model extends CI_Model
{
    protected $roles_table = 'roles';
    protected $user_roles_table = 'user_roles';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Tüm rolleri getir
     */
    public function get_all($active_only = true)
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        return $this->db->get($this->roles_table)->result_array();
    }

    /**
     * Role ID'ye göre getir
     */
    public function get_by_id($id)
    {
        return $this->db->get_where($this->roles_table, ['id' => $id])->row_array();
    }

    /**
     * Role name'e göre getir
     */
    public function get_by_name($name)
    {
        return $this->db->get_where($this->roles_table, ['name' => $name])->row_array();
    }

    /**
     * Kullanıcının rollerini getir
     */
    public function get_user_roles($user_id)
    {
        $this->db->select('r.*, ur.assigned_at, ur.expires_at');
        $this->db->from($this->user_roles_table . ' ur');
        $this->db->join($this->roles_table . ' r', 'r.id = ur.role_id');
        $this->db->where('ur.user_id', $user_id);
        $this->db->where('r.is_active', 1);
        $this->db->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())', null, false);
        return $this->db->get()->result_array();
    }

    /**
     * Kullanıcıya rol ata
     */
    public function assign_role($user_id, $role_id, $assigned_by = null, $expires_at = null)
    {
        // Mevcut rolü kontrol et
        $existing = $this->db->get_where($this->user_roles_table, [
            'user_id' => $user_id,
            'role_id' => $role_id
        ])->row_array();

        if ($existing) {
            // Mevcut rolü güncelle
            $data = [
                'assigned_by' => $assigned_by,
                'assigned_at' => date('Y-m-d H:i:s')
            ];
            if ($expires_at) {
                $data['expires_at'] = $expires_at;
            }
            $this->db->where('id', $existing['id']);
            return $this->db->update($this->user_roles_table, $data);
        } else {
            // Yeni rol ata
            $data = [
                'user_id' => $user_id,
                'role_id' => $role_id,
                'assigned_by' => $assigned_by,
                'expires_at' => $expires_at
            ];
            return $this->db->insert($this->user_roles_table, $data);
        }
    }

    /**
     * Kullanıcıdan rol kaldır
     */
    public function remove_role($user_id, $role_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('role_id', $role_id);
        return $this->db->delete($this->user_roles_table);
    }

    /**
     * Kullanıcının tüm rollerini kaldır
     */
    public function remove_all_roles($user_id)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->user_roles_table);
    }

    /**
     * Kullanıcının belirli bir role sahip olup olmadığını kontrol et
     */
    public function has_role($user_id, $role_name)
    {
        $this->db->select('ur.*');
        $this->db->from($this->user_roles_table . ' ur');
        $this->db->join($this->roles_table . ' r', 'r.id = ur.role_id');
        $this->db->where('ur.user_id', $user_id);
        $this->db->where('r.name', $role_name);
        $this->db->where('r.is_active', 1);
        $this->db->where('(ur.expires_at IS NULL OR ur.expires_at > NOW())', null, false);
        return $this->db->get()->num_rows() > 0;
    }

    /**
     * Kullanıcının yetkisini kontrol et
     */
    public function has_permission($user_id, $resource, $action)
    {
        // Kullanıcının rolleri
        $roles = $this->get_user_roles($user_id);

        foreach ($roles as $role) {
            $permissions = json_decode($role['permissions'], true);

            // Super admin kontrolü
            if (isset($permissions['*']) && $permissions['*'] === '*') {
                return true;
            }

            // Resource kontrolü
            if (isset($permissions[$resource])) {
                // Tüm action'lara izin
                if ($permissions[$resource] === '*') {
                    return true;
                }
                // Belirli action'lara izin
                if (is_array($permissions[$resource]) && in_array($action, $permissions[$resource])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Kullanıcının tüm yetkilerini getir
     */
    public function get_user_permissions($user_id)
    {
        $roles = $this->get_user_roles($user_id);
        $permissions = [];

        foreach ($roles as $role) {
            $role_permissions = json_decode($role['permissions'], true);
            if ($role_permissions) {
                // Super admin
                if (isset($role_permissions['*']) && $role_permissions['*'] === '*') {
                    return ['*' => '*'];
                }
                // Diğer yetkileri birleştir
                foreach ($role_permissions as $resource => $actions) {
                    if (!isset($permissions[$resource])) {
                        $permissions[$resource] = [];
                    }
                    if ($actions === '*') {
                        $permissions[$resource] = '*';
                    } elseif (is_array($actions)) {
                        $permissions[$resource] = array_unique(array_merge(
                            $permissions[$resource] === '*' ? [] : ($permissions[$resource] === '*' ? [] : $permissions[$resource]),
                            $actions
                        ));
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * Role oluştur
     */
    public function create($data)
    {
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions'], JSON_UNESCAPED_UNICODE);
        }
        return $this->db->insert($this->roles_table, $data);
    }

    /**
     * Role güncelle
     */
    public function update($id, $data)
    {
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions'], JSON_UNESCAPED_UNICODE);
        }
        $this->db->where('id', $id);
        return $this->db->update($this->roles_table, $data);
    }

    /**
     * Role sil (soft delete)
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->roles_table, ['is_active' => 0]);
    }
}

