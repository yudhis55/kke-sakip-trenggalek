# KKE-SAKIP

**Kertas Kerja Evaluasi — Sistem Akuntabilitas Kinerja Instansi Pemerintah**

Aplikasi evaluasi akuntabilitas kinerja instansi pemerintah untuk **Kabupaten Trenggalek**. OPD (Organisasi Perangkat Daerah) melakukan self-assessment terhadap kriteria penilaian, mengunggah bukti dukung, dan tiga tingkat reviewer (verifikator, penjamin kualitas, penilai) memberikan skor dan persetujuan.

---

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Framework | Laravel 12 |
| PHP | 8.2+ |
| Frontend | Livewire 3.7 + Tailwind CSS v4 |
| Build Tool | Vite 7 |
| Database | MySQL (production) / SQLite (development) |
| Queue | Database Driver |
| Template | Blade |

---

## Fitur Utama

- **Lembar Kerja Evaluasi** — Penilaian mandiri OPD dengan upload bukti dukung (PDF, JPG, PNG)
- **Multi-tier Review** — Verifikasi → Penjaminan Kualitas → Evaluasi Final
- **Sinkronisasi E-SAKIP** — Import dokumen otomatis dari API E-SAKIP Kabupaten Trenggalek (background queue)
- **Monitoring Progress** — Dashboard overview per tahun evaluasi
- **Rekap Verifikasi & Perbaikan** — Tracking status verifikasi dan penolakan per OPD
- **Ekspor Laporan** — Generate laporan Word (DOCX)
- **Role-based Access** — 7 role dengan permission berbeda

---

## Arsitektur

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│                    (Livewire 3 SPA-like)                     │
└─────────────────────────┬───────────────────────────────────┘
                          │ HTTP + wire:poll
┌─────────────────────────▼───────────────────────────────────┐
│                   Laravel 12 + Livewire                       │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  16 Livewire │  │   Models     │  │  EsakipSync      │  │
│  │  Components  │  │   (21)       │  │  Service         │  │
│  └──────────────┘  └──────────────┘  └────────┬─────────┘  │
│                                                │             │
│  ┌──────────────────────────────────┐         │             │
│  │  Queue Jobs (Background)         │◄────────┘             │
│  │  - ProcessEsakipSync             │                       │
│  │  - PreviewEsakipSync             │                       │
│  └──────────────┬───────────────────┘                       │
└─────────────────┼───────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────┐
│              MySQL Database                                   │
│  sync_progress │ penilaian │ bukti_dukung │ jobs │ ...       │
└─────────────────────────────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────────┐
│          API E-SAKIP (External)                               │
│    https://e-sakip.trenggalekkab.go.id/api                   │
└─────────────────────────────────────────────────────────────┘
```

### Hierarki Domain (5 Level Scoring)

```
Komponen → SubKomponen → KriteriaKomponen → BuktiDukung → Penilaian
                                                          ↓
                                                   PenilaianHistory
```

---

## Role & Permission

| Role | Jenis | Akses |
|------|-------|-------|
| `admin` | admin | Full access, konfigurasi sistem |
| `verifikator_bappeda` | verifikator | Verifikasi bukti dukung (assigned) |
| `verifikator_bag_organisasi` | verifikator | Verifikasi bukti dukung (assigned) |
| `verifikator_inspektorat` | verifikator | Verifikasi bukti dukung (assigned) |
| `penjamin` | penjamin | Penjaminan kualitas setelah verifikasi |
| `penilai` | penilai | Evaluasi final, lock skor |
| `opd` | opd | Self-assessment, upload bukti dukung |

Otorisasi menggunakan custom middleware `EnsureUserHasRole` (bukan Spatie/Fortify/Breeze).

---

## Persyaratan Sistem

- PHP 8.2+
- MySQL 8.0+ atau SQLite 3
- Composer 2.x
- Node.js 18+ & NPM
- Extension PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`

