<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class WornOutfit extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Worn_outfit_model');
    }

    /**
     * Giyilen kombinleri listele
     * GET /api/worn-outfit
     */
    public function index()
    {
        $user_id = $this->require_auth();
        
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'outfit_id' => $this->input->get('outfit_id'),
            'limit' => (int)($this->input->get('limit') ?: 50),
            'offset' => (int)($this->input->get('offset') ?: 0)
        ];

        $worn_outfits = $this->Worn_outfit_model->get_all($user_id, $filters);
        
        $metadata = [
            'count' => count($worn_outfits)
        ];
        
        if (!empty($filters)) {
            $metadata['filters'] = $filters;
        }
        
        $this->success($worn_outfits, 'Worn outfits retrieved successfully', 200, $metadata);
    }

    /**
     * Giyilen kombin oluştur
     * POST /api/worn-outfit
     */
    public function create()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'date_worn' => 'required|date_format',
            'outfit_id' => 'trim|uuid',
            'notes' => 'trim',
            'weather_data' => 'trim|valid_json',
            'location_data' => 'trim|valid_json'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        
        $data = [
            'user_id' => $user_id,
            'outfit_id' => $json['outfit_id'] ?? null,
            'date_worn' => $json['date_worn'],
            'notes' => $json['notes'] ?? null,
            'weather_data' => isset($json['weather_data']) ? (is_array($json['weather_data']) ? $json['weather_data'] : json_decode($json['weather_data'], true)) : null,
            'location_data' => isset($json['location_data']) ? (is_array($json['location_data']) ? $json['location_data'] : json_decode($json['location_data'], true)) : null
        ];

        $id = $this->Worn_outfit_model->create($data);
        
        if ($id) {
            $worn_outfit = $this->Worn_outfit_model->get_by_id($id, $user_id);
            $this->success($worn_outfit, 'Worn outfit created successfully', 201);
        } else {
            $this->error('Failed to create worn outfit', 500);
        }
    }

    /**
     * Giyilen kombin getir
     * GET /api/worn-outfit/{id}
     */
    public function get($id)
    {
        $user_id = $this->require_auth();
        
        $worn_outfit = $this->Worn_outfit_model->get_by_id($id, $user_id);
        
        if ($worn_outfit) {
            $this->success($worn_outfit);
        } else {
            $this->error('Worn outfit not found', 404);
        }
    }

    /**
     * Giyilen kombin güncelle
     * PUT /api/worn-outfit/{id}
     */
    public function update($id)
    {
        $user_id = $this->require_auth();
        
        // JSON body validation (optional fields)
        $this->validate_json([
            'date_worn' => 'trim|date_format',
            'notes' => 'trim',
            'weather_data' => 'trim|valid_json',
            'location_data' => 'trim|valid_json'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        
        $data = [];
        if (isset($json['date_worn'])) {
            $data['date_worn'] = $json['date_worn'];
        }
        if (isset($json['notes'])) {
            $data['notes'] = $json['notes'];
        }
        if (isset($json['weather_data'])) {
            $data['weather_data'] = is_array($json['weather_data']) ? $json['weather_data'] : json_decode($json['weather_data'], true);
        }
        if (isset($json['location_data'])) {
            $data['location_data'] = is_array($json['location_data']) ? $json['location_data'] : json_decode($json['location_data'], true);
        }

        if (empty($data)) {
            $this->error('No data to update', 400);
            return;
        }

        $result = $this->Worn_outfit_model->update($id, $data, $user_id);
        
        if ($result) {
            $worn_outfit = $this->Worn_outfit_model->get_by_id($id, $user_id);
            $this->success($worn_outfit, 'Worn outfit updated successfully');
        } else {
            $this->error('Failed to update worn outfit', 500);
        }
    }

    /**
     * Giyilen kombin sil
     * DELETE /api/worn-outfit/{id}
     */
    public function delete($id)
    {
        $user_id = $this->require_auth();
        
        $result = $this->Worn_outfit_model->delete($id, $user_id);
        
        if ($result) {
            $this->success(null, 'Worn outfit deleted successfully');
        } else {
            $this->error('Failed to delete worn outfit', 500);
        }
    }

    /**
     * Calendar view
     * GET /api/worn-outfit/calendar
     * Query params: year, month
     */
    public function calendar()
    {
        $user_id = $this->require_auth();
        
        $year = (int)($this->input->get('year') ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('m'));

        if ($month < 1 || $month > 12) {
            $this->error('Invalid month', 400);
            return;
        }

        $calendar = $this->Worn_outfit_model->get_calendar_view($user_id, $year, $month);
        
        $this->success([
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar
        ]);
    }

    /**
     * Statistics
     * GET /api/worn-outfit/statistics
     * Query params: date_from, date_to
     */
    public function statistics()
    {
        $user_id = $this->require_auth();
        
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];

        $stats = $this->Worn_outfit_model->get_statistics($user_id, $filters);
        
        $this->success($stats);
    }
}

