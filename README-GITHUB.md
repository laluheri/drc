# Source DRC Laravel

Repository ini menyimpan kode aplikasi. Konfigurasi .env, akun admin, SQL seed privat, database, vendor, file upload, dan paket deployment tidak masuk Git.

Untuk instalasi pengembangan baru: jalankan composer install, salin .env.example ke .env, buat APP_KEY dengan php artisan key:generate, siapkan database, lalu php artisan migrate. Buat admin melalui php artisan drc:admin. Jangan memakai --seed tanpa menyiapkan database/seeders/drc.sql privat terlebih dahulu.

Untuk cPanel yang sudah terpasang, Git tidak menggantikan database.sql instalasi awal. Pembaruan harus menjaga .env hosting, storage, dan database aktif. Repository belum mengatur deployment otomatis: jangan melakukan clone langsung ke public_html yang sudah berisi aplikasi. Simpan repository dalam folder terpisah di luar document root, lalu gunakan aturan deployment yang disesuaikan dengan struktur hosting sebenarnya.

Jangan mengunggah folder .git ke public_html. Perubahan composer.lock memerlukan pembaruan vendor; perubahan skema memerlukan migrasi yang menjaga data. Backup sebelum deployment.
