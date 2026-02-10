<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01'); // Awal bulan ini
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d'); // Hari ini
$status_filter = $_GET['status'] ?? 'all';

// Query untuk laporan peminjaman
$query = "
    SELECT p.*, m.nama_lengkap, m.nim, m.prodi,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE DATE(p.tanggal_pinjam) BETWEEN :tanggal_awal AND :tanggal_akhir
";

if ($status_filter !== 'all') {
    $query .= " AND p.status = :status";
}

$query .= " GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':tanggal_awal' => $tanggal_awal,
    ':tanggal_akhir' => $tanggal_akhir
]);

if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter);
}

$laporan = $stmt->fetchAll();

// Hitung statistik
$total_peminjaman = count($laporan);
$total_disetujui = 0;
$total_ditolak = 0;
$total_selesai = 0;

foreach ($laporan as $p) {
    if ($p['status'] === 'Aktif') $total_disetujui++;
    if ($p['status'] === 'Ditolak') $total_ditolak++;
    if ($p['status'] === 'Selesai') $total_selesai++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            font-family: 'Poppins', sans-serif;
            padding-top: 80px;
        }
        
        .navbar-custom {
            background: var(--primary-gradient);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .card-laporan {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-menunggu {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-aktif {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-selesai {
            background: #dcfce7;
            color: #065f46;
        }
        
        .badge-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-export-excel {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-export-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .date-input {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .date-input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php">
                <i class="bi bi-boxes me-2"></i>SIPASTI - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../inventaris/index.php">Inventaris</a></li>
                    <li class="nav-item"><a class="nav-link" href="../peminjaman/index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="../scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📊 Laporan Peminjaman</h1>
            <p class="text-muted fs-5">Lihat dan export laporan peminjaman inventaris</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tanggal Awal</label>
                        <input type="date" 
                               class="form-control date-input" 
                               name="tanggal_awal" 
                               value="<?= htmlspecialchars($tanggal_awal) ?>"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tanggal Akhir</label>
                        <input type="date" 
                               class="form-control date-input" 
                               name="tanggal_akhir" 
                               value="<?= htmlspecialchars($tanggal_akhir) ?>"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-control date-input" name="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua</option>
                            <option value="Menunggu" <?= $status_filter === 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Aktif" <?= $status_filter === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Selesai" <?= $status_filter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="Ditolak" <?= $status_filter === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-2"></i>Cari
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="export_pdf.php?tanggal_awal=<?= urlencode($tanggal_awal) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>&status=<?= urlencode($status_filter) ?>" 
                           class="btn btn-export w-100"
                           target="_blank">
                            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-primary"><?= $total_peminjaman ?></div>
                    <div class="stat-label">Total Peminjaman</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $total_disetujui ?></div>
                    <div class="stat-label">Disetujui</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $total_ditolak ?></div>
                    <div class="stat-label">Ditolak</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-info"><?= $total_selesai ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Laporan Table -->
        <div class="card-laporan">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">
                    <i class="bi bi-table me-2"></i>Daftar Peminjaman
                </h4>
                <small class="text-muted">
                    Periode: <?= date('d/m/Y', strtotime($tanggal_awal)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?>
                </small>
            </div>
            
            <?php if (count($laporan) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tanggal Pinjam</th>
                                <th>Mahasiswa</th>
                                <th>Prodi</th>
                                <th>Barang</th>
                                <th>Status</th>
                                <th>Tanggal Disetujui</th>
                                <th>Tanggal Kembali</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporan as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id_peminjaman'] ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($p['tanggal_pinjam'])) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($p['nama_lengkap']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($p['nim']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($p['prodi']) ?></small></td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($p['detail_barang']) ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-<?= strtolower($p['status']) ?>">
                                            <?= $p['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['tanggal_disetujui']): ?>
                                            <small><?= date('d/m/Y H:i', strtotime($p['tanggal_disetujui'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['tanggal_dikembalikan']): ?>
                                            <small><?= date('d/m/Y H:i', strtotime($p['tanggal_dikembalikan'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong><?= count($laporan) ?> data</strong> ditemukan. Klik tombol PDF untuk export laporan.
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                    <h4 class="fw-bold mt-3">Tidak Ada Data</h4>
                    <p class="text-muted">Tidak ada peminjaman pada periode yang dipilih.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>