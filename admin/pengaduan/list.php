<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../../includes/header.php';

// Hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Pagination setup
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = '';
if ($status_filter !== '') {
    $where_clause = "WHERE p.status = :status";
}

// Get total records for pagination
$count_query = "SELECT COUNT(*) FROM pengaduan p $where_clause";
$count_stmt = $koneksi->prepare($count_query);
if ($status_filter !== '') {
    $count_stmt->bindParam(':status', $status_filter);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get pengaduan data with tanggapan count
$query = "
    SELECT 
        p.*,
        COALESCE(COUNT(t.id_tanggapan), 0) as jumlah_tanggapan,
        MAX(t.tgl_tanggapan) as tgl_tanggapan,
        MAX(t.tanggapan) as tanggapan
    FROM pengaduan p 
    LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan
    $where_clause
    GROUP BY 
        p.id_pengaduan,
        p.tgl_pengaduan,
        p.no_tiket,
        p.isi_laporan,
        p.foto,
        p.status
    ORDER BY p.tgl_pengaduan DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $koneksi->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
if ($status_filter !== '') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$pengaduan_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengaduan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-border-radius: 15px;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Segoe UI', sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--card-border-radius) var(--card-border-radius);
            color: white;
        }

        .content-card {
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            background-color: #f8f9fc;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .filter-form {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .text-truncate-custom {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <h2 class="mb-0">Daftar Pengaduan</h2>
            <p class="mb-0 opacity-75">Kelola semua pengaduan masyarakat</p>
        </div>
    </div>

    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengaduan</li>
            </ol>
        </nav>
    </div>

    <div class="container">
        <div class="content-card">
            <!-- Filter Form -->
            <form class="filter-form" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Baru</option>
                            <option value="proses" <?php echo $status_filter === 'proses' ? 'selected' : ''; ?>>Proses
                            </option>
                            <option value="selesai" <?php echo $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai
                            </option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>NIK</th>
                            <th>Isi Laporan</th>
                            <th>Status</th>
                            <th>Tgl Tanggapan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pengaduan_list as $pengaduan): ?>
                            <tr>
                                <td><?php echo $pengaduan['id_pengaduan']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pengaduan['tgl_pengaduan'])); ?></td>
                                <td><?php echo htmlspecialchars($pengaduan['no_tiket']); ?></td>
                                <td>
                                    <span class="text-truncate-custom">
                                        <?php echo htmlspecialchars($pengaduan['isi_laporan']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($pengaduan['status']) {
                                        case '0':
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = 'Baru';
                                            break;
                                        case 'proses':
                                            $status_class = 'bg-info text-white';
                                            $status_text = 'Proses';
                                            break;
                                        case 'selesai':
                                            $status_class = 'bg-success text-white';
                                            $status_text = 'Selesai';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    echo $pengaduan['tgl_tanggapan']
                                        ? date('d/m/Y', strtotime($pengaduan['tgl_tanggapan']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="detail.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="tanggapan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                                            class="btn btn-sm btn-success">
                                            <i class="bi bi-chat-dots"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once '../../includes/footer.php'; ?>
</body>

</html>