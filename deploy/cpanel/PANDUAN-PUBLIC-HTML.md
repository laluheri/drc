# Paket DRC: semua di public_html

Paket ini sudah menyertakan Laravel, vendor produksi, data lokal, dan upload yang digunakan. Tidak membutuhkan Terminal atau Composer di hosting. Gunakan PHP 8.2+ dengan pdo_mysql, mbstring, fileinfo, openssl, dom, xml, curl, session, dan tokenizer, serta HTTPS.

## File Manager

1. Backup website dan database lama. Aktifkan **Settings → Show Hidden Files**.
2. Upload `drc-public-html-ready.zip` ke `public_html`, lalu Extract langsung ke folder itu. ZIP berisi `index.php`, `.htaccess`, aset, dan `drc-app` tanpa folder pembungkus tambahan.
3. **Hapus ZIP dari public_html setelah ekstrak**, karena arsip berisi konfigurasi dan sandi. Jangan menghapus kedua `.htaccess` dalam paket.
4. Singkirkan index.html website lama setelah backup agar tidak mengambil alih homepage. Jika .htaccess lama mempunyai handler PHP buatan cPanel, pertahankan blok handler itu bersama aturan paket.

Struktur akhir:

```text
public_html/
├── index.php
├── .htaccess
├── css/
└── drc-app/
    ├── .htaccess
    ├── .env
    ├── app/
    ├── vendor/
    ├── storage/
    └── deployment/
        ├── database.sql
        ├── AKSES-ADMIN.txt
        └── PANDUAN-PUBLIC-HTML.md
```

Source dan konfigurasi berada dalam document root sesuai permintaan, sehingga perlindungan .htaccess wajib aktif. Aturan root menolak URL drc-app, dan aturan dalam drc-app menolak semua akses HTTP ke folder tersebut. PHP tetap dapat membaca file melalui filesystem.

## Database dan konfigurasi

1. Di **MySQL Databases / Manage My Databases**, buat database dan user baru, lalu tambahkan user dengan ALL PRIVILEGES.
2. Di **phpMyAdmin**, pilih database baru yang kosong dan impor `drc-app/deployment/database.sql` dari paket lokal. SQL adalah snapshot data lokal, bukan database terbaru website aktif.
3. Edit `public_html/drc-app/.env` lewat File Manager:

```dotenv
APP_URL=https://drcproject.org
DB_HOST=localhost
DB_DATABASE=USERNAME_database
DB_USERNAME=USERNAME_user
DB_PASSWORD="sandi_database"
```

Gunakan nama lengkap dengan prefix cPanel. Ganti domain dan host MySQL sesuai hosting. APP_KEY sudah dibuat; pertahankan APP_DEBUG=false. Gunakan HTTPS untuk login. Jika sandi berisi dolar atau kutip, gunakan penulisan dotenv yang sesuai atau sandi generated tanpa karakter tersebut.

## Pemeriksaan setelah upload

- Buka homepage, Program Kerja, Kontak, dan Profil.
- **Wajib periksa** URL `/drc-app/.env`, `/drc-app/composer.json`, `/drc-app/deployment/database.sql`, dan `/drc-app/deployment/AKSES-ADMIN.txt`: semuanya harus 403 atau 404, tidak boleh menampilkan atau mengunduh file. Jika bisa diakses, hentikan penggunaan dan minta hosting mengaktifkan aturan Apache .htaccess.
- Login melalui `/admin/login` memakai `AKSES-ADMIN.txt` lokal, lalu ubah sandi melalui Profil.
- Coba upload dan simpan konten. storage dan bootstrap/cache harus dapat ditulis oleh PHP. Mulai folder 755, file 644, konfigurasi privat 600; sesuaikan dengan pemilik PHP pada hosting bila perlu, hindari 777.

Tidak perlu storage:link atau migrasi: SQL sudah memuat tabel dan riwayat migrasi. Jangan migrate:fresh atau seed ulang. MAIL_MAILER=log belum mengirim email; isi SMTP hosting untuk reset sandi melalui email.

Jika 500, periksa `drc-app/storage/logs/laravel.log`, versi PHP, ekstensi, dan izin folder. Jika 404 pada rute, periksa rewrite/.htaccess. Jika 419, periksa HTTPS dan izin storage/framework/sessions. Perlindungan HTTP harus diverifikasi pada server hosting nyata; server PHP lokal tidak menjalankan .htaccess.
