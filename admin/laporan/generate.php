<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once '../../includes/header.php';
require_once '../../vendor/setasign/fpdf/fpdf.php';

// Cek role admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle Generate Report
if (isset($_POST['generate_report'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    // Base query yang hanya mengambil dari tabel pengaduan
    $query = "SELECT * FROM pengaduan 
              WHERE tgl_pengaduan BETWEEN :start_date AND :end_date";

    // Add status filter if specified
    if ($status !== 'all') {
        $query .= " AND status = :status";
    }

    $query .= " ORDER BY tgl_pengaduan DESC";

    $stmt = $koneksi->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    $pengaduan = $stmt->fetchAll();

    // Generate PDF
    if (isset($_POST['format']) && $_POST['format'] === 'pdf') {
        // Hapus output buffering yang mungkin ada
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Periksa tanggal valid
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
        if (!$start_date || !$end_date) {
            die('Tanggal tidak valid.');
        }

        try {
            // Query database dengan PDO (menggunakan $koneksi yang sudah ada)
            $query = "SELECT * FROM pengaduan WHERE tgl_pengaduan BETWEEN :start_date AND :end_date";
            $stmt = $koneksi->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->execute();
            $pengaduan = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($pengaduan)) {
                die('Tidak ada data pengaduan yang tersedia.');
            }

            class PDF extends FPDF
            {
                function Header()
                {
                    global $start_date, $end_date;
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(0, 10, 'LAPORAN PENGADUAN MASYARAKAT', 0, 1, 'C');
                    $this->SetFont('Arial', '', 10);
                    $this->Cell(0, 10, 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)), 0, 1, 'C');
                    $this->Ln(10);
                }

                function Footer()
                {
                    $this->SetY(-15);
                    $this->SetFont('Arial', 'I', 8);
                    $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
                }
            }

            $pdf = new PDF();
            $pdf->AliasNbPages();
            $pdf->AddPage();

            // Table Header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Tanggal', 1);
            $pdf->Cell(90, 7, 'Isi Pengaduan', 1);
            $pdf->Cell(60, 7, 'Status', 1);
            $pdf->Ln();

            // Table Content
            $pdf->SetFont('Arial', '', 10);
            foreach ($pengaduan as $p) {
                $pdf->Cell(40, 7, date('d/m/Y', strtotime($p['tgl_pengaduan'])), 1);
                $isi = substr($p['isi_laporan'], 0, 50) . (strlen($p['isi_laporan']) > 50 ? '...' : '');
                $pdf->Cell(90, 7, $isi, 1);
                $pdf->Cell(60, 7, ucfirst($p['status']), 1);
                $pdf->Ln();
            }

            // Hapus semua output buffering sebelum mengeluarkan PDF
            $pdf->Output('D', 'laporan_pengaduan_' . date('Ymd') . '.pdf');
            exit();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
}

// Get summary stats
$summary = [
    'total' => $koneksi->query("SELECT COUNT(*) FROM pengaduan")->fetchColumn(),
    'baru' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = '0'")->fetchColumn(),
    'proses' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'proses'")->fetchColumn(),
    'selesai' => $koneksi->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'selesai'")->fetchColumn(),
];
?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Laporan - Sistem Pengaduan Masyarakat</title>
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

        .summary-card {
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .preview-table th,
        .preview-table td {
            padding: 1rem;
            vertical-align: middle;
        }
    </style>
</head>

<body class="bg-light">
    <div class="dashboard-header">
        <div class="container">
            <h2 class="mb-0">Generate Laporan</h2>
            <p class="mb-0 opacity-75">Generate dan unduh laporan pengaduan</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <!-- Summary Cards -->
            <div class="col-md-3">
                <div class="card summary-card bg-primary text-white mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Total Pengaduan</h6>
                        <h2><?php echo $summary['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card bg-warning text-white mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Pengaduan Baru</h6>
                        <h2><?php echo $summary['baru']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card bg-info text-white mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Dalam Proses</h6>
                        <h2><?php echo $summary['proses']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card bg-success text-white mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Selesai</h6>
                        <h2><?php echo $summary['selesai']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Generate Laporan Pengaduan</h5>

                        <form method="POST" action="" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="all">Semua Status</option>
                                    <option value="0">Baru</option>
                                    <option value="proses">Proses</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Format Output</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="format" id="formatPreview"
                                            value="preview" checked>
                                        <label class="form-check-label" for="formatPreview">Preview</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="format" id="formatPDF"
                                            value="pdf">
                                        <label class="form-check-label" for="formatPDF">Unduh PDF</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="generate_report" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-text me-2"></i>Generate Laporan
                                </button>
                            </div>
                        </form>

                        <?php if (isset($pengaduan) && $_POST['format'] === 'preview'): ?>
                            <div class="mt-4">
                                <h6>Preview Laporan</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover preview-table">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Isi Pengaduan</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pengaduan as $p): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($p['tgl_pengaduan'])); ?></td>
                                                    <td><?php echo htmlspecialchars($p['isi_laporan']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                        echo $p['status'] === '0' ? 'warning' :
                                                            ($p['status'] === 'proses' ? 'info' : 'success');
                                                        ?>">
                                                            <?php echo ucfirst($p['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biarkan pengguna mengisi tanggal secara manual
        document.addEventListener('DOMContentLoaded', function () {
            // Tidak ada pengaturan tanggal default, biarkan input tanggal kosong
        });
    </script>

</body>

</html>