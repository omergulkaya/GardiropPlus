<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pagination Optimizer Library
 * Cursor-based pagination ve optimizasyonlar
 */
class Pagination_optimizer
{
    /**
     * Cursor-based pagination için cursor oluştur
     */
    public function create_cursor($id, $timestamp = null)
    {
        $data = [
            'id' => $id,
            'ts' => $timestamp ?: time()
        ];
        return base64_encode(json_encode($data));
    }

    /**
     * Cursor'dan ID ve timestamp al
     */
    public function parse_cursor($cursor)
    {
        try {
            $data = json_decode(base64_decode($cursor), true);
            return [
                'id' => $data['id'] ?? null,
                'timestamp' => $data['ts'] ?? null
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cursor-based query oluştur
     */
    public function apply_cursor($db, $cursor, $id_field = 'id', $timestamp_field = 'created_at')
    {
        if (!$cursor) {
            return $db;
        }
        
        $parsed = $this->parse_cursor($cursor);
        if (!$parsed) {
            return $db;
        }
        
        // Cursor'dan sonraki kayıtları getir
        if ($parsed['timestamp']) {
            $db->group_start();
            $db->where($timestamp_field . ' <', date('Y-m-d H:i:s', $parsed['timestamp']));
            $db->or_group_start();
            $db->where($timestamp_field, date('Y-m-d H:i:s', $parsed['timestamp']));
            $db->where($id_field . ' <', $parsed['id']);
            $db->group_end();
            $db->group_end();
        } else {
            $db->where($id_field . ' <', $parsed['id']);
        }
        
        return $db;
    }

    /**
     * Pagination metadata oluştur
     */
    public function create_metadata($items, $limit, $cursor = null, $next_cursor = null)
    {
        $has_more = count($items) > $limit;
        if ($has_more) {
            // Son item'ı çıkar (next cursor için)
            array_pop($items);
        }
        
        return [
            'items' => $items,
            'pagination' => [
                'has_more' => $has_more,
                'cursor' => $cursor,
                'next_cursor' => $next_cursor,
                'limit' => $limit
            ]
        ];
    }
}

