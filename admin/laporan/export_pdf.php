<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';

// Query untuk laporan
$query = "
    SELECT p.*, m.nama_lengkap, m.nim, m.prodi,
           GROUP_CONCAT(CONCAT(i.nama_barang, ' (', d.jumlah_pinjam, ')') SEPARATOR ', ') AS detail_barang
    FROM peminjaman p
    JOIN mahasiswa m ON p.id_mahasiswa = m.id_mahasiswa
    JOIN detail_peminjaman d ON p.id_peminjaman = d.id_peminjaman
    JOIN inventaris i ON d.id_barang = i.id_barang
    WHERE DATE(p.tanggal_pinjam) BETWEEN :tanggal_awal AND :tanggal_akhir
";

if ($status_filter !== 'all') {
    $query .= " AND p.status = :status";
}

$query .= " GROUP BY p.id_peminjaman ORDER BY p.tanggal_pinjam DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':tanggal_awal' => $tanggal_awal,
    ':tanggal_akhir' => $tanggal_akhir
]);

if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter);
}

$laporan = $stmt->fetchAll();

// Hitung statistik
$total_peminjaman = count($laporan);
$total_disetujui = 0;
$total_ditolak = 0;
$total_selesai = 0;

foreach ($laporan as $p) {
    if ($p['status'] === 'Aktif') $total_disetujui++;
    if ($p['status'] === 'Ditolak') $total_ditolak++;
    if ($p['status'] === 'Selesai') $total_selesai++;
}

// Load FPDF
require_once '../../libraries/FPDF/fpdf.php';

class PDF extends FPDF
{
    // Header
    function Header()
    {
        // Logo
        $this->Image('../../assets/images/logo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'SIPASTI - Sistem Peminjaman Sarana Terpadu', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Universitas Sari Mulia', 0, 1, 'C');
        $this->Ln(10);
    }

    // Footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Tabel header
    function FancyTable($header)
    {
        // Colors, line width and bold font
        $this->SetFillColor(102, 126, 234);
        $this->SetTextColor(255);
        $this->SetDrawColor(102, 126, 234);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // Header
        $w = array(15, 30, 40, 30, 50, 25);
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
    }
}

// Create PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Judul Laporan
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN PEMINJAMAN INVENTARIS', 0, 1, 'C');
$pdf->Ln(5);

// Periode
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Periode: ' . date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)), 0, 1, 'C');
$pdf->Ln(10);

// Statistik
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, 'RINGKASAN STATISTIK', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, 'Total Peminjaman:', 0, 0);
$pdf->Cell(20, 6, ': ' . $total_peminjaman, 0, 1);
$pdf->Cell(50, 6, 'Disetujui:', 0, 0);
$pdf->Cell(20, 6, ': ' . $total_disetujui, 0, 1);
$pdf->Cell(50, 6, 'Ditolak:', 0, 0);
$pdf->Cell(20, 6, ': ' . $total_ditolak, 0, 1);
$pdf->Cell(50, 6, 'Selesai:', 0, 0);
$pdf->Cell(20, 6, ': ' . $total_selesai, 0, 1);
$pdf->Ln(10);

// Tabel Data
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, 'DETAIL PEMINJAMAN', 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 9);
$header = array('ID', 'Tanggal', 'Mahasiswa', 'Prodi', 'Barang', 'Status');
$pdf->FancyTable($header);

$pdf->SetFont('Arial', '', 8);
$fill = false;
foreach ($laporan as $p) {
    $pdf->Cell(15, 6, '#' . $p['id_peminjaman'], 'LR', 0, 'C', $fill);
    $pdf->Cell(30, 6, date('d/m/Y', strtotime($p['tanggal_pinjam'])), 'LR', 0, 'C', $fill);
    $pdf->Cell(40, 6, substr($p['nama_lengkap'], 0, 25), 'LR', 0, 'L', $fill);
    $pdf->Cell(30, 6, substr($p['prodi'], 0, 20), 'LR', 0, 'L', $fill);
    $pdf->Cell(50, 6, substr($p['detail_barang'], 0, 35), 'LR', 0, 'L', $fill);
    $pdf->Cell(25, 6, $p['status'], 'LR', 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}
$pdf->Cell(190, 0, '', 'T');

// Output PDF
$pdf->Output('D', 'Laporan_Peminjaman_' . date('Y-m-d') . '.pdf');
?>