# Menjalankan Warkop Cak Kebo di Laragon

> Catatan: dokumen ini merekam penyiapan awal. Panduan fitur terbaru ada di `PRESENSI.md`, `INTEGRASI-KASIR.md`, dan `docs/Panduan-Integrasi-dan-Presensi-Warkop-CKB.pdf`. Bagian "Batasan kode bawaan" di bawah menggambarkan kondisi sebelum integrasi; penyimpanan pembelian, laporan dari transaksi, dan pengaturan database sudah ditambahkan oleh integrasi terbaru.

Proyek lokal: `C:\laragon\www\warkop_ckb`.

## Menjalankan kembali

1. Nyalakan MySQL melalui Laragon.
2. Buka terminal di folder proyek, lalu jalankan:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. Buka <http://127.0.0.1:8000>.
4. Pilih **Daftar Akun Baru** untuk membuat akun, lalu gunakan akun tersebut untuk masuk.

Jika port 8000 sudah digunakan aplikasi ini, langsung buka alamat di atas.
Tekan `Ctrl+C` pada terminal server untuk menghentikannya.

## Konfigurasi lokal

- PHP yang digunakan saat penyiapan: 8.2.34.
- Database MySQL: `warkop_ckb`, host `127.0.0.1`, port `3306`.
- Konfigurasi aplikasi dan koneksi database tersimpan di `.env`.
- Email lokal menggunakan `MAIL_MAILER=log`; pesan email masuk ke `storage/logs/laravel.log`.
- Aset sudah dibangun dengan `npm run build`, sehingga server Vite tidak perlu dinyalakan.
- Database awal kosong; buat akun dan isi katalog melalui aplikasi.

## Instalasi dari awal

Setelah menyediakan PHP 8.2 beserta ekstensi Laravel, Composer, Node.js, npm, dan MySQL:

```powershell
composer install
npm ci
Copy-Item .env.example .env
```

Sesuaikan `APP_NAME`, `APP_URL`, dan `DB_*` di `.env`, lalu buat database
`warkop_ckb` di MySQL. Lanjutkan:

```powershell
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

`composer.lock` sudah disesuaikan dengan PHP 8.2: `laravel/pint` 1.30.4
dan `symfony/css-selector` 7.4.22. Instalasi memakai pemeriksaan platform normal.

## Pengujian terisolasi

Tes menggunakan SQLite di memori agar tidak mengubah database MySQL aplikasi.
Pada PHP Laragon yang menyediakan DLL SQLite tetapi belum mengaktifkannya:

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
try {
    php -d extension=pdo_sqlite vendor/bin/phpunit
} finally {
    Remove-Item Env:\DB_CONNECTION, Env:\DB_DATABASE
}
```

## Batasan kode bawaan

- Penyimpanan pembelian di `routes/web.php` masih berupa respons sukses;
  transaksi pembelian dan perubahan stok belum disimpan.
- Laporan penjualan belum memiliki sumber transaksi; ekspor CSV baru berisi header.
- Pengaturan toko disimpan dalam sesi browser.
- `composer audit` masih melaporkan advisori pada dependensi bawaan. Penyiapan ini
  untuk pengembangan lokal; pembaruan keamanan dan kesiapan produksi perlu ditangani
  sebelum aplikasi dipublikasikan.
