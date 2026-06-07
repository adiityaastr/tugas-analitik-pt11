# Sistem Prediksi Kelulusan Mahasiswa

Aplikasi Data Mining untuk memprediksi kelulusan mahasiswa menggunakan metode **Naive Bayes Classification**, dibangun dengan Laravel.

---

## Teknologi

- **PHP 8.3+** & **Laravel 13**
- **MySQL** (via XAMPP)
- **Naive Bayes** (implementasi kustom)

---

## Persyaratan

| Komponen | Versi |
|----------|-------|
| PHP | 8.3+ |
| Composer | 2.x |
| MySQL | 5.7+ (XAMPP) |

---

## Instalasi

```bash
# 1. Clone project
git clone <repo-url> laravel-app
cd laravel-app

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Buat database (via phpMyAdmin atau CLI)
#    CREATE DATABASE analitik;

# 6. Sesuaikan .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 7. Import data training
php artisan migrate

# 8. Jalankan server
php artisan serve
```

Buka **http://127.0.0.1:8000**

---

## Konfigurasi PATH (Windows + XAMPP)

Jika `php` dan `composer` tidak dikenali:

```powershell
[Environment]::SetEnvironmentVariable("Path", "C:\xampp;C:\xampp\php;" + [Environment]::GetEnvironmentVariable("Path", "User"), "User")
```

Tutup dan buka ulang terminal.

---

## Fitur

- Prediksi kelulusan mahasiswa berdasarkan **IPK, Kehadiran, SKS Lulus, Status Kerja**
- Multi-model: Naive Bayes, Random Forest
- Retrain model dari data terbaru
- Tampilan metrik akurasi (accuracy, precision, recall, F1-score)

---

## Endpoint

| Method | URL | Deskripsi |
|--------|-----|-----------|
| GET | `/` | Halaman utama + form prediksi |
| POST | `/predict` | Kirim data prediksi |
| GET | `/accuracy/{model}` | Lihat metrik model (JSON) |
| POST | `/retrain` | Latih ulang model |

---

## Lisensi

MIT
