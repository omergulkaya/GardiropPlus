<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Metadata_model extends CI_Model
{
    protected $categories_table = 'categories';
    protected $category_translations_table = 'category_translations';
    protected $colors_table = 'colors';
    protected $color_translations_table = 'color_translations';
    protected $seasons_table = 'seasons';
    protected $season_translations_table = 'season_translations';
    protected $styles_table = 'styles';
    protected $style_translations_table = 'style_translations';
    protected $event_types_table = 'event_types';
    protected $event_type_translations_table = 'event_type_translations';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Kategorileri getir (dil desteği ile)
     */
    public function get_categories($language_code = 'tr')
    {
        $this->db->select('c.id, c.code, c.icon_code, c.display_order, ct.name');
        $this->db->from($this->categories_table . ' c');
        $this->db->join($this->category_translations_table . ' ct', 'c.id = ct.category_id', 'left');
        $this->db->where('c.is_active', true);
        $this->db->where('ct.language_code', $language_code);
        $this->db->order_by('c.display_order', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Renkleri getir (dil desteği ile)
     */
    public function get_colors($language_code = 'tr')
    {
        $this->db->select('c.id, c.code, c.hex_code, c.display_order, ct.name');
        $this->db->from($this->colors_table . ' c');
        $this->db->join($this->color_translations_table . ' ct', 'c.id = ct.color_id', 'left');
        $this->db->where('c.is_active', true);
        $this->db->where('ct.language_code', $language_code);
        $this->db->order_by('c.display_order', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Sezonları getir (dil desteği ile)
     */
    public function get_seasons($language_code = 'tr')
    {
        $this->db->select('s.id, s.code, s.display_order, st.name');
        $this->db->from($this->seasons_table . ' s');
        $this->db->join($this->season_translations_table . ' st', 's.id = st.season_id', 'left');
        $this->db->where('s.is_active', true);
        $this->db->where('st.language_code', $language_code);
        $this->db->order_by('s.display_order', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Stilleri getir (dil desteği ile)
     */
    public function get_styles($language_code = 'tr')
    {
        $this->db->select('s.id, s.code, s.display_order, st.name, st.description');
        $this->db->from($this->styles_table . ' s');
        $this->db->join($this->style_translations_table . ' st', 's.id = st.style_id', 'left');
        $this->db->where('s.is_active', true);
        $this->db->where('st.language_code', $language_code);
        $this->db->order_by('s.display_order', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Etkinlik türlerini getir (dil desteği ile)
     */
    public function get_event_types($language_code = 'tr')
    {
        $this->db->select('e.id, e.code, e.display_order, et.name, et.description');
        $this->db->from($this->event_types_table . ' e');
        $this->db->join($this->event_type_translations_table . ' et', 'e.id = et.event_type_id', 'left');
        $this->db->where('e.is_active', true);
        $this->db->where('et.language_code', $language_code);
        $this->db->order_by('e.display_order', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Code'a göre kategori ID'si getir
     */
    public function get_category_id_by_code($code)
    {
        $this->db->select('id');
        $this->db->from($this->categories_table);
        $this->db->where('code', $code);
        $result = $this->db->get()->row();
        return $result ? $result->id : null;
    }

    /**
     * Code'a göre renk ID'si getir
     */
    public function get_color_id_by_code($code)
    {
        $this->db->select('id');
        $this->db->from($this->colors_table);
        $this->db->where('code', $code);
        $result = $this->db->get()->row();
        return $result ? $result->id : null;
    }

    /**
     * Code'a göre sezon ID'si getir
     */
    public function get_season_id_by_code($code)
    {
        $this->db->select('id');
        $this->db->from($this->seasons_table);
        $this->db->where('code', $code);
        $result = $this->db->get()->row();
        return $result ? $result->id : null;
    }

    /**
     * Code'a göre stil ID'si getir
     */
    public function get_style_id_by_code($code)
    {
        $this->db->select('id');
        $this->db->from($this->styles_table);
        $this->db->where('code', $code);
        $result = $this->db->get()->row();
        return $result ? $result->id : null;
    }

    /**
     * Code'a göre etkinlik türü ID'si getir
     */
    public function get_event_type_id_by_code($code)
    {
        $this->db->select('id');
        $this->db->from($this->event_types_table);
        $this->db->where('code', $code);
        $result = $this->db->get()->row();
        return $result ? $result->id : null;
    }
}

