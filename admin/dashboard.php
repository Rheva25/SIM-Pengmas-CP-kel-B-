<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../includes/header.php';

// Hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Mengambil statistik untuk dashboard
$stats = [
    'total_pengaduan' => $koneksi->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn(),
    'pengaduan_baru' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = '0'")->fetchColumn(),
    'pengaduan_proses' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'proses'")->fetchColumn(),
    'pengaduan_selesai' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'selesai'")->fetchColumn(),
    'total_petugas' => $koneksi->query("SELECT COUNT(*) FROM users WHERE role = 'petugas'")->fetchColumn(),
];

// Mengambil data untuk grafik bulanan
$stmt = $koneksi->query("
    SELECT DATE_FORMAT(tgl_pengaduan, '%Y-%m') as bulan, COUNT(*) as total
    FROM pengaduan
    WHERE tgl_pengaduan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tgl_pengaduan, '%Y-%m')
    ORDER BY bulan ASC
");
$grafik_data = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-border-radius: 15px;
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--card-border-radius) var(--card-border-radius);
            color: white;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.1);
        }

        .stat-card {
            border: none;
            border-radius: var(--card-border-radius);
            transition: var(--transition);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card .card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card .card-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.2;
        }

        .stat-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .chart-card, .info-card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .chart-card:hover, .info-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            background: transparent;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Status colors */
        .bg-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important; }
        .bg-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important; }
        .bg-info { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%) !important; }
        .bg-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important; }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php';

    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit();
    }

    $stats = [
        'total_pengaduan' => $koneksi->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn(),
        'pengaduan_baru' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = '0'")->fetchColumn(),
        'pengaduan_proses' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'proses'")->fetchColumn(),
        'pengaduan_selesai' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'selesai'")->fetchColumn(),
        'total_petugas' => $koneksi->query("SELECT COUNT(*) FROM users WHERE role = 'petugas'")->fetchColumn(),
    ];

    $stmt = $koneksi->query("
        SELECT DATE_FORMAT(tgl_pengaduan, '%Y-%m') as bulan, COUNT(*) as total
        FROM pengaduan
        WHERE tgl_pengaduan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(tgl_pengaduan, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $grafik_data = $stmt->fetchAll();
    ?>

    <div class="dashboard-header animate__animated animate__fadeIn">
        <div class="container">
            <h2 class="mb-0">Dashboard Admin</h2>
            <p class="mb-0 opacity-75">Selamat datang di panel kontrol admin</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Statistik Cards -->
            <div class="col-xl-3 col-md-6">
                <div class="stat-card bg-primary text-white animate__animated animate__fadeInUp">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="bi bi-files"></i>
                        </div>
                        <div class="card-title">Total Pengaduan</div>
                        <h2><?php echo $stats['total_pengaduan']; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card bg-warning text-white animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="card-title">Pengaduan Baru</div>
                        <h2><?php echo $stats['pengaduan_baru']; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card bg-info text-white animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="card-title">Dalam Proses</div>
                        <h2><?php echo $stats['pengaduan_proses']; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stat-card bg-success text-white animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="card-title">Selesai</div>
                        <h2><?php echo $stats['pengaduan_selesai']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Grafik Pengaduan -->
            <div class="col-xl-8 col-lg-7">
                <div class="chart-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up me-2"></i>
                            Grafik Pengaduan 6 Bulan Terakhir
                        </h5>
                        <div class="chart-container">
                            <canvas id="chartPengaduan"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Sistem -->
            <div class="col-xl-4 col-lg-5">
                <div class="info-card animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle me-2"></i>
                            Informasi Sistem
                        </h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-person-badge me-2"></i>
                                    Total Petugas
                                </div>
                                <span class="badge bg-primary rounded-pill">
                                    <?php echo $stats['total_petugas']; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Konfigurasi grafik
        const ctx = document.getElementById('chartPengaduan').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($grafik_data, 'bulan')); ?>,
                datasets: [{
                    label: 'Jumlah Pengaduan',
                    data: <?php echo json_encode(array_column($grafik_data, 'total')); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#4e73df',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                    pointRadius: 4,
                    pointHitRadius: 10,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>