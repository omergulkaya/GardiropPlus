<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class ShoppingList extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Shopping_list_model');
    }

    /**
     * Alışveriş listesi öğelerini listele
     * GET /api/shopping-list
     */
    public function index()
    {
        $user_id = $this->require_auth();
        
        $filters = [
            'is_purchased' => $this->input->get('is_purchased'),
            'category' => $this->input->get('category'),
            'limit' => (int)($this->input->get('limit') ?: 50),
            'offset' => (int)($this->input->get('offset') ?: 0)
        ];

        $items = $this->Shopping_list_model->get_all($user_id, $filters);
        
        $metadata = [
            'count' => count($items)
        ];
        
        if (!empty($filters)) {
            $metadata['filters'] = $filters;
        }
        
        $this->success($items, 'Shopping list items retrieved successfully', 200, $metadata);
    }

    /**
     * Öğe oluştur
     * POST /api/shopping-list
     */
    public function create()
    {
        $user_id = $this->require_auth();
        
        $this->validate_input([
            'name' => 'required',
            'category' => 'trim',
            'notes' => 'trim'
        ]);

        $data = [
            'user_id' => $user_id,
            'name' => $this->input->post('name'),
            'category' => $this->input->post('category'),
            'notes' => $this->input->post('notes')
        ];

        $id = $this->Shopping_list_model->create($data);
        
        if ($id) {
            $item = $this->Shopping_list_model->get_by_id($id, $user_id);
            $this->success($item, 'Shopping list item created successfully', 201);
        } else {
            $this->error('Failed to create shopping list item', 500);
        }
    }

    /**
     * Öğe getir
     * GET /api/shopping-list/{id}
     */
    public function get($id)
    {
        $user_id = $this->require_auth();
        
        $item = $this->Shopping_list_model->get_by_id($id, $user_id);
        
        if ($item) {
            $this->success($item);
        } else {
            $this->error('Shopping list item not found', 404);
        }
    }

    /**
     * Öğe güncelle
     * PUT /api/shopping-list/{id}
     */
    public function update($id)
    {
        $user_id = $this->require_auth();
        
        // JSON body validation (optional fields)
        $this->validate_json([
            'name' => 'trim',
            'category' => 'trim|integer',
            'notes' => 'trim',
            'is_purchased' => 'trim'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        
        $data = [];
        if (isset($json['name'])) {
            $data['name'] = $json['name'];
        }
        if (isset($json['category'])) {
            $data['category'] = $json['category'];
        }
        if (isset($json['notes'])) {
            $data['notes'] = $json['notes'];
        }
        if (isset($json['is_purchased'])) {
            $data['is_purchased'] = (bool)$json['is_purchased'];
        }

        if (empty($data)) {
            $this->error('No data to update', 400);
            return;
        }

        $result = $this->Shopping_list_model->update($id, $data, $user_id);
        
        if ($result) {
            $item = $this->Shopping_list_model->get_by_id($id, $user_id);
            $this->success($item, 'Shopping list item updated successfully');
        } else {
            $this->error('Failed to update shopping list item', 500);
        }
    }

    /**
     * Öğe sil
     * DELETE /api/shopping-list/{id}
     */
    public function delete($id)
    {
        $user_id = $this->require_auth();
        
        $result = $this->Shopping_list_model->delete($id, $user_id);
        
        if ($result) {
            $this->success(null, 'Shopping list item deleted successfully');
        } else {
            $this->error('Failed to delete shopping list item', 500);
        }
    }

    /**
     * Status güncelle (purchased/unpurchased)
     * PATCH /api/shopping-list/{id}/status
     */
    public function update_status($id)
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'is_purchased' => 'required'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $is_purchased = (bool)$json['is_purchased'];
        $result = $this->Shopping_list_model->update_status($id, $is_purchased, $user_id);
        
        if ($result) {
            $item = $this->Shopping_list_model->get_by_id($id, $user_id);
            $this->success($item, 'Status updated successfully');
        } else {
            $this->error('Failed to update status', 500);
        }
    }

    /**
     * Bulk create
     * POST /api/shopping-list/batch/create
     */
    public function batch_create()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'items' => 'required|is_array'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $items = $json['items'];
        
        if (!is_array($items) || empty($items)) {
            $this->error('Invalid items format', 400);
            return;
        }

        if (count($items) > 50) {
            $this->error('Maximum 50 items allowed per batch', 400);
            return;
        }

        $result = $this->Shopping_list_model->bulk_create($user_id, $items);
        
        if ($result) {
            $this->success(['created' => count($items)], 'Items created successfully', 201);
        } else {
            $this->error('Failed to create items', 500);
        }
    }

    /**
     * Bulk update status
     * PATCH /api/shopping-list/batch/status
     */
    public function batch_update_status()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'ids' => 'required|is_array',
            'is_purchased' => 'required'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $ids = $json['ids'];
        $is_purchased = (bool)$json['is_purchased'];
        
        if (!is_array($ids) || empty($ids)) {
            $this->error('Invalid ids format', 400);
            return;
        }

        $result = $this->Shopping_list_model->bulk_update_status($user_id, $ids, $is_purchased);
        
        if ($result) {
            $this->success(['updated' => count($ids)], 'Status updated successfully');
        } else {
            $this->error('Failed to update status', 500);
        }
    }

    /**
     * Bulk delete
     * DELETE /api/shopping-list/batch/delete
     */
    public function batch_delete()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'ids' => 'required|is_array'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $ids = $json['ids'];
        
        if (!is_array($ids) || empty($ids)) {
            $this->error('Invalid ids format', 400);
            return;
        }

        $result = $this->Shopping_list_model->bulk_delete($user_id, $ids);
        
        if ($result) {
            $this->success(['deleted' => count($ids)], 'Items deleted successfully');
        } else {
            $this->error('Failed to delete items', 500);
        }
    }

    /**
     * Statistics
     * GET /api/shopping-list/statistics
     */
    public function statistics()
    {
        $user_id = $this->require_auth();
        
        $stats = $this->Shopping_list_model->get_statistics($user_id);
        
        $this->success($stats);
    }
}

