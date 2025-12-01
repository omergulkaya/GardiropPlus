<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Social_model extends CI_Model
{
    protected $shared_outfits_table = 'shared_outfits';
    protected $outfit_likes_table = 'outfit_likes';
    protected $outfit_comments_table = 'outfit_comments';
    protected $user_follows_table = 'user_follows';
    protected $outfits_table = 'outfits';
    protected $users_table = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Outfit paylaş
     */
    public function share_outfit($user_id, $outfit_id, $is_public = true)
    {
        // Zaten paylaşılmış mı kontrol et
        $this->db->where('user_id', $user_id);
        $this->db->where('outfit_id', $outfit_id);
        $existing = $this->db->get($this->shared_outfits_table)->row_array();

        if ($existing) {
            // Güncelle
            $this->db->where('id', $existing['id']);
            $this->db->update($this->shared_outfits_table, ['is_public' => $is_public]);
            return $existing['id'];
        }

        // Yeni paylaşım oluştur
        $data = [
            'user_id' => $user_id,
            'outfit_id' => $outfit_id,
            'is_public' => $is_public
        ];
        $this->db->insert($this->shared_outfits_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Paylaşımı kaldır
     */
    public function unshare_outfit($user_id, $shared_outfit_id)
    {
        $this->db->where('id', $shared_outfit_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->shared_outfits_table);
    }

    /**
     * Paylaşılan outfit'leri getir
     */
    public function get_shared_outfits($user_id = null, $filters = [])
    {
        $this->db->select('so.*, o.name as outfit_name, o.style, u.first_name, u.last_name, u.profile_image_path');
        $this->db->from($this->shared_outfits_table . ' so');
        $this->db->join($this->outfits_table . ' o', 'so.outfit_id = o.id');
        $this->db->join($this->users_table . ' u', 'so.user_id = u.id');

        if ($user_id) {
            $this->db->where('so.user_id', $user_id);
        } else {
            // Public outfit'leri göster
            $this->db->where('so.is_public', true);
        }

        // Filters
        if (isset($filters['user_id'])) {
            $this->db->where('so.user_id', $filters['user_id']);
        }

        $this->db->order_by('so.created_at', 'DESC');
        
        if (isset($filters['limit'])) {
            $this->db->limit($filters['limit']);
        }
        if (isset($filters['offset'])) {
            $this->db->offset($filters['offset']);
        }

        $results = $this->db->get()->result_array();

        // Enrich with likes and comments count
        foreach ($results as &$result) {
            $result['likes_count'] = $this->get_likes_count($result['id']);
            $result['comments_count'] = $this->get_comments_count($result['id']);
            $result['is_liked'] = $user_id ? $this->is_liked($result['id'], $user_id) : false;
        }

        return $results;
    }

    /**
     * Like/Unlike
     */
    public function toggle_like($user_id, $shared_outfit_id)
    {
        // Zaten like edilmiş mi kontrol et
        $this->db->where('user_id', $user_id);
        $this->db->where('shared_outfit_id', $shared_outfit_id);
        $existing = $this->db->get($this->outfit_likes_table)->row_array();

        if ($existing) {
            // Unlike
            $this->db->where('id', $existing['id']);
            $this->db->delete($this->outfit_likes_table);
            return ['action' => 'unliked', 'liked' => false];
        } else {
            // Like
            $this->db->insert($this->outfit_likes_table, [
                'user_id' => $user_id,
                'shared_outfit_id' => $shared_outfit_id
            ]);
            return ['action' => 'liked', 'liked' => true];
        }
    }

    /**
     * Comment ekle
     */
    public function add_comment($user_id, $shared_outfit_id, $comment)
    {
        $data = [
            'user_id' => $user_id,
            'shared_outfit_id' => $shared_outfit_id,
            'comment' => $comment
        ];
        $this->db->insert($this->outfit_comments_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Comments getir
     */
    public function get_comments($shared_outfit_id, $limit = 50)
    {
        $this->db->select('oc.*, u.first_name, u.last_name, u.profile_image_path');
        $this->db->from($this->outfit_comments_table . ' oc');
        $this->db->join($this->users_table . ' u', 'oc.user_id = u.id');
        $this->db->where('oc.shared_outfit_id', $shared_outfit_id);
        $this->db->order_by('oc.created_at', 'ASC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Comment sil
     */
    public function delete_comment($comment_id, $user_id)
    {
        $this->db->where('id', $comment_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->outfit_comments_table);
    }

    /**
     * Follow/Unfollow
     */
    public function toggle_follow($follower_id, $following_id)
    {
        if ($follower_id == $following_id) {
            return false; // Kendini takip edemez
        }

        // Zaten takip ediliyor mu kontrol et
        $this->db->where('follower_id', $follower_id);
        $this->db->where('following_id', $following_id);
        $existing = $this->db->get($this->user_follows_table)->row_array();

        if ($existing) {
            // Unfollow
            $this->db->where('id', $existing['id']);
            $this->db->delete($this->user_follows_table);
            return ['action' => 'unfollowed', 'following' => false];
        } else {
            // Follow
            $this->db->insert($this->user_follows_table, [
                'follower_id' => $follower_id,
                'following_id' => $following_id
            ]);
            return ['action' => 'followed', 'following' => true];
        }
    }

    /**
     * Followers getir
     */
    public function get_followers($user_id)
    {
        $this->db->select('u.id, u.first_name, u.last_name, u.profile_image_path, uf.created_at');
        $this->db->from($this->user_follows_table . ' uf');
        $this->db->join($this->users_table . ' u', 'uf.follower_id = u.id');
        $this->db->where('uf.following_id', $user_id);
        $this->db->order_by('uf.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Following getir
     */
    public function get_following($user_id)
    {
        $this->db->select('u.id, u.first_name, u.last_name, u.profile_image_path, uf.created_at');
        $this->db->from($this->user_follows_table . ' uf');
        $this->db->join($this->users_table . ' u', 'uf.following_id = u.id');
        $this->db->where('uf.follower_id', $user_id);
        $this->db->order_by('uf.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Feed getir (takip edilen kullanıcıların paylaşımları)
     */
    public function get_feed($user_id, $limit = 20, $offset = 0)
    {
        // Takip edilen kullanıcıları al
        $this->db->select('following_id');
        $this->db->where('follower_id', $user_id);
        $following = $this->db->get($this->user_follows_table)->result_array();
        $following_ids = array_column($following, 'following_id');

        if (empty($following_ids)) {
            return [];
        }

        // Takip edilen kullanıcıların public paylaşımlarını getir
        $this->db->select('so.*, o.name as outfit_name, o.style, u.first_name, u.last_name, u.profile_image_path');
        $this->db->from($this->shared_outfits_table . ' so');
        $this->db->join($this->outfits_table . ' o', 'so.outfit_id = o.id');
        $this->db->join($this->users_table . ' u', 'so.user_id = u.id');
        $this->db->where('so.is_public', true);
        $this->db->where_in('so.user_id', $following_ids);
        $this->db->order_by('so.created_at', 'DESC');
        $this->db->limit($limit);
        $this->db->offset($offset);

        $results = $this->db->get()->result_array();

        // Enrich with likes and comments
        foreach ($results as &$result) {
            $result['likes_count'] = $this->get_likes_count($result['id']);
            $result['comments_count'] = $this->get_comments_count($result['id']);
            $result['is_liked'] = $this->is_liked($result['id'], $user_id);
        }

        return $results;
    }

    /**
     * Likes count
     */
    private function get_likes_count($shared_outfit_id)
    {
        $this->db->where('shared_outfit_id', $shared_outfit_id);
        return $this->db->count_all_results($this->outfit_likes_table);
    }

    /**
     * Comments count
     */
    private function get_comments_count($shared_outfit_id)
    {
        $this->db->where('shared_outfit_id', $shared_outfit_id);
        return $this->db->count_all_results($this->outfit_comments_table);
    }

    /**
     * Is liked
     */
    private function is_liked($shared_outfit_id, $user_id)
    {
        $this->db->where('shared_outfit_id', $shared_outfit_id);
        $this->db->where('user_id', $user_id);
        return $this->db->count_all_results($this->outfit_likes_table) > 0;
    }
}

