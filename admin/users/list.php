<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../../includes/header.php';

// Cek role admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle Delete User
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User berhasil dihapus";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
    }
    header("Location: manage_users.php");
    exit();
}

// Handle Add/Edit User
if (isset($_POST['submit'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $role = $_POST['role'];
    $telp = $_POST['telp'];
    
    if (isset($_POST['user_id'])) { // Edit User
        try {
            if ($password) {
                $stmt = $koneksi->prepare("UPDATE users SET nama=?, username=?, password=?, role=?, telp=? WHERE id=?");
                $stmt->execute([$nama, $username, $password, $role, $telp, $_POST['user_id']]);
            } else {
                $stmt = $koneksi->prepare("UPDATE users SET nama=?, username=?, role=?, telp=? WHERE id=?");
                $stmt->execute([$nama, $username, $role, $telp, $_POST['user_id']]);
            }
            $_SESSION['success'] = "User berhasil diupdate";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal mengupdate user: " . $e->getMessage();
        }
    } else { // Add New User
        try {
            $stmt = $koneksi->prepare("INSERT INTO users (nama, username, password, role, telp) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $username, $password, $role, $telp]);
            $_SESSION['success'] = "User baru berhasil ditambahkan";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal menambahkan user: " . $e->getMessage();
        }
    }
    header("Location: manage_users.php");
    exit();
}

// Get Users List
$users = $koneksi->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard-header">
        <div class="container">
            <h2 class="mb-0">Manajemen User</h2>
            <p class="mb-0 opacity-75">Kelola data pengguna sistem</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Daftar Pengguna</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="bi bi-plus-lg"></i> Tambah User
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-action" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-action" 
                                            onclick="deleteUser(<?php echo $user['id_user']; ?>, '<?php echo $user['nama_lengkap']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" id="nama" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password (mode edit)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="admin">Admin</option>
                                <option value="petugas">Petugas</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="tel" class="form-control" name="telp" id="telp" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus user <strong id="deleteUserName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle Edit User
        function editUser(user) {
            document.getElementById('user_id').value = user.id;
            document.getElementById('nama').value = user.nama;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = ''; // Clear password field
            document.getElementById('role').value = user.role;
            document.getElementById('telp').value = user.telp;
            
            document.querySelector('#userModal .modal-title').textContent = 'Edit User';
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }

        // Handle Delete User
        function deleteUser(id, nama) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = nama;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reset modal title when adding new user
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
            document.querySelector('#userModal .modal-title').textContent = 'Tambah User Baru';
            document.getElementById('user_id').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('role').value = 'petugas';
            document.getElementById('telp').value = '';
        });
    </script>
</body>
</html>