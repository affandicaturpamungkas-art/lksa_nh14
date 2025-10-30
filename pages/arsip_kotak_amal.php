<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// --- FUNGSI UNTUK FORMAT TANGGAL KE INDONESIA (DIKEMBALIKAN) ---
function format_tanggal_indo($date_string) {
    if (!$date_string) return '-';
    // Cek apakah string adalah format tanggal YYYY-MM-DD
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_string)) {
        $bulan_indonesia = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $parts = explode('-', $date_string);
        $day = $parts[2];
        $month = $bulan_indonesia[$parts[1]];
        $year = $parts[0];
        return $day . ' ' . $month . ' ' . $year;
    }
    // Jika bukan tanggal, kembalikan string aslinya (misalnya: nama hari 'Senin')
    return $date_string;
}
// -------------------------------------------------------------------------------------

// Mengambil data yang Status = 'Archived'
$sql = "SELECT ka.*
        FROM KotakAmal ka
        WHERE ka.Status = 'Archived'";

$params = [];
$types = "";

// FIX: Hanya Pimpinan Pusat yang tidak difilter
if ($jabatan != 'Pimpinan' || $id_lksa != 'Pimpinan_Pusat') {
    $sql .= " AND ka.Id_lksa = ?";
    $params[] = $id_lksa;
    $types = "s";
}

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<style>
    /* Custom Style untuk tombol aksi kompak (PULIHKAN & HAPUS) */
    .btn-action-icon {
        padding: 6px 12px;
        margin: 0 4px;
        border-radius: 8px;
        font-size: 0.85em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-restore {
        background-color: #047857; /* Deep Emerald Green */
        color: white;
    }
    .btn-delete-permanent {
        background-color: #B91C1C; /* Deep Red */
        color: white;
    }
    .btn-action-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    /* Style untuk table container agar responsive dan rapi */
    .table-container {
        width: 100%; 
        overflow-x: auto;
        margin-top: 25px; 
    }
    
    /* GAYA UNTUK TOMBOL KEMBALI MENGAPUNG (FLOAT) */
    .float-back-btn {
        position: absolute;
        top: 35px; /* Disesuaikan agar sejajar dengan H1 */
        right: 20px;
        z-index: 10;
    }
    .float-back-btn .btn-primary {
        background-color: #6B7280; /* Gray/Cancel color */
        color: white;
        padding: 8px 15px;
        font-weight: 600;
        font-size: 0.9em;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    /* GAYA KHUSUS TABEL UNTUK KONSISTENSI DENGAN KOTAK-AMAL.PHP */
    .responsive-table th, .responsive-table td {
        padding: 12px 15px; 
        font-size: 0.9em;
        white-space: nowrap; 
    }
    .responsive-table tbody tr {
        transition: background-color 0.2s, box-shadow 0.2s;
    }
    .responsive-table tbody tr:hover {
        background-color: #F8F9FA; /* Very light background on hover */
    }
    .alamat-col {
        white-space: normal !important; 
        max-width: 250px; 
        width: 250px; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="float-back-btn">
    <a href="kotak-amal.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Kembali ke Aktif</a>
</div>

<h1 class="dashboard-title"><i class="fas fa-archive" style="color: #F97316;"></i> Arsip Kotak Amal</h1>
<div class="table-container">
    <table class="responsive-table">
        <thead>
            <tr>
                <th style="width: 12%;">ID Kotak Amal</th>
                <th style="width: 20%;">Nama Tempat</th>
                <th style="width: 15%;">Nama Pemilik</th>
                <th style="width: 25%;">Alamat Detail</th>
                <th style="width: 18%;">Wilayah (Kab/Kec/Kel)</th>
                <th style="width: 10%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                
                // Mengambil alamat detail
                $full_address = htmlspecialchars($row['Alamat_Toko'] ?? '-');
                $first_comma_pos = strpos($full_address, ',');
                $detail_address = $first_comma_pos !== false ? substr($full_address, 0, $first_comma_pos) : $full_address;
                
                $wilayah_address = htmlspecialchars($row['ID_Kelurahan'] ?? '-');
                $wilayah_address .= !empty($row['ID_Kecamatan']) ? ', ' . htmlspecialchars($row['ID_Kecamatan']) : '';
                $wilayah_address .= !empty($row['ID_Kabupaten']) ? ', ' . htmlspecialchars($row['ID_Kabupaten']) : '';
                
            ?>
                <tr class="table-row-archived">
                    <td data-label="ID Kotak Amal"><?php echo $row['ID_KotakAmal']; ?></td>
                    <td data-label="Nama Toko"><?php echo $row['Nama_Toko']; ?></td>
                    <td data-label="Nama Pemilik"><?php echo $row['Nama_Pemilik']; ?></td>
                    
                    <td data-label="Alamat Detail" class="alamat-col" title="<?php echo $detail_address; ?>">
                        <?php echo $detail_address; ?>
                    </td>
                    <td data-label="Wilayah">
                        <small style="display: block; color: #6B7280;"><?php echo $wilayah_address; ?></small>
                    </td>
                    
                    <td data-label="Aksi" style="white-space: nowrap;">
                        <a href="proses_restore_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-action-icon btn-restore" title="Pulihkan" onclick="return confirm('Apakah Anda yakin ingin memulihkan Kotak Amal ini?');">
                            <i class="fas fa-undo"></i> Pulihkan
                        </a>
                        <a href="proses_hapus_permanen_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" 
                           class="btn btn-action-icon btn-delete-permanent" 
                           title="Hapus Permanen" 
                           onclick="return confirm('PERINGATAN! Anda akan menghapus Kotak Amal ini secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?');">
                            <i class="fas fa-trash-alt"></i> Hapus
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>