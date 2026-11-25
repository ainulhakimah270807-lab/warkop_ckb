# Web, kasir aplikasi, resep, dan stok

Web Laravel dan Flutter memakai database Warkop CKB yang sama. Menu aplikasi diambil dari `/api/mobile/pos/catalog`; penjualan disimpan melalui `/api/mobile/pos/checkout`. Migrasi `2026_11_18_000003_connect_pos_inventory` sudah diterapkan pada komputer ini.

## Cara memakai

1. Login Owner di `http://127.0.0.1:8000/login` menggunakan akun Owner yang sudah diberikan di chat.
2. Buka **Master Bahan Baku**. Isi nama, satuan stok, stok nyata, dan batas minimum. Satuan bisa liter, ml, kg, gram, pcs, atau satuan hitungan lain yang konsisten.
3. Buka **Master Produk → Tambah Produk & Resep**. Isi nama, harga, kategori, dan status aktif.
4. Pilih **Resep (BoM)**. Tambahkan bahan dan takaran **untuk satu porsi/gelas**. Satuan resep boleh berbeda dari satuan stok jika jenisnya cocok: ml ↔ liter dan gram ↔ kg dikonversi otomatis. Massa tidak dapat dikonversi menjadi volume.
5. Untuk produk kemasan, pilih **Barang Statis** dan isi stok pcs. **Tidak Dihitung** tidak memotong stok. Menu BoM tanpa resep tidak dapat dijual.
6. Login aplikasi, buka **Kasir**, lalu muat ulang katalog atau tunggu pembaruan sekitar 20 detik. Pilih menu dan jumlah, periksa rincian, lalu catat setelah pembayaran benar-benar diterima.
7. Setelah server mengonfirmasi, muncul struk dengan nomor transaksi, kasir, item, harga, pajak, dan total dari server. Stok dipotong sesuai resep dalam transaksi database yang sama.
8. Owner membuka **Pusat Owner** di aplikasi atau web untuk melihat penjualan, bahan terpakai, mutasi stok, menu/resep, stok, presensi, akun/jabatan, supplier, dan pembelian. Daftar memiliki halaman berikutnya dan pencarian.
9. Di rincian penjualan, lihat pemakaian bahan dan saldo sebelum/sesudah. Riwayat tetap memakai resep saat transaksi terjadi, meskipun resep kemudian diubah.
10. Catat pembelian supplier melalui **Pembelian Supplier** agar stok bertambah. Nomor invoice yang sama untuk supplier yang sama ditolak. Koreksi melalui Master Bahan Baku tercatat sebagai penyesuaian stok.

## Contoh perhitungan

Ini contoh pengujian, bukan resep atau stok nyata yang otomatis dimasukkan:

| Bahan | Stok awal | Resep per gelas | Terpakai 2 gelas | Sisa |
|---|---:|---:|---:|---:|
| Teh seduh | 10 liter | 200 ml | 0,4 liter | 9,6 liter |
| Gula | 2 kg | 20 gram | 0,04 kg | 1,96 kg |

Untuk nasi atau bahan lain, isi takaran sesuai resep outlet. Sistem tidak menebak komposisi produk.

## Akses Owner

- Jabatan `users.role = owner` diperiksa oleh server. Pengguna biasa dapat memakai kasir/presensi, tetapi tidak dapat mengubah menu, stok, pengaturan, atau melihat data seluruh karyawan.
- Dari Pusat Owner aplikasi, menu **Buka / kelola di web** membuka seluruh halaman pengelolaan web dengan sesi Owner. Tautan login hanya berlaku satu kali selama 60 detik dan tidak memuat token API.
- Foto/template biometrik dan token login tidak ditampilkan dalam daftar umum Owner.

## Konsistensi dan batasan

- Stok seluruh bahan pada satu pesanan diperiksa bersama, dikunci di database, dan dipotong secara atomik. Jika salah satu bahan kurang, seluruh pesanan ditolak.
- Harga dan pajak dihitung server. Bila harga/pajak berubah sejak katalog dimuat, kasir diminta meninjau ulang. Kosongkan dan susun ulang keranjang dengan harga baru bila diperlukan.
- Pajak awal 0%; Owner dapat mengisi persentase yang digunakan toko melalui Pengaturan. Angka pajak bukan ditentukan oleh data contoh lama aplikasi.
- QRIS/EDC di sini adalah **pencatatan pembayaran manual** setelah dicek kasir, belum integrasi payment gateway. Pencetakan printer otomatis juga belum dihubungkan.
- Penjualan membutuhkan koneksi. Jika respons terputus, aplikasi menyimpan ID dan isi pesanan per akun. Gunakan **Cek / kirim ulang**; permintaan yang sama tidak membuat penjualan atau potongan stok ganda, termasuk setelah aplikasi dibuka kembali.
- Satu titik outlet dan satu persediaan bersama digunakan saat ini. Belum ada stok per cabang, retur/refund otomatis, atau pembayaran online.
- Stok mendukung enam angka desimal. Satuan bahan yang sudah dipakai resep/riwayat tidak dapat diubah; buat bahan baru jika ingin mengganti cara pencatatan satuannya.
- Menu/bahan/supplier yang memiliki riwayat tidak dihapus agar audit tetap utuh. Menu dapat dinonaktifkan.
- Pengubahan stok dari formulir memakai nilai awal sebagai pembanding agar stok dari penjualan terbaru tidak tertimpa formulir lama.
- Katalog aplikasi diperbarui saat dibuka, ditarik ulang, dan sekitar 20 detik selama layar kasir aktif. Web dan Pusat Owner diperbarui saat halaman dimuat/di-refresh; belum memakai WebSocket.

## Menjalankan dan menguji

Backend lokal: `php artisan serve --host=0.0.0.0 --port=8000` dan MySQL Laragon aktif.

```powershell
# Emulator Android
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
# Ponsel di Wi-Fi yang sama: ganti IP-LAN-KOMPUTER dengan IP komputer
flutter run --dart-define=API_BASE_URL=http://IP-LAN-KOMPUTER:8000/api
```

APK debug yang disediakan memakai alamat emulator. Untuk HP fisik, jalankan/build dengan IP LAN atau URL HTTPS backend. HTTP hanya diizinkan dalam debug Android.

Pengujian backend harus memakai database terpisah:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
php vendor/phpunit/phpunit/phpunit
```

Pengujian aplikasi: `flutter test --dart-define=API_BASE_URL=http://localhost/api`.

Uji MySQL lokal juga dilakukan di dalam transaksi yang di-rollback: 3 × 5 ml menghasilkan saldo 0,985 liter dari 1 liter; retry tidak memotong stok lagi. Tidak ada menu, resep, atau penjualan contoh yang disimpan sebagai data operasional.
