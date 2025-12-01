<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Shopping_list_model extends CI_Model
{
    protected $table = 'shopping_list_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Alışveriş listesi öğelerini getir
     */
    public function get_all($user_id, $filters = [])
    {
        $this->db->where('user_id', $user_id);
        
        // Status filter
        if (isset($filters['is_purchased']) && $filters['is_purchased'] !== '') {
            $this->db->where('is_purchased', $filters['is_purchased']);
        }
        
        // Category filter
        if (isset($filters['category']) && $filters['category'] !== '') {
            $this->db->where('category', $filters['category']);
        }

        $this->db->order_by('is_purchased', 'ASC');
        $this->db->order_by('created_at', 'DESC');
        
        if (isset($filters['limit'])) {
            $this->db->limit($filters['limit']);
        }
        if (isset($filters['offset'])) {
            $this->db->offset($filters['offset']);
        }

        return $this->db->get($this->table)->result_array();
    }

    /**
     * ID'ye göre öğe getir
     */
    public function get_by_id($id, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->get($this->table)->row_array();
    }

    /**
     * Öğe oluştur
     */
    public function create($data)
    {
        $insert_data = [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_purchased' => false
        ];

        $this->db->insert($this->table, $insert_data);
        return $this->db->insert_id();
    }

    /**
     * Öğe güncelle
     */
    public function update($id, $data, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        $update_data = [];
        if (isset($data['name'])) {
            $update_data['name'] = $data['name'];
        }
        if (isset($data['category'])) {
            $update_data['category'] = $data['category'];
        }
        if (isset($data['notes'])) {
            $update_data['notes'] = $data['notes'];
        }
        if (isset($data['is_purchased'])) {
            $update_data['is_purchased'] = $data['is_purchased'];
        }

        return $this->db->update($this->table, $update_data);
    }

    /**
     * Öğe sil
     */
    public function delete($id, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->delete($this->table);
    }

    /**
     * Status güncelle (purchased/unpurchased)
     */
    public function update_status($id, $is_purchased, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->update($this->table, ['is_purchased' => $is_purchased]);
    }

    /**
     * Bulk create
     */
    public function bulk_create($user_id, $items)
    {
        $insert_data = [];
        foreach ($items as $item) {
            $insert_data[] = [
                'user_id' => $user_id,
                'name' => $item['name'],
                'category' => $item['category'] ?? null,
                'notes' => $item['notes'] ?? null,
                'is_purchased' => false
            ];
        }

        if (empty($insert_data)) {
            return false;
        }

        return $this->db->insert_batch($this->table, $insert_data);
    }

    /**
     * Bulk update status
     */
    public function bulk_update_status($user_id, $ids, $is_purchased)
    {
        if (empty($ids)) {
            return false;
        }

        $this->db->where('user_id', $user_id);
        $this->db->where_in('id', $ids);

        return $this->db->update($this->table, ['is_purchased' => $is_purchased]);
    }

    /**
     * Bulk delete
     */
    public function bulk_delete($user_id, $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $this->db->where('user_id', $user_id);
        $this->db->where_in('id', $ids);

        return $this->db->delete($this->table);
    }

    /**
     * Statistics
     */
    public function get_statistics($user_id)
    {
        $this->db->where('user_id', $user_id);
        $total = $this->db->count_all_results($this->table);

        $this->db->where('user_id', $user_id);
        $this->db->where('is_purchased', true);
        $purchased = $this->db->count_all_results($this->table);

        $this->db->where('user_id', $user_id);
        $this->db->where('is_purchased', false);
        $pending = $this->db->count_all_results($this->table);

        return [
            'total' => $total,
            'purchased' => $purchased,
            'pending' => $pending,
            'completion_rate' => $total > 0 ? round(($purchased / $total) * 100, 2) : 0
        ];
    }
}

