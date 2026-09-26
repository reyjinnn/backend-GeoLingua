# Backend GeoLingua

Fondasi REST API PHP 8 native untuk GeoLingua. Struktur mengikuti [Technical Architecture](https://docs.google.com/document/d/10PgYylfnI5oMamGIKTqXa5HvXVkTjOu64doJuJsLdAk/edit), [API Specification](https://docs.google.com/document/d/1QgBYdrbHzTVxwgQeDD-N9U003FKvkZeaCTfSSTU_YR8/edit), dan [Deployment Guide](https://docs.google.com/document/d/1KOrgMqCk3xmEc7wOch4_864YAq_wQJ7LDJLl-LC1cwQ/edit).

## Persiapan lokal

1. Gunakan PHP 8.x dengan ekstensi `pdo_mysql` untuk fitur yang memakai database.
2. Salin `.env.example` ke `.env` jika belum ada, lalu isi `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` dengan kredensial lokal. Jangan commit `.env`.
3. Jalankan dari direktori ini: `php -S localhost:8000 index.php`.
4. Cek `http://localhost:8000/api/health`; responsnya JSON dengan `data.status` bernilai `ok`.

`/api/health` hanya menguji bootstrap, routing, dan output JSON. Koneksi database dibuat saat repository pertama kali memanggil `databaseConnection()`.

## Struktur

- `index.php`: front controller untuk semua request API.
- `config/`: pembacaan `.env`, pengaturan aplikasi, CORS, dan koneksi PDO.
- `routes/`: pemetaan method dan path ke controller.
- `controllers/`: validasi request dan format respons.
- `services/`: aturan bisnis tanpa SQL.
- `repositories/`: query database dengan PDO prepared statements.
- `models/`: entitas atau DTO.
- `middleware/`: pemeriksaan token dan role.
- `helpers/`: utilitas respons dan validasi.
- `logs/`, `storage/`: data lokal yang tidak masuk Git.

Folder lapisan yang masih kosong disimpan dengan `.gitkeep`. Endpoint fitur pada API Specification belum diimplementasikan; router saat ini hanya menyediakan `/api/health` dan respons 404 JSON.

## Deployment InfinityFree

Unggah file runtime (`index.php`, `.htaccess`, folder `config/`, `routes/`, `helpers/`, dan lapisan lainnya) ke `htdocs/`. Sediakan `.env` di `htdocs/` dengan kredensial database dari panel hosting dan `APP_ENV=production`. `.htaccess` memblokir akses HTTP ke `.env` serta folder kode internal. Pastikan konfigurasi Apache pada hosting mengizinkan aturan `.htaccess` tersebut dan HTTPS aktif. Jangan unggah `.git`, log, atau file pengembangan.

Frontend production origin yang diizinkan secara default adalah `https://geolingua.vercel.app`; sesuaikan `CORS_ALLOWED_ORIGINS` jika domain frontend berubah. File `.env.example` sengaja tidak berisi kredensial asli.
