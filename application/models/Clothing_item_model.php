<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Clothing_item_model extends CI_Model
{
    protected $table = 'clothing_items';
    protected $colors_table = 'clothing_item_colors';
    protected $seasons_table = 'clothing_item_seasons';
    protected $styles_table = 'clothing_item_styles';
    protected $event_types_table = 'clothing_item_event_types';
    protected $custom_tags_table = 'clothing_item_custom_tags';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Tüm kıyafetleri getir (filtreleme ile)
     */
    public function get_all($user_id, $filters = [])
    {
        $this->db->where('user_id', $user_id);
        if (isset($filters['category']) && $filters['category'] !== '') {
            $this->db->where('category', $filters['category']);
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $this->db->where('is_favorite', $filters['is_favorite']);
        }

        $this->db->order_by('date_added', 'DESC');
        $items = $this->db->get($this->table)->result_array();
// Batch eager loading ile N+1 query önleme
        $items = $this->enrich_items_batch($items);
        return $items;
    }

    /**
     * Pagination ile kıyafetleri getir (search ve filter ile)
     */
    public function get_all_paginated($user_id, $filters = [], $page = 1, $limit = 20)
    {
        // Base query - Önce from() çağrısı yap
        $this->db->from($this->table . ' ci');
        $this->db->where('ci.user_id', $user_id);
// Search filter - Full-text search (MATCH AGAINST) veya LIKE
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
// Full-text search kullan (eğer FULLTEXT index varsa)
            // MySQL'de FULLTEXT index varsa MATCH AGAINST kullan, yoksa LIKE kullan
            $use_fulltext = isset($filters['use_fulltext']) ? $filters['use_fulltext'] : true;
            if ($use_fulltext) {
            // Full-text search (MATCH AGAINST) - daha hızlı ve relevanslı
                $this->db->where("MATCH(ci.name, ci.description, ci.brand, ci.material, ci.notes) AGAINST(? IN BOOLEAN MODE)", $search);
            } else {
            // Fallback: LIKE search
                $this->db->group_start();
                $this->db->like('ci.name', $search);
                $this->db->or_like('ci.description', $search);
                $this->db->or_like('ci.brand', $search);
                $this->db->or_like('ci.material', $search);
                $this->db->or_like('ci.notes', $search);
                $this->db->group_end();
            }
        }

        // Date range filtering
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $this->db->where('ci.date_added >=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $this->db->where('ci.date_added <=', $filters['date_to'] . ' 23:59:59');
        }

        // Last worn date range
        if (isset($filters['last_worn_from']) && !empty($filters['last_worn_from'])) {
            $this->db->where('ci.last_worn >=', $filters['last_worn_from']);
        }

        if (isset($filters['last_worn_to']) && !empty($filters['last_worn_to'])) {
            $this->db->where('ci.last_worn <=', $filters['last_worn_to'] . ' 23:59:59');
        }

        // Category filter
        if (isset($filters['category']) && $filters['category'] !== '') {
            $this->db->where('ci.category', $filters['category']);
        }

        // Favorite filter
        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $this->db->where('ci.is_favorite', $filters['is_favorite']);
        }

        // Color filter
        if (isset($filters['color']) && !empty($filters['color'])) {
            $this->db->join($this->colors_table . ' cic', 'ci.id = cic.clothing_item_id', 'left');
            $this->db->where('cic.color_name', $filters['color']);
            $this->db->group_by('ci.id');
        }

        // Season filter
        if (isset($filters['season']) && !empty($filters['season'])) {
            $this->db->join($this->seasons_table . ' cis', 'ci.id = cis.clothing_item_id', 'left');
            $this->db->where('cis.season', $filters['season']);
            $this->db->group_by('ci.id');
        }

        // Style filter
        if (isset($filters['style']) && !empty($filters['style'])) {
            $this->db->join($this->styles_table . ' cist', 'ci.id = cist.clothing_item_id', 'left');
            $this->db->where('cist.style', $filters['style']);
            $this->db->group_by('ci.id');
        }

        // Count total items - Mevcut query'yi count için kullan
        $total_items = $this->db->count_all_results('', false);
        
        // Calculate pagination
        $total_pages = ceil($total_items / $limit);
        $offset = ($page - 1) * $limit;
        
        // Reset query and apply filters again for data fetch
        $this->db->reset_query();
        $this->db->select('ci.*');
        $this->db->from($this->table . ' ci');
        $this->db->where('ci.user_id', $user_id);
