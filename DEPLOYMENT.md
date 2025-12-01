# GardıropPlus API - Deployment Kılavuzu

## 🌐 Production URL

**Base URL:** `https://gardiropplus.igyazilim.com/`

## 📋 Deployment Öncesi Kontrol Listesi

### 1. Environment Variables

`.env` dosyasını production değerleriyle güncelleyin:

```env
CI_ENV=production
JWT_SECRET_KEY=<güçlü-secret-key-32-karakter-minimum>
CORS_ALLOWED_ORIGINS=https://gardiropplus.igyazilim.com,https://www.gardiropplus.igyazilim.com
FORCE_HTTPS=true
ALLOW_HTTP_IN_DEVELOPMENT=false
```

### 2. Veritabanı

- Veritabanı bağlantı bilgilerini `.env` dosyasında güncelleyin
- `docs/database.sql` dosyasını import edin
- Gerekli migration'ları çalıştırın

### 3. Dosya İzinleri

```bash
chmod 755 application/cache
chmod 755 application/logs
chmod 755 uploads
chmod 755 uploads/images
chmod 755 uploads/profiles
```

### 4. Base URL

`application/config/config.php` dosyasında:

```php
$config['base_url'] = 'https://gardiropplus.igyazilim.com/';
```

### 5. Composer Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 6. .htaccess Kontrolü

`.htaccess` dosyasının doğru yapılandırıldığından emin olun.

## 🚀 Deployment Adımları

1. Dosyaları sunucuya yükleyin
2. `.env` dosyasını oluşturun ve production değerlerini girin
3. Veritabanını import edin
4. Composer dependencies'i yükleyin
5. Dosya izinlerini ayarlayın
6. Apache/Nginx yapılandırmasını kontrol edin

## ✅ Post-Deployment Kontrolleri

- [ ] API endpoint'leri çalışıyor mu?
- [ ] Admin paneli erişilebilir mi?
- [ ] Dosya yükleme çalışıyor mu?
- [ ] JWT authentication çalışıyor mu?
- [ ] CORS ayarları doğru mu?
- [ ] HTTPS zorunlu mu?
- [ ] Log dosyaları yazılabiliyor mu?

## 🔒 Güvenlik Kontrolleri

- [ ] `.env` dosyası web erişiminden korunuyor mu?
- [ ] `application/config/database.php` hassas bilgiler içermiyor mu?
- [ ] JWT secret key güçlü mü?
- [ ] Rate limiting aktif mi?
- [ ] CORS ayarları doğru mu?

## 📞 Destek

Sorunlar için: [GitHub Issues](https://github.com/your-repo/issues)

