<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class ClothingItem extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Clothing_item_model');
    }

    /**
     * Tüm kıyafetleri listele
     * GET /api/clothing-item
     * POST /api/clothing-item -> create() metoduna yönlendir
     * Query params:
     *   - category, is_favorite, page, limit, search, color, season, style
     *   - date_from, date_to (date range filtering)
     *   - sort (sorting: name, date_added, last_worn, etc.)
     *   - order (asc/desc)
     *   - cursor (cursor-based pagination için)
     *   - fields (field selection: name,id,category)
     */
    public function index()
    {
        // POST isteği ise create metoduna yönlendir
        if ($this->input->method() === 'post') {
            $this->create();
            return;
        }
        
        $user_id = $this->require_auth();
        $category = $this->input->get('category');
        $is_favorite = $this->input->get('is_favorite');
        $page = (int)($this->input->get('page') ?: 1);
        $limit = (int)($this->input->get('limit') ?: 20);
        $search = $this->input->get('search');
        $color = $this->input->get('color');
        $season = $this->input->get('season');
        $style = $this->input->get('style');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $sort = $this->input->get('sort') ?: 'date_added';
        $order = strtoupper($this->input->get('order') ?: 'DESC');
        $cursor = $this->input->get('cursor');
// Cursor-based pagination

        // Filter validation
        $this->validate_filters([
            'category' => $category,
            'is_favorite' => $is_favorite,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'sort' => $sort,
            'order' => $order
        ]);
        $filters = [
            'category' => $category,
            'is_favorite' => $is_favorite,
            'search' => $search,
            'color' => $color,
            'season' => $season,
            'style' => $style,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'sort' => $sort,
            'order' => $order,
        ];
// Cursor-based pagination veya offset-based pagination
        if ($cursor) {
            $result = $this->Clothing_item_model->get_all_cursor($user_id, $filters, $cursor, $limit);
        } else {
            $result = $this->Clothing_item_model->get_all_paginated($user_id, $filters, $page, $limit);
        }

        // Response library ile metadata ekle
        $metadata = [];
        
        if (isset($result['pagination'])) {
            $metadata['pagination'] = $result['pagination'];
        } else {
            $metadata['pagination'] = [
                'current_page' => $result['current_page'],
                'total_pages' => $result['total_pages'],
                'total_items' => $result['total_items'],
                'per_page' => $limit,
                'has_next' => $result['current_page'] < $result['total_pages'],
                'has_prev' => $result['current_page'] > 1
            ];
        }
        
        if (isset($result['next_cursor'])) {
            $metadata['cursor'] = $result['next_cursor'];
        }
        
        if (!empty($filters)) {
            $metadata['filters'] = $filters;
        }

        $this->success($result['items'], 'Items retrieved successfully', 200, $metadata);
    }

    /**
     * Filter validation
     */
    private function validate_filters($filters)
    {
        // Category validation
        if (isset($filters['category']) && $filters['category'] !== '') {
            if (!in_array((int)$filters['category'], [0, 1, 2, 3, 4, 5])) {
                $this->error('Invalid category value', 400);
            }
        }

        // Date validation
        if (isset($filters['date_from']) && $filters['date_from']) {
            if (!strtotime($filters['date_from'])) {
                $this->error('Invalid date_from format. Use YYYY-MM-DD', 400);
            }
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            if (!strtotime($filters['date_to'])) {
                $this->error('Invalid date_to format. Use YYYY-MM-DD', 400);
            }
        }

        // Sort validation
        $allowed_sorts = ['name', 'date_added', 'last_worn', 'last_cleaned', 'category', 'created_at'];
        if (isset($filters['sort']) && !in_array($filters['sort'], $allowed_sorts)) {
            $this->error('Invalid sort field. Allowed: ' . implode(', ', $allowed_sorts), 400);
        }

        // Order validation
        if (isset($filters['order']) && !in_array(strtoupper($filters['order']), ['ASC', 'DESC'])) {
            $this->error('Invalid order. Use ASC or DESC', 400);
        }
    }

    /**
     * Tek bir kıyafet getir
     * GET /api/clothing-item/{id}
     */
    public function get($id)
    {
        $user_id = $this->require_auth();
        $item = $this->Clothing_item_model->get_by_id($id, $user_id);
        if ($item) {
            $this->success($item);
        } else {
            $this->error('Clothing item not found', 404);
        }
    }

    /**
     * Yeni kıyafet ekle
     * POST /api/clothing-item
     */
    public function create()
    {
        $user_id = $this->require_auth();
        
        // JSON body'yi parse et
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $this->input->post() ?: [];
        }
        
        // JSON body validation - input'u set et
        $this->load->library('form_validation');
        $this->form_validation->set_data($input);
        $this->form_validation->set_rules('name', 'Name', 'required|trim');
        $this->form_validation->set_rules('image_path', 'Image path', 'required');
        
        // Category validation - integer kontrolü ve range kontrolü
        $category = isset($input['category']) ? (int)$input['category'] : null;
        if ($category === null || !is_numeric($input['category']) || $category < 0 || $category > 5) {
            $this->error('Validation failed', 422, ['category' => 'The Category field must be one of: 0,1,2,3,4,5.']);
            return;
        }
        
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $this->error('Validation failed', 422, $errors);
            return;
        }

        $data = [
            'user_id' => $user_id,
            'name' => $input['name'] ?? '',
            'description' => $input['description'] ?? null,
            'image_path' => $input['image_path'] ?? '',
            'category' => $category, // Validated category değerini kullan
            'brand' => $input['brand'] ?? null,
            'store' => $input['store'] ?? null,
            'material' => $input['material'] ?? null,
            'price' => $input['price'] ?? null,
            'purchase_date' => $input['purchase_date'] ?? null,
            'washing_instructions' => $input['washing_instructions'] ?? null,
            'is_favorite' => isset($input['is_favorite']) && $input['is_favorite'] ? 1 : 0,
            'notes' => $input['notes'] ?? null,
            'date_added' => date('Y-m-d H:i:s')
        ];
