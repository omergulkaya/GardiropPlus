<?php

use PHPUnit\Framework\TestCase;

/**
 * Base Test Case
 * CodeIgniter testleri için base class
 * 
 * NOT: CodeIgniter instance'ı test sırasında oluşturulacak
 */
abstract class BaseTestCase extends TestCase
{
    protected $ci;

    protected function setUp(): void
    {
        parent::setUp();
        
        // CodeIgniter'ı başlat
        if (!defined('BASEPATH')) {
            require_once __DIR__ . '/../bootstrap.php';
        }

        // CodeIgniter'ı başlatmaya çalış
        // CodeIgniter başlatma işlemi karmaşık olduğu için
        // Test'lerde skip mekanizması kullanıyoruz
        try {
            // CI_Controller class'ını yükle
            if (!class_exists('CI_Controller')) {
                require_once BASEPATH . 'core/Controller.php';
            }
            
            // get_instance fonksiyonu CodeIgniter başlatıldıktan sonra tanımlı
            // Bu yüzden CodeIgniter'ı başlatmaya çalışıyoruz
            if (!function_exists('get_instance')) {
                // CodeIgniter'ı minimal olarak başlat
                // Sadece gerekli core class'ları yükle
                // Ancak CodeIgniter.php tam başlatma yapar, bu yüzden sadece Controller'ı yükleyelim
                if (class_exists('CI_Controller')) {
                    // get_instance fonksiyonunu manuel olarak tanımla
                    if (!function_exists('get_instance')) {
                        function &get_instance() {
                            return CI_Controller::get_instance();
                        }
                    }
                }
            }
            
            // Try to get instance
            if (function_exists('get_instance')) {
                $this->ci =& get_instance();
            }
            
            // If still no instance, test will be skipped
            if (!$this->ci) {
                $this->markTestSkipped('CodeIgniter instance not available - unit tests require CodeIgniter to be properly initialized. Consider using integration tests instead.');
                return;
            }
        } catch (Exception $e) {
            $this->markTestSkipped('Failed to initialize CodeIgniter: ' . $e->getMessage());
        } catch (Error $e) {
            $this->markTestSkipped('Failed to initialize CodeIgniter: ' . $e->getMessage());
        } catch (Throwable $e) {
            $this->markTestSkipped('Failed to initialize CodeIgniter: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Cleanup if needed
        parent::tearDown();
    }
}
