<?php
session_start();
require_once 'config/koneksi.php';

// Function untuk format angka
function formatNumber($number)
{
    return number_format($number, 0, ',', '.');
}

// Function untuk format waktu Indonesia
function formatWaktuIndonesia($timestamp)
{
    setlocale(LC_TIME, 'id_ID');
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

    $waktu = strtotime($timestamp);
    $tanggal = date('d', $waktu);
    $bulan = $bulan[date('n', $waktu)];
    $tahun = date('Y', $waktu);
    $jam = date('H:i', $waktu);

    return "$tanggal $bulan $tahun Pukul $jam WIB";
}

$result = null;
$error = null;

if (isset($_GET['no_tiket'])) {
    try {
        $no_tiket = $_GET['no_tiket'];
        $stmt = $koneksi->prepare("SELECT * FROM pengaduan WHERE no_tiket = ?");
        $stmt->execute([$no_tiket]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $error = "Nomor tiket tidak ditemukan";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pengaduan - Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-radius: 1rem 1rem 0 0 !important;
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            border: none;
            padding: 0.5rem 1.5rem;
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 0.5rem 1.5rem;
        }

        #mapid {
            height: 400px;
            width: 100%;
            border-radius: 1rem;
            margin-top: 1rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }

        .status-timeline {
            position: relative;
            padding: 1rem;
            margin: 1rem 0;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .status-active {
            background-color: #0dcaf0;
            color: white;
        }

        .foto-laporan {
            border-radius: 1rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }

        .input-group {
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .input-group input {
            border: none;
            padding: 1rem;
        }

        .input-group .btn {
            padding: 0 1.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <h1 class="text-center mb-0">Tracking Pengaduan</h1>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <a href="index.php" class="btn btn-secondary mb-4">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title text-white mb-0">
                            <i class="fas fa-search me-2"></i>Lacak Pengaduan Anda
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="no_tiket"
                                    placeholder="Masukkan Nomor Tiket"
                                    value="<?= isset($_GET['no_tiket']) ? htmlspecialchars($_GET['no_tiket']) : '' ?>"
                                    required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search me-1"></i> Lacak
                                </button>
                            </div>
                        </form>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($result): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-ticket-alt me-2"></i>
                                            Pengaduan #<?= htmlspecialchars($result['no_tiket']) ?>
                                        </h5>
                                        <?php
                                        $statusClass = 'warning';
                                        $statusIcon = 'clock';
                                        $statusText = 'Menunggu';

                                        if ($result['status'] == 'proses') {
                                            $statusClass = 'info';
                                            $statusIcon = 'spinner';
                                            $statusText = 'Sedang Diproses';
                                        } elseif ($result['status'] == 'selesai') {
                                            $statusClass = 'success';
                                            $statusIcon = 'check-circle';
                                            $statusText = 'Selesai';
                                        }
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?> p-2">
                                            <i class="fas fa-<?= $statusIcon ?> me-1"></i>
                                            <?= $statusText ?>
                                        </span>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">
                                                        <i class="fas fa-calendar-alt me-2"></i>Tanggal Pengaduan
                                                    </h6>
                                                    <p class="card-text">
                                                        <?= formatWaktuIndonesia($result['tgl_pengaduan']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">
                                                        <i class="fas fa-heading me-2"></i>Judul
                                                    </h6>
                                                    <p class="card-text">
                                                        <?= htmlspecialchars($result['judul']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <i class="fas fa-file-alt me-2"></i>Isi Laporan
                                            </h6>
                                            <p class="card-text">
                                                <?= nl2br(htmlspecialchars($result['isi_laporan'])) ?>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if ($result['foto']): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <i class="fas fa-image me-2"></i>Lampiran Foto
                                                </h6>
                                                <img src="assets/uploads/<?= htmlspecialchars($result['foto']) ?>"
                                                    class="img-fluid foto-laporan" alt="Foto Laporan">
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($result['latitude']) && isset($result['longitude'])): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <i class="fas fa-map-marker-alt me-2"></i>Lokasi Kejadian
                                                </h6>
                                                <div id="mapid"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="status-timeline">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-history me-2"></i>Status Pengaduan
                                        </h6>
                                        <div class="status-item">
                                            <div
                                                class="status-icon <?= $result['status'] == 'pending' ? 'status-active' : '' ?>">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div>Pengaduan Diterima</div>
                                        </div>
                                        <div class="status-item">
                                            <div
                                                class="status-icon <?= $result['status'] == 'proses' ? 'status-active' : '' ?>">
                                                <i class="fas fa-cog"></i>
                                            </div>
                                            <div>Sedang Diproses</div>
                                        </div>
                                        <div class="status-item">
                                            <div
                                                class="status-icon <?= $result['status'] == 'selesai' ? 'status-active' : '' ?>">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>Selesai</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php if ($result && isset($result['latitude']) && isset($result['longitude'])): ?>
        <script>
            var mymap = L.map('mapid').setView([<?= $result['latitude'] ?>, <?= $result['longitude'] ?>], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(mymap);

            L.marker([<?= $result['latitude'] ?>, <?= $result['longitude'] ?>])
                .addTo(mymap)
                .bindPopup("<strong>Lokasi Kejadian</strong>")
                .openPopup();
        </script>
    <?php endif; ?>
</body>

</html>