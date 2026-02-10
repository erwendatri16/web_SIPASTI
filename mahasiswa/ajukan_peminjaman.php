<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Redirect jika belum login atau bukan mahasiswa
if (!isMahasiswaLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$id_mahasiswa = $_SESSION['id_mahasiswa'];
$error = '';
$success = '';

// Get daftar barang tersedia
$stmt = $pdo->query("
    SELECT * FROM inventaris 
    WHERE stok > 0 
    ORDER BY kategori, nama_barang
");
$barang_tersedia = $stmt->fetchAll();

// Proses submit peminjaman
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_peminjaman'])) {
    $selected_barang = $_POST['barang'] ?? [];
    $jumlah_barang = $_POST['jumlah'] ?? [];
    
    if (empty($selected_barang)) {
        $error = 'Pilih minimal 1 barang untuk dipinjam!';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert ke tabel peminjaman (header)
            $stmt = $pdo->prepare("
                INSERT INTO peminjaman (id_mahasiswa, status) 
                VALUES (:id_mahasiswa, 'Menunggu')
            ");
            $stmt->execute([':id_mahasiswa' => $id_mahasiswa]);
            $id_peminjaman = $pdo->lastInsertId();
            
// Insert detail peminjaman
$stmt = $pdo->prepare("
    INSERT INTO detail_peminjaman (id_peminjaman, id_barang, jumlah_pinjam) 
    VALUES (:id_peminjaman, :id_barang, :jumlah_pinjam)
");

foreach ($selected_barang as $id_barang) {
    // Ambil jumlah dari array dengan key ID barang
    $jumlah = (int)($jumlah_barang[$id_barang] ?? 1);
    
    if ($jumlah > 0) {
        $stmt->execute([
            ':id_peminjaman' => $id_peminjaman,
            ':id_barang' => $id_barang,
            ':jumlah_pinjam' => $jumlah
        ]);
    }
}
            
            $pdo->commit();
            
            $success = '✅ Pengajuan peminjaman berhasil! Tunggu approval dari admin.';
            
        } catch (Exception $e) {
            $pdo->rollBack();
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
    <title>Ajukan Peminjaman - SIPASTI</title>
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
            padding: 40px;
            margin-bottom: 30px;
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
        
        .card-item.selected {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6edff 100%);
            border-color: var(--primary-500);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .btn-add {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
        }
        
        .quantity-input {
            width: 100px;
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .quantity-input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-quantity {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-quantity:hover {
            transform: scale(1.1);
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
                    <li class="nav-item"><a class="nav-link active" href="ajukan_peminjaman.php">Ajukan Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="qr_pengembalian.php">QR Pengembalian</a></li>
                    <li class="nav-item"><a class="nav-link" href="riwayat_peminjaman.php">Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
        </a>
        
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📦 Ajukan Peminjaman</h1>
            <p class="text-muted fs-5">Pilih barang yang ingin dipinjam, maksimal 5 barang dalam 1 transaksi</p>
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

        <form method="POST" action="">
            <div class="card-form">
                <h4 class="fw-bold mb-4">
                    <i class="bi bi-list-check me-2"></i>Daftar Barang Tersedia
                </h4>
                
                <?php if (count($barang_tersedia) > 0): ?>
                    <div class="row g-3">
                        <?php foreach ($barang_tersedia as $barang): ?>
                            <div class="col-md-6">
                                <div class="card-item">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($barang['nama_barang']) ?></h5>
                                            <p class="text-muted mb-2">
                                                <span class="category-badge">
                                                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($barang['kategori']) ?>
                                                </span>
                                            </p>
                                        </div>
                                        <span class="stock-badge">
                                            <i class="bi bi-boxes me-1"></i><?= $barang['stok'] ?> Tersedia
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="condition-badge condition-<?= strtolower(str_replace(' ', '-', $barang['kondisi'])) ?>">
                                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($barang['kondisi']) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted me-2">Jumlah:</span>
                                            <div class="input-group" style="width: 120px;">
                                                <button type="button" class="btn btn-outline-secondary btn-quantity" onclick="decrement(<?= $barang['id_barang'] ?>)">
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                                <input type="number" 
                                                       class="form-control quantity-input" 
                                                       id="jumlah-<?= $barang['id_barang'] ?>" 
                                                       name="jumlah[<?= $barang['id_barang'] ?>]" 
                                                       value="1" 
                                                       min="1" 
                                                       max="<?= $barang['stok'] ?>"
                                                       onchange="validateQuantity(<?= $barang['id_barang'] ?>, <?= $barang['stok'] ?>)">
                                                <button type="button" class="btn btn-outline-secondary btn-quantity" onclick="increment(<?= $barang['id_barang'] ?>)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="barang-<?= $barang['id_barang'] ?>" 
                                               name="barang[]" 
                                               value="<?= $barang['id_barang'] ?>"
                                               onchange="toggleCard(<?= $barang['id_barang'] ?>)">
                                        <label class="form-check-label" for="barang-<?= $barang['id_barang'] ?>">
                                            <strong>Pilih barang ini</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                        <h4 class="fw-bold mt-3">Tidak Ada Barang Tersedia</h4>
                        <p class="text-muted">Semua barang sedang dipinjam atau stok habis. Silakan cek kembali nanti.</p>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" name="ajukan_peminjaman" class="btn btn-add">
                <i class="bi bi-check-circle-fill me-2"></i>Ajukan Peminjaman
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleCard(id) {
        const card = document.querySelector(`#barang-${id}`).closest('.card-item');
        const checkbox = document.querySelector(`#barang-${id}`);
        
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
    
    function increment(id) {
        const input = document.getElementById(`jumlah-${id}`);
        const currentValue = parseInt(input.value);
        const maxValue = parseInt(input.max);
        
        if (currentValue < maxValue) {
            input.value = currentValue + 1;
        }
    }
    
    function decrement(id) {
        const input = document.getElementById(`jumlah-${id}`);
        const currentValue = parseInt(input.value);
        const minValue = parseInt(input.min);
        
        if (currentValue > minValue) {
            input.value = currentValue - 1;
        }
    }
    
    function validateQuantity(id, maxStock) {
        const input = document.getElementById(`jumlah-${id}`);
        let value = parseInt(input.value);
        
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > maxStock) {
            input.value = maxStock;
            alert(`Jumlah maksimal adalah ${maxStock}!`);
        }
    }
    
    // Limit maksimal 5 barang yang dipilih
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.form-check-input:checked');
            
            if (checkedBoxes.length > 5 && this.checked) {
                this.checked = false;
                alert('Maksimal 5 barang dalam 1 transaksi!');
                return;
            }
        });
    });
    </script>
</body>
</html>