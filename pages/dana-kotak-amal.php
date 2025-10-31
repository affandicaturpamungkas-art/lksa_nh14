<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

// --- Helper functions for formatting ---
// MODIFIKASI: Mengembalikan hanya Bulan dan Tahun dalam Huruf
function format_tanggal_indo($date_string) {
    if (!$date_string) return '-';
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_string)) {
        $bulan_indonesia = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $parts = explode('-', $date_string);
        $month = $bulan_indonesia[$parts[1]];
        $year = $parts[0];
        // Mengembalikan Nama Bulan dan Tahun saja
        return $month . ' ' . $year;
    }
    return $date_string;
}
// ----------------------------------------------------

// Ambil input pencarian (untuk pencarian teks bebas)
$search_query = $_GET['search'] ?? '';
// NEW: Ambil filter bulan dari dropdown
$filter_month = $_GET['filter_month'] ?? ''; 
$search_param = "%" . $search_query . "%";

// LOGIKA MAPPING BULAN UNTUK DROPDOWN
$bulan_map = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// LOGIKA MAPPING BULAN UNTUK PENCARIAN (untuk mencari dari query teks, jika tidak ada filter_month)
$found_month_number = null;
$lower_query = strtolower($search_query);

foreach ($bulan_map as $number => $name) {
    if (strpos($lower_query, strtolower($name)) !== false) {
        $found_month_number = $number;
        break;
    }
}
// END LOGIKA MAPPING

// Daftar kolom yang diizinkan untuk pencarian
$allowed_columns = ['ID_KotakAmal', 'Nama_Toko', 'Alamat_Toko', 'Nama_Pemilik', 'Jadwal_Pengambilan'];
$column_labels = [
    'All' => 'Semua Kolom',
    'ID_KotakAmal' => 'ID Kotak Amal',
    'Nama_Toko' => 'Nama Tempat',
    'Alamat_Toko' => 'Alamat Lengkap',
    'Nama_Pemilik' => 'Nama Pemilik',
    'Jadwal_Pengambilan' => 'Jadwal Ambil'
];

// Query untuk mengambil data Kotak Amal AKTIF
$sql = "SELECT ka.ID_KotakAmal, ka.Nama_Toko, ka.Alamat_Toko, ka.Nama_Pemilik, ka.WA_Pemilik, ka.Jadwal_Pengambilan, ka.ID_LKSA, 
                MAX(dka.Tgl_Ambil) AS Tgl_Terakhir_Ambil
        FROM KotakAmal ka
        LEFT JOIN Dana_KotakAmal dka ON ka.ID_KotakAmal = dka.ID_KotakAmal
        WHERE ka.Status = 'Active'";
        
$params = [];
$types = "";

// 1. Cek Filter Bulan/Teks
if (!empty($filter_month)) {
    // Filter berdasarkan bulan yang dipilih dari dropdown
    $sql .= " AND MONTH(ka.Jadwal_Pengambilan) = ?";
    $params[] = $filter_month;
    $types .= "s";
}

// 2. Cek Pencarian Teks (Sekunder/Pelengkap)
if (!empty($search_query)) {
    // Jika filter bulan tidak digunakan, gunakan query teks sebagai filter utama
    if (empty($filter_month) && $found_month_number) {
        // Jika user mengetik nama bulan di search box, filter by month number
        $sql .= " AND MONTH(ka.Jadwal_Pengambilan) = ?";
        $params[] = $found_month_number;
        $types .= "s";
    } elseif (empty($filter_month)) {
        // Jika tidak ada filter bulan, gunakan teks sebagai filter umum (termasuk tahun)
        $sql .= " AND (ka.ID_KotakAmal LIKE ? OR ka.Nama_Toko LIKE ? OR ka.Alamat_Toko LIKE ? OR ka.Nama_Pemilik LIKE ? OR ka.Jadwal_Pengambilan LIKE ?)";
        $search_param_like = "%" . $search_query . "%";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $search_param_like;
            $types .= "s";
        }
    }
}

