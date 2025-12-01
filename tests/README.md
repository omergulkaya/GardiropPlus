# Test Documentation

Bu klasör, API için unit ve integration testlerini içerir.

## Kurulum

1. **Composer dependencies yükleyin:**
```bash
composer install
```

2. **Test veritabanı oluşturun:**
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS closet_db_test;"
mysql -u root -p closet_db_test < database.sql
```

3. **Test config dosyasını ayarlayın:**
`application/config/database.php` dosyasında test veritabanı ayarlarını yapın.

## Test Çalıştırma

### Tüm Testler
```bash
composer test
```

### Sadece Unit Testler
```bash
vendor/bin/phpunit tests/Unit
```

### Sadece Integration Testler
```bash
vendor/bin/phpunit tests/Integration
```

### Test Coverage
```bash
composer test-coverage
```

Coverage raporu `coverage/` klasöründe HTML formatında oluşturulur.

## Test Yapısı

### Unit Tests (`tests/Unit/`)
- **UserModelTest.php**: User model testleri
- **JwtLibraryTest.php**: JWT library testleri

### Integration Tests (`tests/Integration/`)
- **AuthTest.php**: Authentication endpoint testleri
- **ClothingItemTest.php**: Clothing item API testleri

## Notlar

### CodeIgniter Bootstrap
Unit testler CodeIgniter instance gerektirir. Eğer `get_instance()` hatası alıyorsanız:
1. `tests/bootstrap.php` dosyasının doğru çalıştığından emin olun
2. CodeIgniter'ın düzgün yüklendiğini kontrol edin

### Integration Testler
Integration testler gerçek API endpoint'lerini test eder. Bu testlerin çalışması için:
1. Web server'ın çalışıyor olması gerekir
2. `base_url` test dosyalarında doğru ayarlanmalıdır
3. Test veritabanı hazır olmalıdır

### Test Veritabanı
Integration testler test veritabanı kullanır. Production veritabanını kullanmayın!

## Troubleshooting

### "Call to undefined function get_instance()"
- `tests/bootstrap.php` dosyasını kontrol edin
- CodeIgniter'ın düzgün yüklendiğinden emin olun

### "500 Internal Server Error" (Integration Tests)
- Web server'ın çalıştığından emin olun
- API endpoint'lerinin erişilebilir olduğunu kontrol edin
- PHP error log'larını kontrol edin

### "Database connection failed"
- Test veritabanı ayarlarını kontrol edin
- Veritabanının oluşturulduğundan emin olun

