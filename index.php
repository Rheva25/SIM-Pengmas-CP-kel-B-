<?php
session_start();
require_once 'config/koneksi.php';

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Function untuk format angka
function formatNumber($number)
{
    return number_format($number, 0, ',', '.');
}

// Function untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal)
{
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $split = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[(int) $split[1]] . ' ' . $split[0];
}

// Function untuk format waktu Indonesia
function formatWaktuIndonesia($waktu)
{
    return date('H:i', strtotime($waktu)) . ' WIB';
}

// Function untuk generate nomor tiket
function generateTicketNumber()
{
    $prefix = 'TKT';
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    return $prefix . $date . $random;
}

// Proses pengaduan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $no_tiket = generateTicketNumber();
        $tgl_pengaduan = date('Y-m-d H:i:s');
        $judul = $_POST['judul'];
        $isi_laporan = $_POST['isi_laporan'];
        $status = '0';
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        // Handle upload foto
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $target_dir = "assets/uploads/";
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto = $no_tiket . '.' . $file_extension;
            move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto);
        }

        $stmt = $koneksi->prepare("INSERT INTO pengaduan (no_tiket, tgl_pengaduan, judul, isi_laporan, foto, status, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$no_tiket, $tgl_pengaduan, $judul, $isi_laporan, $foto, $status, $latitude, $longitude]);

        $success_message = "Pengaduan berhasil dikirim pada " . formatTanggalIndonesia($tgl_pengaduan) . " " . formatWaktuIndonesia($tgl_pengaduan) . ". Nomor Tiket Anda: " . $no_tiket;
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Mengambil statistik pengaduan
try {
    // Total Pengaduan
    $stmt = $koneksi->query("SELECT COUNT(*) as count FROM pengaduan");
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Status Pengaduan
    $stmt = $koneksi->query("SELECT COUNT(*) as count FROM pengaduan WHERE status = '0'");
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $koneksi->query("SELECT COUNT(*) as count FROM pengaduan WHERE status = 'proses'");
    $stats['proses'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $koneksi->query("SELECT COUNT(*) as count FROM pengaduan WHERE status = 'selesai'");
    $stats['selesai'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
        }

        .hero-section {
            background: linear-gradient(rgba(44, 62, 80, 0.9), rgba(52, 73, 94, 0.9)), url('https://images.unsplash.com/photo-1475776408506-9a5371e7a068?q=80&w=3158&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: #f5f6fa;
            clip-path: polygon(0 50%, 100% 0, 100% 100%, 0% 100%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
        }

        .stats-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .stats-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: -50px;
        }

        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #eee;
            padding: 0.8rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn {
            border-radius: 10px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        #map {
            height: 200px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .current-time {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            margin-top: 2rem;
            font-size: 1.2rem;
            font-weight: 300;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .form-container {
                margin-top: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center hero-content">
            <h1 class="hero-title">Sistem Pengaduan Masyarakat</h1>
            <p class="hero-subtitle">Sampaikan aspirasi Anda untuk pelayanan yang lebih baik</p>
            <div class="current-time" id="current-time"></div>
            <a href="auth/login.php" class="btn btn-primary mt-4">Login</a>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="row g-4 m-2">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt stats-icon"></i>
                        <h5 class="card-title mb-3">Total Pengaduan</h5>
                        <div class="stats-number"><?= formatNumber($stats['total']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock stats-icon"></i>
                        <h5 class="card-title mb-3">Menunggu</h5>
                        <div class="stats-number"><?= formatNumber($stats['pending']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-sync stats-icon"></i>
                        <h5 class="card-title mb-3">Diproses</h5>
                        <div class="stats-number"><?= formatNumber($stats['proses']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle stats-icon"></i>
                        <h5 class="card-title mb-3">Selesai</h5>
                        <div class="stats-number"><?= formatNumber($stats['selesai']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Pengaduan dan Tracking -->
        <div class="row m-2 p-5 mt-5">
            <!-- Form Pengaduan -->
            <div class="col-md-8">
                <div class="form-container">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Buat Pengaduan Baru</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="judul" class="form-label">Judul Pengaduan</label>
                                <input type="text" class="form-control" id="judul" name="judul" required
                                    placeholder="Masukkan judul pengaduan">
                            </div>
                            <div class="mb-4">
                                <label for="isi_laporan" class="form-label">Isi Laporan</label>
                                <textarea class="form-control" id="isi_laporan" name="isi_laporan" rows="5" required
                                    placeholder="Deskripsikan pengaduan Anda secara detail"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Lokasi Kejadian</label>
                                <div id="map"></div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                                <button type="button" class="btn btn-secondary" id="getCurrentLocation">
                                    <i class="fas fa-map-marker-alt me-2"></i> Gunakan Lokasi Saat Ini
                                </button>
                            </div>
                            <div class="mb-4">
                                <label for="foto" class="form-label">Lampiran Foto</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                    <label class="input-group-text" for="foto">
                                        <i class="fas fa-camera me-2"></i>Upload
                                    </label>
                                </div>
                                <small class="text-muted">Format yang didukung: JPG, PNG, GIF (Max. 5MB)</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Pengaduan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Form Tracking -->
            <div class="col-md-4">
                <div class="form-container">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Lacak Pengaduan</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="tracking.php" method="GET">
                            <div class="mb-4">
                                <label for="no_tiket" class="form-label">Nomor Tiket</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-ticket-alt"></i>
                                    </span>
                                    <input type="text" class="form-control" id="no_tiket" name="no_tiket" required
                                        placeholder="Masukkan nomor tiket">
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Lacak Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Informasi Tambahan -->
                <div class="form-container mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-clock text-warning me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">Waktu Respon</h6>
                                <small>Maksimal 24 jam kerja</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-shield-alt text-primary me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">Privasi Terjamin</h6>
                                <small>Data Anda dilindungi</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-headset text-success me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">Dukungan 24/7</h6>
                                <small>Selalu siap membantu</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Inisialisasi peta dengan style custom
        var map = L.map('map').setView([-6.2088, 106.8456], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker = null;

        // Custom icon untuk marker
        var customIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
            shadowSize: [41, 41]
        });

        // Fungsi untuk menambah marker dengan animasi
        function addMarker(lat, lng) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng], {
                icon: customIcon,
                draggable: true
            }).addTo(map);

            marker.bindPopup('Lokasi yang dipilih').openPopup();

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }

        // Event click pada peta
        map.on('click', function (e) {
            addMarker(e.latlng.lat, e.latlng.lng);
        });

        // Tombol untuk mendapatkan lokasi saat ini dengan animasi loading
        document.getElementById('getCurrentLocation').addEventListener('click', function () {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengambil Lokasi...';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;
                    map.setView([lat, lng], 16);
                    addMarker(lat, lng);

                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Gunakan Lokasi Saat Ini';
                }, function (error) {
                    alert('Error mendapatkan lokasi: ' + error.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Gunakan Lokasi Saat Ini';
                });
            } else {
                alert('Geolocation tidak didukung oleh browser Anda');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Gunakan Lokasi Saat Ini';
            }
        });

        // Fungsi untuk memperbarui waktu dengan animasi
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            };
            const timeString = now.toLocaleString('id-ID', options) + ' WIB';
            const timeElement = document.getElementById('current-time');
            timeElement.textContent = timeString;
            timeElement.style.opacity = '0';
            setTimeout(() => {
                timeElement.style.opacity = '1';
            }, 100);
        }

        // Update waktu setiap detik dengan transisi halus
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>

</html>