<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'controllers/Api.php';

class Upload extends Api
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('upload');
    }

    /**
     * Image upload endpoint
     * POST /api/upload/image
     * Supports: single file, multiple files, automatic optimization
     */
    public function image()
    {
        $user_id = $this->require_auth();
// Upload klasörü yolu
        $upload_path = FCPATH . 'uploads/images/';
// Klasör yoksa oluştur
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        // User klasörü oluştur
        $user_folder = $upload_path . 'user_' . $user_id . '/';
        if (!is_dir($user_folder)) {
            mkdir($user_folder, 0755, true);
        }

        // Thumbnail klasörü
        $thumbnail_folder = $user_folder . 'thumbnails/';
        if (!is_dir($thumbnail_folder)) {
            mkdir($thumbnail_folder, 0755, true);
        }

        // Multiple file upload kontrolü
        $multiple = $this->input->post('multiple') === 'true' || $this->input->post('multiple') === true;
        $optimize = $this->input->post('optimize') !== 'false';
// Default: true
        $create_webp = $this->input->post('webp') === 'true' || $this->input->post('webp') === true;
        $this->load->library('image_processing_library');
        if ($multiple && isset($_FILES['images'])) {
        // Multiple file upload
            $uploaded_files = [];
            $failed_files = [];
            foreach ($_FILES['images']['name'] as $key => $filename) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
                    $failed_files[] = ['filename' => $filename, 'error' => 'Upload error'];
                    continue;
                }

                // File validation
                $temp_file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];
                $validation = $this->validate_file($temp_file);
                if (!$validation['valid']) {
                    $failed_files[] = ['filename' => $filename, 'error' => $validation['error']];
                    continue;
                }

                // Upload single file
                $result = $this->upload_single_file($temp_file, $user_folder, $thumbnail_folder, $optimize, $create_webp, $user_id);
                if ($result['success']) {
                    $uploaded_files[] = $result['data'];
                } else {
                    $failed_files[] = ['filename' => $filename, 'error' => $result['error']];
                }
            }

            $this->success([
                'uploaded' => $uploaded_files,
                'failed' => $failed_files,
                'total' => count($_FILES['images']['name']),
                'success_count' => count($uploaded_files)
            ], 'Upload completed: ' . count($uploaded_files) . ' successful, ' . count($failed_files) . ' failed');
        } else {
        // Single file upload
            $config['upload_path'] = $user_folder;
            $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
            $config['max_size'] = 10240;
        // 10MB
            $config['encrypt_name'] = true;
            $config['remove_spaces'] = true;
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('image')) {
                $error = $this->upload->display_errors('', '');
                $this->error('Upload failed: ' . $error, 400);
            }

            $upload_data = $this->upload->data();
            $source_path = $upload_data['full_path'];
        // Image optimization
            $optimization_result = null;
            if ($optimize) {
                $optimized_path = $user_folder . 'optimized_' . $upload_data['file_name'];
                $optimization_result = $this->image_processing_library->optimize($source_path, $optimized_path, [
                'max_width' => 1920,
                'max_height' => 1920,
                'quality' => 85
                ]);
                if ($optimization_result['success']) {
                        // Optimized version'ı kullan
                                unlink($source_path);
                        // Original'ı sil
                                rename($optimized_path, $source_path);
                        $upload_data['file_size'] = $optimization_result['new_size'];
                }
            }

            // WebP conversion
            $webp_path = null;
            if ($create_webp && function_exists('imagewebp')) {
                $webp_result = $this->image_processing_library->convert_to_webp($source_path);
                if ($webp_result['success']) {
                    $webp_path = $webp_result['path'];
                }
            }

            // Thumbnail oluştur
            $thumbnail_path = $thumbnail_folder . 'thumb_' . $upload_data['file_name'];
            $thumbnail_result = $this->image_processing_library->create_thumbnail($source_path, $thumbnail_path, 300, 300);
            $image_url = base_url('uploads/images/user_' . $user_id . '/' . $upload_data['file_name']);
            $relative_path = 'uploads/images/user_' . $user_id . '/' . $upload_data['file_name'];
            $response_data = [
                'url' => $image_url,
                'path' => $relative_path,
                'filename' => $upload_data['file_name'],
                'size' => $upload_data['file_size'],
                'width' => $upload_data['image_width'],
                'height' => $upload_data['image_height'],
            ];
            if ($optimization_result && $optimization_result['success']) {
                $response_data['compression_ratio'] = $optimization_result['compression_ratio'];
                $response_data['original_size'] = $optimization_result['original_size'];
            }

            if ($webp_path) {
                $response_data['webp_url'] = base_url(str_replace(FCPATH, '', $webp_path));
                $response_data['webp_path'] = str_replace(FCPATH, '', $webp_path);
            }

            if ($thumbnail_result && $thumbnail_result['success']) {
                $response_data['thumbnail_url'] = base_url(str_replace(FCPATH, '', $thumbnail_path));
            }

            $this->success($response_data, 'Image uploaded successfully');
        }
    }

    /**
     * Single file upload helper
     */
    private function upload_single_file($file, $upload_path, $thumbnail_path, $optimize, $create_webp, $user_id)
    {
        // File validation
        $validation = $this->validate_file($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;
        $destination = $upload_path . $filename;
// Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        // Image optimization
        $optimization_result = null;
        if ($optimize) {
            $optimized_path = $upload_path . 'optimized_' . $filename;
            $optimization_result = $this->image_processing_library->optimize($destination, $optimized_path);
            if ($optimization_result['success']) {
                unlink($destination);
                rename($optimized_path, $destination);
            }
        }

        // WebP conversion
        $webp_path = null;
        if ($create_webp && function_exists('imagewebp')) {
            $webp_result = $this->image_processing_library->convert_to_webp($destination);
            if ($webp_result['success']) {
                $webp_path = $webp_result['path'];
            }
        }

        // Thumbnail
        $thumbnail_result = $this->image_processing_library->create_thumbnail($destination, $thumbnail_path . 'thumb_' . $filename);
        $image_info = $this->image_processing_library->get_image_info($destination);
        $image_url = base_url('uploads/images/user_' . $user_id . '/' . $filename);
        $relative_path = 'uploads/images/user_' . $user_id . '/' . $filename;
        $data = [
            'url' => $image_url,
            'path' => $relative_path,
            'filename' => $filename,
            'size' => filesize($destination),
            'width' => $image_info['width'] ?? 0,
            'height' => $image_info['height'] ?? 0,
        ];
        if ($webp_path) {
            $data['webp_url'] = base_url(str_replace(FCPATH, '', $webp_path));
        }

        if ($thumbnail_result && $thumbnail_result['success']) {
            $data['thumbnail_url'] = base_url(str_replace(FCPATH, '', $thumbnail_path . 'thumb_' . $filename));
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * File validation
     */
    private function validate_file($file)
    {
        // File type validation
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)];
        }

        // MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime_type, $allowed_types)) {
            return ['valid' => false, 'error' => 'Invalid MIME type: ' . $mime_type];
        }

        // File size validation (10MB)
        $max_size = 10 * 1024 * 1024;
