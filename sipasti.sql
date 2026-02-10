-- Database: websipasti
CREATE DATABASE IF NOT EXISTS sipasti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE websipasti;

-- Tabel Mahasiswa
CREATE TABLE mahasiswa (
    id_mahasiswa INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(15) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    prodi VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Admin
CREATE TABLE admin (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nama_admin VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default admin (password: admin123)
INSERT INTO admin (nama_admin, email, password) VALUES 
('Administrator', 'admin@sipasti.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Tabel Inventaris
CREATE TABLE inventaris (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(20) UNIQUE NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    kategori ENUM('Elektronik', 'Furniture', 'Jaringan', 'Aksesoris', 'Listrik') NOT NULL,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert sample data inventaris
INSERT INTO inventaris (kode_barang, nama_barang, kategori, kondisi, stok) VALUES
('BRG-001', 'Proyektor Epson', 'Elektronik', 'Baik', 5),
('BRG-002', 'Speaker Aktif', 'Elektronik', 'Baik', 8),
('BRG-003', 'Mikrofon Wireless', 'Elektronik', 'Baik', 10),
('BRG-004', 'Terminal Listrik', 'Listrik', 'Baik', 15),
('BRG-005', 'Meja Rapat', 'Furniture', 'Baik', 6),
('BRG-006', 'Kabel HDMI 5m', 'Aksesoris', 'Baik', 20),
('BRG-007', 'Switch 24 Port', 'Jaringan', 'Baik', 3),
('BRG-008', 'Laptop Dell', 'Elektronik', 'Baik', 12);

-- Tabel Peminjaman (Header)
CREATE TABLE peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_mahasiswa INT NOT NULL,
    qr_token VARCHAR(100) UNIQUE,
    qr_generated_at TIMESTAMP NULL,
    qr_scanned_at TIMESTAMP NULL,
    status ENUM('Menunggu', 'Aktif', 'Selesai', 'Ditolak') DEFAULT 'Menunggu',
    tanggal_pinjam TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_disetujui TIMESTAMP NULL,
    tanggal_dikembalikan TIMESTAMP NULL,
    catatan_admin TEXT,
    FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa(id_mahasiswa) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Detail Peminjaman
CREATE TABLE detail_peminjaman (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    id_barang INT NOT NULL,
    jumlah_pinjam INT NOT NULL,
    jumlah_kembali INT DEFAULT 0,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES inventaris(id_barang) ON DELETE RESTRICT
) ENGINE=InnoDB;