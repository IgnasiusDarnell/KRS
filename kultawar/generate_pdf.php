<?php
require '../tcpdf-main/tcpdf.php';
require '../conn.php';
$conn = getDbConnection();

// Inisialisasi TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetMargins(15, 20, 15);

// Tambahkan Header
class CustomPDF extends TCPDF
{
    public function Header()
    {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 128);
        $this->Cell(0, 10, 'Laporan Data Kuliah Tawar', 0, 1, 'C');
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

// Judul Laporan
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 128);
$pdf->Cell(0, 10, 'Daftar Kuliah Tawar', 0, 1, 'C');
$pdf->Ln(5);

// Membuat Tabel
$pdf->SetFont('helvetica', '', 10);

// Warna Header Tabel
$tbl_header = <<<EOD
<style>
    table {
        border: 1px solid #000;
    }
    th {
        background-color: #4CAF50;
        color: white;
        text-align: center;
        font-weight: bold;
    }
    td {
        text-align: center;
    }
</style>
<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <th width="10%">No</th>
        <th width="15%">Kelompok</th>
        <th width="15%">Hari</th>
        <th width="20%">Jam Kuliah</th>
        <th width="15%">Ruang</th>
        <th width="25%">Mata Kuliah</th>
    </tr>
EOD;

// Mengambil Data dari Database
$tbl_content = '';
$result = $conn->query("
    SELECT k.*, m.namamatkul 
    FROM kultawar k 
    JOIN matkul m ON k.idmatkul = m.idmatkul
");

$no = 1;
while ($row = $result->fetch_assoc()) {
    $tbl_content .= <<<EOD
    <tr>
        <td>{$no}</td>
        <td>{$row['klp']}</td>
        <td>{$row['hari']}</td>
        <td>{$row['jamkul']}</td>
        <td>{$row['ruang']}</td>
        <td>{$row['namamatkul']}</td>
    </tr>
EOD;
    $no++;
}

$tbl_footer = "</table>";

// Gabungkan Semua Bagian Tabel
$html = $tbl_header . $tbl_content . $tbl_footer;

// Cetak Tabel ke PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Laporan_Kuliah_Tawar.pdf', 'D');
