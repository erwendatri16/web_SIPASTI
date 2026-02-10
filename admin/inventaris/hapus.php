<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Redirect jika belum login atau bukan admin
if (!isAdminLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Ambil ID barang dari URL
$id_barang = $_GET['id'] ?? null;

if (!$id_barang) {
    header('Location: index.php');
    exit;
}

// Cek apakah barang ada
$stmt = $pdo->prepare("SELECT * FROM inventaris WHERE id_barang = :id");
$stmt->execute([':id' => $id_barang]);
$barang = $stmt->fetch();

if (!$barang) {
    $_SESSION['error'] = 'Barang tidak ditemukan!';
    header('Location: index.php');
    exit;
}

// Cek apakah barang sedang dipinjam
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM detail_peminjaman d
    JOIN peminjaman p ON d.id_peminjaman = p.id_peminjaman
    WHERE d.id_barang = :id 
    AND p.status IN ('Menunggu', 'Aktif')
");
$stmt->execute([':id' => $id_barang]);
$check = $stmt->fetch();

if ($check['total'] > 0) {
    $_SESSION['error'] = 'Barang tidak bisa dihapus karena sedang dipinjam atau menunggu approval!';
    header('Location: index.php');
    exit;
}

// Hapus barang
try {
    $stmt = $pdo->prepare("DELETE FROM inventaris WHERE id_barang = :id");
    $stmt->execute([':id' => $id_barang]);
    
    $_SESSION['success'] = '✅ Barang berhasil dihapus!';
} catch (PDOException $e) {
    $_SESSION['error'] = '❌ Terjadi kesalahan: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>