// Re-apply filters
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('ci.name', $search);
            $this->db->or_like('ci.description', $search);
            $this->db->or_like('ci.brand', $search);
            $this->db->group_end();
        }

        if (isset($filters['category']) && $filters['category'] !== '') {
            $this->db->where('ci.category', $filters['category']);
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite'] !== '') {
            $this->db->where('ci.is_favorite', $filters['is_favorite']);
        }

        if (isset($filters['color']) && !empty($filters['color'])) {
            $this->db->join($this->colors_table . ' cic', 'ci.id = cic.clothing_item_id', 'left');
            $this->db->where('cic.color_name', $filters['color']);
            $this->db->group_by('ci.id');
        }

        if (isset($filters['season']) && !empty($filters['season'])) {
            $this->db->join($this->seasons_table . ' cis', 'ci.id = cis.clothing_item_id', 'left');
            $this->db->where('cis.season', $filters['season']);
            $this->db->group_by('ci.id');
        }

        if (isset($filters['style']) && !empty($filters['style'])) {
            $this->db->join($this->styles_table . ' cist', 'ci.id = cist.clothing_item_id', 'left');
            $this->db->where('cist.style', $filters['style']);
            $this->db->group_by('ci.id');
        }

        // Sorting - Multiple fields support
        $sort = $filters['sort'] ?? 'date_added';
        $order = isset($filters['order']) ? strtoupper($filters['order']) : 'DESC';
// Sort field mapping
        $sort_fields = [
            'name' => 'ci.name',
            'date_added' => 'ci.date_added',
            'last_worn' => 'ci.last_worn',
            'last_cleaned' => 'ci.last_cleaned',
            'category' => 'ci.category',
            'created_at' => 'ci.created_at'
        ];
        $sort_field = $sort_fields[$sort] ?? 'ci.date_added';
        $this->db->order_by($sort_field, $order);
// Secondary sort (her zaman aynı sıralama için)
        if ($sort !== 'date_added') {
            $this->db->order_by('ci.date_added', 'DESC');
        }

        $this->db->limit($limit, $offset);
        $items = $this->db->get()->result_array();
