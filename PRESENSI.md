# Presensi mobile Warkop CKB

Panduan PDF lengkap dengan potongan kode, alur YuNet/SFace, ngrok, dan langkah uji: [Panduan integrasi dan presensi](docs/Panduan-Integrasi-dan-Presensi-Warkop-CKB.pdf). Sumber pembangkit PDF berada pada repository Flutter di `tools/docs/generate_guide.py`.

Backend Laravel terhubung dengan aplikasi Flutter `warkop-ckb-mobile`.

## Menyiapkan server

1. Jalankan MySQL Laragon dan migrasi dengan `php artisan migrate`.
2. Buat virtual environment Python 3.10+ di `tools/face/.venv`, lalu instal `tools/face/requirements.txt`.
   Pada Windows: `tools\face\.venv\Scripts\python.exe -m pip install -r tools\face\requirements.txt`.
3. Unduh model dengan `tools\face\.venv\Scripts\python.exe tools\face\setup_models.py`.
   Skrip memverifikasi SHA-256 dari pointer Git LFS OpenCV Zoo pada revisi tetap dan menyertakan lisensi model.
4. Default Python Windows sudah mengarah ke venv di atas. Pada Linux atau lokasi lain, set `ATTENDANCE_PYTHON` dan bila perlu `ATTENDANCE_MODELS` di `.env`.
5. Buat Owner baru: `php artisan presensi:owner owner@warkopckb.id`. Kata sandi acak hanya dicetak saat pembuatan. Perintah menolak email yang sudah ada.
   Untuk akun yang sudah ada: `php artisan presensi:admin email@contoh.id`; cabut akses dengan `--revoke`.
6. Untuk uji lokal: `php artisan serve --host=0.0.0.0 --port=8000`. Di produksi gunakan HTTPS.

Pada komputer pengembangan ini model, venv, kedua migrasi, dan akun Owner sudah disiapkan. Kata sandi diberikan di chat dan tidak disimpan di repository.

Pada Windows, subprocess verifikasi diberi `SystemRoot`/`WINDIR` secara eksplisit agar Python dapat memuat penyedia bilangan acak Windows, termasuk ketika worker `artisan serve` menghilangkan variabel tersebut. Jika Windows dipasang di lokasi khusus, isi `ATTENDANCE_SYSTEM_ROOT` di `.env`. Kegagalan proses dicatat pada log Laravel tanpa mencatat embedding wajah.

## Menjalankan aplikasi

Untuk ngrok, jalankan `ngrok http 8000` setelah Laravel menyala pada port 8000. Di halaman login Flutter pilih **Alamat server**, tempel link HTTPS Forwarding ngrok, lalu **Cek & simpan**. Tidak perlu build ulang ketika domain berubah. Link dicek melalui `/api/mobile/server-info`; aplikasi menambahkan header `ngrok-skip-browser-warning` pada permintaan API dan gambar produk. Middleware hanya mempercayai forwarded headers dari proxy localhost agar tautan backend mengikuti HTTPS ngrok. MySQL tetap di komputer backend; perangkat Flutter cukup mempunyai internet.

Emulator Android:

```powershell
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

Ponsel Android di Wi-Fi yang sama:

```powershell
flutter run --dart-define=API_BASE_URL=http://IP-LAN-KOMPUTER:8000/api
```

HTTP hanya diizinkan dalam build debug Android. Build release membutuhkan URL HTTPS. Pastikan ponsel dapat menjangkau server. iOS juga perlu server HTTPS. Jangan memakai `localhost` pada ponsel untuk menunjuk ke komputer.

## Penggunaan

1. Login dengan akun Owner. Menu **Atur presensi** tersedia di beranda untuk `users.role = owner`.
2. Berdiri di outlet, isi nama dan radius 50–1000 meter, ketuk **Gunakan lokasi saat ini**, lalu **Simpan titik dan radius**. Saat ini satu titik outlet dipakai seluruh karyawan.
3. Pilih akun karyawan yang sudah terdaftar, pastikan identitas dan persetujuannya, lalu ambil foto untuk mendaftarkan wajahnya. Pendaftaran ulang mengganti template sebelumnya.
   Akun Owner yang login kini dipilih otomatis untuk uji sendiri. Di browser, tombol foto membuka preview webcam langsung. Posisi wajah harus terlihat dan cukup terang sebelum menekan Ambil foto wajah.
   Pencarian lokasi browser dibatasi maksimal 12 detik dan tidak mengunci pilihan karyawan. Jika izin baru diberikan setelah batas waktu, tekan Gunakan lokasi saat ini sekali lagi. Lokasi tetap harus asli dan akurat maksimal 50 meter.
4. Karyawan login memakai akun masing-masing. Presensi memeriksa GPS dan radius, mengambil foto lewat kamera, lalu mencocokkan wajah di server terhadap template akun tersebut.
5. Foto harus menampilkan tepat satu wajah yang cukup besar dan tidak buram. Jika wajah tidak cocok, presensi ditolak.
6. Presensi masuk dan pulang masing-masing satu kali per tanggal WIB. Pulang membutuhkan presensi masuk pada tanggal yang sama. Shift lintas tengah malam belum didukung.
7. Layar sukses hanya ditampilkan setelah server menyimpan data. Nomor presensi dan waktu server menjadi bukti. Pengulangan permintaan dengan request ID yang sama tidak membuat data ganda.

## Data dan batasan

- Template wajah dienkripsi dengan `APP_KEY` Laravel; jangan mengganti atau kehilangan key tanpa rencana migrasi template.
- Foto bukti berada di disk lokal `storage/app/private/attendance`, bukan folder publik. Foto pendaftaran tidak disimpan; hanya embedding terenkripsi.
- Tidak ada layanan wajah pihak ketiga; inferensi berjalan lokal memakai YuNet dan SFace dari OpenCV Zoo.
- Threshold cosine default 0.45 dapat diubah melalui `ATTENDANCE_FACE_THRESHOLD`. Uji dengan data karyawan dan kondisi outlet sebelum penggunaan operasional; skor bukan persentase keyakinan.
- Akurasi GPS harus 1–50 meter dan jarak ditambah ketidakpastian GPS harus berada dalam radius. Lokasi dibaca ulang sebelum pengiriman. Aplikasi menolak lokasi yang ditandai `isMocked` oleh OS.
- Belum ada liveness/anti-foto, attestation perangkat, atau perlindungan terhadap klien API yang dimodifikasi. Pencocokan satu foto dan pemeriksaan GPS bukan jaminan anti-kecurangan.
- Presensi membutuhkan koneksi server. Tidak ada sukses/offline otomatis. Atur kebijakan retensi dan penghapusan foto/template sebelum pemakaian operasional.
- Endpoint pengaturan dan pendaftaran memeriksa jabatan Owner di server; pengguna biasa tidak dapat menaikkan hak melalui formulir pendaftaran.

## Pengujian

Gunakan database uji terpisah, jangan database operasional:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
php vendor/phpunit/phpunit/phpunit --filter=AttendanceTest
```

PHP harus memiliki `pdo_sqlite` dan `gd` untuk tes. PHP 8.3 Laragon pada komputer ini memiliki keduanya. Tes endpoint memakai mock inferensi; pemeriksaan runtime model dilakukan terpisah dengan contoh wajah OpenCV.

Dokumentasi algoritme: https://docs.opencv.org/4.10.0/d0/dd4/tutorial_dnn_face.html
Sumber model dan lisensi: https://github.com/opencv/opencv_zoo
