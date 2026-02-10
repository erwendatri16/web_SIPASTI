<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
$success = '';

// Ambil ID barang dari URL
$id_barang = $_GET['id'] ?? null;

if (!$id_barang) {
    header('Location: index.php');
    exit;
}

// Ambil data barang
$stmt = $pdo->prepare("SELECT * FROM inventaris WHERE id_barang = :id");
$stmt->execute([':id' => $id_barang]);
$barang = $stmt->fetch();

if (!$barang) {
    header('Location: index.php');
    exit;
}

// Proses update barang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_barang'])) {
    $kode_barang = trim($_POST['kode_barang'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $kondisi = trim($_POST['kondisi'] ?? '');
    $stok = (int)($_POST['stok'] ?? 0);
    
    // Validasi
    if (empty($kode_barang) || empty($nama_barang) || empty($kategori) || empty($kondisi)) {
        $error = 'Semua field harus diisi!';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh negatif!';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE inventaris 
                SET kode_barang = :kode_barang,
                    nama_barang = :nama_barang,
                    kategori = :kategori,
                    kondisi = :kondisi,
                    stok = :stok
                WHERE id_barang = :id
            ");
            
            $stmt->execute([
                ':kode_barang' => $kode_barang,
                ':nama_barang' => $nama_barang,
                ':kategori' => $kategori,
                ':kondisi' => $kondisi,
                ':stok' => $stok,
                ':id' => $id_barang
            ]);
            
            $success = '✅ Barang berhasil diupdate!';
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM inventaris WHERE id_barang = :id");
            $stmt->execute([':id' => $id_barang]);
            $barang = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = '❌ Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - SIPASTI</title>
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
        
        .card-form {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 50px 40px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-back {
            background: white;
            color: var(--primary-500);
            border: 2px solid var(--primary-500);
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .required {
            color: #ef4444;
            font-weight: bold;
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Inventaris</a></li>
                    <li class="nav-item"><a class="nav-link" href="../peminjaman/index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="../scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link" href="../laporan/index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="index.php" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Inventaris
        </a>
        
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">✏️ Edit Barang</h1>
            <p class="text-muted fs-5">Update informasi barang inventaris</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="card-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="kode_barang">
                        Kode Barang <span class="required">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="kode_barang" 
                           name="kode_barang" 
                           value="<?= htmlspecialchars($barang['kode_barang']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="nama_barang">
                        Nama Barang <span class="required">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="nama_barang" 
                           name="nama_barang" 
                           value="<?= htmlspecialchars($barang['nama_barang']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="kategori">
                        Kategori <span class="required">*</span>
                    </label>
                    <select class="form-control" id="kategori" name="kategori" required>
                        <option value="Elektronik" <?= ($barang['kategori'] == 'Elektronik') ? 'selected' : '' ?>>Elektronik</option>
                        <option value="Furniture" <?= ($barang['kategori'] == 'Furniture') ? 'selected' : '' ?>>Furniture</option>
                        <option value="Jaringan" <?= ($barang['kategori'] == 'Jaringan') ? 'selected' : '' ?>>Jaringan</option>
                        <option value="Aksesoris" <?= ($barang['kategori'] == 'Aksesoris') ? 'selected' : '' ?>>Aksesoris</option>
                        <option value="Listrik" <?= ($barang['kategori'] == 'Listrik') ? 'selected' : '' ?>>Listrik</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="kondisi">
                        Kondisi <span class="required">*</span>
                    </label>
                    <select class="form-control" id="kondisi" name="kondisi" required>
                        <option value="Baik" <?= ($barang['kondisi'] == 'Baik') ? 'selected' : '' ?>>Baik</option>
                        <option value="Rusak Ringan" <?= ($barang['kondisi'] == 'Rusak Ringan') ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="Rusak Berat" <?= ($barang['kondisi'] == 'Rusak Berat') ? 'selected' : '' ?>>Rusak Berat</option>
                        <option value="Ruangan" <?= ($barang['kategori'] == 'Ruangan') ? 'selected' : '' ?>>Ruangan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stok">
                        Stok <span class="required">*</span>
                    </label>
                    <input type="number" 
                           class="form-control" 
                           id="stok" 
                           name="stok" 
                           value="<?= $barang['stok'] ?>"
                           min="0"
                           required>
                </div>
                
                <button type="submit" name="update_barang" class="btn btn-submit">
                    <i class="bi bi-check-circle me-2"></i>Update Barang
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>