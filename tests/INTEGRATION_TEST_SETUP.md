# Integration Test Setup

Integration testlerin çalışması için aşağıdaki adımları izleyin:

## Gereksinimler

1. **Web Server**: XAMPP, Apache veya Nginx çalışıyor olmalı
2. **PHP**: PHP 7.4+ yüklü olmalı
3. **Database**: MySQL/MariaDB çalışıyor olmalı

## Kurulum

### 1. Web Server'ı Başlatın

XAMPP kullanıyorsanız:
- XAMPP Control Panel'den Apache'yi başlatın
- MySQL'i başlatın

### 2. Database Ayarları

`application/config/database.php` dosyasında database ayarlarını yapın:

```php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '', // XAMPP için genellikle boş
    'database' => 'closet_db', // veya test database
    // ...
);
```

### 3. Base URL Ayarları

Test dosyalarında `base_url` ayarını kontrol edin:

- `tests/Integration/AuthTest.php`: `$base_url = 'http://localhost/closet/web-api/index.php/api/auth';`
- `tests/Integration/ClothingItemTest.php`: `$base_url = 'http://localhost/closet/web-api/index.php/api/clothing-item';`

Eğer farklı bir URL kullanıyorsanız, bu değerleri güncelleyin.

### 4. Test Database Oluşturun (Opsiyonel)

Test'ler için ayrı bir database kullanmak istiyorsanız:

```sql
CREATE DATABASE closet_db_test;
```

Sonra `database.sql` dosyasını import edin:

```bash
mysql -u root -p closet_db_test < database.sql
```

## Test Çalıştırma

```bash
# Integration testleri çalıştır
vendor/bin/phpunit tests/Integration

# Veya tüm testleri
composer test
```

## Sorun Giderme

### "Connection error" veya "500 Internal Server Error"

1. **Web server çalışıyor mu?**
   - Tarayıcıda `http://localhost/closet/web-api/` adresini açın
   - Eğer CodeIgniter welcome sayfası görünüyorsa, server çalışıyor demektir

2. **Database bağlantısı**
   - `application/config/database.php` dosyasını kontrol edin
   - Database'in oluşturulduğundan emin olun
   - `application/logs/` klasöründeki log dosyalarını kontrol edin

3. **PHP hataları**
   - `application/logs/` klasöründeki log dosyalarını kontrol edin
   - PHP error log'unu kontrol edin (XAMPP için genellikle `C:\xampp\php\logs\php_error_log`)

4. **URL yapılandırması**
   - `.htaccess` dosyasının doğru çalıştığından emin olun
   - `application/config/config.php` dosyasında `base_url` ayarını kontrol edin

### "Invalid JSON response"

API endpoint'i HTML veya hata sayfası döndürüyor olabilir. Bu genellikle:
- Route yapılandırması hatası
- Controller bulunamıyor
- PHP fatal error

Çözüm: `application/logs/` klasöründeki log dosyalarını kontrol edin.

## Notlar

- Integration testler gerçek API endpoint'lerini test eder
- Test'ler sırasında database'e veri yazılabilir
- Production database kullanmayın!
- Test'lerden sonra test verilerini temizlemek isteyebilirsiniz

