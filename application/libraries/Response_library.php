<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Response Library
 * Consistent response structure, metadata support, response transformer için
 */
class Response_library
{
    private $ci;
    private $metadata = [];
    private $transformer = null;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * Success response oluştur
     * 
     * @param mixed $data
     * @param string $message
     * @param int $status_code
     * @param array $metadata
     * @return array
     */
    public function success($data = null, $message = 'Success', $status_code = 200, $metadata = [])
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        // Metadata ekle
        if (!empty($metadata) || !empty($this->metadata)) {
            $response['meta'] = array_merge($this->metadata, $metadata);
        }

        // Transformer uygula
        if ($this->transformer && $data) {
            $response['data'] = $this->transform($data);
        }

        return $response;
    }

    /**
     * Error response oluştur
     * 
     * @param string $message
     * @param int $status_code
     * @param array $errors
     * @return array
     */
    public function error($message = 'Error', $status_code = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $this->get_error_code($status_code),
                'status_code' => $status_code
            ]
        ];

        if ($errors !== null) {
            $response['error']['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Pagination metadata ekle
     */
    public function with_pagination($page, $limit, $total, $total_pages = null)
    {
        $this->metadata['pagination'] = [
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total' => (int)$total,
            'total_pages' => $total_pages ?: (int)ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ];

        return $this;
    }

    /**
     * Filter metadata ekle
     */
    public function with_filters($filters)
    {
        $this->metadata['filters'] = $filters;
        return $this;
    }

    /**
     * Sort metadata ekle
     */
    public function with_sort($sort, $order)
    {
        $this->metadata['sort'] = [
            'field' => $sort,
            'order' => strtoupper($order)
        ];

        return $this;
    }

    /**
     * Custom metadata ekle
     */
    public function with_meta($key, $value)
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Transformer set et
     */
    public function set_transformer($transformer)
    {
        $this->transformer = $transformer;
        return $this;
    }

    /**
     * Data transform et
     */
    private function transform($data)
    {
        if (is_callable($this->transformer)) {
            if (is_array($data) && isset($data[0])) {
                // Array of items
                return array_map($this->transformer, $data);
            } else {
                // Single item
                return call_user_func($this->transformer, $data);
            }
        }

        return $data;
    }

    /**
     * Metadata temizle
     */
    public function clear_metadata()
    {
        $this->metadata = [];
        return $this;
    }

    /**
     * Field selection uygula
     */
    public function select_fields($data, $fields)
    {
        if (empty($fields) || !is_array($fields)) {
            return $data;
        }

        if (is_array($data) && isset($data[0])) {
            // Array of items
            return array_map(function($item) use ($fields) {
                return $this->select_fields($item, $fields);
            }, $data);
        }

        // Single item
        $filtered = [];
        foreach ($fields as $field) {
            $field = trim($field);
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }

        return $filtered;
    }

    /**
     * Error code al
     */
    private function get_error_code($status_code)
    {
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'UNPROCESSABLE_ENTITY',
            423 => 'LOCKED',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_SERVER_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            504 => 'GATEWAY_TIMEOUT'
        ];

        return $codes[$status_code] ?? 'INTERNAL_SERVER_ERROR';
    }
}

