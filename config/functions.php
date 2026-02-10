<?php
// config/functions.php

// Format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Format tanggal
function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Get total barang
function getTotalBarang($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventaris");
    return $stmt->fetch()['total'];
}

// Get total peminjaman
function getTotalPeminjaman($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman");
    return $stmt->fetch()['total'];
}

// Get total peminjaman menunggu
function getTotalMenunggu($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Menunggu'");
    return $stmt->fetch()['total'];
}

// Get total peminjaman aktif
function getTotalAktif($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Aktif'");
    return $stmt->fetch()['total'];
}

// Get total peminjaman selesai
function getTotalSelesai($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Selesai'");
    return $stmt->fetch()['total'];
}

// Get total stok barang
function getTotalStok($pdo) {
    $stmt = $pdo->query("SELECT SUM(stok) as total FROM inventaris");
    return $stmt->fetch()['total'];
}
?>