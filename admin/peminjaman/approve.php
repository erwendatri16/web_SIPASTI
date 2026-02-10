<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Ambil ID peminjaman dari URL
$id_peminjaman = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id_peminjaman || !$action) {
    header('Location: index.php');
    exit;
}

// Cek apakah peminjaman ada
$stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id_peminjaman = :id");
$stmt->execute([':id' => $id_peminjaman]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    $_SESSION['error'] = 'Peminjaman tidak ditemukan!';
    header('Location: index.php');
    exit;
}

// Proses approval atau rejection
try {
    if ($action === 'approve') {
        // Generate QR Token unik
        $qr_token = 'SIPASTI-' . strtoupper(bin2hex(random_bytes(5))) . '-' . $id_peminjaman;
        
        // Update status & generate QR
        $stmt = $pdo->prepare("
            UPDATE peminjaman 
            SET status = 'Aktif', 
                tanggal_disetujui = NOW(),
                qr_token = :qr_token,
                qr_generated_at = NOW()
            WHERE id_peminjaman = :id
        ");
        $stmt->execute([
            ':qr_token' => $qr_token,
            ':id' => $id_peminjaman
        ]);
        
        // Kurangi stok barang
        $stmt = $pdo->prepare("
            UPDATE inventaris i
            INNER JOIN detail_peminjaman d ON i.id_barang = d.id_barang
            SET i.stok = i.stok - d.jumlah_pinjam
            WHERE d.id_peminjaman = :id_peminjaman
        ");
        $stmt->execute([':id_peminjaman' => $id_peminjaman]);
        
        $_SESSION['success'] = '✅ Peminjaman disetujui! QR Code berhasil digenerate.';
        
    } elseif ($action === 'reject') {
        // Tolak peminjaman
        $stmt = $pdo->prepare("
            UPDATE peminjaman 
            SET status = 'Ditolak'
            WHERE id_peminjaman = :id
        ");
        $stmt->execute([':id' => $id_peminjaman]);
        
        $_SESSION['success'] = '✅ Peminjaman ditolak!';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = '❌ Terjadi kesalahan: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>