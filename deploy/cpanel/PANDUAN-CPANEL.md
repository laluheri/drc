# Upload DRC Laravel ke cPanel

Paket ini untuk instalasi baru dari versi aplikasi lokal terakhir, termasuk alamat Lombok Utara dan layout Program Kerja yang telah dirapikan. Vendor produksi sudah disertakan; npm, Composer, dan Terminal tidak diperlukan untuk pemasangan awal.

## 1. Siapkan hosting

Di **MultiPHP Manager**, pilih PHP 8.2 atau lebih baru untuk domain. Pastikan ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `ctype`, `curl`, `dom`, `xml`, `session`, dan `tokenizer` tersedia. Aktifkan SSL/HTTPS untuk domain melalui hosting. Folder aplikasi memerlukan izin tulis pada `storage` dan `bootstrap/cache`.

Dokumentasi: [PHP di cPanel](https://docs.cpanel.net/cpanel/software/multiphp-manager-for-cpanel/), [persyaratan dan deployment Laravel](https://laravel.com/docs/12.x/deployment).

## 2. Upload dan tempatkan file

Simpan backup file website aktif dan database lama sebelum mengganti website. Database SQL dalam paket adalah snapshot versi **lokal**, bukan dump terbaru database website aktif.

1. Buka **File Manager**, lalu **Settings → Show Hidden Files** agar `.env` dan `.htaccess` terlihat.
2. Buat folder sementara `/home/USERNAME/drc-upload`, di luar `public_html`.
3. Upload ZIP paket ke folder sementara itu, lalu **Extract**.
4. Pindahkan folder `drc-app` ke `/home/USERNAME/drc-app`.
5. Setelah menyimpan backup website lama, pindahkan **isi** folder `public_html` dari paket ke document root domain, biasanya `/home/USERNAME/public_html`. Jangan meninggalkan `index.html` lama yang bisa mengambil alih homepage. Simpan blok handler PHP yang dibuat cPanel pada `.htaccess` lama jika diperlukan, bersama aturan rewrite Laravel dari paket.
6. `database.sql`, `AKSES-ADMIN.txt`, ZIP, dan panduan tetap di folder privat, di luar document root.

Hasil untuk domain utama:

```text
/home/USERNAME/
├── drc-app/
│   ├── .env
│   ├── app/
│   ├── bootstrap/
│   ├── storage/
│   └── vendor/
└── public_html/
    ├── .htaccess
    ├── index.php
    ├── css/
    └── logo.png
```

`public_html/index.php` sudah menunjuk ke folder saudara `drc-app`. Jika domain menggunakan document root lain, edit `$appPath` dalam file tersebut menjadi path absolut, misalnya `/home/USERNAME/drc-app`. Hanya file publik yang berada dalam document root; source aplikasi dan `.env` berada di folder privat. Ini mengikuti prinsip [document root Laravel](https://laravel.com/docs/12.x/deployment#server-configuration).

## 3. Buat database MySQL baru

1. Buka **MySQL Databases** atau **Manage My Databases**.
2. Buat database baru dan user database baru. cPanel biasanya menambahkan awalan username akun; gunakan nama lengkap yang ditampilkan cPanel.
3. Tambahkan user ke database dengan **ALL PRIVILEGES** untuk instalasi.
4. Buka **phpMyAdmin**, pilih database baru yang masih kosong, lalu **Import → database.sql** dari paket.

SQL mencakup skema, data lokal, akun admin produksi, dan riwayat migrasi Laravel. Jangan impor ke database website lama atau database yang sudah berisi tabel. SQL tidak menghapus tabel lama. Log aktivitas dan kunjungan lokal, session, cache, remember token, serta token reset tidak disertakan.

Jika Anda ingin menggunakan data terbaru dari MySQL website lama, siapkan target database baru yang kosong dan gunakan alur `drc:import-legacy` di `README-DRC.md` dengan Terminal/SSH. **Jangan impor `database.sql` paket lebih dulu pada alur ini.** Kredensial `AKSES-ADMIN.txt` paket berlaku untuk database SQL paket, bukan akun hasil impor legacy.

## 4. Edit konfigurasi produksi

Edit `/home/USERNAME/drc-app/.env` menggunakan File Manager:

```dotenv
APP_URL=https://drcproject.org
DB_HOST=localhost
DB_DATABASE=USERNAME_namadatabase
DB_USERNAME=USERNAME_userdatabase
DB_PASSWORD="password_database_anda"
```

Ganti `APP_URL` bila memakai domain/subdomain lain. Gunakan host MySQL yang diberikan hosting jika bukan `localhost`. `APP_KEY` acak sudah dibuat di paket; jangan menggantinya setelah situs mulai digunakan. `APP_DEBUG=false` dan cookie aman sudah diatur. Buka website melalui **HTTPS** agar session dan login berjalan.

Untuk password database yang mengandung tanda kutip atau `$`, gunakan sandi generated cPanel tanpa karakter kutip/dolar, atau tulis nilai memakai kutip tunggal sesuai sintaks dotenv. Jangan menghapus variabel lainnya.

## 5. Izin folder

Mulai dengan file `644`, folder `755`, dan `.env` privat. Jika user PHP berbeda dan hosting memerlukannya, sesuaikan group/izin `storage` dan `bootstrap/cache` menjadi `775` dengan bantuan penyedia hosting. Hindari `777`. Pastikan subfolder berikut ikut terupload: `storage/framework/views`, `storage/framework/sessions`, `storage/framework/cache/data`, `storage/logs`, dan `bootstrap/cache`. ZIP sudah menyertakan folder kosong tersebut.

Tidak perlu `storage:link`: gambar dan file unduhan dilayani oleh endpoint Laravel. File unggahan yang dirujuk database berada dalam `drc-app/storage/app/public`.

## 6. Buka dan periksa

1. Buka homepage domain dengan HTTPS.
2. Periksa halaman Program Kerja, Kontak, dan Profil; alamat Lombok Utara harus tampil.
3. Buka `/admin/login`, lalu gunakan kredensial baru dari `AKSES-ADMIN.txt` paket.
4. Ubah sandi melalui **Profil** setelah login.
5. Coba membuat konten dan mengunggah gambar, lalu periksa halaman publik.

Reset sandi menggunakan `MAIL_MAILER=log` pada paket awal. Untuk mengirim email sungguhan, isi konfigurasi SMTP hosting (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan alamat pengirim yang sesuai). Tautan reset lokal/produksi dapat tercatat di log privat saat mailer masih `log`.

## Jika Terminal tersedia

```sh
cd /home/USERNAME/drc-app
php -v
php artisan migrate:status
php artisan optimize
```

Pastikan PHP CLI juga versi 8.2+. Di sebagian cPanel, PHP CLI memakai binary khusus versi domain yang perlu ditanyakan ke hosting. SQL paket sudah memiliki tabel dan riwayat migrasi; tidak perlu `migrate:fresh`, seed ulang, atau membuat admin baru untuk paket ini.

## Jika muncul error

- **500**: lihat `/home/USERNAME/drc-app/storage/logs/laravel.log` dan menu **Errors** cPanel. Periksa versi PHP, ekstensi, path `$appPath`, dan izin folder.
- **Access denied / Unknown database**: periksa nama database/user lengkap dengan prefix cPanel dan hak user pada database.
- **404 pada halaman selain beranda**: pastikan `.htaccess` Laravel ikut terupload dan rewrite Apache tersedia.
- **419 / login kembali ke form**: gunakan HTTPS dan pastikan `APP_URL` sesuai domain; periksa izin folder session.
- **Tampilan lama**: pastikan document root domain benar dan file index website lama tidak mengambil alih. Setelah mengganti file, muat ulang tanpa cache.

Paket tidak menyertakan config cache atau compiled views lokal yang berisi path Windows. Pemasangan pada cPanel nyata tetap perlu diverifikasi setelah user mengisi database dan mengunggah file.
