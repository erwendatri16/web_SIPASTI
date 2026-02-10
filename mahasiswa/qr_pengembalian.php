<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Redirect jika belum login atau bukan mahasiswa
if (!isMahasiswaLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$id_mahasiswa = $_SESSION['id_mahasiswa'];

// Ambil peminjaman aktif yang sudah disetujui (status = Aktif) dan punya QR token
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_lengkap, m.nim,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE p.id_mahasiswa = :id 
    AND p.status = 'Aktif' 
    AND p.qr_token IS NOT NULL
    GROUP BY p.id_peminjaman
    ORDER BY p.tanggal_disetujui DESC
");
$stmt->execute([':id' => $id_mahasiswa]);
$peminjaman_aktif = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Pengembalian - SIPASTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-500: #667eea;
            --secondary: #f093fb;
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
        
        .card-qr {
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            padding: 40px 30px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #eef2f7;
        }
        
        .card-qr:hover {
            transform: translateY(-8px);
            border-color: rgba(102, 126, 234, 0.3);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
        }
        
        .card-qr::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102,126,234,0.1) 0%, transparent 70%);
            z-index: 0;
        }
        
        .qr-container {
            background: white;
            padding: 25px;
            border-radius: 20px;
            display: inline-block;
            margin: 25px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 1;
            border: 1px dashed #cbd5e1;
        }
        
        .qr-code {
            width: 280px;
            height: 280px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border-radius: 12px;
        }
        
        .status-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 25px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
            border-left: 4px solid var(--primary-500);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .info-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .btn-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            justify-content: center;
        }
        
        .btn-download {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
        }
        
        .btn-instruction {
            background: white;
            color: var(--primary-500);
            border: 2px solid var(--primary-500);
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
        }
        
        .btn-instruction:hover {
            background: #f0f4ff;
            transform: translateY(-3px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-icon {
            font-size: 6rem;
            margin-bottom: 25px;
            opacity: 0.3;
        }
        
        .glow {
            position: relative;
        }
        
        .glow::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #667eea);
            z-index: -1;
            border-radius: 24px;
            animation: rotate 6s linear infinite;
            opacity: 0.7;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .card-qr {
                padding: 30px 20px;
            }
            
            .qr-code {
                width: 240px;
                height: 240px;
            }
            
            .btn-actions {
                flex-direction: column;
            }
            
            .btn-download, .btn-instruction {
                width: 100%;
            }
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
                    <li class="nav-item"><a class="nav-link active" href="qr_pengembalian.php">QR Pengembalian</a></li>
                    <li class="nav-item"><a class="nav-link" href="riwayat_peminjaman.php">Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3">📱 QR Pengembalian</h1>
            <p class="text-muted fs-5">Tunjukkan QR Code ini kepada admin saat mengembalikan barang</p>
        </div>

        <?php if (count($peminjaman_aktif) > 0): ?>
            <div class="row justify-content-center">
                <?php foreach ($peminjaman_aktif as $p): ?>
                    <div class="col-lg-8 mb-4">
                        <div class="card-qr glow">
                            <div class="status-badge">
                                <i class="bi bi-check-circle-fill"></i> Siap Dikembalikan
                            </div>
                            
                            <h3 class="fw-bold mb-3">Peminjaman #<?= $p['id_peminjaman'] ?></h3>
                            <p class="text-muted mb-4">
                                <i class="bi bi-calendar-check me-2"></i>Disetujui: <?= date('d M Y, H:i', strtotime($p['tanggal_disetujui'])) ?>
                            </p>
                            
                            <!-- QR Code Container -->
                            <div class="qr-container">
                                <div id="qr-<?= $p['id_peminjaman'] ?>" class="qr-code"></div>
                            </div>
                            
                            <!-- Detail Barang -->
                            <div class="info-box">
                                <div class="info-row">
                                    <span class="info-label">Nama Mahasiswa</span>
                                    <span class="info-value"><?= htmlspecialchars($p['nama_lengkap']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">NIM</span>
                                    <span class="info-value"><?= htmlspecialchars($p['nim']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Barang Dipinjam</span>
                                    <span class="info-value"><?= htmlspecialchars($p['detail_barang']) ?></span>
                                </div>
                                <div class="info-row mt-3 pt-3 border-top">
                                    <span class="info-label fw-bold">Token QR</span>
                                    <span class="info-value text-primary fw-bold"><?= htmlspecialchars(substr($p['qr_token'], 0, 15)) ?>...</span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="btn-actions">
                                <button class="btn btn-download" onclick="downloadQR(<?= $p['id_peminjaman'] ?>)">
                                    <i class="bi bi-download me-2"></i>Simpan QR
                                </button>
                                <button class="btn btn-instruction" data-bs-toggle="modal" data-bs-target="#instructionModal">
                                    <i class="bi bi-question-circle me-2"></i>Cara Penggunaan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generate QR dengan JavaScript -->
                    <script>
                    (function() {
                        var qr = qrcode(4, 'M');
                        qr.addData('SIPASTI|<?= $p['id_peminjaman'] ?>|<?= $p['qr_token'] ?>');
                        qr.make();
                        document.getElementById('qr-<?= $p['id_peminjaman'] ?>').innerHTML = qr.createImgTag(6, 12);
                    })();
                    </script>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-qr-code-scan empty-icon"></i>
                <h3 class="fw-bold mb-3">Belum Ada QR Pengembalian</h3>
                <p class="fs-5 mb-4">Anda belum memiliki peminjaman yang disetujui oleh admin.</p>
                <a href="ajukan_peminjaman.php" class="btn btn-primary btn-lg px-4 py-3">
                    <i class="bi bi-plus-circle me-2"></i>Ajukan Peminjaman
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Instruction Modal -->
    <div class="modal fade" id="instructionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-question-circle me-2"></i>Cara Penggunaan QR</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <span class="fw-bold">1</span>
                        </div>
                        <div>
                            <h6 class="fw-bold">Simpan QR Code</h6>
                            <p class="text-muted mb-0">Klik tombol "Simpan QR" atau screenshot layar ini</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <span class="fw-bold">2</span>
                        </div>
                        <div>
                            <h6 class="fw-bold">Bawa Barang & QR</h6>
                            <p class="text-muted mb-0">Bawa barang yang dipinjam + tunjukkan QR ke admin</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <span class="fw-bold">3</span>
                        </div>
                        <div>
                            <h6 class="fw-bold">Admin Scan QR</h6>
                            <p class="text-muted mb-0">Admin akan memindai QR untuk konfirmasi pengembalian</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-4 mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>QR hanya berlaku 1x pakai!</strong> Setelah discan, QR tidak bisa digunakan lagi.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function downloadQR(id) {
        // Ambil elemen QR
        const qrElement = document.getElementById(`qr-${id}`);
        const qrImage = qrElement.querySelector('img');
        
        if (qrImage) {
            // Buat link download
            const link = document.createElement('a');
            link.href = qrImage.src;
            link.download = `SIPASTI_QR_Peminjaman_${id}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Toast notification
            showToast('✅ QR Code berhasil disimpan! Tunjukkan ke admin saat pengembalian.');
        } else {
            alert('QR Code tidak ditemukan. Silakan screenshot layar ini.');
        }
    }
    
    function showToast(message) {
        // Buat toast element
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-success border-0';
        toast.role = 'alert';
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        // Tampilkan toast
        const bsToast = new bootstrap.Toast(toast, {
            delay: 3000
        });
        bsToast.show();
        
        // Hapus setelah toast hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toastContainer.remove();
        });
    }
    </script>
</body>
</html>