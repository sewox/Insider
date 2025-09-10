# Insider SMS API

Otomatik mesaj gönderim sistemi - Laravel 10.x ile geliştirilmiş RESTful API

## Proje Açıklaması

Bu proje, belirli bir segmentteki kullanıcılara toplu mesaj göndermek için tasarlanmış otomatik mesaj gönderim sistemidir. Sistem, veritabanında bulunan mesaj içeriklerini ve alıcı bilgilerini kullanarak SMS gönderimi yapar.

## Özellikler

- ✅ **Repository Pattern** implementasyonu
- ✅ **Service Layer** mimarisi
- ✅ **Queue/Job** yapısı ile asenkron mesaj gönderimi
- ✅ **Laravel Command** ile manuel tetikleme
- ✅ **Redis Cache** ile mesaj ID'lerinin saklanması
- ✅ **Swagger/OpenAPI** dokümantasyonu
- ✅ **Unit ve Integration** testler
- ✅ **RESTful API** standartları
- ✅ **Rate Limiting** ile API koruması
- ✅ **Retry Mekanizması** ile hata toleransı

## Teknik Gereksinimler

- PHP 8.2+
- Laravel 10.x
- MySQL 8.0
- Redis 7
- Docker & Docker Compose
- Composer

## Kurulum

### Docker ile Kurulum (Önerilen)

#### 1. Projeyi klonlayın

```bash
git clone <repository-url>
cd Insider
```

#### 2. Environment dosyasını yapılandırın

```bash
cp .env.docker .env
```

`.env` dosyasında aşağıdaki ayarları yapın:

```env
# SMS Service
SMS_WEBHOOK_URL=https://webhook.site/your-webhook-url
SMS_API_KEY=your-api-key

# App Key (generate edilecek)
APP_KEY=
```

#### 3. Docker container'larını başlatın

```bash
# Container'ları build et ve başlat
docker-compose up -d --build

# App key generate et
docker-compose exec app php artisan key:generate

# Migration'ları çalıştır
docker-compose exec app php artisan migrate

# Swagger dokümantasyonunu generate et
docker-compose exec app php artisan l5-swagger:generate
```

#### 4. Servisleri kontrol edin

```bash
# Container durumlarını kontrol et
docker-compose ps

# Logları görüntüle
docker-compose logs -f
```

### Manuel Kurulum

#### 1. Projeyi klonlayın

```bash
git clone <repository-url>
cd Insider
```

#### 2. Bağımlılıkları yükleyin

```bash
composer install
```

#### 3. Environment dosyasını yapılandırın

```bash
cp .env.example .env
php artisan key:generate
```

`.env` dosyasında aşağıdaki ayarları yapın:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=insider_sms
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# SMS Service
SMS_WEBHOOK_URL=https://webhook.site/your-webhook-url
SMS_API_KEY=your-api-key

# Queue
QUEUE_CONNECTION=redis
```

#### 4. Veritabanını oluşturun ve migration'ları çalıştırın

```bash
php artisan migrate
```

#### 5. Swagger dokümantasyonunu generate edin

```bash
php artisan l5-swagger:generate
```

## Kullanım

### Docker ile Kullanım

#### 1. Servisleri başlatın

```bash
# Tüm servisleri başlat
docker-compose up -d

# Sadece belirli servisleri başlat
docker-compose up -d nginx app mysql redis
```

#### 2. Queue Worker'ı kontrol edin

```bash
# Queue worker loglarını görüntüle
docker-compose logs -f queue

# Queue worker'ı yeniden başlat
docker-compose restart queue
```

#### 3. Mesaj gönderim işlemini manuel tetikleyin

```bash
# Her 5 saniyede 2 mesaj işle
docker-compose exec app php artisan messages:process --limit=2
```

#### 4. Scheduler'ı kontrol edin

```bash
# Scheduler loglarını görüntüle
docker-compose logs -f scheduler

# Scheduler'ı yeniden başlat
docker-compose restart scheduler
```

### Manuel Kullanım

#### 1. Queue Worker'ı başlatın

```bash
php artisan queue:work
```

#### 2. Mesaj gönderim işlemini tetikleyin

```bash
# Her 5 saniyede 2 mesaj işle
php artisan messages:process --limit=2
```

### 3. API Endpoints

> **Rate Limiting**: Tüm API endpoint'leri rate limiting ile korunmaktadır. Limit aşıldığında 429 status code döner.

#### Rate Limit Kuralları
- **Mesaj Oluşturma**: 10 istek/dakika
- **Mesaj Listeleme**: 30 istek/dakika
- **Gönderilmiş Mesajlar**: 20 istek/dakika
- **Mesaj Durumu**: 60 istek/dakika
- **Mesaj Detayı**: 30 istek/dakika

#### Rate Limit Headers
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 5
Retry-After: 45
```

#### Rate Limit Error Response
```json
{
    "error": "Too Many Requests",
    "message": "Rate limit exceeded. Please try again later.",
    "retry_after": 45,
    "max_attempts": 10
}
```

