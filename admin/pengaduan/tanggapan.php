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

// Get pengaduan detail
$query = "SELECT * FROM pengaduan WHERE id_pengaduan = :id_pengaduan";
$stmt = $koneksi->prepare($query);
$stmt->bindParam(':id_pengaduan', $id_pengaduan);
$stmt->execute();
$pengaduan = $stmt->fetch();

if (!$pengaduan) {
    $_SESSION['error'] = "Pengaduan tidak ditemukan";
    header("Location: list.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggapan = trim($_POST['tanggapan']);
    $status = $_POST['status'];
    $id_petugas = $_SESSION['user_id'];

    if (empty($tanggapan)) {
        $_SESSION['error'] = "Tanggapan tidak boleh kosong";
    } else {
        try {
            $koneksi->beginTransaction();

            // Insert tanggapan
            $query = "INSERT INTO tanggapan (id_pengaduan, tgl_tanggapan, tanggapan, id_petugas) 
                     VALUES (:id_pengaduan, NOW(), :tanggapan, :id_petugas)";
            $stmt = $koneksi->prepare($query);
            $stmt->bindParam(':id_pengaduan', $id_pengaduan);
            $stmt->bindParam(':tanggapan', $tanggapan);
            $stmt->bindParam(':id_petugas', $id_petugas);
            $stmt->execute();

            // Update status pengaduan
            $query = "UPDATE pengaduan SET status = :status WHERE id_pengaduan = :id_pengaduan";
            $stmt = $koneksi->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id_pengaduan', $id_pengaduan);
            $stmt->execute();

            $koneksi->commit();

            $_SESSION['success'] = "Tanggapan berhasil ditambahkan";
            header("Location: detail.php?id=" . $id_pengaduan);
            exit();
        } catch (PDOException $e) {
            $koneksi->rollBack();
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tanggapan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .tanggapan-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            color: white;
        }

        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="tanggapan-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Tambah Tanggapan</h2>
                <a href="detail.php?id=<?php echo $pengaduan['id_pengaduan']; ?>"
                    class="btn btn-outline-light rounded-pill">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form Tanggapan -->
        <div class="form-card">
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="tanggapan" class="form-label">Tanggapan</label>
                    <textarea class="form-control" id="tanggapan" name="tanggapan" rows="5"
                        required><?php echo isset($_POST['tanggapan']) ? htmlspecialchars($_POST['tanggapan']) : ''; ?></textarea>
                    <div class="invalid-feedback">
                        Tanggapan tidak boleh kosong
                    </div>
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label">Update Status Pengaduan</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="0" <?php echo $pengaduan['status'] === '0' ? 'selected' : ''; ?>>Belum diproses
                        </option>
                        <option value="1" <?php echo $pengaduan['status'] === '1' ? 'selected' : ''; ?>>Sedang diproses
                        </option>
                        <option value="2" <?php echo $pengaduan['status'] === '2' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="3" <?php echo $pengaduan['status'] === '3' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                    <div class="invalid-feedback">
                        Silakan pilih status pengaduan
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Kirim Tanggapan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>

</html>