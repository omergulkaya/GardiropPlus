# Test Notları

## Mevcut Durum

Testler şu anda bazı hatalar verebilir çünkü:

1. **CodeIgniter Bootstrap**: CodeIgniter'ı test ortamında başlatmak karmaşık bir işlemdir. `get_instance()` fonksiyonu CodeIgniter başlatıldıktan sonra tanımlı olur.

2. **Integration Testler**: Integration testler gerçek web server gerektirir. Eğer web server çalışmıyorsa 500 hatası alınır.

## Çözüm Önerileri

### Unit Testler İçin

CodeIgniter instance olmadan da test yazılabilir. Örneğin JWT library'yi doğrudan test edebilirsiniz:

```php
// JWT library'yi CodeIgniter olmadan test et
$jwt = new Jwt_library();
// Ancak constructor CI instance bekliyor, bu yüzden mock gerekli
```

### Integration Testler İçin

1. Web server'ın çalıştığından emin olun
2. `base_url` ayarlarını kontrol edin
3. Database bağlantısını kontrol edin

### Alternatif Yaklaşım

Test'leri şu şekilde organize edebilirsiniz:

1. **Unit Tests**: CodeIgniter bağımlılığı olmayan testler
2. **Integration Tests**: Web server üzerinden çalışan testler
3. **Functional Tests**: CodeIgniter'ı başlatarak çalışan testler (daha karmaşık)

## Test Çalıştırma

```bash
# Sadece çalışan testleri görmek için
composer test --verbose

# Skip edilen testleri görmek için
composer test --verbose --testdox
```

## Gelecek İyileştirmeler

- CodeIgniter test helper'ı oluşturulabilir
- Mock CI instance oluşturulabilir
- Test database setup script'i oluşturulabilir

