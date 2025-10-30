<?php
session_start();
include '../config/database.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses
$jabatan = $_SESSION['jabatan'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'] ?? '';

// Query untuk mengambil data Pengambilan Dana Kotak Amal
$sql = "SELECT dka.ID_Kwitansi_KA, dka.Tgl_Ambil, ka.Nama_Toko, dka.JmlUang, u.Nama_User AS Petugas_Pengambil, dka.ID_KotakAmal, dka.Id_lksa
        FROM Dana_KotakAmal dka
        LEFT JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
        LEFT JOIN User u ON dka.Id_user = u.Id_user";
        
if ($jabatan != 'Pimpinan') {
    // Batasi data hanya pada LKSA yang bersangkutan
    $sql .= " WHERE dka.Id_lksa = '$id_lksa'";
}
$result = $conn->query($sql);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

// 1. Set headers untuk download file CSV
$filename = "Data_Pengambilan_Dana_KotakAmal_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Buka output stream
$output = fopen('php://output', 'w');

// 3. Tulis header kolom
$headers = [
    'ID Kwitansi KA', 
    'Tanggal Ambil', 
    'Nama Toko', 
    'Jumlah Uang (Rp)', 
    'Petugas Pengambil', 
    'ID Kotak Amal',
    'ID LKSA'
];
fputcsv($output, $headers, ';');

// INISIALISASI TOTAL
$total_jml_uang = 0;

// 4. Tulis data baris dan akumulasi total
while ($row = $result->fetch_assoc()) {
    $jml_uang = $row['JmlUang'] ?? 0;
    
    // AKUMULASI TOTAL
    $total_jml_uang += $jml_uang;
    
    fputcsv($output, $row, ';');
}

// 5. Tulis baris total (SUM per kolom)
$total_row = [
    'TOTAL', // Placeholder kolom 1
    '', // Placeholder kolom 2
    '', // Placeholder kolom 3
    $total_jml_uang, // Total di kolom 4
    '', // Placeholder kolom 5
    '', // Placeholder kolom 6
    '' // Placeholder kolom 7
];
fputcsv($output, $total_row, ';');

// 6. Tutup stream dan keluar
fclose($output);
$conn->close();
exit;
?>