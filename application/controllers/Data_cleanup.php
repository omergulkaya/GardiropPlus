<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Data Cleanup Controller
 * Veri temizleme için cron job controller
 * 
 * Kullanım: php index.php data_cleanup run
 * veya cron: 0 2 * * * php /path/to/index.php data_cleanup run
 */
class Data_cleanup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Data_retention_model');
        
        // Sadece CLI veya admin erişimi
        if (!$this->input->is_cli_request() && !$this->is_admin()) {
            show_404();
        }
    }

    /**
     * Tüm veri temizleme işlemlerini çalıştır
     */
    public function run()
    {
        log_message('info', 'Data cleanup job started');
        
        $results = $this->Data_retention_model->cleanup_all();
        
        $output = "Data Cleanup Results:\n";
        $output .= "====================\n\n";
        
        foreach ($results as $data_type => $result) {
            $output .= "Data Type: {$data_type}\n";
            $output .= "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            $output .= "Records Deleted: {$result['records_deleted']}\n";
            $output .= "Execution Time: {$result['execution_time']}s\n";
            if (!$result['success']) {
                $output .= "Error: {$result['message']}\n";
            }
            $output .= "\n";
        }
        
        log_message('info', 'Data cleanup job completed');
        
        if ($this->input->is_cli_request()) {
            echo $output;
        } else {
            $this->output->set_content_type('text/plain');
            $this->output->set_output($output);
        }
    }

    /**
     * Belirli bir veri tipi için temizleme
     */
    public function cleanup($data_type)
    {
        if (!$data_type) {
            show_404();
        }
        
        $result = $this->Data_retention_model->cleanup_data($data_type);
        
        if ($this->input->is_cli_request()) {
            echo "Cleanup result for {$data_type}: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "Records deleted: {$result['records_deleted']}\n";
        } else {
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($result));
        }
    }

    /**
     * Admin kontrolü
     */
    private function is_admin()
    {
        $this->load->library('session');
        return $this->session->userdata('admin_logged_in') === true;
    }
}

