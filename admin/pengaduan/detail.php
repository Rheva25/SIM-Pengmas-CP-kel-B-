<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../../includes/header.php';

// Cek role admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID Pengaduan tidak valid";
    header("Location: list.php");
    exit();
}

$id_pengaduan = $_GET['id'];

// Get pengaduan detail (query dimodifikasi tanpa join ke tabel masyarakat)
$query = "
    SELECT *
    FROM pengaduan
    WHERE id_pengaduan = :id_pengaduan
";

$stmt = $koneksi->prepare($query);
$stmt->bindParam(':id_pengaduan', $id_pengaduan);
$stmt->execute();
$pengaduan = $stmt->fetch();

if (!$pengaduan) {
    $_SESSION['error'] = "Pengaduan tidak ditemukan";
    header("Location: list.php");
    exit();
}

// Get tanggapan list
// Modifikasi query tanggapan untuk JOIN ke tabel users
$query_tanggapan = "
    SELECT 
        t.*,
        u.nama_lengkap as nama_petugas
    FROM tanggapan t
    LEFT JOIN users u ON t.id_user = u.id_user
    WHERE t.id_pengaduan = :id_pengaduan
    ORDER BY t.tgl_tanggapan DESC
";
$stmt = $koneksi->prepare($query_tanggapan);
$stmt->bindParam(':id_pengaduan', $id_pengaduan);
$stmt->execute();
$tanggapan_list = $stmt->fetchAll();

$status_badges = [
    '0' => ['badge' => 'bg-warning text-dark', 'text' => 'Belum diproses'],
    'proses' => ['badge' => 'bg-info text-white', 'text' => 'Sedang diproses'],
    'selesai' => ['badge' => 'bg-success text-white', 'text' => 'Selesai']
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengaduan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .detail-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            color: white;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .tanggapan-item {
            border-left: 3px solid #4e73df;
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }

        .tanggapan-meta {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .foto-pengaduan {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="detail-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Detail Pengaduan</h2>
                <a href="list.php" class="btn btn-outline-light rounded-pill">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Pengaduan Detail -->
        <div class="content-card">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h3><?php echo htmlspecialchars($pengaduan['judul']); ?></h3>
                        <div class="text-muted mb-3">
                            <span class="me-3">
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('d/m/Y H:i', strtotime($pengaduan['tgl_pengaduan'])); ?>
                            </span>
                        </div>
                        <span class="status-badge <?php echo $status_badges[$pengaduan['status']]['badge']; ?>">
                            <?php echo $status_badges[$pengaduan['status']]['text']; ?>
                        </span>
                    </div>

                    <div class="mb-4">
                        <h5>Isi Laporan</h5>
                        <p><?php echo nl2br(htmlspecialchars($pengaduan['isi_laporan'])); ?></p>
                        <?php if ($pengaduan['foto']): ?>
                            <img src="../../uploads/<?php echo htmlspecialchars($pengaduan['foto']); ?>"
                                alt="Foto Pengaduan" class="foto-pengaduan">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Informasi Pelapor</h5>
                            <hr>
                            <p class="mb-2">
                                <strong><i class="bi bi-map"></i> Lokasi:</strong><br>
                                <?php echo htmlspecialchars("{$pengaduan['latitude']},{$pengaduan['longitude']}"); ?>
                            </p>
                            <p class="mb-0">
                                <strong><i class="bi bi-person-circle"></i> No Tiket:</strong><br>
                                <?php echo htmlspecialchars($pengaduan['no_tiket']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tanggapan Section -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Riwayat Tanggapan</h4>
                <a href="tanggapan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                    class="btn btn-primary rounded-pill">
                    <i class="bi bi-plus-lg"></i> Tambah Tanggapan
                </a>
            </div>

            <?php if ($tanggapan_list): ?>
                <?php foreach ($tanggapan_list as $tanggapan): ?>
                    <div class="tanggapan-item">
                        <div class="tanggapan-meta mb-2">
                            <strong><?php echo htmlspecialchars($tanggapan['nama_petugas']); ?></strong> &bull;
                            <?php echo date('d/m/Y H:i', strtotime($tanggapan['tgl_tanggapan'])); ?>
                        </div>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($tanggapan['tanggapan'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-chat-dots" style="font-size: 2rem;"></i>
                    <p class="mt-2">Belum ada tanggapan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once '../../includes/footer.php'; ?>
</body>

</html>