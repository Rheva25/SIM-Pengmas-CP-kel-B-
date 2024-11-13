<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../includes/header.php';

// Hanya petugas yang bisa akses
if ($_SESSION['role'] !== 'petugas') {
    header("Location: ../auth/login.php");
    exit();
}
// Mengambil statistik untuk dashboard petugas
$stats = [
    'total_ditangani' => $koneksi->prepare("
        SELECT COUNT(*) FROM pengaduan p 
        INNER JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
        WHERE t.id_user = ?
    "),
    'perlu_ditangani' => $koneksi->query("
        SELECT COUNT(*) FROM pengaduan 
        WHERE status = '0' OR status = 'proses'
    ")
];

// Jalankan query untuk mengambil total_ditangani
$stats['total_ditangani']->execute([$_SESSION['user_id']]);
$total_ditangani = $stats['total_ditangani']->fetchColumn();

// Jalankan query untuk mengambil perlu_ditangani
$perlu_ditangani = $stats['perlu_ditangani']->fetchColumn();

// Mengambil daftar pengaduan terbaru
$stmt = $koneksi->query("
    SELECT p.* 
    FROM pengaduan p 
    WHERE p.status IN ('0', 'proses')
    ORDER BY p.tgl_pengaduan DESC 
    LIMIT 5
");
$pengaduan_terbaru = $stmt->fetchAll();
?>

<!-- CSS Tambahan -->
<style>
    .dashboard-header {
        background: linear-gradient(135deg, #4a90e2 0%, #2c5282 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .stats-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .table-card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;

    }

    .table-card .card-body {
        padding: 1.5rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .action-btn {
        border-radius: 20px;
        padding: 0.4rem 1rem;
        transition: all 0.3s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="dashboard-header">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2><i class="bi bi-person-gear"></i> Dashboard Petugas</h2>
            <p class="mb-0">Selamat datang kembali, <?php echo htmlspecialchars($user['nama'] ?? $user['nama_lengkap']); ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="mb-0"><i class="bi bi-calendar3"></i> <?php echo date('l, d F Y'); ?></p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Card Statistik -->
    <div class="col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="bi bi-exclamation-triangle text-warning"
                        style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3), -1px -1px 2px rgba(255,255,255,0.5);"></i>
                </div>
                <h5 class="card-title">Pengaduan yang Perlu Ditangani</h5>
                <h2 class="display-4 fw-bold"><?php echo number_format($perlu_ditangani); ?></h2>
                <p class="mb-0">Laporan menunggu tindakan</p>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #34c759 0%, #2a9055 100%);">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="bi bi-check-circle text-warning"
                        style="text-shadow: 1px 1px 2px rgba(0,0,0,0.3), -1px -1px 2px rgba(255,255,255,0.5);"></i>
                </div>
                <h5 class="card-title">Total Pengaduan Ditangani</h5>
                <h2 class="display-4 fw-bold"><?php echo $total_ditangani; ?></h2>
                <p class="mb-0">Laporan telah diselesaikan</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daftar Pengaduan Terbaru -->
    <div class="col-md-12">
        <div class="card table-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul"></i> Pengaduan Terbaru
                    </h5>
                    <a href="pengaduan/list.php" class="btn btn-outline-primary action-btn">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><i class="bi bi-calendar3"></i> Tanggal</th>
                                <th><i class="bi bi-person"></i> No Tiket</th>
                                <th><i class="bi bi-card-text"></i> Judul</th>
                                <th><i class="bi bi-flag"></i> Status</th>
                                <th><i class="bi bi-gear"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengaduan_terbaru as $pengaduan): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($pengaduan['tgl_pengaduan'])); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-circle me-2"></i>
                                            <?php echo htmlspecialchars($pengaduan['no_tiket']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pengaduan['judul']); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            '0' => 'bg-warning',
                                            'proses' => 'bg-info',
                                            'selesai' => 'bg-success'
                                        ];
                                        $status_text = [
                                            '0' => 'Baru',
                                            'proses' => 'Diproses',
                                            'selesai' => 'Selesai'
                                        ];
                                        $status_icons = [
                                            '0' => 'bi-exclamation-circle',
                                            'proses' => 'bi-arrow-repeat',
                                            'selesai' => 'bi-check-circle'
                                        ];
                                        ?>
                                        <span class="status-badge <?php echo $badges[$pengaduan['status']]; ?>">
                                            <i class="bi <?php echo $status_icons[$pengaduan['status']]; ?>"></i>
                                            <?php echo $status_text[$pengaduan['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pengaduan/detail.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                            class="btn btn-sm btn-primary action-btn">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pengaduan_terbaru)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        Tidak ada pengaduan baru saat ini
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>