# Backend GeoLingua

GeoLingua adalah platform pembelajaran bahasa inovatif yang dirancang untuk membantu pengguna menguasai bahasa baru secara efektif melalui siklus belajar yang terstruktur: **Learn → Drill → Write → Quiz**. Proyek ini merupakan repositori backend yang menyediakan layanan REST API berbasis PHP 8 native, melayani data dan logika bisnis untuk aplikasi frontend.

Struktur dan pengembangan proyek ini mengikuti dokumen-dokumen utama berikut:
- [Technical Architecture]
- [API Specification]
- [Deployment Guide]

## Persiapan Lokal

Untuk menjalankan backend ini di komputer lokal Anda:

1. Gunakan PHP 8.x dengan ekstensi `pdo_mysql` untuk fitur yang menggunakan database.
2. Salin `.env.example` ke `.env` (misalnya di PowerShell: `Copy-Item .env.example .env`).
3. Sesuaikan isi file `.env` dengan kredensial database lokal Anda (`DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD`). **Jangan commit file `.env` ke repositori.**
4. Jalankan server backend dari direktori utama ini: `php -S localhost:8000 index.php`.
5. Cek `http://localhost:8000/api/health`; pastikan responsnya berupa JSON dengan `data.status` bernilai `ok`.

**Catatan:** Endpoint `/api/health` hanya menguji *bootstrap*, *routing*, dan *output* JSON. Koneksi database baru akan dibuat secara *lazy* saat *repository* pertama kali memanggil `databaseConnection()`. Endpoint fitur lainnya pada *API Specification* saat ini masih dalam tahap persiapan.

## Struktur Direktori

Proyek ini disusun dengan pola MVC/Layered Architecture:
- `index.php`: *Front controller* tunggal untuk memproses semua *request* API.
- `config/`: Berisi skrip pembacaan `.env`, pengaturan aplikasi, konfigurasi CORS, dan koneksi PDO.
- `routes/`: Pemetaan HTTP *method* dan path ke *controller* yang sesuai.
- `controllers/`: Mengatur validasi *request* masuk dan memformat respons keluar.
- `services/`: Berisi aturan bisnis (*business logic*) inti tanpa kueri SQL.
- `repositories/`: Berisi logika kueri database menggunakan *prepared statements* PDO.
- `models/`: Definisi entitas atau *Data Transfer Objects* (DTO).
- `middleware/`: Tempat pemeriksaan keamanan seperti token otorisasi dan peran (*role*).
- `helpers/`: Fungsi utilitas bantuan (seperti pembentuk respons JSON standar dan validasi).
- `logs/`, `storage/`: Direktori untuk data lokal yang dikecualikan dari Git.

## Deployment InfinityFree

Untuk melakukan *deployment* (misal ke InfinityFree):
1. Unggah file *runtime* (`index.php`, `.htaccess`, beserta folder `config/`, `routes/`, `helpers/`, dll) ke direktori `htdocs/` di *hosting*.
2. Sediakan file `.env` di dalam `htdocs/` dengan kredensial database dari panel hosting, serta atur `APP_ENV=production`.
3. File `.htaccess` bawaan telah dikonfigurasi untuk memblokir akses HTTP ke `.env` dan kode internal. Pastikan konfigurasi Apache di *hosting* mengizinkan hal ini.
4. Jangan pernah unggah folder `.git`, *logs*, atau berkas pengembangan lainnya.
5. Domain frontend *production* yang diizinkan secara default adalah `https://geolingua.vercel.app`; sesuaikan `CORS_ALLOWED_ORIGINS` jika domain berubah.

## Panduan Kontribusi (Git Workflow)

Untuk menjaga riwayat *commit* yang rapi dan mempermudah kolaborasi, jika Anda ingin melakukan *push* dan berkontribusi, Anda **wajib** membuat *branch* baru dengan format penamaan yang spesifik. Hindari melakukan *push* langsung ke *branch* utama (`main` atau `master`).

Format penamaan *branch* yang digunakan adalah:
- **`feat/<nama_fitur>`**: Digunakan saat menambahkan fitur baru.
- **`fix/<nama_perbaikan>`**: Digunakan saat memperbaiki *bug* atau *error*.
- **`docs/<nama_dokumentasi>`**: Digunakan saat ada penambahan atau perbaikan pada dokumentasi.
- **`refactor/<nama_refaktor>`**: Digunakan saat merapikan atau menulis ulang kode tanpa mengubah fungsionalitas.
- **`style/<nama_styling>`**: Digunakan saat ada perubahan standar penulisan kode atau format kode.

**Langkah-langkah berkontribusi:**
1. *Pull branch* dari *development*.
2. Buat *branch* baru dari *branch* utama: `git checkout -b <branch_type>/<nama_branch_anda>`
3. Lakukan perubahan pada kode Anda.
4. *Commit* perubahan Anda dengan pesan yang jelas dan deskriptif.
5. *Push branch* Anda ke repositori: `git push origin <branch_type>/<nama_branch_anda>`
6. Buat *Pull Request* (PR) untuk ditinjau oleh tim.
