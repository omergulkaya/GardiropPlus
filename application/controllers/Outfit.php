<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class Outfit extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Outfit_model');
    }

    /**
     * Tüm kombinleri listele
     * GET /api/outfit
     */
    public function index()
    {
        $user_id = $this->require_auth();
        $style = $this->input->get('style');
        $is_favorite = $this->input->get('is_favorite');
        $outfits = $this->Outfit_model->get_all($user_id, [
            'style' => $style,
            'is_favorite' => $is_favorite
        ]);
        $this->success($outfits);
    }

    /**
     * Tek bir kombin getir
     * GET /api/outfit/{id}
     */
    public function get($id)
    {
        $user_id = $this->require_auth();
        $outfit = $this->Outfit_model->get_by_id($id, $user_id);
        if ($outfit) {
            $this->success($outfit);
        } else {
            $this->error('Outfit not found', 404);
        }
    }

    /**
     * Yeni kombin oluştur
     * POST /api/outfit
     */
    public function create()
    {
        $user_id = $this->require_auth();
        $this->validate_input([
            'name' => 'required',
            'style' => 'required|integer|in_list[0,1,2,3,4,5,6,7,8]',
            'items' => 'required'
        ]);
// JSON body'yi parse et
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $this->input->post() ?: [];
        }

        $data = [
            'user_id' => $user_id,
            'name' => $input['name'] ?? '',
            'style' => $input['style'] ?? 0,
            'is_favorite' => isset($input['is_favorite']) && $input['is_favorite'] ? 1 : 0,
            'items' => isset($input['items']) ? (is_array($input['items']) ? $input['items'] : json_decode($input['items'], true)) : []
        ];
        $outfit_id = $this->Outfit_model->create($data);
        if ($outfit_id) {
            $outfit = $this->Outfit_model->get_by_id($outfit_id, $user_id);
            $this->success($outfit, 'Outfit created successfully', 201);
        } else {
            $this->error('Failed to create outfit', 500);
        }
    }

    /**
     * Kombin güncelle
     * PUT /api/outfit/{id}
     */
    public function update($id)
    {
        $user_id = $this->require_auth();
        $existing = $this->Outfit_model->get_by_id($id, $user_id);
        if (!$existing) {
            $this->error('Outfit not found', 404);
        }

        // PUT/PATCH için JSON body'yi parse et
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $this->input->post() ?: [];
        }

        $data = [];
        if (isset($input['name'])) {
            $data['name'] = $input['name'];
        }
        if (isset($input['style'])) {
            $data['style'] = $input['style'];
        }
        if (isset($input['is_favorite'])) {
            $data['is_favorite'] = $input['is_favorite'] ? 1 : 0;
        }
        if (isset($input['items'])) {
            $data['items'] = is_array($input['items']) ? $input['items'] : json_decode($input['items'], true);
        }

        if ($this->Outfit_model->update($id, $data, $user_id)) {
            $outfit = $this->Outfit_model->get_by_id($id, $user_id);
            $this->success($outfit, 'Outfit updated successfully');
        } else {
            $this->error('Failed to update outfit', 500);
        }
    }

    /**
     * Kombin sil
     * DELETE /api/outfit/{id}
     */
    public function delete($id)
    {
        $user_id = $this->require_auth();
        if ($this->Outfit_model->delete($id, $user_id)) {
            $this->success(null, 'Outfit deleted successfully');
        } else {
            $this->error('Outfit not found or deletion failed', 404);
        }
    }

    /**
     * Favori durumunu değiştir
     * POST /api/outfit/{id}/toggle-favorite
     */
    public function toggle_favorite($id)
    {
        $user_id = $this->require_auth();
        $outfit = $this->Outfit_model->get_by_id($id, $user_id);
        if (!$outfit) {
            $this->error('Outfit not found', 404);
        }

        $new_favorite_status = !$outfit['is_favorite'];
        if ($this->Outfit_model->update($id, ['is_favorite' => $new_favorite_status], $user_id)) {
            $this->success(['is_favorite' => $new_favorite_status], 'Favorite status updated');
        } else {
            $this->error('Failed to update favorite status', 500);
        }
    }
}
