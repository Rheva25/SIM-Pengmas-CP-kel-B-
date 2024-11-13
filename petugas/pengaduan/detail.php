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

// Ambil ID pengaduan dari parameter URL
$id_pengaduan = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_pengaduan) {
    $_SESSION['error'] = "ID Pengaduan tidak valid";
    header("Location: list.php");
    exit();
}

// Ambil detail pengaduan (query dimodifikasi tanpa join ke tabel masyarakat)
$stmt = $koneksi->prepare("
    SELECT p.* 
    FROM pengaduan p
    WHERE p.id_pengaduan = ?
");
$stmt->execute([$id_pengaduan]);
$pengaduan = $stmt->fetch();

if (!$pengaduan) {
    $_SESSION['error'] = "Pengaduan tidak ditemukan";
    header("Location: list.php");
    exit();
}

// ...

// Ambil tanggapan untuk pengaduan ini
$stmt = $koneksi->prepare("
    SELECT t.*, u.nama_lengkap as nama_petugas
    FROM tanggapan t
    LEFT JOIN users u ON t.id_user = u.id_user
    WHERE t.id_pengaduan = ?
    ORDER BY t.tgl_tanggapan DESC
");
$stmt->execute([$id_pengaduan]);
$tanggapan = $stmt->fetchAll();

// Proses form tanggapan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_tanggapan'])) {
    $isi_tanggapan = $_POST['isi_tanggapan'];
    $status_baru = $_POST['status'];

    try {
        $koneksi->beginTransaction();

        // Periksa apakah tanggapan sudah ada sebelumnya
        $stmt = $koneksi->prepare("
            SELECT COUNT(*) 
            FROM tanggapan
            WHERE id_pengaduan = ? AND id_user = ?
        ");
        $stmt->execute([$id_pengaduan, $_SESSION['user_id']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Jika sudah ada, perbarui tanggapan yang ada
            $stmt = $koneksi->prepare("
                UPDATE tanggapan 
                SET tanggapan = ?, tgl_tanggapan = NOW()
                WHERE id_pengaduan = ? AND id_user = ?
            ");
            $stmt->execute([$isi_tanggapan, $id_pengaduan, $_SESSION['user_id']]);
        } else {
            // Jika belum ada, simpan tanggapan baru
            $stmt = $koneksi->prepare("
                INSERT INTO tanggapan (id_pengaduan, id_user, tanggapan, tgl_tanggapan)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$id_pengaduan, $_SESSION['user_id'], $isi_tanggapan]);
        }

        // Update status pengaduan
        $stmt = $koneksi->prepare("
            UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?
        ");
        $stmt->execute([$status_baru, $id_pengaduan]);

        $koneksi->commit();

        // Tampilkan pesan sukses
        echo "<div class='alert alert-success'>Tanggapan berhasil disimpan dan status diperbarui</div>";
    } catch (Exception $e) {
        $koneksi->rollBack();

        // Tampilkan pesan error
        echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    }
}

// ...
?>

<style>
    .detail-card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }

    .tanggapan-card {
        border-left: 4px solid #4a90e2;
        margin-bottom: 1rem;
        border-radius: 0 15px 15px 0;
    }

    .tanggapan-form {
        background-color: #f8f9fa;
        border-radius: 15px;
        padding: 1.5rem;
    }

    .back-button {
        border-radius: 20px;
        padding: 0.5rem 1.5rem;
    }
</style>

<div class="container py-4">
    <!-- Header dan Tombol Kembali -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-text"></i> Detail Pengaduan</h2>
        <a href="list.php" class="btn btn-outline-secondary back-button">
            <i class="bi bi-arrow-left"></i> Kembali
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

    <!-- Detail Pengaduan -->
    <div class="card detail-card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title"><?php echo htmlspecialchars($pengaduan['judul']); ?></h5>
                    <p class="text-muted mb-2">
                        <i class="bi bi-ticket-detailed"></i> No. Tiket:
                        <?php echo htmlspecialchars($pengaduan['no_tiket']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="bi bi-calendar3"></i> Tanggal:
                        <?php echo date('d/m/Y H:i', strtotime($pengaduan['tgl_pengaduan'])); ?>
                    </p>
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
                        <i class="bi bi-flag"></i> Status: <?php echo $status_text[$pengaduan['status']]; ?>
                    </span>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2">Isi Pengaduan:</h6>
                    <p><?php echo nl2br(htmlspecialchars($pengaduan['isi_laporan'])); ?></p>
                    <?php if ($pengaduan['foto']): ?>
                        <div class="mt-3">
                            <h6 class="mb-2">Lampiran Foto:</h6>
                            <img src="../../uploads/<?php echo htmlspecialchars($pengaduan['foto']); ?>"
                                alt="Foto Pengaduan" class="img-fluid rounded" style="max-width: 300px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Tanggapan -->
    <?php if ($pengaduan['status'] !== 'selesai'): ?>
        <div class="card detail-card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-reply"></i> Berikan Tanggapan</h5>
                <form action="" method="POST" class="tanggapan-form">
                    <div class="mb-3">
                        <label for="isi_tanggapan" class="form-label">Tanggapan:</label>
                        <textarea class="form-control" id="isi_tanggapan" name="isi_tanggapan" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status:</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="proses" <?php echo $pengaduan['status'] === 'proses' ? 'selected' : ''; ?>>Diproses
                            </option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                    <button type="submit" name="submit_tanggapan" class="btn btn-primary">
                        <i class="bi bi-send"></i> Kirim Tanggapan
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Riwayat Tanggapan -->
    <div class="card detail-card">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-clock-history"></i> Riwayat Tanggapan</h5>
            <?php if ($tanggapan): ?>
                <?php foreach ($tanggapan as $t): ?>
                    <div class="tanggapan-card p-3 bg-light">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-2"><?php echo htmlspecialchars($t['nama_petugas']); ?></h6>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($t['tgl_tanggapan'])); ?>
                            </small>
                        </div>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($t['tanggapan'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">
                    <i class="bi bi-chat-left-dots"></i> Belum ada tanggapan
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>