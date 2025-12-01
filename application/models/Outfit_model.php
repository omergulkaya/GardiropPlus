<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Outfit_model extends CI_Model
{
    protected $table = 'outfits';
    protected $items_table = 'outfit_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Clothing_item_model');
    }

    /**
     * Tüm kombinleri getir (filtreleme ile)
     */
    public function get_all($user_id, $filters = [])
    {
        $this->db->where('user_id', $user_id);
        if (isset($filters['style']) && $filters['style'] !== '') {
            $this->db->where('style', $filters['style']);
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $this->db->where('is_favorite', $filters['is_favorite']);
        }

        $this->db->order_by('created_at', 'DESC');
        $outfits = $this->db->get($this->table)->result_array();
// Her bir outfit için kıyafetleri getir
        foreach ($outfits as &$outfit) {
            $outfit = $this->enrich_outfit($outfit);
        }

        return $outfits;
    }

    /**
     * ID'ye göre kombin getir
     */
    public function get_by_id($id, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        $outfit = $this->db->get($this->table)->row_array();
        if ($outfit) {
            $outfit = $this->enrich_outfit($outfit);
        }

        return $outfit;
    }

    /**
     * Kombin oluştur
     */
    public function create($data)
    {
        $outfit_id = $this->generate_uuid();
        $main_data = [
            'id' => $outfit_id,
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'style' => $data['style'],
            'is_favorite' => $data['is_favorite'] ?? 0
        ];
        $this->db->trans_start();
// Ana kaydı ekle
        $this->db->insert($this->table, $main_data);
// Kıyafetleri ekle
        if (isset($data['items']) && is_array($data['items'])) {
            $this->save_items($outfit_id, $data['items']);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $outfit_id : false;
    }

    /**
     * Kombin güncelle
     */
    public function update($id, $data, $user_id)
    {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $main_data = [];
        $allowed_fields = ['name', 'style', 'is_favorite'];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $main_data[$field] = $data[$field];
            }
        }

        $this->db->trans_start();
        if (!empty($main_data)) {
            $this->db->update($this->table, $main_data);
        }

        // Kıyafetleri güncelle
        if (isset($data['items'])) {
            $this->delete_items($id);
            if (is_array($data['items']) && !empty($data['items'])) {
                $this->save_items($id, $data['items']);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Kombin sil
     */
    public function delete($id, $user_id)
    {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->table);
    }

    /**
     * Outfit'i zenginleştir (kıyafetleri ekle)
     */
    private function enrich_outfit($outfit)
    {
        // Kıyafet ID'lerini getir
        $item_ids = $this->get_item_ids($outfit['id']);
// Kıyafet detaylarını getir
        $items = [];
        foreach ($item_ids as $item_id) {
            $item = $this->Clothing_item_model->get_by_id($item_id);
            if ($item) {
                $items[] = $item;
            }
        }

        $outfit['items'] = $items;
        return $outfit;
    }

    /**
     * Outfit'e ait kıyafet ID'lerini getir
     */
    private function get_item_ids($outfit_id)
    {
        $items = $this->db->get_where($this->items_table, ['outfit_id' => $outfit_id])->result_array();
        return array_column($items, 'clothing_item_id');
    }

    /**
     * Outfit kıyafetlerini kaydet
     */
    private function save_items($outfit_id, $item_ids)
    {
        foreach ($item_ids as $item_id) {
            $this->db->insert($this->items_table, [
                'outfit_id' => $outfit_id,
                'clothing_item_id' => $item_id
            ]);
        }
    }

    /**
     * Outfit kıyafetlerini sil
     */
    private function delete_items($outfit_id)
    {
        $this->db->delete($this->items_table, ['outfit_id' => $outfit_id]);
    }

    /**
     * UUID oluştur
     */
    private function generate_uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Tüm kombinleri say (admin için)
     */
    public function count_all()
    {
        return $this->db->count_all($this->table);
    }

    /**
     * Son kombinleri getir (admin için)
     */
    public function get_recent($limit = 10)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $outfits = $this->db->get($this->table)->result_array();
        foreach ($outfits as &$outfit) {
            $outfit = $this->enrich_outfit($outfit);
        }
        return $outfits;
    }

    /**
     * Pagination ile tüm kombinleri getir (admin için)
     */
    public function get_all_paginated($per_page = 30, $offset = 0)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($per_page, $offset);
        $outfits = $this->db->get($this->table)->result_array();
        foreach ($outfits as &$outfit) {
            $outfit = $this->enrich_outfit($outfit);
        }
        return $outfits;
    }
}
