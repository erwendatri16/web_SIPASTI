<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Filter status
$filter_status = $_GET['status'] ?? 'all';

// Query untuk peminjaman
$query = "
    SELECT p.*, m.nama_lengkap, m.nim, m.prodi, m.email,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
";

if ($filter_status !== 'all') {
    $query .= " WHERE p.status = :status";
}

$query .= " GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam DESC";

$stmt = $pdo->prepare($query);

if ($filter_status !== 'all') {
    $stmt->execute([':status' => $filter_status]);
} else {
    $stmt->execute();
}

$peminjaman = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman - SIPASTI</title>
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
        
        .card-peminjaman {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 25px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-500);
            color: var(--primary-500);
        }
        
        .filter-tab.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .badge-menunggu {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .badge-aktif {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .badge-selesai {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #065f46;
        }
        
        .badge-ditolak {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-approve:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-reject:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .btn-detail {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-detail:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 20px;
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="../scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link" href="../laporan/index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📋 Manajemen Peminjaman</h1>
            <p class="text-muted fs-5">Kelola pengajuan peminjaman inventaris</p>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $filter_status === 'all' ? 'active' : '' ?>">
                <i class="bi bi-funnel me-1"></i>Semua
            </a>
            <a href="?status=Menunggu" class="filter-tab <?= $filter_status === 'Menunggu' ? 'active' : '' ?>">
                <i class="bi bi-clock me-1"></i>Menunggu
            </a>
            <a href="?status=Aktif" class="filter-tab <?= $filter_status === 'Aktif' ? 'active' : '' ?>">
                <i class="bi bi-check-circle me-1"></i>Aktif
            </a>
            <a href="?status=Selesai" class="filter-tab <?= $filter_status === 'Selesai' ? 'active' : '' ?>">
                <i class="bi bi-check2-all me-1"></i>Selesai
            </a>
            <a href="?status=Ditolak" class="filter-tab <?= $filter_status === 'Ditolak' ? 'active' : '' ?>">
                <i class="bi bi-x-circle me-1"></i>Ditolak
            </a>
        </div>

        <div class="card-peminjaman">
            <?php if (count($peminjaman) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mahasiswa</th>
                                <th>Prodi</th>
                                <th>Barang Dipinjam</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peminjaman as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id_peminjaman'] ?></strong></td>
                                    <td>
                                        <small><?= htmlspecialchars($p['nama_lengkap']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($p['nim']) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($p['prodi']) ?></small></td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($p['detail_barang']) ?></small>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($p['tanggal_pinjam'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-<?= strtolower($p['status']) ?>">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                            <?= $p['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'Menunggu'): ?>
                                            <a href="approve.php?id=<?= $p['id_peminjaman'] ?>&action=approve" 
                                               class="btn btn-approve btn-sm me-2"
                                               onclick="return confirm('Yakin ingin menyetujui peminjaman ini?')">
                                                <i class="bi bi-check-circle me-1"></i>Setujui
                                            </a>
                                            <a href="approve.php?id=<?= $p['id_peminjaman'] ?>&action=reject" 
                                               class="btn btn-reject btn-sm"
                                               onclick="return confirm('Yakin ingin menolak peminjaman ini?')">
                                                <i class="bi bi-x-circle me-1"></i>Tolak
                                            </a>
                                        <?php elseif ($p['status'] === 'Aktif' && $p['qr_token']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-qr-code-scan me-1"></i>QR Generated
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox empty-icon"></i>
                    <h3 class="fw-bold mb-3">Tidak Ada Data</h3>
                    <p class="text-muted mb-4">
                        <?php if ($filter_status === 'all'): ?>
                            Belum ada peminjaman.
                        <?php else: ?>
                            Tidak ada peminjaman dengan status "<?= htmlspecialchars($filter_status) ?>".
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>