// Batch eager loading ile N+1 query önleme
        $items = $this->enrich_items_batch($items);
        return [
            'items' => $items,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
        ];
    }

    /**
     * ID'ye göre kıyafet getir (cache ile)
     */
    public function get_by_id($id, $user_id = null)
    {
        // Cache kontrolü
        $this->load->library('cache_library');
        $cache_key = 'clothing_item_' . $id . '_' . ($user_id ?: 'all');
        $cached = $this->cache_library->get($cache_key);
        if ($cached !== null) {
            return $cached;
        }

        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        $item = $this->db->get($this->table)->row_array();
        if ($item) {
            $item = $this->enrich_item($item);
        // Cache'e kaydet
            $this->cache_library->set($cache_key, $item, 600);
        // 10 dakika
        }

        return $item;
    }

    /**
     * Kıyafet oluştur
     */
    public function create($data)
    {
        // Ana verileri ayır
        $main_data = [
            'id' => $this->generate_uuid(),
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'],
            'category' => $data['category'],
            'brand' => $data['brand'] ?? null,
            'store' => $data['store'] ?? null,
            'material' => $data['material'] ?? null,
            'price' => $data['price'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'washing_instructions' => $data['washing_instructions'] ?? null,
            'is_favorite' => $data['is_favorite'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'ai_confidence_score' => $data['ai_confidence_score'] ?? null,
            'ai_analysis' => isset($data['ai_analysis']) ? json_encode($data['ai_analysis']) : null,
            'combination_suggestions' => isset($data['combination_suggestions']) ? json_encode($data['combination_suggestions']) : null,
            'qr_code' => $data['qr_code'] ?? null,
            'wear_count' => isset($data['wear_count']) ? json_encode($data['wear_count']) : null,
            'date_added' => $data['date_added'] ?? date('Y-m-d H:i:s'),
            'last_worn' => $data['last_worn'] ?? null,
            'last_cleaned' => $data['last_cleaned'] ?? null
        ];
        $this->db->trans_start();
// Ana kaydı ekle
        $this->db->insert($this->table, $main_data);
        $item_id = $main_data['id'];
// Cache'i temizle
        $this->load->library('cache_library');
        $this->cache_library->delete_pattern('clothing_item_*');
// İlişkili verileri ekle
        if (isset($data['colors']) && is_array($data['colors'])) {
            $this->save_colors($item_id, $data['colors']);
        }

        if (isset($data['seasons']) && is_array($data['seasons'])) {
            $this->save_seasons($item_id, $data['seasons']);
        }

        if (isset($data['styles']) && is_array($data['styles'])) {
            $this->save_styles($item_id, $data['styles']);
        }

        if (isset($data['event_types']) && is_array($data['event_types'])) {
            $this->save_event_types($item_id, $data['event_types']);
        }

        if (isset($data['custom_tags']) && is_array($data['custom_tags'])) {
            $this->save_custom_tags($item_id, $data['custom_tags']);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $item_id : false;
    }

    /**
     * Kıyafet güncelle
     */
    public function update($id, $data, $user_id)
    {
        // Cache'i temizle
        $this->load->library('cache_library');
        $this->cache_library->delete('clothing_item_' . $id . '_' . $user_id);
        $this->cache_library->delete('clothing_item_' . $id . '_all');
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $main_data = [];
        $allowed_fields = ['name', 'description', 'image_path', 'category', 'brand', 'store',
                          'material', 'price', 'purchase_date', 'washing_instructions',
                          'is_favorite', 'notes', 'last_worn', 'last_cleaned',
                          'ai_confidence_score', 'qr_code'];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $main_data[$field] = $data[$field];
            }
        }

        if (isset($data['ai_analysis'])) {
            $main_data['ai_analysis'] = json_encode($data['ai_analysis']);
        }

        if (isset($data['combination_suggestions'])) {
            $main_data['combination_suggestions'] = json_encode($data['combination_suggestions']);
        }

        if (isset($data['wear_count'])) {
            $main_data['wear_count'] = json_encode($data['wear_count']);
        }

        $this->db->trans_start();
        if (!empty($main_data)) {
            $this->db->update($this->table, $main_data);
        }

        // İlişkili verileri güncelle
        if (isset($data['colors'])) {
            $this->delete_colors($id);
            if (is_array($data['colors']) && !empty($data['colors'])) {
                $this->save_colors($id, $data['colors']);
            }
        }

        if (isset($data['seasons'])) {
            $this->delete_seasons($id);
            if (is_array($data['seasons']) && !empty($data['seasons'])) {
                $this->save_seasons($id, $data['seasons']);
            }
        }

        if (isset($data['styles'])) {
            $this->delete_styles($id);
            if (is_array($data['styles']) && !empty($data['styles'])) {
                $this->save_styles($id, $data['styles']);
            }
        }

        if (isset($data['event_types'])) {
            $this->delete_event_types($id);
            if (is_array($data['event_types']) && !empty($data['event_types'])) {
                $this->save_event_types($id, $data['event_types']);
            }
        }

        if (isset($data['custom_tags'])) {
            $this->delete_custom_tags($id);
            if (is_array($data['custom_tags']) && !empty($data['custom_tags'])) {
                $this->save_custom_tags($id, $data['custom_tags']);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Kıyafet sil
     */
    public function delete($id, $user_id)
    {
        // Cache'i temizle
        $this->load->library('cache_library');
        $this->cache_library->delete('clothing_item_' . $id . '_' . $user_id);
        $this->cache_library->delete('clothing_item_' . $id . '_all');
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->table);
    }

    /**
     * Item'ı zenginleştir (ilişkili verileri ekle)
     * N+1 query problem'ini önlemek için eager loading kullanılıyor
     */
    public function enrich_item($item)
    {
        // JSON alanları decode et
        if (isset($item['ai_analysis']) && $item['ai_analysis']) {
            $item['ai_analysis'] = json_decode($item['ai_analysis'], true);
        }
        if (isset($item['combination_suggestions']) && $item['combination_suggestions']) {
            $item['combination_suggestions'] = json_decode($item['combination_suggestions'], true);
        }
        if (isset($item['wear_count']) && $item['wear_count']) {
            $item['wear_count'] = json_decode($item['wear_count'], true);
        }

        // İlişkili verileri getir
        $item['colors'] = $this->get_colors($item['id']);
        $item['seasons'] = $this->get_seasons($item['id']);
        $item['styles'] = $this->get_styles($item['id']);
        $item['event_types'] = $this->get_event_types($item['id']);
        $item['custom_tags'] = $this->get_custom_tags($item['id']);
        return $item;
    }

    /**
     * Birden fazla item'ı zenginleştir (N+1 query önleme)
     * Eager loading ile tüm ilişkili verileri tek seferde getirir
     */
    private function enrich_items_batch($items)
    {
        if (empty($items)) {
            return $items;
        }

        $item_ids = array_column($items, 'id');
// JSON alanları decode et
        foreach ($items as &$item) {
            if (isset($item['ai_analysis']) && $item['ai_analysis']) {
                $item['ai_analysis'] = json_decode($item['ai_analysis'], true);
            }
            if (isset($item['combination_suggestions']) && $item['combination_suggestions']) {
                $item['combination_suggestions'] = json_decode($item['combination_suggestions'], true);
            }
            if (isset($item['wear_count']) && $item['wear_count']) {
                $item['wear_count'] = json_decode($item['wear_count'], true);
            }
        }

        // Tüm ilişkili verileri tek seferde getir (eager loading)
        $colors = $this->get_colors_batch($item_ids);
        $seasons = $this->get_seasons_batch($item_ids);
        $styles = $this->get_styles_batch($item_ids);
        $event_types = $this->get_event_types_batch($item_ids);
        $custom_tags = $this->get_custom_tags_batch($item_ids);
// İlişkili verileri item'lara ekle
        foreach ($items as &$item) {
            $item['colors'] = $colors[$item['id']] ?? [];
            $item['seasons'] = $seasons[$item['id']] ?? [];
            $item['styles'] = $styles[$item['id']] ?? [];
            $item['event_types'] = $event_types[$item['id']] ?? [];
            $item['custom_tags'] = $custom_tags[$item['id']] ?? [];
        }

        return $items;
    }

    /**
     * Batch color getir (N+1 önleme)
     */
    private function get_colors_batch($item_ids)
    {
        $this->db->where_in('clothing_item_id', $item_ids);
        $colors = $this->db->get($this->colors_table)->result_array();
        $result = [];
        foreach ($colors as $color) {
            $result[$color['clothing_item_id']][] = $color;
        }

        return $result;
    }

    /**
     * Batch season getir (N+1 önleme)
     */
    private function get_seasons_batch($item_ids)
    {
        $this->db->where_in('clothing_item_id', $item_ids);
        $seasons = $this->db->get($this->seasons_table)->result_array();
        $result = [];
        foreach ($seasons as $season) {
            $result[$season['clothing_item_id']][] = $season['season'];
        }

        return $result;
    }

    /**
     * Batch style getir (N+1 önleme)
     */
    private function get_styles_batch($item_ids)
    {
        $this->db->where_in('clothing_item_id', $item_ids);
        $styles = $this->db->get($this->styles_table)->result_array();
        $result = [];
        foreach ($styles as $style) {
            $result[$style['clothing_item_id']][] = $style['style'];
        }

        return $result;
    }

    /**
     * Batch event type getir (N+1 önleme)
     */
    private function get_event_types_batch($item_ids)
    {
        $this->db->where_in('clothing_item_id', $item_ids);
        $event_types = $this->db->get($this->event_types_table)->result_array();
        $result = [];
        foreach ($event_types as $event_type) {
            $result[$event_type['clothing_item_id']][] = $event_type['event_type'];
        }

        return $result;
    }

    /**
     * Batch custom tag getir (N+1 önleme)
     */
    private function get_custom_tags_batch($item_ids)
    {
        $this->db->where_in('clothing_item_id', $item_ids);
        $tags = $this->db->get($this->custom_tags_table)->result_array();
        $result = [];
        foreach ($tags as $tag) {
            $result[$tag['clothing_item_id']][] = $tag;
        }

        return $result;
    }

    // Renkler
    private function get_colors($item_id)
    {
        return $this->db->get_where($this->colors_table, ['clothing_item_id' => $item_id])->result_array();
    }

    private function save_colors($item_id, $colors)
    {
        foreach ($colors as $color) {
            $this->db->insert($this->colors_table, [
                'clothing_item_id' => $item_id,
                'color_name' => $color['name'],
                'color_hex' => $color['hexCode'] ?? null,
                'color_value' => $color['color'] ?? null
            ]);
        }
    }

    private function delete_colors($item_id)
    {
        $this->db->delete($this->colors_table, ['clothing_item_id' => $item_id]);
    }

    // Sezonlar
    private function get_seasons($item_id)
    {
        $seasons = $this->db->get_where($this->seasons_table, ['clothing_item_id' => $item_id])->result_array();
        return array_column($seasons, 'season');
    }

    private function save_seasons($item_id, $seasons)
    {
        foreach ($seasons as $season) {
            $this->db->insert($this->seasons_table, [
                'clothing_item_id' => $item_id,
                'season' => $season
            ]);
        }
    }

    private function delete_seasons($item_id)
    {
        $this->db->delete($this->seasons_table, ['clothing_item_id' => $item_id]);
    }

    // Stiller
    private function get_styles($item_id)
    {
        $styles = $this->db->get_where($this->styles_table, ['clothing_item_id' => $item_id])->result_array();
        return array_column($styles, 'style');
    }

    private function save_styles($item_id, $styles)
    {
        foreach ($styles as $style) {
            $this->db->insert($this->styles_table, [
                'clothing_item_id' => $item_id,
                'style' => $style
            ]);
        }
    }

    private function delete_styles($item_id)
    {
        $this->db->delete($this->styles_table, ['clothing_item_id' => $item_id]);
    }

    // Etkinlik tipleri
    private function get_event_types($item_id)
    {
        $event_types = $this->db->get_where($this->event_types_table, ['clothing_item_id' => $item_id])->result_array();
        return array_column($event_types, 'event_type');
    }

    private function save_event_types($item_id, $event_types)
    {
        foreach ($event_types as $event_type) {
            $this->db->insert($this->event_types_table, [
                'clothing_item_id' => $item_id,
                'event_type' => $event_type
            ]);
        }
    }

    private function delete_event_types($item_id)
    {
        $this->db->delete($this->event_types_table, ['clothing_item_id' => $item_id]);
    }

    // Özel etiketler
    private function get_custom_tags($item_id)
    {
        return $this->db->get_where($this->custom_tags_table, ['clothing_item_id' => $item_id])->result_array();
    }

    private function save_custom_tags($item_id, $custom_tags)
    {
        foreach ($custom_tags as $tag) {
            $this->db->insert($this->custom_tags_table, [
                'clothing_item_id' => $item_id,
                'tag_id' => $tag['id'],
                'tag_name' => $tag['name'],
                'tag_icon' => $tag['icon'] ?? null,
                'tag_color' => $tag['color'] ?? null
            ]);
        }
    }

    private function delete_custom_tags($item_id)
    {
        $this->db->delete($this->custom_tags_table, ['clothing_item_id' => $item_id]);
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
     * Tüm kıyafetleri say (admin için)
     */
    public function count_all()
    {
        return $this->db->count_all($this->table);
    }

    /**
     * Son kıyafetleri getir (admin için)
     */
    public function get_recent($limit = 10)
    {
        $this->db->order_by('date_added', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Pagination ile tüm kıyafetleri getir (admin için)
     */
    public function get_all_admin_paginated($per_page = 30, $offset = 0)
    {
        $this->db->order_by('date_added', 'DESC');
        $this->db->limit($per_page, $offset);
        $items = $this->db->get($this->table)->result_array();
        return $this->enrich_items_batch($items);
    }
}