// JSON alanlar
        if (isset($input['colors'])) {
            $data['colors'] = is_array($input['colors']) ? $input['colors'] : json_decode($input['colors'], true);
        }
        if (isset($input['seasons'])) {
            $data['seasons'] = is_array($input['seasons']) ? $input['seasons'] : json_decode($input['seasons'], true);
        }
        if (isset($input['styles'])) {
            $data['styles'] = is_array($input['styles']) ? $input['styles'] : json_decode($input['styles'], true);
        }
        if (isset($input['event_types'])) {
            $data['event_types'] = is_array($input['event_types']) ? $input['event_types'] : json_decode($input['event_types'], true);
        }
        if (isset($input['custom_tags'])) {
            $data['custom_tags'] = is_array($input['custom_tags']) ? $input['custom_tags'] : json_decode($input['custom_tags'], true);
        }
        if (isset($input['ai_analysis'])) {
            $data['ai_analysis'] = is_array($input['ai_analysis']) ? $input['ai_analysis'] : json_decode($input['ai_analysis'], true);
        }
        if (isset($input['combination_suggestions'])) {
            $data['combination_suggestions'] = is_array($input['combination_suggestions']) ? $input['combination_suggestions'] : json_decode($input['combination_suggestions'], true);
        }
        if (isset($input['wear_count'])) {
            $data['wear_count'] = is_array($input['wear_count']) ? $input['wear_count'] : json_decode($input['wear_count'], true);
        }

        $item_id = $this->Clothing_item_model->create($data);
        if ($item_id) {
            // Cache'i temizle - yeni oluşturulan item için
            $this->load->library('cache_library');
            $this->cache_library->delete_pattern('clothing_item_' . $item_id . '_*');
            $this->cache_library->delete_pattern('clothing_item_*');
            
            // Item'ı getir - get_by_id kullan (cache temizlendi, yeni query yapacak)
            $item = $this->Clothing_item_model->get_by_id($item_id, $user_id);
            
            // Eğer hala false döndüyse, direkt query yap
            if (!$item) {
                $this->db->where('id', $item_id);
                $this->db->where('user_id', $user_id);
                $item = $this->db->get('clothing_items')->row_array();
                
                if ($item) {
                    // İlişkili verileri manuel olarak ekle (private metodlar olduğu için direkt query)
                    $colors = $this->db->get_where('clothing_item_colors', ['clothing_item_id' => $item_id])->result_array();
                    $item['colors'] = $colors;
                    
                    $seasons = $this->db->get_where('clothing_item_seasons', ['clothing_item_id' => $item_id])->result_array();
                    $item['seasons'] = array_column($seasons, 'season');
                    
                    $styles = $this->db->get_where('clothing_item_styles', ['clothing_item_id' => $item_id])->result_array();
                    $item['styles'] = array_column($styles, 'style');
                    
                    $event_types = $this->db->get_where('clothing_item_event_types', ['clothing_item_id' => $item_id])->result_array();
                    $item['event_types'] = array_column($event_types, 'event_type');
                    
                    $custom_tags = $this->db->get_where('clothing_item_custom_tags', ['clothing_item_id' => $item_id])->result_array();
                    $item['custom_tags'] = $custom_tags;
                    
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
                }
            }
            
            if ($item && is_array($item)) {
                // enrich_item metodunu kullanarak item'ı zenginleştir
                $item = $this->Clothing_item_model->enrich_item($item);
                $this->success($item, 'Clothing item created successfully', 201);
            } else {
                // Item bulunamadıysa, input data'dan oluştur
                $this->error('Clothing item created but could not be retrieved', 500);
            }
        } else {
            $this->error('Failed to create clothing item', 500);
        }
    }

    /**
     * Kıyafet güncelle
     * PUT /api/clothing-item/{id}
     */
    public function update($id)
    {
        $user_id = $this->require_auth();
// Kıyafetin kullanıcıya ait olduğunu kontrol et
        $existing = $this->Clothing_item_model->get_by_id($id, $user_id);
        if (!$existing) {
            $this->error('Clothing item not found', 404);
        }

        // PUT/PATCH için JSON body'yi parse et
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $this->input->post() ?: [];
        }

        $data = [];
