<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Cek apakah admin sudah login
if (!isAdminLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// ============================================
// PROCESS PENGEMBALIAN (AJAX POST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'process_pengembalian') {
    header('Content-Type: application/json');
    
    $id_peminjaman = $_POST['id_peminjaman'] ?? null;
    $qr_token = $_POST['qr_token'] ?? null;
    
    if (!$id_peminjaman || !$qr_token) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap!'
        ]);
        exit;
    }
    
    try {
        // Cek peminjaman di database
        $stmt = $pdo->prepare("
            SELECT p.*, m.nama_lengkap, m.nim
            FROM peminjaman p
            JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
            WHERE p.id_peminjaman = :id 
            AND p.qr_token = :token
        ");
        $stmt->execute([
            ':id' => $id_peminjaman,
            ':token' => $qr_token
        ]);
        $peminjaman = $stmt->fetch();
        
        if (!$peminjaman) {
            echo json_encode([
                'success' => false,
                'message' => 'QR Code tidak valid atau tidak ditemukan!'
            ]);
            exit;
        }
        
        // Cek apakah sudah pernah discan
        if ($peminjaman['qr_scanned_at'] !== null) {
            echo json_encode([
                'success' => false,
                'message' => 'QR Code ini sudah pernah digunakan! Pengembalian sudah dikonfirmasi.'
            ]);
            exit;
        }
        
        // Cek status harus "Aktif"
        if ($peminjaman['status'] !== 'Aktif') {
            echo json_encode([
                'success' => false,
                'message' => 'Peminjaman tidak dalam status Aktif!'
            ]);
            exit;
        }
        
        // Update status & tambah stok
        $pdo->beginTransaction();
        
        // Update peminjaman
        $stmt = $pdo->prepare("
            UPDATE peminjaman 
            SET status = 'Selesai',
                tanggal_dikembalikan = NOW(),
                qr_scanned_at = NOW()
            WHERE id_peminjaman = :id
        ");
        $stmt->execute([':id' => $id_peminjaman]);
        
        // Tambah stok barang
        $stmt = $pdo->prepare("
            UPDATE inventaris i
            INNER JOIN detail_peminjaman d ON i.id_barang = d.id_barang
            SET i.stok = i.stok + d.jumlah_pinjam
            WHERE d.id_peminjaman = :id_peminjaman
        ");
        $stmt->execute([':id_peminjaman' => $id_peminjaman]);
        
        // Ambil detail barang untuk notifikasi
        $stmt = $pdo->prepare("
            SELECT i.nama_barang, d.jumlah_pinjam
            FROM detail_peminjaman d
            JOIN inventaris i ON d.id_barang = i.id_barang
            WHERE d.id_peminjaman = :id
        ");
        $stmt->execute([':id' => $id_peminjaman]);
        $detail_barang = $stmt->fetchAll();
        
        $pdo->commit();
        
        // Format detail barang
        $detail_text = '';
        foreach ($detail_barang as $barang) {
            $detail_text .= "• {$barang['nama_barang']} ({$barang['jumlah_pinjam']})<br>";
        }
        
        echo json_encode([
            'success' => true,
            'message' => '✅ Pengembalian berhasil dikonfirmasi!',
            'details' => "
                <strong>ID Peminjaman:</strong> #{$peminjaman['id_peminjaman']}<br>
                <strong>Mahasiswa:</strong> {$peminjaman['nama_lengkap']} ({$peminjaman['nim']})<br>
                <strong>Tanggal:</strong> " . date('d M Y, H:i') . "<br><br>
                <strong>Barang yang dikembalikan:</strong><br>
                {$detail_text}
            "
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ]);
    }
    
    exit; // Penting! Stop execution agar tidak lanjut ke HTML
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Pengembalian - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
            --success: #10b981;
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
        
        .scanner-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            padding: 50px 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        #qr-reader__dashboard_section_swaplink {
            display: none !important;
        }
        
        #qr-reader {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }
        
        .result-box {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            margin-top: 30px;
            display: none;
            border-left: 5px solid #cbd5e1;
        }
        
        .result-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-left-color: var(--success);
        }
        
        .result-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left-color: #ef4444;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .spinner-border {
            width: 4rem;
            height: 4rem;
        }
        
        .status-indicator {
            text-align: center;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        
        .status-ready {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .status-scanning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .status-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #065f46;
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventaris/index.php">Inventaris</a></li>
                    <li class="nav-item"><a class="nav-link" href="peminjaman/index.php">Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link active" href="scan_pengembalian.php">Scan QR</a></li>
                    <li class="nav-item"><a class="nav-link" href="laporan/index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📷 Scan QR Pengembalian</h1>
            <p class="text-muted fs-5">Arahkan kamera ke QR Code mahasiswa untuk konfirmasi pengembalian</p>
        </div>

        <div class="scanner-container">
            <div class="status-indicator status-ready" id="statusIndicator">
                <i class="bi bi-camera-video-fill me-2" style="font-size: 1.5rem;"></i>
                <strong>Kamera Siap</strong> - Tunjukkan QR Code ke kamera
            </div>
            
            <div id="qr-reader"></div>
            
            <div class="loading" id="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-4 fw-bold text-muted">Memproses pengembalian...</p>
            </div>
            
            <div class="result-box" id="resultBox">
                <h5 id="resultTitle" class="fw-bold mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>Berhasil!
                </h5>
                <div id="resultMessage"></div>
                
                <button class="btn btn-primary mt-4" onclick="resetScanner()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Scan Lagi
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
    const resultBox = document.getElementById('resultBox');
    const resultTitle = document.getElementById('resultTitle');
    const resultMessage = document.getElementById('resultMessage');
    const loading = document.getElementById('loading');
    const statusIndicator = document.getElementById('statusIndicator');

    function onScanSuccess(decodedText, decodedResult) {
        console.log(`QR Code terdeteksi: ${decodedText}`);
        
        // Hentikan scanner
        html5QrcodeScanner.clear();
        
        // Update status
        statusIndicator.className = 'status-indicator status-scanning';
        statusIndicator.innerHTML = '<i class="bi bi-hourglass-split me-2"></i><strong>Memproses...</strong> Validasi QR Code';
        
        // Tampilkan loading
        loading.style.display = 'block';
        
        // Parse QR Code
        const parts = decodedText.split('|');
        
        if (parts.length !== 3 || parts[0] !== 'SIPASTI') {
            showError('❌ Format QR Code tidak valid!');
            return;
        }
        
        const idPeminjaman = parts[1];
        const qrToken = parts[2];
        
        // Kirim ke server untuk validasi (ke file yang sama)
        fetch('scan_pengembalian.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=process_pengembalian&id_peminjaman=${idPeminjaman}&qr_token=${encodeURIComponent(qrToken)}`
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success) {
                showSuccess(data.message, data.details);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            showError('❌ Terjadi kesalahan: ' + error.message);
        });
    }

    function onScanError(errorMessage) {
        // Skip error logging untuk cleaner console
    }

    // Inisialisasi QR Scanner
    const html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader", 
        { 
            fps: 10,
            qrbox: { width: 300, height: 300 },
            aspectRatio: 1,
            disableFlip: false,
            supportedScanTypes: [
                Html5QrcodeScanType.SCAN_TYPE_CAMERA
            ]
        },
        false
    );
    
    html5QrcodeScanner.render(onScanSuccess, onScanError);

    function showSuccess(message, details) {
        resultBox.style.display = 'block';
        resultBox.className = 'result-box result-success';
        resultTitle.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Berhasil!';
        resultMessage.innerHTML = `<div class="alert alert-success mb-0">${message}</div><div class="mt-3">${details}</div>`;
        
        statusIndicator.className = 'status-indicator status-success';
        statusIndicator.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i><strong>Berhasil!</strong> Pengembalian telah dikonfirmasi';
    }

    function showError(message) {
        resultBox.style.display = 'block';
        resultBox.className = 'result-box result-error';
        resultTitle.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal!';
        resultMessage.innerHTML = `<div class="alert alert-danger mb-0">${message}</div>`;
        
        statusIndicator.className = 'status-indicator status-error';
        statusIndicator.innerHTML = '<i class="bi bi-x-circle-fill me-2"></i><strong>Gagal!</strong> Silakan coba lagi';
        
        // Auto reset setelah 3 detik
        setTimeout(() => {
            resetScanner();
        }, 3000);
    }
    
    function resetScanner() {
        // Reload halaman untuk reset scanner
        location.reload();
    }
    </script>
</body>
</html>