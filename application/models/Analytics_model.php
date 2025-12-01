<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Analytics_model extends CI_Model
{
    protected $clothing_items_table = 'clothing_items';
    protected $outfits_table = 'outfits';
    protected $worn_outfits_table = 'worn_outfits';
    protected $shopping_list_table = 'shopping_list_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Wardrobe statistics
     */
    public function get_wardrobe_statistics($user_id)
    {
        // Total items
        $this->db->where('user_id', $user_id);
        $total_items = $this->db->count_all_results($this->clothing_items_table);

        // By category
        $this->db->select('category, COUNT(*) as count');
        $this->db->where('user_id', $user_id);
        $this->db->group_by('category');
        $by_category = $this->db->get($this->clothing_items_table)->result_array();

        // By style
        $this->db->select('cis.style, COUNT(*) as count');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->join('clothing_item_styles cis', 'ci.id = cis.clothing_item_id');
        $this->db->where('ci.user_id', $user_id);
        $this->db->group_by('cis.style');
        $by_style = $this->db->get()->result_array();

        // Favorite items
        $this->db->where('user_id', $user_id);
        $this->db->where('is_favorite', true);
        $favorite_items = $this->db->count_all_results($this->clothing_items_table);

        // Total value
        $this->db->select_sum('price');
        $this->db->where('user_id', $user_id);
        $total_value = $this->db->get($this->clothing_items_table)->row()->price ?? 0;

        // Most worn items (last 30 days)
        $date_from = date('Y-m-d', strtotime('-30 days'));
        $this->db->select('ci.id, ci.name, COUNT(wo.id) as wear_count');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->join('outfit_items oi', 'ci.id = oi.clothing_item_id', 'left');
        $this->db->join('worn_outfits wo', 'oi.outfit_id = wo.outfit_id', 'left');
        $this->db->where('ci.user_id', $user_id);
        $this->db->where('wo.date_worn >=', $date_from);
        $this->db->group_by('ci.id');
        $this->db->order_by('wear_count', 'DESC');
        $this->db->limit(10);
        $most_worn = $this->db->get()->result_array();

        return [
            'total_items' => $total_items,
            'by_category' => $by_category,
            'by_style' => $by_style,
            'favorite_items' => $favorite_items,
            'total_value' => (float)$total_value,
            'most_worn_items' => $most_worn
        ];
    }

    /**
     * Usage analytics
     */
    public function get_usage_analytics($user_id, $filters = [])
    {
        $date_from = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $filters['date_to'] ?? date('Y-m-d');

        // Total outfits worn
        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $date_from);
        $this->db->where('date_worn <=', $date_to);
        $total_worn = $this->db->count_all_results($this->worn_outfits_table);

        // Daily usage
        $this->db->select('date_worn, COUNT(*) as count');
        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $date_from);
        $this->db->where('date_worn <=', $date_to);
        $this->db->group_by('date_worn');
        $this->db->order_by('date_worn', 'ASC');
        $daily_usage = $this->db->get($this->worn_outfits_table)->result_array();

        // Category usage
        $this->db->select('ci.category, COUNT(DISTINCT wo.id) as usage_count');
        $this->db->from($this->worn_outfits_table . ' wo');
        $this->db->join('outfit_items oi', 'wo.outfit_id = oi.outfit_id', 'left');
        $this->db->join($this->clothing_items_table . ' ci', 'oi.clothing_item_id = ci.id', 'left');
        $this->db->where('wo.user_id', $user_id);
        $this->db->where('wo.date_worn >=', $date_from);
        $this->db->where('wo.date_worn <=', $date_to);
        $this->db->where('ci.category IS NOT NULL');
        $this->db->group_by('ci.category');
        $category_usage = $this->db->get()->result_array();

        return [
            'total_worn' => $total_worn,
            'daily_usage' => $daily_usage,
            'category_usage' => $category_usage,
            'date_range' => [
                'from' => $date_from,
                'to' => $date_to
            ]
        ];
    }

    /**
     * Style insights
     */
    public function get_style_insights($user_id)
    {
        // Most used styles
        $this->db->select('o.style, COUNT(wo.id) as usage_count');
        $this->db->from($this->worn_outfits_table . ' wo');
        $this->db->join($this->outfits_table . ' o', 'wo.outfit_id = o.id', 'left');
        $this->db->where('wo.user_id', $user_id);
        $this->db->where('wo.date_worn >=', date('Y-m-d', strtotime('-90 days')));
        $this->db->group_by('o.style');
        $this->db->order_by('usage_count', 'DESC');
        $most_used_styles = $this->db->get()->result_array();

        // Style distribution
        $this->db->select('style, COUNT(*) as count');
        $this->db->where('user_id', $user_id);
        $this->db->group_by('style');
        $style_distribution = $this->db->get($this->outfits_table)->result_array();

        return [
            'most_used_styles' => $most_used_styles,
            'style_distribution' => $style_distribution
        ];
    }

    /**
     * Seasonal recommendations
     */
    public function get_seasonal_recommendations($user_id)
    {
        $current_month = (int)date('m');
        $season = $this->get_current_season($current_month);

        // Season mapping: 0=spring, 1=summer, 2=autumn, 3=winter
        $season_map = [3, 3, 0, 0, 0, 1, 1, 1, 2, 2, 2, 3];
        $current_season = $season_map[$current_month - 1];

        // Items suitable for current season
        $this->db->select('ci.*');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->join('clothing_item_seasons cis', 'ci.id = cis.clothing_item_id');
        $this->db->where('ci.user_id', $user_id);
        $this->db->where('cis.season', $current_season);
        $this->db->where('ci.last_worn IS NULL OR ci.last_worn <', date('Y-m-d', strtotime('-30 days')));
        $this->db->order_by('ci.date_added', 'DESC');
        $this->db->limit(10);
        $recommendations = $this->db->get()->result_array();

        // Items not worn recently
        $this->db->select('ci.*');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->where('ci.user_id', $user_id);
        $this->db->where('(ci.last_worn IS NULL OR ci.last_worn <', date('Y-m-d', strtotime('-90 days')), false);
        $this->db->or_where('ci.last_worn IS NULL)', null, false);
        $this->db->order_by('ci.last_worn', 'ASC');
        $this->db->limit(10);
        $not_worn_recently = $this->db->get()->result_array();

        return [
            'current_season' => $current_season,
            'season_name' => $this->get_season_name($current_season),
            'recommendations' => $recommendations,
            'not_worn_recently' => $not_worn_recently
        ];
    }

    /**
     * Maintenance recommendations
     */
    public function get_maintenance_recommendations($user_id)
    {
        // Items that need cleaning (last_cleaned > 30 days ago or never cleaned)
        $this->db->select('ci.*');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->where('ci.user_id', $user_id);
        $this->db->where('(ci.last_cleaned IS NULL OR ci.last_cleaned <', date('Y-m-d', strtotime('-30 days')), false);
        $this->db->or_where('ci.last_cleaned IS NULL)', null, false);
        $this->db->where('ci.last_worn IS NOT NULL');
        $this->db->order_by('ci.last_worn', 'DESC');
        $needs_cleaning = $this->db->get()->result_array();

        // Items worn frequently but not cleaned
        $this->db->select('ci.*, COUNT(wo.id) as wear_count');
        $this->db->from($this->clothing_items_table . ' ci');
        $this->db->join('outfit_items oi', 'ci.id = oi.clothing_item_id', 'left');
        $this->db->join($this->worn_outfits_table . ' wo', 'oi.outfit_id = wo.outfit_id', 'left');
        $this->db->where('ci.user_id', $user_id);
        $this->db->where('wo.date_worn >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->where('(ci.last_cleaned IS NULL OR ci.last_cleaned < wo.date_worn)');
        $this->db->group_by('ci.id');
        $this->db->having('wear_count >=', 3);
        $this->db->order_by('wear_count', 'DESC');
        $frequently_worn = $this->db->get()->result_array();

        return [
            'needs_cleaning' => $needs_cleaning,
            'frequently_worn_not_cleaned' => $frequently_worn
        ];
    }

    /**
     * Get current season
     */
    private function get_current_season($month)
    {
        $season_map = [3, 3, 0, 0, 0, 1, 1, 1, 2, 2, 2, 3];
        return $season_map[$month - 1];
    }

    /**
     * Get season name
     */
    private function get_season_name($season)
    {
        $seasons = ['Spring', 'Summer', 'Autumn', 'Winter'];
        return $seasons[$season] ?? 'Unknown';
    }

    /**
     * Tüm istatistikleri getir (admin için)
     */
    public function get_all_statistics()
    {
        // Toplam kullanıcılar
        $total_users = $this->db->count_all('users');
        
        // Toplam kıyafetler
        $total_clothing = $this->db->count_all($this->clothing_items_table);
        
        // Toplam kombinler
        $total_outfits = $this->db->count_all($this->outfits_table);
        
        // Aktif kullanıcılar (son 30 gün)
        $this->db->where('last_login >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        $active_users = $this->db->count_all_results('users');
        
        // Kategori dağılımı
        $this->db->select('category, COUNT(*) as count');
        $this->db->group_by('category');
        $category_dist = $this->db->get($this->clothing_items_table)->result_array();
        
        // Stil dağılımı
        $this->db->select('style, COUNT(*) as count');
        $this->db->group_by('style');
        $style_dist = $this->db->get($this->outfits_table)->result_array();
        
        // Son 7 günlük aktivite
        $daily_activity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $this->db->where('DATE(created_at)', $date);
            $daily_activity[$date] = [
                'users' => $this->db->count_all_results('users'),
                'clothing' => $this->db->count_all_results($this->clothing_items_table),
                'outfits' => $this->db->count_all_results($this->outfits_table)
            ];
        }
        
        return [
            'total_users' => $total_users,
            'total_clothing' => $total_clothing,
            'total_outfits' => $total_outfits,
            'active_users' => $active_users,
            'category_distribution' => $category_dist,
            'style_distribution' => $style_dist,
            'daily_activity' => $daily_activity
        ];
    }
}