// Sadece gönderilen alanları güncelle
        $fields = ['name', 'description', 'image_path', 'category', 'brand', 'store',
                   'material', 'price', 'purchase_date', 'washing_instructions',
                   'notes', 'last_worn', 'last_cleaned', 'is_favorite'];
        foreach ($fields as $field) {
            if (isset($input[$field])) {
                if ($field === 'is_favorite') {
                    $data[$field] = $input[$field] ? 1 : 0;
                } else {
                    $data[$field] = $input[$field];
                }
            }
        }

        // JSON alanlar
        $json_fields = ['colors', 'seasons', 'styles', 'event_types', 'custom_tags',
                       'ai_analysis', 'combination_suggestions', 'wear_count'];
        foreach ($json_fields as $field) {
            if (isset($input[$field])) {
                $data[$field] = is_array($input[$field]) ? $input[$field] : json_decode($input[$field], true);
            }
        }

        if ($this->Clothing_item_model->update($id, $data, $user_id)) {
            $item = $this->Clothing_item_model->get_by_id($id, $user_id);
            $this->success($item, 'Clothing item updated successfully');
        } else {
            $this->error('Failed to update clothing item', 500);
        }
    }

    /**
     * Kıyafet sil
     * DELETE /api/clothing-item/{id}
     */
    public function delete($id)
    {
        $user_id = $this->require_auth();
        if ($this->Clothing_item_model->delete($id, $user_id)) {
            $this->success(null, 'Clothing item deleted successfully');
        } else {
            $this->error('Clothing item not found or deletion failed', 404);
        }
    }

    /**
     * Favori durumunu değiştir
     * POST /api/clothing-item/{id}/toggle-favorite
     */
    public function toggle_favorite($id)
    {
        $user_id = $this->require_auth();
        $item = $this->Clothing_item_model->get_by_id($id, $user_id);
        if (!$item) {
            $this->error('Clothing item not found', 404);
        }

        $new_favorite_status = !$item['is_favorite'];
        if ($this->Clothing_item_model->update($id, ['is_favorite' => $new_favorite_status], $user_id)) {
            $this->success(['is_favorite' => $new_favorite_status], 'Favorite status updated');
        } else {
            $this->error('Failed to update favorite status', 500);
        }
    }

    /**
     * Toplu kıyafet sil
     * DELETE /api/clothing-item/batch/delete
     * Body: { "ids": ["id1", "id2", ...] }
     */
    public function batch_delete()
    {
        $user_id = $this->require_auth();
        $input = json_decode($this->input->raw_input_stream, true);
        $ids = $input['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            $this->error('Invalid request. IDs array required.', 400);
        }

        $deleted = 0;
        $failed = [];
        foreach ($ids as $id) {
            if ($this->Clothing_item_model->delete($id, $user_id)) {
                $deleted++;
            } else {
                $failed[] = $id;
            }
        }

        $this->success([
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($ids),
        ], "Deleted $deleted of " . count($ids) . " items");
    }

    /**
     * Toplu kıyafet güncelle
     * PUT /api/clothing-item/batch/update
     * Body: { "items": [{ "id": "...", "updates": {...} }, ...] }
     */
    public function batch_update()
    {
        $user_id = $this->require_auth();
        $input = json_decode($this->input->raw_input_stream, true);
        $items = $input['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $this->error('Invalid request. Items array required.', 400);
        }

        // Transaction başlat
        $this->load->database();
        $this->db->trans_start();
        $updated = 0;
        $failed = [];
        $results = [];
        foreach ($items as $index => $item) {
            $id = $item['id'] ?? null;
            $updates = $item['updates'] ?? [];
            if (!$id || empty($updates)) {
                $failed[] = ['index' => $index, 'id' => $id ?? 'unknown', 'reason' => 'Missing id or updates'];
                continue;
            }

            // Kullanıcıya ait olduğunu kontrol et
            $existing = $this->Clothing_item_model->get_by_id($id, $user_id);
            if (!$existing) {
                $failed[] = ['index' => $index, 'id' => $id, 'reason' => 'Item not found or access denied'];
                continue;
            }

            if ($this->Clothing_item_model->update($id, $updates, $user_id)) {
                $updated++;
                $results[] = ['index' => $index, 'id' => $id, 'status' => 'success'];
            } else {
                $failed[] = ['index' => $index, 'id' => $id, 'reason' => 'Update failed'];
            }
        }

        $this->db->trans_complete();
        $this->success([
            'updated' => $updated,
            'failed' => $failed,
            'results' => $results,
            'total' => count($items),
            'transaction_success' => $this->db->trans_status()
        ], "Updated $updated of " . count($items) . " items");
    }

    /**
     * Toplu kıyafet oluştur
     * POST /api/clothing-item/batch/create
     * Body: { "items": [{ "name": "...", "category": 0, ... }, ...] }
     */
    public function batch_create()
    {
        $user_id = $this->require_auth();
        $input = json_decode($this->input->raw_input_stream, true);
        $items = $input['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $this->error('Invalid request. Items array required.', 400);
        }

        // Maximum batch size
        if (count($items) > 50) {
            $this->error('Maximum 50 items allowed per batch', 400);
        }

        // Transaction başlat
        $this->load->database();
        $this->db->trans_start();
        $created = 0;
        $failed = [];
        $results = [];
        foreach ($items as $index => $item_data) {
        // Validation
            if (empty($item_data['name']) || !isset($item_data['category'])) {
                $failed[] = ['index' => $index, 'reason' => 'Missing required fields: name, category'];
                continue;
            }

            // Prepare data
            $data = [
                'user_id' => $user_id,
                'name' => $item_data['name'],
                'description' => $item_data['description'] ?? null,
                'image_path' => $item_data['image_path'] ?? '',
                'category' => $item_data['category'],
                'brand' => $item_data['brand'] ?? null,
                'store' => $item_data['store'] ?? null,
                'material' => $item_data['material'] ?? null,
                'price' => $item_data['price'] ?? null,
                'purchase_date' => $item_data['purchase_date'] ?? null,
                'washing_instructions' => $item_data['washing_instructions'] ?? null,
                'is_favorite' => isset($item_data['is_favorite']) && $item_data['is_favorite'] ? 1 : 0,
                'notes' => $item_data['notes'] ?? null,
                'date_added' => date('Y-m-d H:i:s')
            ];
        // JSON fields
            $json_fields = ['colors', 'seasons', 'styles', 'event_types', 'custom_tags'];
            foreach ($json_fields as $field) {
                if (isset($item_data[$field])) {
                    $data[$field] = is_array($item_data[$field]) ? $item_data[$field] : json_decode($item_data[$field], true);
                }
            }

            $item_id = $this->Clothing_item_model->create($data);
            if ($item_id) {
                $created++;
                $results[] = ['index' => $index, 'id' => $item_id, 'status' => 'success'];
            } else {
                $failed[] = ['index' => $index, 'reason' => 'Creation failed'];
            }
        }

        $this->db->trans_complete();
        $this->success([
            'created' => $created,
            'failed' => $failed,
            'results' => $results,
            'total' => count($items),
            'transaction_success' => $this->db->trans_status()
        ], "Created $created of " . count($items) . " items", 201);
    }
}
