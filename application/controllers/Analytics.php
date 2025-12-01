<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class Analytics extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Analytics_model');
    }

    /**
     * Wardrobe statistics
     * GET /api/analytics/wardrobe
     */
    public function wardrobe()
    {
        $user_id = $this->require_auth();
        
        $stats = $this->Analytics_model->get_wardrobe_statistics($user_id);
        
        $this->success($stats);
    }

    /**
     * Usage analytics
     * GET /api/analytics/usage
     * Query params: date_from, date_to
     */
    public function usage()
    {
        $user_id = $this->require_auth();
        
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];

        $analytics = $this->Analytics_model->get_usage_analytics($user_id, $filters);
        
        $this->success($analytics);
    }

    /**
     * Style insights
     * GET /api/analytics/style
     */
    public function style()
    {
        $user_id = $this->require_auth();
        
        $insights = $this->Analytics_model->get_style_insights($user_id);
        
        $this->success($insights);
    }

    /**
     * Seasonal recommendations
     * GET /api/analytics/seasonal
     */
    public function seasonal()
    {
        $user_id = $this->require_auth();
        
        $recommendations = $this->Analytics_model->get_seasonal_recommendations($user_id);
        
        $this->success($recommendations);
    }

    /**
     * Maintenance recommendations
     * GET /api/analytics/maintenance
     */
    public function maintenance()
    {
        $user_id = $this->require_auth();
        
        $recommendations = $this->Analytics_model->get_maintenance_recommendations($user_id);
        
        $this->success($recommendations);
    }
}