// 3. Cek Filter LKSA
if ($_SESSION['jabatan'] != 'Pimpinan') {
    $sql .= " AND ka.ID_LKSA = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

$sql .= " GROUP BY ka.ID_KotakAmal ORDER BY ka.Nama_Toko ASC";

// Eksekusi Kueri Kotak Amal
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result_ka = $stmt->get_result();


// --- Query untuk Riwayat Pengambilan ---
$sql_history = "SELECT dka.*, ka.Nama_Toko, u.Nama_User
                FROM Dana_KotakAmal dka
                LEFT JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
                LEFT JOIN User u ON dka.Id_user = u.Id_user";
                
$params_hist = [];
$types_hist = "";
                
if ($_SESSION['jabatan'] != 'Pimpinan') {
    $sql_history .= " WHERE dka.Id_lksa = ?";
    $params_hist[] = $id_lksa;
    $types_hist = "s";
}
$sql_history .= " ORDER BY dka.Tgl_Ambil DESC LIMIT 10"; // Limit history

$stmt_history = $conn->prepare($sql_history);

if (!empty($params_hist)) {
    $stmt_history->bind_param($types_hist, ...$params_hist);
}

$stmt_history->execute();
$result_history = $stmt_history->get_result();

?>
<style>
    :root {
        --primary-color: #1F2937; 
        --secondary-color: #06B6D4; 
        --accent-kotak-amal: #F97316; /* Orange */
        --success-color: #10B981; 
        --danger-color: #EF4444; 
        --border-color: #E5E7EB;
        --bg-light: #F9FAFB;
        --cancel-color: #6B7280; /* Neutral Gray */
    }
    
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .btn-lokasi {
        background-color: var(--secondary-color);
        color: white;
        padding: 6px 12px;
        font-size: 0.8em;
        font-weight: 700;
        text-decoration: none;
        border-radius: 5px;
    }
    .btn-lokasi:hover {
        background-color: #0594a9;
    }

    /* --- SIMPLIFIED SEARCH BAR (Fokus pada Bulan) --- */
    .search-control-group-simple {
        display: flex;
        align-items: stretch;
        gap: 0; 
        margin-bottom: 25px;
        max-width: 600px; /* Batasi lebar form */
        border-radius: 12px; 
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    /* WRAPPER BULAN (Tombol Filter) */
    .month-filter-wrapper { 
        position: relative;
        flex-shrink: 0;
        width: 45px; 
        height: 44px; 
        border-right: 1px solid var(--border-color);
        background-color: #F8F9FA;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    /* Ikon Filter */
    .filter-icon {
        position: static; 
        z-index: 5; 
        font-size: 1.1em;
        color: var(--accent-kotak-amal);
    }
    /* Dropdown Bulan (Overlay Transparan) */
    .month-select-simple {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0; 
        z-index: 15; 
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    
    /* Input Pencarian */
    .search-input-simple {
        padding: 12px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        flex-grow: 1;
        min-width: 200px;
        border-right: 1px solid var(--border-color);
    }
    
    /* Tombol Cari */
    .btn-search-simple {
        background-color: var(--accent-kotak-amal);
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 0;
        font-weight: 700;
        line-height: 1.5;
        height: 100%;
        display: flex;
        align-items: center;
    }
    
    /* Tombol Reset (Icon Only) - DIPERBAIKI */
    .btn-reset-simple {
        background-color: var(--cancel-color); /* Neutral Gray */
        color: white;
        width: 40px; 
        padding: 0;
        border: none;
        border-radius: 8px; /* Sudut halus */
        height: 44px; /* Tinggi disesuaikan dengan input */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        margin-left: 10px; /* Jarak visual dari search bar */
    }
    .btn-reset-simple:hover { 
        background-color: #5A626A; /* Warna lebih gelap saat hover */
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15); /* Efek terangkat */
    }
    .btn-reset-simple i {
        font-size: 1.1em;
        margin: 0;
    }
    .btn-reset-simple span {
        display: none; 
    }
    
    /* TABLE STYLES SPECIFIC FOR THIS PAGE */
    .table-container {
        overflow-x: auto; 
        margin-top: 20px;
    }

    .responsive-table th {
        background-color: var(--accent-kotak-amal);
        color: white;
    }
    .responsive-table td {
        white-space: nowrap; 
    }
    .tgl-terakhir {
        font-size: 0.85em;
        color: #6B7280;
    }
    .tgl-recent {
        color: var(--success-color);
        font-weight: 600;
    }
    .status-alert {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .status-jadwal-success {
        background-color: #D1FAE5;
        color: #047857;
        border: 1px solid #10B981;
    }

</style>

<?php if (isset($_GET['status']) && $_GET['status'] == 'jadwal_success') { ?>
    <div class="status-alert status-jadwal-success">
        <i class="fas fa-check-circle"></i> Jadwal pengambilan berikutnya berhasil diperbarui!
    </div>
<?php } ?>

<h1 class="dashboard-title">Manajemen Pengambilan Kotak Amal</h1>
<p>Filter data Kotak Amal berdasarkan Jadwal Pengambilan Bulan.</p>

<form method="GET" action="" class="search-form" id="filter-form">
    <div style="display: flex; gap: 10px;">
        <div class="search-control-group-simple">
            
            <div class="month-filter-wrapper">
                <i class="fas fa-filter filter-icon"></i>
                <select name="filter_month" id="filter_month" class="month-select-simple">
                    <option value=""></option> <?php 
                    foreach ($bulan_map as $num => $name) {
                        $selected = ($num == $filter_month) ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
            </div>
            
            <input type="text" name="search" id="search_input" placeholder="Cari Tahun atau Teks Lain..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input-simple">
            
            <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i> Cari</button>
        </div>

        <?php if (!empty($search_query) || !empty($filter_month)) { ?>
            <a href="dana-kotak-amal.php" class="btn-reset-simple" title="Reset Pencarian">
                <i class="fas fa-times"></i>
                <span>Reset</span>
            </a>
        <?php } ?>
    </div>
</form>

<h2>Daftar Kotak Amal Aktif</h2>
<div class="table-container">
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID KA</th>
            <th>Nama Tempat</th>
            <th>Nama Pemilik (WA)</th>
            <th>Lokasi</th>
            <th>Jadwal Ambil</th>
            <th>Terakhir Ambil</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_ka->num_rows > 0) { ?>
            <?php while ($row = $result_ka->fetch_assoc()) { 
                $tgl_terakhir_ambil = $row['Tgl_Terakhir_Ambil'] ? date('d-m-Y', strtotime($row['Tgl_Terakhir_Ambil'])) : 'Belum Pernah';
                $is_recent = (strtotime($row['Tgl_Terakhir_Ambil'] ?? '1970-01-01') >= strtotime('-7 days'));
            ?>
                <tr>
                    <td><?php echo $row['ID_KotakAmal']; ?></td>
                    <td><?php echo $row['Nama_Toko']; ?></td>
                    <td>
                        <?php echo $row['Nama_Pemilik']; ?>
                        <span class="tgl-terakhir">(WA: <?php echo $row['WA_Pemilik'] ?? '-'; ?>)</span>
                    </td>
                    
                    <td class="location-cell">
                        <a href="detail_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn-lokasi">
                            <i class="fas fa-map-marker-alt"></i> Lihat Lokasi
                        </a>
                    </td>
                    
                    <td><?php echo format_tanggal_indo($row['Jadwal_Pengambilan']); ?></td>
                    <td>
                        <span class="tgl-terakhir <?php echo $is_recent ? 'tgl-recent' : ''; ?>">
                            <?php echo $tgl_terakhir_ambil; ?>
                        </span>
                    </td>
                    <td>
                        <a href="catat_pengambilan_ka.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-success btn-action-icon" title="Catat Pengambilan">
                            <i class="fas fa-money-bill-wave"></i> Catat Pengambilan
                        </a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="7" class="no-data">Tidak ada Kotak Amal aktif yang ditemukan.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<h2 style="margin-top: 40px;">Riwayat 10 Pengambilan Terakhir</h2>
<div class="table-container">
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Nama Toko</th>
            <th>Jumlah Uang</th>
            <th>Tanggal Ambil</th>
            <th>Petugas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_history->num_rows > 0) { ?>
            <?php while ($row_hist = $result_history->fetch_assoc()) { ?>
                <tr>
                    <td data-label="ID Kwitansi"><?php echo $row_hist['ID_Kwitansi_KA']; ?></td>
                    <td data-label="Nama Toko"><?php echo $row_hist['Nama_Toko']; ?></td>
                    <td data-label="Jumlah Uang">Rp <?php echo number_format($row_hist['JmlUang']); ?></td>
                    <td data-label="Tanggal Ambil"><?php echo $row_hist['Tgl_Ambil']; ?></td>
                    <td data-label="Petugas"><?php echo $row_hist['Nama_User']; ?></td>
                    <td data-label="Aksi">
                        <a href="edit_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-primary btn-action-icon" style="background-color: #6B7280;" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="hapus_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-danger btn-action-icon" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data pengambilan ini?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="6" class="no-data">Belum ada riwayat pengambilan dana kotak amal yang tercatat.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.getElementById('filter_month');
        const searchInput = document.getElementById('search_input');
        const filterForm = document.getElementById('filter-form');
        
        // Cek apakah halaman dimuat karena filter bulan sudah aktif
        const initialMonth = monthSelect.value;
        if (initialMonth !== "" && searchInput.value === "") {
             // Jika filter bulan aktif tapi search input kosong, isi search input dengan nama bulan
             const selectedText = monthSelect.options[monthSelect.selectedIndex].text;
             searchInput.value = selectedText;
        }

        monthSelect.addEventListener('change', function() {
            const selectedValue = this.value; // '01', '02', ''
            const selectedText = this.options[this.selectedIndex].text; // 'Januari', 'Semua Bulan'
            
            if (selectedValue !== "") {
                // Jika bulan dipilih, isi input dengan nama bulan
                searchInput.value = selectedText;
            } else {
                // Jika "Semua Bulan" dipilih, bersihkan input pencarian
                searchInput.value = '';
            }
            
            // Otomatis submit form
            filterForm.submit();
        });
    });
</script>

<?php
$stmt->close(); 
$stmt_history->close(); 
include '../includes/footer.php';
$conn->close();
?>