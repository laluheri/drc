# DRC — Laravel

Penulisan ulang aplikasi PHP MVC Daygun Research Center menjadi Laravel 12 dan Blade, kompatibel dengan PHP 8.2. Sumber lama tetap berada di folder induk. Document root aplikasi baru adalah `laravel/public`.

## Jalankan di komputer ini

Dependensi Composer, database SQLite, seed dari SQL asli, dan akun super admin sudah disiapkan.

```powershell
cd C:\Users\ASUS\Downloads\drc\laravel
.\serve.ps1
```

Buka http://127.0.0.1:8000 dan http://127.0.0.1:8000/admin/login. Username dan kata sandi acak terdapat dalam `.local/ACCESS.md`. Ubah kata sandi melalui menu Profil. File kredensial dan `.env` diabaikan oleh Git.

Script memakai konfigurasi PHP lokal di `../.runtime/php.ini` untuk mengaktifkan ZIP dan SQLite yang tersedia dalam `D:/php82/ext`. Konfigurasi PHP global tidak diubah. Untuk perintah Artisan di komputer ini:

```powershell
php -c ../.runtime/php.ini artisan migrate
```

## Fitur

- Halaman beranda, profil, tentang kami, visi/misi, sejarah, legalitas, dan semua modul publik pada URL lama.
- Berita, jurnal, buku, galeri dan detail; pencarian, kategori berita, pagination; agenda, dokumen, unduhan, partner, FAQ, tim, struktur organisasi, penelitian, layanan, program kerja.
- Panel CRUD untuk 24 modul, termasuk kategori, pengguna, menu, slider, media sosial, media galeri, kegiatan, komentar, dan testimoni. Media galeri menggunakan relasi album.
- Login username/email, remember me, pembatasan percobaan login, reset sandi Laravel, profil, serta logout dengan POST.
- Super admin mengelola seluruh modul dan backup. Admin tidak mengelola pengguna atau backup. Editor mengelola konten, tanpa pengaturan, menu, media sosial, pesan, log, pengguna, atau backup.
- Validasi berbasis skema, CSRF Laravel, allowlist modul, sanitasi HTML dasar, upload gambar/PDF/Word hingga 5 MB, soft delete pada tabel lama yang mendukungnya.
- Pesan kontak dengan captcha dan pembatasan laju; penanda dibaca, log aktivitas, statistik kunjungan beranda, sitemap, backup JSON seluruh tabel.

Tampilan publik mengikuti https://drcproject.org/: top bar, logo asli, navigasi Beranda/Kegiatan/Riset/Buku/Journal/Tentang, hero gradasi, Bidang Kegiatan, Profil Lembaga, tabel Struktur Organisasi, kartu Program Kerja, dan footer tiga kolom. Desain asli dari source PHP dipindahkan ke Blade; CSS berada dalam `public/css/reference.css` dengan penyesuaian ponsel dalam `reference-responsive.css`. Font Inter dan ikon Font Awesome memakai CDN yang sama dengan situs referensi. Panel admin mempertahankan CSS terpisah. Tidak perlu npm/Vite.

Program Kerja dan pengaturan profil tetap berasal dari database lokal; isi seed contoh dapat berbeda dari situs aktif. Susunan organisasi bawaan mengikuti template asli. Tombol Hubungi Kami sudah terhubung ke form kontak. Navigasi ponsel dapat dibuka dengan tombol menu, dan tombol kembali ke atas tersedia setelah menggulir halaman.

## Instalasi di lingkungan baru

PHP 8.2+, Composer, PDO MySQL atau PDO SQLite, serta ekstensi Laravel standar diperlukan.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Untuk SQLite, buat `database/database.sqlite`, lalu jalankan:

```sh
php artisan migrate --seed
php artisan drc:admin --write-credentials
php artisan serve
```

Seeder memasukkan contoh konten yang memang terdapat dalam SQL asli dan tidak menimpa tabel berisi data. Seeder tidak membuat akun dengan kata sandi umum. Untuk akun tambahan, `php artisan drc:admin namauser --email=alamat@example.org` meminta sandi secara interaktif.

## Data MySQL lama

Database lokal saat ini berisi seed SQL yang tersedia dalam proyek. Database MySQL aktif milik aplikasi lama belum diimpor. Sumber tidak dimodifikasi oleh alat impor.

