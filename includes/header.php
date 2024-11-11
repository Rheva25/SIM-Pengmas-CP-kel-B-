<!-- File: includes/header.php -->
<?php
session_start();
require_once '../config/koneksi.php';

// Cek jika user belum login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data user yang login
if ($_SESSION['role'] === 'masyarakat') {
    $stmt = $koneksi->prepare("SELECT * FROM masyarakat WHERE id_masyarakat = ?");
} else {
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE id_user = ?");
}
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Pengaduan</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($_SESSION['role'] === 'masyarakat'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaduan/buat.php">Buat Pengaduan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaduan/riwayat.php">Riwayat Pengaduan</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pengaduan/list.php">Daftar Pengaduan</a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="users/list.php">Manajemen User</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="laporan/generate.php">Generate Laporan</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['nama'] ?? $user['nama_lengkap']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
