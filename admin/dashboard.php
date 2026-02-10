<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Get statistik
$total_barang = getTotalBarang($pdo);
$total_peminjaman = getTotalPeminjaman($pdo);
$total_menunggu = getTotalMenunggu($pdo);
$total_aktif = getTotalAktif($pdo);
$total_selesai = getTotalSelesai($pdo);
$total_stok = getTotalStok($pdo);

// Get peminjaman menunggu
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_lengkap, m.nim, m.prodi,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE p.status = 'Menunggu'
    GROUP BY p.id_peminjaman
    ORDER BY p.tanggal_pinjam DESC
    LIMIT 5
");
$stmt->execute();
$peminjaman_menunggu = $stmt->fetchAll();

// Get barang stok rendah
$stmt = $pdo->query("
    SELECT * FROM inventaris 
    WHERE stok <= 3 
    ORDER BY stok ASC
    LIMIT 5
");
$barang_stok_rendah = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        
        .card-stat {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-stat.total-barang { border-left-color: #667eea; }
        .card-stat.total-peminjaman { border-left-color: #3b82f6; }
        .card-stat.menunggu { border-left-color: #fbbf24; }
        .card-stat.aktif { border-left-color: #10b981; }
        
        .stat-icon {
            font-size: 2.5rem;
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
        
        .card-waiting {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .card-low-stock {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
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
        
        .low-stock-badge {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
        }
        
        .quick-action-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .quick-action-btn.scan {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .quick-action-btn.scan:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .quick-action-btn.laporan {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .quick-action-btn.laporan:hover {
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-boxes me-2"></i>SIPASTI - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventaris/index.php">Inventaris</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman/index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link" href="laporan/index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="mb-5">
            <h1 class="fw-bold">👋 Halo, <?= htmlspecialchars($_SESSION['nama_admin']) ?>!</h1>
            <p class="text-muted">Selamat datang di Dashboard Admin SIPASTI</p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card-stat total-barang">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="stat-number"><?= $total_barang ?></div>
                    <div class="stat-label">Total Barang</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stat total-peminjaman">
                    <div class="stat-icon text-blue">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-number"><?= $total_peminjaman ?></div>
                    <div class="stat-label">Total Peminjaman</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stat menunggu">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-number"><?= $total_menunggu ?></div>
                    <div class="stat-label">Menunggu Approval</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stat aktif">
                    <div class="stat-icon text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $total_aktif ?></div>
                    <div class="stat-label">Peminjaman Aktif</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-md-3">
                <a href="inventaris/tambah.php" class="quick-action-btn">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Barang
                </a>
                <a href="peminjaman/index.php" class="quick-action-btn">
                    <i class="bi bi-check-circle me-2"></i>Approve Peminjaman
                </a>
                <a href="scan_pengembalian.php" class="quick-action-btn scan">
                    <i class="bi bi-qr-code-scan me-2"></i>Scan QR Pengembalian
                </a>
                <a href="laporan/index.php" class="quick-action-btn laporan">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Export Laporan PDF
                </a>
            </div>

            <!-- Peminjaman Menunggu -->
            <div class="col-md-5">
                <div class="card-waiting">
                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-clock-history me-2"></i>Peminjaman Menunggu Approval
                        <span class="badge bg-warning text-dark ms-2"><?= count($peminjaman_menunggu) ?></span>
                    </h4>
                    
                    <?php if (count($peminjaman_menunggu) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Mahasiswa</th>
                                        <th>Barang</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($peminjaman_menunggu as $p): ?>
                                        <tr>
                                            <td><strong>#<?= $p['id_peminjaman'] ?></strong></td>
                                            <td>
                                                <small><?= htmlspecialchars($p['nama_lengkap']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($p['nim']) ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars($p['detail_barang']) ?></small>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?></small>
                                            </td>
                                            <td>
                                                <a href="peminjaman/approve.php?id=<?= $p['id_peminjaman'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-check-circle me-1"></i>Setujui
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="peminjaman/index.php" class="btn btn-outline-primary btn-sm mt-3">
                            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle" style="font-size: 3rem; color: #10b981;"></i>
                            <p class="text-muted mt-3">Tidak ada peminjaman yang menunggu approval</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Barang Stok Rendah -->
            <div class="col-md-4">
                <div class="card-low-stock">
                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Stok Rendah
                        <span class="badge bg-danger ms-2"><?= count($barang_stok_rendah) ?></span>
                    </h4>
                    
                    <?php if (count($barang_stok_rendah) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($barang_stok_rendah as $b): ?>
                                <div class="list-group-item bg-transparent border-0 p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-1 fw-bold"><?= htmlspecialchars($b['nama_barang']) ?></p>
                                            <small class="text-muted">
                                                Kode: <?= htmlspecialchars($b['kode_barang']) ?>
                                            </small>
                                        </div>
                                        <span class="low-stock-badge">
                                            <i class="bi bi-box-seam me-1"></i><?= $b['stok'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="inventaris/index.php" class="btn btn-warning btn-sm mt-3 w-100">
                            Kelola Inventaris <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #10b981;"></i>
                            <p class="text-dark mt-3">Semua barang stok aman</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>