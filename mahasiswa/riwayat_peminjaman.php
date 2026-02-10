<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Redirect jika belum login atau bukan mahasiswa
if (!isMahasiswaLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$id_mahasiswa = $_SESSION['id_mahasiswa'];
$filter_status = $_GET['status'] ?? 'all';

// Build query dengan filter
$query = "
    SELECT p.*, m.nama_lengkap, m.nim,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE p.id_mahasiswa = :id_mahasiswa
";

if ($filter_status !== 'all') {
    $query .= " AND p.status = :status";
}

$query .= " GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([':id_mahasiswa' => $id_mahasiswa]);

if ($filter_status !== 'all') {
    $stmt->bindValue(':status', $filter_status);
    $stmt->execute();
}

$riwayat = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - SIPASTI</title>
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
        
        .card-history {
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
        
        .history-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 20px 0;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .qr-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-boxes me-2"></i>SIPASTI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="ajukan_peminjaman.php">Ajukan Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="qr_pengembalian.php">QR Pengembalian</a></li>
                    <li class="nav-item"><a class="nav-link active" href="riwayat_peminjaman.php">Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📊 Riwayat Peminjaman</h1>
            <p class="text-muted fs-5">Lihat semua riwayat peminjaman Anda</p>
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

        <div class="card-history">
            <?php if (count($riwayat) > 0): ?>
                <?php foreach ($riwayat as $p): ?>
                    <div class="history-item">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h3 class="fw-bold text-primary mb-1">#<?= $p['id_peminjaman'] ?></h3>
                                    <p class="text-muted mb-0 small">
                                        <?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <h5 class="fw-bold mb-2">Barang Dipinjam</h5>
                                <p class="text-muted mb-1"><?= htmlspecialchars($p['detail_barang']) ?></p>
                                <p class="mb-0">
                                    <span class="status-badge badge-<?= strtolower($p['status']) ?>">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        <?= $p['status'] ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="col-md-3">
                                <h5 class="fw-bold mb-2">Tanggal</h5>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Pinjam: <?= date('d/m/Y H:i', strtotime($p['tanggal_pinjam'])) ?>
                                </p>
                                <?php if ($p['tanggal_disetujui']): ?>
                                    <p class="text-muted mb-1">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Disetujui: <?= date('d/m/Y H:i', strtotime($p['tanggal_disetujui'])) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($p['tanggal_dikembalikan']): ?>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-box-arrow-in-left me-1"></i>
                                        Dikembalikan: <?= date('d/m/Y H:i', strtotime($p['tanggal_dikembalikan'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-2 text-center">
                                <?php if ($p['status'] === 'Aktif' && $p['qr_token']): ?>
                                    <a href="qr_pengembalian.php" class="qr-badge">
                                        <i class="bi bi-qr-code-scan"></i> Lihat QR
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox empty-icon"></i>
                    <h3 class="fw-bold mb-3">Tidak Ada Riwayat</h3>
                    <p class="text-muted mb-4">
                        <?php if ($filter_status === 'all'): ?>
                            Belum ada riwayat peminjaman.
                        <?php else: ?>
                            Tidak ada peminjaman dengan status "<?= htmlspecialchars($filter_status) ?>".
                        <?php endif; ?>
                    </p>
                    <a href="ajukan_peminjaman.php" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-plus-circle me-2"></i>Ajukan Peminjaman
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>