// 10MB
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'File size exceeds 10MB limit'];
        }

        // Image validation (gerçek resim mi)
        $image_validation = $this->image_processing_library->validate_image($file['tmp_name']);
        if (!$image_validation['valid']) {
            return ['valid' => false, 'error' => $image_validation['error']];
        }

        return ['valid' => true];
    }

    /**
     * Profile photo upload
     * POST /api/upload/profile-photo
     */
    public function profile_photo()
    {
        $user_id = $this->require_auth();
// Upload klasörü yolu
        $upload_path = FCPATH . 'uploads/profiles/';
// Klasör yoksa oluştur
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        // Upload yapılandırması
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
        $config['max_size'] = 5120;
// 5MB
        $config['file_name'] = 'user_' . $user_id;
// Kullanıcı ID'si ile kaydet
        $config['overwrite'] = true;
// Eski fotoğrafı üzerine yaz
        $config['remove_spaces'] = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('image')) {
            $error = $this->upload->display_errors('', '');
            $this->error('Upload failed: ' . $error, 400);
        }

        $upload_data = $this->upload->data();
// Kullanıcı profil fotoğrafını güncelle
        $this->load->model('User_model');
        $image_url = base_url('uploads/profiles/' . $upload_data['file_name']);
        $relative_path = 'uploads/profiles/' . $upload_data['file_name'];
        $this->User_model->update($user_id, [
            'profile_image_path' => $relative_path
        ]);
        // Güvenli URL oluştur (API endpoint üzerinden)
        $secure_url = base_url('api/upload/profile-photo/' . $user_id);
        
        $this->success([
            'url' => $secure_url,
            'path' => $relative_path,
            'filename' => $upload_data['file_name'],
        ], 'Profile photo uploaded successfully');
    }

    /**
     * Profil fotoğrafını güvenli şekilde servis et
     * GET /api/upload/profile-photo/{user_id}
     * Sadece login olan kullanıcılar kendi fotoğraflarına erişebilir
     */
    public function get_profile_photo($user_id = null)
    {
        // Authentication kontrolü
        $current_user_id = $this->require_auth();
        
        // Eğer user_id belirtilmemişse, mevcut kullanıcının ID'sini kullan
        if ($user_id === null) {
            $user_id = $current_user_id;
        }
        
        // Güvenlik: Kullanıcı sadece kendi profil fotoğrafına erişebilir
        if ($user_id != $current_user_id) {
            $this->error('Unauthorized: You can only access your own profile photo', 403);
        }
        
        // Dosya yolunu oluştur
        $file_path = FCPATH . 'uploads/profiles/user_' . $user_id . '.jpg';
        
        // Farklı formatları dene
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $found_file = null;
        
        foreach ($formats as $format) {
            $test_path = FCPATH . 'uploads/profiles/user_' . $user_id . '.' . $format;
            if (file_exists($test_path)) {
                $found_file = $test_path;
                break;
            }
        }
        
        if ($found_file === null) {
            $this->error('Profile photo not found', 404);
        }
        
        // Dosya bilgilerini al
        $file_info = pathinfo($found_file);
        $mime_type = $this->get_mime_type($file_info['extension']);
        
        // Content-Type header'ı ayarla
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($found_file));
        header('Cache-Control: private, max-age=3600');
        
        // Dosyayı oku ve gönder
        readfile($found_file);
        exit;
    }
    
    /**
     * Dosya uzantısına göre MIME type döndür
     */
    private function get_mime_type($extension)
    {
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        return $mime_types[strtolower($extension)] ?? 'image/jpeg';
    }

    /**
     * Delete uploaded image
     * DELETE /api/upload/image/{filename}
     */
    public function delete_image($filename)
    {
        $user_id = $this->require_auth();
// Güvenlik: Sadece kullanıcının kendi dosyalarını silebilir
        $file_path = FCPATH . 'uploads/images/user_' . $user_id . '/' . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
            $this->success(null, 'Image deleted successfully');
        } else {
            $this->error('Image not found', 404);
        }
    }
}
