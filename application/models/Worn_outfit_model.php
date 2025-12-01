<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Worn_outfit_model extends CI_Model
{
    protected $table = 'worn_outfits';
    protected $outfits_table = 'outfits';
    protected $outfit_items_table = 'outfit_items';
    protected $clothing_items_table = 'clothing_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Giyilen kombinleri getir
     */
    public function get_all($user_id, $filters = [])
    {
        $this->db->where('wo.user_id', $user_id);
        
        // Date range filter
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $this->db->where('wo.date_worn >=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $this->db->where('wo.date_worn <=', $filters['date_to']);
        }
        
        // Outfit filter
        if (isset($filters['outfit_id']) && !empty($filters['outfit_id'])) {
            $this->db->where('wo.outfit_id', $filters['outfit_id']);
        }

        $this->db->select('wo.*, o.name as outfit_name, o.style as outfit_style');
        $this->db->from($this->table . ' wo');
        $this->db->join($this->outfits_table . ' o', 'wo.outfit_id = o.id', 'left');
        $this->db->order_by('wo.date_worn', 'DESC');
        
        if (isset($filters['limit'])) {
            $this->db->limit($filters['limit']);
        }
        if (isset($filters['offset'])) {
            $this->db->offset($filters['offset']);
        }

        $results = $this->db->get()->result_array();
        
        // Enrich with outfit items
        foreach ($results as &$result) {
            if ($result['outfit_id']) {
                $result['outfit_items'] = $this->get_outfit_items($result['outfit_id']);
            }
        }

        return $results;
    }

    /**
     * ID'ye göre giyilen kombin getir
     */
    public function get_by_id($id, $user_id = null)
    {
        $this->db->where('wo.id', $id);
        if ($user_id) {
            $this->db->where('wo.user_id', $user_id);
        }

        $this->db->select('wo.*, o.name as outfit_name, o.style as outfit_style');
        $this->db->from($this->table . ' wo');
        $this->db->join($this->outfits_table . ' o', 'wo.outfit_id = o.id', 'left');
        
        $result = $this->db->get()->row_array();
        
        if ($result && $result['outfit_id']) {
            $result['outfit_items'] = $this->get_outfit_items($result['outfit_id']);
        }

        return $result;
    }

    /**
     * Giyilen kombin oluştur
     */
    public function create($data)
    {
        $insert_data = [
            'user_id' => $data['user_id'],
            'outfit_id' => $data['outfit_id'] ?? null,
            'date_worn' => $data['date_worn'],
            'notes' => $data['notes'] ?? null,
            'weather_data' => isset($data['weather_data']) ? json_encode($data['weather_data']) : null,
            'location_data' => isset($data['location_data']) ? json_encode($data['location_data']) : null
        ];

        $this->db->insert($this->table, $insert_data);
        $id = $this->db->insert_id();

        // Wear count güncelle
        if ($data['outfit_id']) {
            $this->increment_outfit_wear_count($data['outfit_id']);
        }

        // Clothing items wear count güncelle
        if ($data['outfit_id']) {
            $this->increment_clothing_items_wear_count($data['outfit_id'], $data['date_worn']);
        }

        return $id;
    }

    /**
     * Giyilen kombin güncelle
     */
    public function update($id, $data, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        $update_data = [];
        if (isset($data['date_worn'])) {
            $update_data['date_worn'] = $data['date_worn'];
        }
        if (isset($data['notes'])) {
            $update_data['notes'] = $data['notes'];
        }
        if (isset($data['weather_data'])) {
            $update_data['weather_data'] = json_encode($data['weather_data']);
        }
        if (isset($data['location_data'])) {
            $update_data['location_data'] = json_encode($data['location_data']);
        }

        return $this->db->update($this->table, $update_data);
    }

    /**
     * Giyilen kombin sil
     */
    public function delete($id, $user_id = null)
    {
        // Wear count'u azalt
        $worn_outfit = $this->get_by_id($id, $user_id);
        if ($worn_outfit && $worn_outfit['outfit_id']) {
            $this->decrement_outfit_wear_count($worn_outfit['outfit_id']);
        }

        $this->db->where('id', $id);
        if ($user_id) {
            $this->db->where('user_id', $user_id);
        }

        return $this->db->delete($this->table);
    }

    /**
     * Calendar view - Belirli bir ay için giyilen kombinler
     */
    public function get_calendar_view($user_id, $year, $month)
    {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $start_date);
        $this->db->where('date_worn <=', $end_date);
        $this->db->order_by('date_worn', 'ASC');

        $results = $this->db->get($this->table)->result_array();

        // Group by date
        $calendar = [];
        foreach ($results as $result) {
            $day = date('d', strtotime($result['date_worn']));
            if (!isset($calendar[$day])) {
                $calendar[$day] = [];
            }
            $calendar[$day][] = $result;
        }

        return $calendar;
    }

    /**
     * Statistics - Giyilen kombin istatistikleri
     */
    public function get_statistics($user_id, $filters = [])
    {
        $date_from = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $filters['date_to'] ?? date('Y-m-d');

        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $date_from);
        $this->db->where('date_worn <=', $date_to);

        $total_worn = $this->db->count_all_results($this->table);

        // Most worn outfit
        $this->db->select('outfit_id, COUNT(*) as wear_count');
        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $date_from);
        $this->db->where('date_worn <=', $date_to);
        $this->db->where('outfit_id IS NOT NULL');
        $this->db->group_by('outfit_id');
        $this->db->order_by('wear_count', 'DESC');
        $this->db->limit(1);
        $most_worn = $this->db->get($this->table)->row_array();

        // Daily average
        $days = (strtotime($date_to) - strtotime($date_from)) / 86400 + 1;
        $daily_average = $days > 0 ? round($total_worn / $days, 2) : 0;

        // Weekly distribution
        $this->db->select('DAYOFWEEK(date_worn) as day_of_week, COUNT(*) as count');
        $this->db->where('user_id', $user_id);
        $this->db->where('date_worn >=', $date_from);
        $this->db->where('date_worn <=', $date_to);
        $this->db->group_by('DAYOFWEEK(date_worn)');
        $weekly_dist = $this->db->get($this->table)->result_array();

        return [
            'total_worn' => $total_worn,
            'daily_average' => $daily_average,
            'most_worn_outfit' => $most_worn,
            'weekly_distribution' => $weekly_dist,
            'date_range' => [
                'from' => $date_from,
                'to' => $date_to
            ]
        ];
    }

    /**
     * Wear count tracking - Kıyafet giyim sayısını güncelle
     */
    private function increment_clothing_items_wear_count($outfit_id, $date_worn)
    {
        // Outfit'teki tüm kıyafetleri al
        $items = $this->get_outfit_items($outfit_id);
        
        foreach ($items as $item) {
            $this->db->where('id', $item['id']);
            $this->db->set('last_worn', $date_worn);
            
            // Wear count JSON'u güncelle
            $this->db->select('wear_count');
            $current = $this->db->get($this->clothing_items_table)->row();
            $wear_count = $current ? json_decode($current->wear_count, true) : [];
            
            $year_month = date('Y-m', strtotime($date_worn));
            if (!isset($wear_count[$year_month])) {
                $wear_count[$year_month] = 0;
            }
            $wear_count[$year_month]++;
            
            $this->db->where('id', $item['id']);
            $this->db->update($this->clothing_items_table, [
                'wear_count' => json_encode($wear_count),
                'last_worn' => $date_worn
            ]);
        }
    }

    /**
     * Outfit wear count artır
     */
    private function increment_outfit_wear_count($outfit_id)
    {
        // Outfit tablosunda wear_count kolonu yoksa, bu fonksiyon kullanılmaz
        // İstatistikler worn_outfits tablosundan hesaplanır
    }

    /**
     * Outfit wear count azalt
     */
    private function decrement_outfit_wear_count($outfit_id)
    {
        // Outfit tablosunda wear_count kolonu yoksa, bu fonksiyon kullanılmaz
    }

    /**
     * Outfit items getir
     */
    private function get_outfit_items($outfit_id)
    {
        $this->db->select('ci.*');
        $this->db->from($this->outfit_items_table . ' oi');
        $this->db->join($this->clothing_items_table . ' ci', 'oi.clothing_item_id = ci.id');
        $this->db->where('oi.outfit_id', $outfit_id);
        
        return $this->db->get()->result_array();
    }
}