#### Mesaj Oluştur
```bash
POST /api/messages
Content-Type: application/json

{
    "content": "Test mesajı",
    "recipients": [
        {
            "phone_number": "+905551234567",
            "name": "John Doe"
        },
        {
            "phone_number": "+905559876543",
            "name": "Jane Doe"
        }
    ]
}
```

#### Gönderilmiş Mesajları Listele
```bash
GET /api/messages
```

#### Gönderilmiş Mesaj ID'lerini Listele
```bash
GET /api/messages/sent/list
```

#### Mesaj Durumunu Kontrol Et
```bash
GET /api/messages/status/{messageId}
```

#### Belirli Mesajı Getir
```bash
GET /api/messages/{id}
```

### 4. Swagger Dokümantasyonu

API dokümantasyonuna erişmek için:
```
http://localhost:8000/api/documentation
```

## Proje Yapısı

```
app/
├── Console/Commands/
│   └── ProcessMessagesCommand.php    # Mesaj işleme komutu
├── Http/
│   ├── Controllers/Api/
│   │   └── MessageController.php     # API Controller
│   └── Middleware/
│       └── RateLimitMiddleware.php   # Rate limiting middleware
├── Jobs/
│   └── SendMessageJob.php            # SMS gönderim job'ı
├── Models/
│   ├── Message.php                   # Mesaj modeli
│   └── Recipient.php                 # Alıcı modeli
├── Repositories/
│   ├── Contracts/                    # Repository interface'leri
│   └── Eloquent/                     # Repository implementasyonları
├── Services/
│   ├── MessageService.php            # Mesaj servisi
│   └── SmsService.php                # SMS servisi
└── Providers/
    └── RepositoryServiceProvider.php # Repository binding'leri

tests/
├── Feature/
│   ├── ExampleTest.php               # Feature test örneği
│   ├── MessageApiTest.php            # API endpoint testleri
│   └── RateLimitTest.php             # Rate limiting testleri
└── Unit/
    ├── ExampleTest.php               # Unit test örneği
    ├── MessageServiceTest.php        # MessageService testleri
    └── SendMessageJobTest.php        # SendMessageJob testleri
```

## Test

### Docker ile Test

#### Unit Testleri Çalıştırın

```bash
docker-compose exec app php artisan test --testsuite=Unit
```

#### Feature Testleri Çalıştırın

```bash
docker-compose exec app php artisan test --testsuite=Feature
```

#### Tüm Testleri Çalıştırın

```bash
docker-compose exec app php artisan test
```

#### Rate Limiting Testleri

```bash
# Rate limiting testlerini çalıştır
docker-compose exec app php artisan test tests/Feature/RateLimitTest.php
```

**Rate Limiting Test Coverage:**
- ✅ Mesaj oluşturma rate limit testi
- ✅ Mesaj listeleme rate limit testi  
- ✅ Rate limit header kontrolü
- ✅ Rate limit reset testi

#### Retry Mekanizması Testleri

```bash
# Retry mekanizması testlerini çalıştır
docker-compose exec app php artisan test tests/Feature/RetryTest.php
```

**Retry Test Coverage:**
- ✅ SMS service retry mechanism testi
- ✅ Tüm denemeler başarısız testi
- ✅ SendMessageJob retry konfigürasyonu
- ✅ Queue ile retry testi
- ✅ Exponential backoff timing testi
- ✅ Farklı hata türleri ile retry testi

### Manuel Test

#### Unit Testleri Çalıştırın

```bash
php artisan test --testsuite=Unit
```

#### Feature Testleri Çalıştırın

```bash
php artisan test --testsuite=Feature
```

#### Tüm Testleri Çalıştırın

```bash
php artisan test
```

## Docker Yapılandırması

### Servisler

- **nginx**: Web server (Port 80, 443)
- **app**: PHP-FPM application (Port 9000)
- **mysql**: MySQL 8.0 database (Port 3306)
- **redis**: Redis 7 cache (Port 6379)
- **queue**: Queue worker container
- **scheduler**: Laravel scheduler container

### Docker Komutları

```bash
# Tüm servisleri başlat
docker-compose up -d

# Servisleri durdur
docker-compose down

# Servisleri yeniden başlat
docker-compose restart

# Logları görüntüle
docker-compose logs -f [service_name]

# Container'a bağlan
docker-compose exec [service_name] bash

# Volume'ları temizle
docker-compose down -v

# Image'ları yeniden build et
docker-compose build --no-cache
```

### Production Deployment

