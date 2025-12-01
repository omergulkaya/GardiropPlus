# Production Hata Düzeltmeleri

## Yapılan Düzeltmeler

### 1. Session Path Hatası

**Sorun:** `open_basedir` kısıtlaması nedeniyle `/var/lib/php/session` dizinine erişilemiyordu.

**Çözüm:** Session path'i `/tmp/ci_sessions` olarak ayarlandı ve dizin otomatik oluşturuluyor.

**Dosya:** `application/config/config.php`

```php
$session_path = sys_get_temp_dir() . '/ci_sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
$config['sess_save_path'] = $session_path;
```

### 2. Veritabanı Bağlantı Hatası

**Sorun:** `.env` dosyası okunamıyordu veya yanlış konumda aranıyordu.

**Çözüm:** `.env` dosyası okuma işlemi iyileştirildi, birden fazla olası konum kontrol ediliyor.

**Dosya:** `application/config/database.php`

```php
$env_files = array(
    FCPATH . '.env',           // Proje root
    APPPATH . '../.env',       // Bir üst dizin
    __DIR__ . '/../../.env',   // Relative path
);
```

### 3. CORS Ayarları

**Sorun:** CORS_ALLOWED_ORIGINS eski URL'leri içeriyordu.

**Çözüm:** `.env` dosyasındaki CORS ayarları production URL'ine güncellendi.

**Dosya:** `.env`

```env
CORS_ALLOWED_ORIGINS=https://gardiropplus.igyazilim.com,https://www.gardiropplus.igyazilim.com
```

## Kontrol Listesi

Sunucuda şunları kontrol edin:

- [ ] `.env` dosyası proje root dizininde mevcut
- [ ] `.env` dosyası okunabilir (chmod 644)
- [ ] Session dizini oluşturuldu (`/tmp/ci_sessions`)
- [ ] Session dizini yazılabilir (chmod 700)
- [ ] Veritabanı bilgileri `.env` dosyasında doğru
- [ ] PHP `open_basedir` ayarları `/tmp/` dizinine izin veriyor

## Sunucu Ayarları

### Session Dizini İzinleri

```bash
# Session dizinini oluştur
mkdir -p /tmp/ci_sessions
chmod 700 /tmp/ci_sessions
```

### .env Dosyası Kontrolü

```bash
# .env dosyasının varlığını kontrol et
ls -la /var/www/vhosts/igyazilim.com/gardiropplus.igyazilim.com/.env

# İzinleri kontrol et
chmod 644 .env
```

### PHP open_basedir Ayarları

Plesk veya cPanel'de `open_basedir` ayarlarının `/tmp/` dizinini içerdiğinden emin olun.

## Sorun Giderme

### Session Hataları Devam Ediyorsa

1. Alternatif olarak proje içinde session dizini kullanın:

```php
$session_path = APPPATH . 'cache/sessions';
if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}
$config['sess_save_path'] = $session_path;
```

2. Session dizinini manuel oluşturun:

```bash
mkdir -p /var/www/vhosts/igyazilim.com/gardiropplus.igyazilim.com/application/cache/sessions
chmod 700 /var/www/vhosts/igyazilim.com/gardiropplus.igyazilim.com/application/cache/sessions
```

### Veritabanı Bağlantı Hatası Devam Ediyorsa

1. `.env` dosyasının doğru konumda olduğundan emin olun
2. `.env` dosyasındaki veritabanı bilgilerini kontrol edin
3. PHP error log'larını kontrol edin
4. Veritabanı kullanıcısının şifresini doğrulayın

## Notlar

- Session dosyaları `/tmp/` dizininde saklanır (sunucu yeniden başlatıldığında silinebilir)
- Production'da session'ları veritabanında saklamak daha güvenli olabilir
- `.env` dosyası hassas bilgiler içerdiği için web erişiminden korunmalıdır

