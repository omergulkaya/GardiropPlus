<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Image Processing Library
 * Resim işleme, compression, resizing, WebP conversion
 */
class Image_processing_library
{
    private $ci;
    private $max_width = 1920;
    private $max_height = 1920;
    private $quality = 85;
// JPEG quality
    private $webp_quality = 85; // WebP quality

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * Resmi optimize et (resize + compress)
     *
     * @param string $source_path Kaynak dosya yolu
     * @param string $destination_path Hedef dosya yolu
     * @param array $options İşlem seçenekleri
     * @return array İşlem sonucu
     */
    public function optimize($source_path, $destination_path = null, $options = [])
    {
        if (!file_exists($source_path)) {
            return ['success' => false, 'error' => 'Source file not found'];
        }

        $max_width = $options['max_width'] ?? $this->max_width;
        $max_height = $options['max_height'] ?? $this->max_height;
        $quality = $options['quality'] ?? $this->quality;
        $format = $options['format'] ?? null;
// 'webp', 'jpg', 'png', null (original)

        $image_info = getimagesize($source_path);
        if (!$image_info) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }

        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
// Resim boyutlarını hesapla
        $dimensions = $this->calculate_dimensions($original_width, $original_height, $max_width, $max_height);
        $new_width = $dimensions['width'];
        $new_height = $dimensions['height'];
// Resmi yükle
        $source_image = $this->load_image($source_path, $mime_type);
        if (!$source_image) {
            return ['success' => false, 'error' => 'Failed to load image'];
        }

        // Yeni resim oluştur
        $new_image = imagecreatetruecolor($new_width, $new_height);
// Transparency için (PNG, GIF)
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefill($new_image, 0, 0, $transparent);
        }

        // Resize
        imagecopyresampled(
            $new_image,
            $source_image,
            0,
            0,
            0,
            0,
            $new_width,
            $new_height,
            $original_width,
            $original_height
        );
// Hedef dosya yolu belirle
        if (!$destination_path) {
            $destination_path = $source_path;
// Overwrite original
        }

        // Format belirle
        if ($format === 'webp' && function_exists('imagewebp')) {
            $destination_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $destination_path);
            $saved = imagewebp($new_image, $destination_path, $this->webp_quality);
        } elseif ($format === 'jpg' || $mime_type === 'image/jpeg') {
            $destination_path = preg_replace('/\.(png|gif|webp)$/i', '.jpg', $destination_path);
            $saved = imagejpeg($new_image, $destination_path, $quality);
        } elseif ($mime_type === 'image/png') {
            $saved = imagepng($new_image, $destination_path, 9);
        // PNG compression level 0-9
        } elseif ($mime_type === 'image/gif') {
            $saved = imagegif($new_image, $destination_path);
        } else {
            $saved = imagejpeg($new_image, $destination_path, $quality);
        }

        // Memory temizle
        imagedestroy($source_image);
        imagedestroy($new_image);
        if ($saved) {
            $original_size = filesize($source_path);
            $new_size = filesize($destination_path);
            $compression_ratio = round((1 - ($new_size / $original_size)) * 100, 2);
            return [
                'success' => true,
                'path' => $destination_path,
                'original_size' => $original_size,
                'new_size' => $new_size,
                'compression_ratio' => $compression_ratio,
                'width' => $new_width,
                'height' => $new_height,
                'format' => $format ?? pathinfo($destination_path, PATHINFO_EXTENSION)
            ];
        }

        return ['success' => false, 'error' => 'Failed to save image'];
    }

    /**
     * Resim boyutlarını hesapla (aspect ratio korunarak)
     */
    private function calculate_dimensions($original_width, $original_height, $max_width, $max_height)
    {
        $ratio = $original_width / $original_height;
        if ($original_width <= $max_width && $original_height <= $max_height) {
        // Resize gerekmiyor
            return ['width' => $original_width, 'height' => $original_height];
        }

        if ($ratio > 1) {
// Landscape
            $new_width = $max_width;
            $new_height = $max_width / $ratio;
        } else {
        // Portrait
            $new_height = $max_height;
            $new_width = $max_height * $ratio;
        }

        return [
            'width' => (int)$new_width,
            'height' => (int)$new_height
        ];
    }

    /**
     * Resmi yükle
     */
    private function load_image($path, $mime_type)
    {
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($path);
                }

                break;
        }
        return false;
    }

    /**
     * Thumbnail oluştur
     *
     * @param string $source_path Kaynak dosya
     * @param string $destination_path Hedef dosya
     * @param int $width Thumbnail genişliği
     * @param int $height Thumbnail yüksekliği
     * @return array
     */
    public function create_thumbnail($source_path, $destination_path, $width = 300, $height = 300)
    {
        return $this->optimize($source_path, $destination_path, [
            'max_width' => $width,
            'max_height' => $height,
            'quality' => 75
        ]);
    }

    /**
     * WebP formatına dönüştür
     */
    public function convert_to_webp($source_path, $destination_path = null)
    {
        if (!function_exists('imagewebp')) {
            return ['success' => false, 'error' => 'WebP support not available'];
        }

        if (!$destination_path) {
            $destination_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $source_path);
        }

        return $this->optimize($source_path, $destination_path, [
            'format' => 'webp'
        ]);
    }

    /**
     * Resim bilgilerini al
     */
    public function get_image_info($path)
    {
        if (!file_exists($path)) {
            return null;
        }

        $info = getimagesize($path);
        if (!$info) {
            return null;
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime' => $info['mime'],
            'size' => filesize($path),
            'format' => pathinfo($path, PATHINFO_EXTENSION)
        ];
    }

    /**
     * Resim doğrulama (gerçek resim mi kontrol et)
     */
    public function validate_image($path)
    {
        if (!file_exists($path)) {
            return ['valid' => false, 'error' => 'File not found'];
        }

        $image_info = getimagesize($path);
        if (!$image_info) {
            return ['valid' => false, 'error' => 'Not a valid image file'];
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($image_info['mime'], $allowed_types)) {
            return ['valid' => false, 'error' => 'Unsupported image type: ' . $image_info['mime']];
        }

        return ['valid' => true, 'info' => $image_info];
    }
}