### Production Tambahan
- Supervisor (untuk queue worker)
- PHP `upload_max_filesize = 100M`
- PHP `post_max_size = 100M`

---

## Instalasi (Development)

### 1. Clone & Install Dependencies

```bash
git clone <repository-url> kke-sakip
cd kke-sakip
composer install
npm install
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai database:

```env
# Untuk MySQL (recommended)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kke-sakip
DB_USERNAME=root
DB_PASSWORD=

# Untuk SQLite (simple)
DB_CONNECTION=sqlite
# Pastikan file database/database.sqlite ada

# Queue (wajib untuk sinkronisasi)
QUEUE_CONNECTION=database

# E-SAKIP API (opsional, default sudah terisi)
ESAKIP_API_URL=https://e-sakip.trenggalekkab.go.id/api
ESAKIP_SYNC_DELAY=100
```

### 3. Setup Database

```bash
php artisan migrate --seed
php artisan storage:link
```

### 4. Build Assets

```bash
npm run build
```

### 5. Jalankan Development Server

```bash
composer dev
```

Ini menjalankan secara bersamaan:
- **Server** — `php artisan serve` (http://127.0.0.1:8000)
- **Queue Worker** — `php artisan queue:work` (untuk sinkronisasi background)
- **Vite** — Hot reload CSS/JS

Atau jalankan manual terpisah:

```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan queue:work --tries=1 --timeout=7200