Gunakan database target baru/kosong untuk impor. Jangan jalankan seed atau buat admin sebelum impor. Konfigurasikan target melalui variabel `DB_*` di `.env`. Untuk MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drc_laravel
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Tambahkan konfigurasi sumber melalui `.env`:

```dotenv
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=legacy_database_name
LEGACY_DB_USERNAME=legacy_user
LEGACY_DB_PASSWORD=legacy_password
```

Lalu:

```sh
php artisan config:clear
php artisan migrate
php artisan drc:import-legacy
```

Impor mempertahankan ID, relasi, hash password, status, dan data seluruh 28 tabel. Impor menolak target yang sudah berisi data dan berjalan dalam transaksi; hubungan parent yang merujuk ID lebih besar ditulis setelah insert. Jalur file lama dengan awalan `uploads/` tetap dikenali. Salin isi folder `uploads` lama ke `storage/app/public` pada target. File logo yang tersedia sudah disalin pada instalasi lokal ini.

Jangan jalankan `migrate:fresh` pada database berisi data yang ingin dipertahankan. Jika memindahkan data produksi, gunakan salinan sumber untuk pemeriksaan sebelum cutover.

## Email reset sandi

Mailer lokal menggunakan `log`. Tautan reset muncul di `storage/logs/laravel.log`; email sungguhan memerlukan konfigurasi SMTP `MAIL_*` di `.env`. Reset token memakai tabel `password_reset_tokens` Laravel, bukan token legacy.

## Verifikasi

```powershell
.\test.ps1
php -c ../.runtime/php.ini tools/smoke-http.php
```

Suite menggunakan SQLite in-memory: halaman publik, daftar/form admin, CRUD setiap modul, auth, peran, captcha, unggahan/unduhan, status publikasi, reset sandi, dan isi backup. Smoke HTTP memerlukan server lokal aktif serta `.local/ACCESS.md`; memeriksa login, penolakan CSRF, dashboard, logout, dan redirect tamu melalui HTTP sungguhan.

## Hosting

Untuk File Manager dan phpMyAdmin cPanel tanpa Terminal, paket ZIP produksi dibuat dalam `../dist/drc-cpanel-ready.zip`. Panduan lengkap ada dalam `deploy/cpanel/PANDUAN-CPANEL.md` dan salinan di dalam ZIP. Paket mencakup vendor tanpa dependensi development, SQL snapshot lokal untuk database MySQL baru, APP_KEY produksi baru, dan sandi admin produksi baru pada file privat `AKSES-ADMIN.txt`. `.env` lokal, SQLite, session, cache, compiled views, dan log lokal tidak disertakan. Aplikasi privat `drc-app` ditempatkan di luar document root; hanya isi `public_html` paket yang ditempatkan pada document root domain.

Arahkan document root Nginx/Apache ke `public`, bukan folder induk proyek. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` ke domain HTTPS, `SESSION_SECURE_COOKIE=true`, database produksi, dan SMTP. Beri izin tulis pada `storage` dan `bootstrap/cache`. Jalankan `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache`.

Unggahan dilayani oleh endpoint `media` untuk gambar dan endpoint `file` untuk dokumen yang aktif/terbit. Tidak perlu `storage:link`; link publik untuk dokumen dapat melewati pemeriksaan status publikasi. Backup JSON mencakup seluruh tabel termasuk hash akun, tanpa file unggahan; simpan privat dan salin `storage/app/public` terpisah. Fitur restore backup belum tersedia dalam UI.

## Struktur utama

- `routes/web.php`: routing HTTP publik/admin dan metode request.
- `app/Http/Controllers`: logika publik, CRUD admin, dan autentikasi.
- `app/Models`: user Laravel dan model konten dengan daftar field per tabel.
- `config/drc.php`: modul, label, rute publik, jenis file.
- `config/drc_schema.php`: metadata 28 tabel berdasarkan SQL lama.
- `database/migrations`: skema portable dan tabel pendukung Laravel.
- `resources/views`: halaman Blade publik/admin.
- `public/css/drc.css`: tema responsif.
- `app/Console/Commands`: pembuatan admin dan impor MySQL.

Pengujian impor terhadap server MySQL produksi dan pengiriman SMTP belum dilakukan pada lingkungan lokal ini.