```bash
# Production için environment ayarla
export APP_ENV=production

# Production build
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

## Sistem Mimarisi

### 1. Repository Pattern
- `MessageRepositoryInterface` ve `RecipientRepositoryInterface`
- Eloquent implementasyonları
- Service Container'da binding

### 2. Service Layer
- `MessageService`: Mesaj işlemleri
- `SmsService`: SMS gönderim ve cache işlemleri

### 3. Queue System
- `SendMessageJob`: Asenkron SMS gönderimi
- Redis queue driver
- Retry mekanizması (3 deneme)

### 4. Command System
- `ProcessMessagesCommand`: Manuel mesaj işleme
- Her 5 saniyede 2 mesaj limiti
- Bekleyen mesajları otomatik işleme

## Veritabanı Şeması

### Messages Tablosu
- `id`: Primary key
- `content`: Mesaj içeriği
- `external_message_id`: Dış servisten dönen ID
- `status`: Gönderim durumu (pending/sent/failed)
- `sent_at`: Gönderim zamanı
- `error_message`: Hata mesajı
- `created_at`, `updated_at`: Timestamp'ler

### Recipients Tablosu
- `id`: Primary key
- `message_id`: Foreign key (messages.id)
- `phone_number`: Telefon numarası
- `name`: Alıcı adı
- `status`: Gönderim durumu (pending/sent/failed)
- `external_message_id`: Dış servisten dönen ID
- `sent_at`: Gönderim zamanı
- `error_message`: Hata mesajı
- `created_at`, `updated_at`: Timestamp'ler

## Retry Mekanizması

SMS gönderiminde hata durumunda otomatik retry mekanizması:

### SendMessageJob Retry
- **Maksimum Deneme**: 5 kez
- **Backoff Süreleri**: 30s, 1m, 2m, 4m
- **Timeout**: 2 dakika
- **Queue Driver**: Redis

### SmsService Retry
- **Maksimum Deneme**: 5 kez
- **Backoff Stratejisi**: Exponential backoff (2s, 4s, 6s, 8s)
- **Retry Koşulları**: HTTP hataları ve network timeout'ları
- **Logging**: Her deneme detaylı loglanır

### Retry Senaryoları
1. **Network Timeout**: Bağlantı zaman aşımı
2. **HTTP 5xx Errors**: Sunucu hataları
3. **HTTP 4xx Errors**: İstemci hataları (retry edilmez)
4. **Exception**: Beklenmeyen hatalar

### Retry Logları
```json
{
    "phone_number": "+905551234567",
    "attempt": 3,
    "max_retries": 5,
    "error": "Service unavailable",
    "response_status": 503
}
```

## Redis Cache

Mesaj ID'leri Redis'te 7 gün boyunca cache'lenir:
- Key format: `sms_message_id:{messageId}`
- Value: Mesaj detayları (ID, telefon, gönderim zamanı)

## Postman Collection

API'yi test etmek için hazır Postman collection'ı:

### Collection Dosyası
- `Insider_SMS_API.postman_collection.json` - Tüm API endpoint'leri
- `Insider_SMS_API_Environment.postman_environment.json` - Environment değişkenleri

### Import Etme
1. Postman'i açın
2. Import butonuna tıklayın
3. `Insider_SMS_API.postman_collection.json` dosyasını seçin
4. Environment dosyasını da import edin
5. "Insider SMS API Environment" environment'ını seçin

### Kullanım
- **base_url**: `http://localhost` (Docker container çalışırken)
- **message_id**: Test için kullanılacak mesaj ID'si
- **external_message_id**: Test için kullanılacak external mesaj ID'si

### Test Senaryoları
1. **Get All Messages** - Tüm mesajları listele
2. **Create Message** - Yeni mesaj oluştur
3. **Get Message by ID** - Belirli mesaj detayını getir
4. **Get Sent Messages List** - Gönderilmiş mesaj ID'lerini listele
5. **Check Message Status** - Mesaj durumunu kontrol et

## Gereksinimler Karşılanması

- ✅ Her 5 saniyede 2 mesaj gönderimi
- ✅ Mesaj içeriği karakter sınırı kontrolü (160 karakter)
- ✅ Gönderim durumu takibi
- ✅ Tekrar gönderim önleme
- ✅ Repository Pattern
- ✅ Service Layer
- ✅ Queue/Job yapısı
- ✅ Laravel Command
- ✅ Redis cache (Bonus)
- ✅ Swagger dokümantasyonu
- ✅ Unit/Integration testler

## Güvenlik

### Rate Limiting
API endpoint'leri rate limiting ile korunmaktadır:

#### Rate Limit Kuralları
- **Mesaj Oluşturma**: 10 istek/dakika
- **Mesaj Listeleme**: 30 istek/dakika
- **Gönderilmiş Mesajlar**: 20 istek/dakika
- **Mesaj Durumu**: 60 istek/dakika
- **Mesaj Detayı**: 30 istek/dakika

#### Rate Limit Özellikleri
- **IP Tabanlı**: Her IP adresi için ayrı limit
- **Redis Backend**: Rate limit verileri Redis'te saklanır
- **Configurable**: Limit ve decay time ayarlanabilir
- **Standard Headers**: X-RateLimit-Limit, X-RateLimit-Remaining
- **Error Response**: 429 status code ile JSON response

#### Rate Limit Aşımı
Limit aşıldığında:
```json
{
    "error": "Too Many Requests",
    "message": "Rate limit exceeded. Please try again later.",
    "retry_after": 45,
    "max_attempts": 10
}
```

### Güvenlik Önerileri
- API key'leri güvenli şekilde saklayın
- HTTPS kullanın
- Rate limit kurallarını ihtiyacınıza göre ayarlayın
- Webhook URL'lerini güvenli tutun

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## İletişim

Proje hakkında sorularınız için issue açabilirsiniz.