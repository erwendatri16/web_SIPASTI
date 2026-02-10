<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';

// Redirect jika belum login atau bukan mahasiswa
if (!isMahasiswaLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$id_mahasiswa = $_SESSION['id_mahasiswa'];

// Get statistik
$total_peminjaman = getTotalPeminjaman($pdo);
$total_menunggu = getTotalMenunggu($pdo);
$total_aktif = getTotalAktif($pdo);
$total_selesai = getTotalSelesai($pdo);

// Get riwayat peminjaman terbaru
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_lengkap,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE p.id_mahasiswa = :id
    GROUP BY p.id_peminjaman
    ORDER BY p.tanggal_pinjam DESC
    LIMIT 5
");
$stmt->execute([':id' => $id_mahasiswa]);
$riwayat_terbaru = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIPASTI</title>
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
        
        .card-stat.menunggu { border-left-color: #fbbf24; }
        .card-stat.aktif { border-left-color: #3b82f6; }
        .card-stat.selesai { border-left-color: #10b981; }
        
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
        
        .card-recent {
            background: white;
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="ajukan_peminjaman.php">Ajukan Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="qr_pengembalian.php">QR Pengembalian</a></li>
                    <li class="nav-item"><a class="nav-link" href="riwayat_peminjaman.php">Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="mb-5">
            <h1 class="fw-bold">👋 Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!</h1>
            <p class="text-muted">Selamat datang di Sistem Peminjaman Sarana Terpadu</p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card-stat menunggu">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-number"><?= $total_menunggu ?></div>
                    <div class="stat-label">Menunggu Approval</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat aktif">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $total_aktif ?></div>
                    <div class="stat-label">Peminjaman Aktif</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat selesai">
                    <div class="stat-icon text-success">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div class="stat-number"><?= $total_selesai ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card-recent">
            <h4 class="fw-bold mb-4">📊 Riwayat Peminjaman Terbaru</h4>
            
            <?php if (count($riwayat_terbaru) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat_terbaru as $p): ?>
                                <tr>
                                    <td>#<?= $p['id_peminjaman'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?></td>
                                    <td><?= htmlspecialchars($p['detail_barang']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        switch($p['status']) {
                                            case 'Menunggu':
                                                $badge_class = 'badge-menunggu';
                                                break;
                                            case 'Aktif':
                                                $badge_class = 'badge-aktif';
                                                break;
                                            case 'Selesai':
                                                $badge_class = 'badge-selesai';
                                                break;
                                            case 'Ditolak':
                                                $badge_class = 'badge-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $badge_class ?>">
                                            <?= $p['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="riwayat_peminjaman.php" class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                    <p class="text-muted mt-3">Belum ada riwayat peminjaman</p>
                    <a href="ajukan_peminjaman.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-2"></i>Ajukan Peminjaman
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>