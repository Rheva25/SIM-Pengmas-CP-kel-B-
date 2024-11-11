# Sistem Informasi Manajemen Pengaduan Masyarakat

Sistem Informasi Manajemen Pengaduan Masyarakat adalah platform digital yang memungkinkan masyarakat untuk menyampaikan pengaduan, keluhan, atau aspirasi kepada pemerintah secara online. Sistem ini dirancang untuk memudahkan proses pelaporan dan pengelolaan pengaduan dengan lebih efisien dan transparan.

## 🌟 Fitur Utama

### Untuk Masyarakat
- Tampilan yang user friendly
- Membuat pengaduan baru dengan lampiran foto
- Otomatis mendapatkan titik lokasi pelapor
- Melacak status pengaduan
- Menerima tanggapan dari petugas
- Melihat riwayat pengaduan

### Untuk Petugas
- Manajemen pengaduan masyarakat
- Memberikan tanggapan pengaduan
- Mengubah status pengaduan
- Melihat detail pengaduan

### Untuk Admin
- Dashboard dengan statistik pengaduan
- Manajemen data petugas
- Manajemen data masyarakat
- Laporan pengaduan
- Verifikasi pengaduan

## 💻 Teknologi yang Digunakan

- PHP 8.2+
- MySQL/MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Bootstrap icon
- Chart.js
- jQuery
- Leaflet.js

## 📋 Prasyarat

Sebelum menginstal aplikasi ini, pastikan Anda telah menginstal:
- PHP versi 8.2 atau lebih tinggi
- MySQL/MariaDB
- Web Server (Apache/Nginx)
- Composer (PHP Package Manager)

## 🚀 Instalasi

1. Clone repositori ini
```bash
git clone https://github.com/Rheva25/SIM-Pengmas-CP-kel-B-.git
```

2. Pindah ke direktori project
```bash
cd SIM-PENGMAS
```

3. Install dependencies
```bash
composer install
```

4. Buat database baru dan import file SQL
```bash
mysql -u root -p
create database db_pengaduan;
use db_pengaduan;
source database/pengmas.sql;
```

5. Konfigurasi database
```bash
cp config/koneksi.php
```
Sesuaikan konfigurasi database pada file `config/koneksi.php`

6. Jalankan aplikasi
```bash
php -S localhost:8000
```

## 📁 Struktur Direktori

```
pengaduan-masyarakat/
├── admin/          # File-file untuk admin
├── assets/         # Asset statis (CSS, JS, Images, database)
├── config/         # File konfigurasi database
├── includes/       # File yang dapat di-include
├── masyarakat/     # File-file untuk masyarakat
├── petugas/        # File-file untuk petugas
└── uploads/        # Folder untuk upload gambar
```

## 👥 Hak Akses

### Admin
- Username: admin
- Password: 123456
- Akses penuh ke semua fitur sistem

### Petugas
- Username: petugas1
- Password: 123456
- Akses terbatas untuk mengelola pengaduan

### Masyarakat
- Dapat membuat pengaduan secara anonim
- Dapat melacak pengaduan melalui sistem tiket tracking

## 📱 Screenshot Aplikasi

### Dashboard Admin
![Dashboard Admin](/assets/img/admin%20dashboard.png)

### Dashboard Petugas
![Dashboard Petugas](/assets/img/officer%20dashboard.png)

### Form Pengaduan
![Form Pengaduan](/assets/img/landing%20page.png)

### Tracking Pengaduan
![Daftar Pengaduan](/assets/img/tracking%20page.png)

## 🔒 Keamanan

- Password standar
- Validasi input untuk mencegah SQL Injection
- Sanitasi output untuk mencegah XSS
- CSRF Protection
- Session Management

## 📈 Status Pengaduan

- `1` : Pengaduan baru
- `proses` : Sedang diproses
- `selesai` : Pengaduan selesai

## 🔄 Alur Pengaduan

1. Masyarakat membuat pengaduan baru
2. Admin/Petugas memverifikasi pengaduan
3. Petugas memproses pengaduan
4. Petugas memberikan tanggapan
5. Pengaduan ditandai selesai
6. Masyarakat dapat melihat tanggapan

## 📊 Laporan

Sistem dapat menghasilkan berbagai laporan:
- Laporan pengaduan per periode
- Statistik status pengaduan
- Kinerja petugas
- Grafik pengaduan bulanan

## 🤝 Kontribusi

Kontribusi selalu welcome! Berikut cara untuk berkontribusi:

1. Fork repositori
2. Buat branch baru
3. Commit perubahan
4. Push ke branch
5. Buat Pull Request

## 📝 Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE)

## 👨‍💻 Developer

Dikembangkan oleh [Kelompok B](https://github.com/Rheva25)

## 📞 Kontak

Jika Anda memiliki pertanyaan atau masukan, silakan hubungi:
- Email: revaiqbal@gmail.com (silahkan tambahkan email yang lain)
- Website: https://revaiqbal.com (silahkan tambahkan website portfolio bila ada)

## ⭐ Dukungan

Jika Anda menyukai project ini, berikan bintang! ⭐

## 🙏 Ucapan Terima Kasih

Terima kasih kepada semua kontributor yang telah membantu mengembangkan sistem ini.

---
© 2024 Sistem Informasi Manajemen Pengaduan Masyarakat - Kelompok B Capstone Project Universitas Terbuka. All rights reserved.