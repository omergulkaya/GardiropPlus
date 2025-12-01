<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Read Replica Library
 * Okuma işlemleri için read replica kullanımı
 */
class Read_replica_library
{
    private $replicas = [];
    private $current_replica = null;
    
    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->database();
        
        // Replica bilgilerini veritabanından al
        $this->load_replicas();
    }

    /**
     * Replica'ları yükle
     */
    private function load_replicas()
    {
        $CI =& get_instance();
        
        // Replica'ları veritabanından al
        $query = $CI->db->get_where('database_replicas', [
            'type' => 'read',
            'is_active' => 1
        ]);
        
        $this->replicas = $query->result_array();
        
        // Priority'ye göre sırala
        usort($this->replicas, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
    }

    /**
     * Read replica bağlantısı al
     */
    public function get_connection()
    {
        if (empty($this->replicas)) {
            // Replica yoksa default connection kullan
            $CI =& get_instance();
            return $CI->db;
        }
        
        // Round-robin veya load balancing
        if (!$this->current_replica || !isset($this->replicas[$this->current_replica])) {
            $this->current_replica = 0;
        }
        
        $replica = $this->replicas[$this->current_replica];
        
        // Bağlantıyı oluştur
        $config = [
            'hostname' => $replica['host'],
            'username' => $replica['username'],
            'password' => $replica['password'],
            'database' => $replica['database'],
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug' => false,
            'cache_on' => false,
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
            'encrypt' => false,
            'compress' => false,
            'stricton' => false,
            'failover' => [],
            'save_queries' => false
        ];
        
        // Sonraki replica'ya geç (round-robin)
        $this->current_replica = ($this->current_replica + 1) % count($this->replicas);
        
        $CI =& get_instance();
        return $CI->load->database($config, true);
    }

    /**
     * Read-only query çalıştır
     */
    public function query($sql, $binds = false, $return_object = null)
    {
        $db = $this->get_connection();
        return $db->query($sql, $binds, $return_object);
    }
}

