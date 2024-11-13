<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../../includes/header.php';

// Cek role petugas
if ($_SESSION['role'] !== 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter setup
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT p.*, COALESCE(COUNT(t.id_tanggapan), 0) as jumlah_tanggapan 
          FROM pengaduan p 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan";

$where_conditions = [];
$params = [];

// Add status filter
if ($status_filter !== 'all') {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

// Add search filter
if ($search) {
    $where_conditions[] = "(p.no_tiket LIKE ? OR p.judul LIKE ? OR p.isi_laporan LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

// Combine where conditions
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY p.id_pengaduan ORDER BY p.tgl_pengaduan DESC";

// Count total rows for pagination
$count_query = "SELECT COUNT(*) FROM (" . $query . ") as count_table";
$stmt = $koneksi->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Add limit and offset to main query
$query .= " LIMIT $limit OFFSET $offset";  // Diubah: menghapus parameter binding untuk LIMIT dan OFFSET

// Execute main query
$stmt = $koneksi->prepare($query);
$stmt->execute($params);  // Diubah: menghapus $limit dan $offset dari parameter
$pengaduan_list = $stmt->fetchAll();

?>

<style>
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .pengaduan-card {
        border-radius: 15px;
        transition: transform 0.2s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .pengaduan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .pagination-custom .page-link {
        border-radius: 10px;
        margin: 0 2px;
    }

    .search-input {
        border-radius: 20px;
        padding-left: 2.5rem;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-list-ul"></i> Daftar Pengaduan</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-house"></i> Dashboard
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="position-relative">
                    <i class="bi bi-search search-icon text-muted"></i>
                    <input type="text" class="form-control search-input" name="search"
                        placeholder="Cari nomor tiket, judul..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Baru</option>
                    <option value="proses" <?php echo $status_filter === 'proses' ? 'selected' : ''; ?>>Diproses</option>
                    <option value="selesai" <?php echo $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="list.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Pengaduan List -->
    <?php if ($pengaduan_list): ?>
        <?php foreach ($pengaduan_list as $pengaduan): ?>
            <div class="card pengaduan-card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title"><?php echo htmlspecialchars($pengaduan['judul']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-ticket-detailed"></i> <?php echo htmlspecialchars($pengaduan['no_tiket']); ?>
                                &nbsp;&bull;&nbsp;
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('d/m/Y H:i', strtotime($pengaduan['tgl_pengaduan'])); ?>
                            </p>
                            <p class="mb-2"><?php echo substr(htmlspecialchars($pengaduan['isi_laporan']), 0, 100) . '...'; ?>
                            </p>
                        </div>
                        <div class="col-md-3 text-md-center">
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
                            ?>
                            <span class="status-badge <?php echo $badges[$pengaduan['status']]; ?>">
                                <?php echo $status_text[$pengaduan['status']]; ?>
                            </span>
                            <div class="mt-2 text-muted">
                                <i class="bi bi-chat-dots"></i> <?php echo $pengaduan['jumlah_tanggapan']; ?> Tanggapan
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <a href="detail.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                class="btn btn-primary rounded-pill">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination pagination-custom justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted">Tidak ada pengaduan yang ditemukan</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>