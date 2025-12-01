<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class Social extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Social_model');
    }

    /**
     * Outfit paylaş
     * POST /api/social/share
     */
    public function share()
    {
        $user_id = $this->require_auth();
        
        $this->validate_input([
            'outfit_id' => 'required',
            'is_public' => 'trim'
        ]);

        $outfit_id = $this->input->post('outfit_id');
        $is_public = $this->input->post('is_public') !== null ? (bool)$this->input->post('is_public') : true;

        $shared_id = $this->Social_model->share_outfit($user_id, $outfit_id, $is_public);
        
        if ($shared_id) {
            $shared = $this->Social_model->get_shared_outfits($user_id, ['user_id' => $user_id]);
            $this->success(['shared_outfit_id' => $shared_id], 'Outfit shared successfully', 201);
        } else {
            $this->error('Failed to share outfit', 500);
        }
    }

    /**
     * Paylaşımı kaldır
     * DELETE /api/social/share/{id}
     */
    public function unshare($id)
    {
        $user_id = $this->require_auth();
        
        $result = $this->Social_model->unshare_outfit($user_id, $id);
        
        if ($result) {
            $this->success(null, 'Outfit unshared successfully');
        } else {
            $this->error('Failed to unshare outfit', 500);
        }
    }

    /**
     * Paylaşılan outfit'leri getir
     * GET /api/social/shared
     */
    public function shared()
    {
        $user_id = $this->require_auth();
        
        $filters = [
            'user_id' => $this->input->get('user_id'),
            'limit' => (int)($this->input->get('limit') ?: 20),
            'offset' => (int)($this->input->get('offset') ?: 0)
        ];

        $shared = $this->Social_model->get_shared_outfits($user_id, $filters);
        
        $metadata = [
            'count' => count($shared)
        ];
        
        if (!empty($filters)) {
            $metadata['filters'] = $filters;
        }
        
        $this->success($shared, 'Shared outfits retrieved successfully', 200, $metadata);
    }

    /**
     * Like/Unlike
     * POST /api/social/like
     */
    public function like()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'shared_outfit_id' => 'required|integer'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $shared_outfit_id = (int)$json['shared_outfit_id'];
        $result = $this->Social_model->toggle_like($user_id, $shared_outfit_id);
        
        $this->success($result, 'Like toggled successfully');
    }

    /**
     * Comment ekle
     * POST /api/social/comment
     */
    public function comment()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'shared_outfit_id' => 'required|integer',
            'comment' => 'required'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $shared_outfit_id = (int)$json['shared_outfit_id'];
        $comment = $json['comment'];

        $comment_id = $this->Social_model->add_comment($user_id, $shared_outfit_id, $comment);
        
        if ($comment_id) {
            $comments = $this->Social_model->get_comments($shared_outfit_id);
            $this->success(['comment_id' => $comment_id, 'comments' => $comments], 'Comment added successfully', 201);
        } else {
            $this->error('Failed to add comment', 500);
        }
    }

    /**
     * Comments getir
     * GET /api/social/comments/{shared_outfit_id}
     */
    public function comments($shared_outfit_id)
    {
        $user_id = $this->require_auth();
        
        $limit = (int)($this->input->get('limit') ?: 50);
        $comments = $this->Social_model->get_comments($shared_outfit_id, $limit);
        
        $this->success([
            'comments' => $comments,
            'count' => count($comments)
        ]);
    }

    /**
     * Comment sil
     * DELETE /api/social/comment/{id}
     */
    public function delete_comment($id)
    {
        $user_id = $this->require_auth();
        
        $result = $this->Social_model->delete_comment($id, $user_id);
        
        if ($result) {
            $this->success(null, 'Comment deleted successfully');
        } else {
            $this->error('Failed to delete comment', 500);
        }
    }

    /**
     * Follow/Unfollow
     * POST /api/social/follow
     */
    public function follow()
    {
        $user_id = $this->require_auth();
        
        // JSON body validation
        $this->validate_json([
            'following_id' => 'required|integer'
        ]);

        $json = json_decode(file_get_contents('php://input'), true);
        $following_id = (int)$json['following_id'];
        
        if ($user_id == $following_id) {
            $this->error('Cannot follow yourself', 400);
            return;
        }

        $result = $this->Social_model->toggle_follow($user_id, $following_id);
        
        if ($result !== false) {
            $this->success($result, 'Follow toggled successfully');
        } else {
            $this->error('Failed to toggle follow', 500);
        }
    }

    /**
     * Followers getir
     * GET /api/social/followers
     */
    public function followers()
    {
        $user_id = $this->require_auth();
        
        $target_user_id = (int)($this->input->get('user_id') ?: $user_id);
        $followers = $this->Social_model->get_followers($target_user_id);
        
        $this->success([
            'followers' => $followers,
            'count' => count($followers)
        ]);
    }

    /**
     * Following getir
     * GET /api/social/following
     */
    public function following()
    {
        $user_id = $this->require_auth();
        
        $target_user_id = (int)($this->input->get('user_id') ?: $user_id);
        $following = $this->Social_model->get_following($target_user_id);
        
        $this->success([
            'following' => $following,
            'count' => count($following)
        ]);
    }

    /**
     * Feed getir
     * GET /api/social/feed
     */
    public function feed()
    {
        $user_id = $this->require_auth();
        
        $limit = (int)($this->input->get('limit') ?: 20);
        $offset = (int)($this->input->get('offset') ?: 0);

        $feed = $this->Social_model->get_feed($user_id, $limit, $offset);
        
        $metadata = [
            'count' => count($feed),
            'limit' => $limit,
            'offset' => $offset
        ];
        
        $this->success($feed, 'Feed retrieved successfully', 200, $metadata);
    }
}

