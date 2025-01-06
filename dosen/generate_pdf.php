<?php
require '../tcpdf-main/tcpdf.php';
require '../conn.php';
$conn = getDbConnection();

$action = $_GET['action'] ?? 'view';

// Inisialisasi TCPDF
class CustomPDF extends TCPDF
{
    public function Header()
    {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 128);
        $this->Cell(0, 10, 'Laporan Data Dosen', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5); // Spasi tambahan
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new CustomPDF();
$pdf->AddPage();
$pdf->SetMargins(15, 20, 15);

// Judul Laporan
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 128);
$pdf->Cell(0, 10, 'Daftar Dosen', 0, 1, 'C');
$pdf->Ln(5);

// Membuat Tabel
$pdf->SetFont('helvetica', '', 10);

// Warna Header Tabel
$tbl_header = <<<EOD
<style>
    table {
        border-collapse: collapse;
        width: 100%; /* Sesuaikan dengan lebar area margin */
        margin: auto; /* Buat tabel di tengah */
    }
    th {
        background-color: #4CAF50;
        color: white;
        text-align: center;
        font-weight: bold;
        padding: 10px;
    }
    td {
        text-align: center;
        padding: 8px;
        border: 1px solid #ddd;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #ddd;
    }
</style>
<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <th width="10%">No</th>
        <th width="35%">NPP</th>
        <th width="40%">Nama Dosen</th>
        <th width="15%">Homebase</th>
    </tr>
EOD;

// Mengambil Data dari Database
$tbl_content = '';
$result = $conn->query("
    SELECT * FROM dosen
");

$no = 1;
while ($row = $result->fetch_assoc()) {
    $tbl_content .= <<<EOD
    <tr>
        <td>{$no}</td>
        <td>{$row['npp']}</td>
        <td>{$row['namadosen']}</td>
        <td>{$row['homebase']}</td>
    </tr>
EOD;
    $no++;
}

$tbl_footer = "</table>";

// Gabungkan Semua Bagian Tabel
$html = $tbl_header . $tbl_content . $tbl_footer;

// Cetak Tabel ke PDF
$pdf->writeHTML($html, true, false, true, false, 'C');

// Output PDF berdasarkan pilihan user
if ($action === 'download') {
    // Download the PDF
    $pdf->Output('Laporan_Data_Matkul.pdf', 'D');
} else {
    // View the PDF in the browser
    $pdf->Output('Laporan_Data_Matkul.pdf', 'I');
}