# Terminal 3
npm run dev
```

---

## Instalasi (Production — VPS Ubuntu + aaPanel)

### 1. Upload Code ke Server

```bash
cd /www/wwwroot/your-domain.com
git clone <repository-url> .
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kke_sakip
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
ESAKIP_SYNC_DELAY=100
```

### 3. Setup Database & Cache

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Setup Queue Worker (Supervisor)

Install supervisor:

```bash
sudo apt update
sudo apt install supervisor -y
sudo systemctl enable supervisor
```

Buat config:

```bash
sudo nano /etc/supervisor/conf.d/kke-sakip-worker.conf
```

Isi:

```ini
[program:kke-sakip-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/your-domain.com/artisan queue:work --tries=1 --timeout=7200 --rest=1 --max-jobs=10
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/your-domain.com/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stopwaitsecs=7200
```

Aktifkan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kke-sakip-worker:*
```

Verifikasi:

```bash
sudo supervisorctl status
# Output: kke-sakip-worker:kke-sakip-worker_00   RUNNING
```

### 5. Konfigurasi PHP (php.ini)

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 512M
```

### 6. Konfigurasi Web Server (Nginx)

Pastikan root mengarah ke `/public`:

```nginx
root /www/wwwroot/your-domain.com/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Deployment Update

Setiap kali push code baru ke production:

```bash
cd /www/wwwroot/your-domain.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart kke-sakip-worker:*
```

---

## Sinkronisasi E-SAKIP

Fitur sinkronisasi mengambil dokumen dari API E-SAKIP dan mengisi bukti dukung secara otomatis.

### Cara Kerja

1. User pilih filter (Tahun, OPD, Jenis Dokumen) → klik **Preview**
2. Preview berjalan di background (queue job) → hasil muncul setelah selesai
3. User klik **Proses Sinkronisasi** → job di-dispatch ke queue
4. Progress bar update otomatis setiap 3 detik
5. User bisa **membatalkan** kapan saja
6. Hanya **1 sinkronisasi aktif** secara global (mutex)

### Konfigurasi

| Env Variable | Default | Keterangan |
|-------------|---------|------------|
| `ESAKIP_API_URL` | `https://e-sakip.trenggalekkab.go.id/api` | Base URL API E-SAKIP |
| `ESAKIP_SYNC_DELAY` | `100` | Delay antar API request (ms) |
| `ESAKIP_SYNC_TIMEOUT` | `60` | Timeout per API request (detik) |
| `ESAKIP_SYNC_RETRY` | `3` | Jumlah retry jika request gagal |
| `ESAKIP_AUTO_VERIFY_ENABLED` | `true` | Auto-verifikasi setelah sync |

### Estimasi Waktu Full Sync

- 48 OPD × 20 jenis dokumen × ~51 bukti dukung
- ~7344 API calls × 100ms delay = **~12 menit**

---

## Struktur Direktori

```
kke-sakip/
├── app/
│   ├── Exceptions/          # SyncCancelledException
│   ├── Http/Middleware/     # EnsureUserHasRole
│   ├── Jobs/                # ProcessEsakipSync, PreviewEsakipSync
│   ├── Livewire/            # 16 page components (controller layer)
│   │   ├── Auth/Login.php
│   │   └── Dashboard/
│   │       ├── Dashboard.php
│   │       ├── EksporLaporan.php
│   │       ├── LembarKerja.php
│   │       ├── Mapping.php
│   │       ├── Monitoring.php
│   │       ├── Pengaturan.php
│   │       ├── RekapPenolakan.php
│   │       ├── RekapPerbaikan.php
│   │       ├── RekapVerifikasi.php
│   │       └── SinkronData.php
│   ├── Models/              # 21 Eloquent models
│   ├── Providers/           # AppServiceProvider
│   └── Services/            # EsakipSyncService
├── config/
│   └── esakip.php           # Konfigurasi sync API
├── database/
│   ├── migrations/          # 37 migrations
│   └── seeders/             # 8 seeders (wajib untuk app berfungsi)
├── resources/views/
│   ├── components/layouts/  # app.blade.php (main layout + sidebar)
│   └── livewire/            # Blade views per component
├── routes/
│   └── web.php              # Semua route = Livewire class
├── public/assets/           # Vendor assets (icons, fonts)
└── storage/                 # Upload files, logs
```

---

## Perintah Berguna

| Perintah | Fungsi |
|----------|--------|
| `composer dev` | Jalankan server + queue + vite (development) |
| `composer test` | Jalankan test suite |
| `php artisan migrate:fresh --seed` | Reset database (development only!) |
| `php artisan storage:link` | Symlink storage untuk upload files |
| `php artisan pint` | Format code (Laravel Pint) |
| `php artisan queue:work --tries=1 --timeout=7200` | Jalankan queue worker manual |
| `php artisan config:cache` | Cache config (production) |
| `php artisan tinker` | Interactive PHP shell |

---

## Troubleshooting

### Sinkronisasi stuck / tidak jalan
```bash
# Cek status queue worker
sudo supervisorctl status

# Restart worker
sudo supervisorctl restart kke-sakip-worker:*

# Cek log
tail -f storage/logs/worker.log
tail -f storage/logs/laravel.log
```

### Upload gagal (file terlalu besar)
Pastikan `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
```

### Error 502 Cloudflare
Proses yang berjalan lama (sync, preview) sudah dipindahkan ke background queue. Jika masih 502:
- Pastikan queue worker running
- Cek `storage/logs/worker.log`

### Dashboard menampilkan 0
Pastikan tahun aktif sudah di-set di menu Pengaturan, atau session `tahun_session` terisi.

### ERR_SSL_PROTOCOL_ERROR di local
`APP_ENV` harus `local` di `.env`. Jika masih error, clear browser cache (Ctrl+Shift+Delete).

---

## Konvensi Kode

- **Livewire-first** — Tidak ada Controller untuk halaman baru, gunakan Livewire component
- **Snake_case relations** — `kriteria_komponen()`, `bukti_dukung()` (bukan camelCase)
- **`$guarded = ['id']`** — Semua model, JANGAN gunakan `$fillable`
- **Singular table names** — `role`, `penilaian`, `bukti_dukung` (override Eloquent default)
- **Indonesian identifiers** — Kolom, method, dan role dalam Bahasa Indonesia
- **No Spatie/Fortify/Breeze** — Auth custom via `EnsureUserHasRole` middleware

---

## Lisensi

Proprietary — Pemerintah Kabupaten Trenggalek.