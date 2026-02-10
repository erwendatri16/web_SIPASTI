<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Get semua inventaris
$stmt = $pdo->query("
    SELECT * FROM inventaris 
    ORDER BY kategori, nama_barang
");
$inventaris = $stmt->fetchAll();

// Filter berdasarkan kategori
$filter_kategori = $_GET['kategori'] ?? 'all';
if ($filter_kategori !== 'all') {
    $stmt = $pdo->prepare("
        SELECT * FROM inventaris 
        WHERE kategori = :kategori 
        ORDER BY nama_barang
    ");
    $stmt->execute([':kategori' => $filter_kategori]);
    $inventaris = $stmt->fetchAll();
}

// Cari berdasarkan keyword
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM inventaris 
        WHERE nama_barang LIKE :search 
        OR kode_barang LIKE :search 
        OR kategori LIKE :search
        ORDER BY kategori, nama_barang
    ");
    $stmt->execute([':search' => "%$search%"]);
    $inventaris = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Inventaris - SIPASTI</title>
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
        
        .card-inventaris {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn {
            padding: 8px 20px;
            border-radius: 50px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .filter-btn:hover {
            border-color: var(--primary-500);
            color: var(--primary-500);
        }
        
        .filter-btn.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .card-item {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .card-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
            border-color: var(--primary-500);
        }
        
        .category-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .condition-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .condition-baik {
            background: #dcfce7;
            color: #065f46;
        }
        
        .condition-rusak-ringan {
            background: #fef3c7;
            color: #92400e;
        }
        
        .condition-rusak-berat {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stock-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .stock-high {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: scale(1.05);
        }
        
        .search-box {
            max-width: 400px;
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Inventaris</a></li>
                    <li class="nav-item"><a class="nav-link" href="../peminjaman/index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="../scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link" href="../laporan/index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-2">📦 Manajemen Inventaris</h1>
                <p class="text-muted">Kelola data barang inventaris laboratorium</p>
            </div>
            <a href="tambah.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i>Tambah Barang
            </a>
        </div>

        <!-- Filter & Search -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="d-flex flex-wrap">
                        <a href="?kategori=all" class="filter-btn <?= $filter_kategori === 'all' ? 'active' : '' ?>">
                            <i class="bi bi-funnel me-1"></i>Semua
                        </a>
                        <a href="?kategori=Elektronik" class="filter-btn <?= $filter_kategori === 'Elektronik' ? 'active' : '' ?>">
                            <i class="bi bi-tv me-1"></i>Elektronik
                        </a>
                        <a href="?kategori=Furniture" class="filter-btn <?= $filter_kategori === 'Furniture' ? 'active' : '' ?>">
                            <i class="bi bi-chair me-1"></i>Furniture
                        </a>
                        <a href="?kategori=Jaringan" class="filter-btn <?= $filter_kategori === 'Jaringan' ? 'active' : '' ?>">
                            <i class="bi bi-router me-1"></i>Jaringan
                        </a>
                        <a href="?kategori=Aksesoris" class="filter-btn <?= $filter_kategori === 'Aksesoris' ? 'active' : '' ?>">
                            <i class="bi bi-plug me-1"></i>Aksesoris
                        </a>
                        <a href="?kategori=Listrik" class="filter-btn <?= $filter_kategori === 'Listrik' ? 'active' : '' ?>">
                            <i class="bi bi-lightning me-1"></i>Listrik
                        </a>
                        <a href="?kategori=Ruangan" class="filter-btn <?= $filter_kategori === 'Ruangan' ? 'active' : '' ?>">
        <i class="bi bi-door me-1"></i>Ruangan
    </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="GET" action="">
                        <div class="input-group search-box">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="Cari barang..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Total Items Info -->
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong><?= count($inventaris) ?> barang</strong> ditemukan
            <?php if ($filter_kategori !== 'all'): ?>
                - Kategori: <strong><?= htmlspecialchars($filter_kategori) ?></strong>
            <?php endif; ?>
        </div>

        <!-- Inventaris List -->
        <div class="card-inventaris">
            <?php if (count($inventaris) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Kondisi</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventaris as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['kode_barang']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                                    <td>
                                        <span class="category-badge">
                                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($b['kategori']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="condition-badge condition-<?= strtolower(str_replace(' ', '-', $b['kondisi'])) ?>">
                                            <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($b['kondisi']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="stock-badge <?= $b['stok'] > 3 ? 'stock-high' : 'stock-low' ?>">
                                            <i class="bi bi-boxes me-1"></i><?= $b['stok'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?= $b['id_barang'] ?>" 
                                           class="btn btn-sm btn-warning btn-action me-2"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="hapus.php?id=<?= $b['id_barang'] ?>" 
                                           class="btn btn-sm btn-danger btn-action"
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus barang ini?')">
                                            <i class="bi bi-trash"></i>
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
                    <h4 class="fw-bold mt-3">Tidak Ada Data</h4>
                    <p class="text-muted">
                        <?php if (!empty($search)): ?>
                            Tidak ada barang yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>"
                        <?php elseif ($filter_kategori !== 'all'): ?>
                            Tidak ada barang di kategori "<?= htmlspecialchars($filter_kategori) ?>"
                        <?php else: ?>
                            Belum ada data inventaris. Tambahkan barang pertama Anda!
                        <?php endif; ?>
                    </p>
                    <a href="tambah.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Barang
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>