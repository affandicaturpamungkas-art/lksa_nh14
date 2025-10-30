<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

// --- FUNGSI BARU UNTUK FORMAT TANGGAL KE INDONESIA ---
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
// ----------------------------------------------------

// Ambil input pencarian dan filter
$search_query = $_GET['search'] ?? '';
$filter_by = $_GET['filter_by'] ?? 'All'; // Default: Cari di semua kolom
$search_param = "%" . $search_query . "%";

// Daftar kolom yang diizinkan untuk pencarian (WHITELIST)
$allowed_columns = [
    'ID_KotakAmal', 'Nama_Toko', 'Alamat_Toko', 'Nama_Pemilik', 
    'ID_Provinsi', 'ID_Kabupaten', 'ID_Kecamatan', 'ID_Kelurahan',
    'Jadwal_Pengambilan' 
];
// Label untuk ditampilkan di dropdown
$column_labels = [
    'All' => 'Semua Kolom',
    'ID_KotakAmal' => 'ID Kotak Amal',
    'Nama_Toko' => 'Nama Tempat',
    'Alamat_Toko' => 'Alamat Lengkap',
    'Nama_Pemilik' => 'Nama Pemilik',
    'ID_Provinsi' => 'Provinsi',
    'ID_Kabupaten' => 'Kota/Kabupaten',
    'ID_Kecamatan' => 'Kecamatan',
    'ID_Kelurahan' => 'Kelurahan/Desa',
    'Jadwal_Pengambilan' => 'Jadwal Ambil'
];


// PERUBAHAN: Menambahkan klausa WHERE untuk pencarian dinamis
$sql = "SELECT ka.*, MAX(dka.ID_Kwitansi_KA) AS is_collected_today
        FROM KotakAmal ka
        LEFT JOIN Dana_KotakAmal dka ON ka.ID_KotakAmal = dka.ID_KotakAmal AND dka.Tgl_Ambil = CURDATE()
        WHERE ka.Status = 'Active'";
        
$params = [];
$types = "";

// 1. Cek Pencarian
if (!empty($search_query)) {
    if ($filter_by !== 'All' && in_array($filter_by, $allowed_columns)) {
        // Pencarian spesifik pada kolom yang diizinkan
        $sql .= " AND ka." . $filter_by . " LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } else {
        // Pencarian di semua kolom (Default/Fallback)
        $sql .= " AND (ka.ID_KotakAmal LIKE ? OR ka.Nama_Toko LIKE ? OR ka.Alamat_Toko LIKE ? OR ka.Nama_Pemilik LIKE ? OR ka.ID_Provinsi LIKE ? OR ka.ID_Kabupaten LIKE ? OR ka.ID_Kecamatan LIKE ? OR ka.ID_Kelurahan LIKE ? OR ka.Jadwal_Pengambilan LIKE ?)";
        
        // Bind parameter untuk 9 kolom
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
            $types .= "s";
        }
    }
}

// 2. Cek Filter LKSA
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    $sql .= " AND ka.Id_lksa = ?";
    $params[] = $id_lksa;
    $types .= "s";
}

$sql .= " GROUP BY ka.ID_KotakAmal";

// Eksekusi Kueri
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Menggunakan splat operator untuk dynamic binding
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result = $stmt->get_result();

?>
<style>
    /* Style tambahan untuk tombol ikon yang sederhana */
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
    }
    /* MENAMBAH GAYA KRITIS UNTUK SCROLL HORIZONTAL */
    .table-container {
        overflow-x: auto;
    }
    /* Memperkecil padding kolom agar lebih ringkas */
    #kotak-amal-table th, #kotak-amal-table td {
        padding: 10px 12px;
        font-size: 0.9em;
        white-space: nowrap; 
    }
    /* GAYA BARU: Mengizinkan kolom alamat pecah baris agar tampilan ringkas */
    #kotak-amal-table .alamat-col {
        white-space: normal;
        max-width: 250px; /* Lebar kolom dikurangi untuk tampilan compact */
        width: 250px; 
    }
    
    /* GAYA BARU UNTUK FORM PENCARIAN (SIMPLE & COMPACT) */
    .search-control-group-simple {
        display: flex;
        align-items: stretch; /* Membuat semua item memiliki tinggi yang sama */
        gap: 0;
        margin-bottom: 20px;
        max-width: 700px;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        overflow: hidden; /* Penting untuk menyatukan border */
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .search-select-wrapper { /* Wrapper baru untuk ikon + select */
        position: relative;
        flex-shrink: 0;
        width: 150px;
        border-right: 1px solid #E5E7EB;
        background-color: #F9FAFB;
    }
    .filter-icon { /* Ikon Filter */
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280; /* Warna abu-abu yang lebih lembut */
        font-size: 1.1em;
        pointer-events: none; /* Agar klik bisa menembus ke select */
        z-index: 2;
    }
    .search-select-simple {
        padding: 10px 15px 10px 35px; /* Tambahkan padding kiri untuk ikon */
        border: none;
        /* PERUBAHAN UKURAN FONT */
        font-size: 0.9em; 
        background-color: transparent; /* Ubah ke transparan */
        transition: background-color 0.2s;
        width: 100%;
        height: 100%;
        font-weight: 600;
        color: var(--primary-color);
        -webkit-appearance: none; /* Hilangkan default arrow di Chrome/Safari */
        -moz-appearance: none; /* Hilangkan default arrow di Firefox */
        appearance: none; /* Hilangkan default arrow di standar */
        cursor: pointer;
    }
    .search-input-simple {
        padding: 10px 15px;
        border: none;
        font-size: 1em;
        background-color: white;
        transition: background-color 0.2s;
        flex-grow: 1;
        min-width: 150px;
    }
    .btn-search-simple, .btn-reset-simple {
        background-color: var(--secondary-color); 
        color: var(--primary-color);
        padding: 10px 15px;
        border: none;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s;
        line-height: 1.5;
        border-radius: 0;
    }
    .btn-search-simple:hover { background-color: #0594a9; color: white;}
    .btn-reset-simple {
        background-color: #6B7280;
        color: white;
        border-left: 1px solid #5A626A;
    }
    .btn-reset-simple:hover { background-color: #7f8c8d; }

    /* MEDIA QUERIES */
    @media (max-width: 600px) {
        .search-control-group-simple {
            flex-direction: column;
            border-radius: 8px;
        }
        .search-select-wrapper {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid #E5E7EB;
        }
        .btn-reset-simple {
            border-left: none;
            border-top: 1px solid #5A626A;
            border-radius: 0 0 8px 8px;
        }
        .btn-search-simple {
            border-radius: 0;
        }
        .search-select-simple {
            padding-right: 15px; /* Hapus padding agar teks lebih pas di mobile */
        }
        .filter-icon {
            /* Pindahkan ikon ke kanan (opsional, untuk tata letak vertikal) */
            left: auto;
            right: 10px;
            color: var(--primary-color);
        }
    }
</style>
<h1 class="dashboard-title">Manajemen Kotak Amal</h1>
<p>Kelola data kotak amal.</p>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <a href="tambah_kotak_amal.php" class="btn btn-success">Tambah Kotak Amal</a>
        <a href="arsip_kotak_amal.php" class="btn btn-cancel" style="background-color: #F97316; margin-left: 10px;">Lihat Arsip Kotak Amal</a>
    </div>
</div>

<form method="GET" action="" class="search-form">
    <div class="search-control-group-simple">
        
        <div class="search-select-wrapper">
            <i class="fas fa-filter filter-icon"></i>
            <select name="filter_by" id="filter_by" class="search-select-simple">
                <?php 
                foreach ($column_labels as $value => $label) {
                    $selected = ($filter_by == $value) ? 'selected' : '';
                    echo "<option value=\"$value\" $selected>$label</option>";
                }
                ?>
            </select>
        </div>
        
        <input type="text" name="search" placeholder="Cari di kolom terpilih..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input-simple">
        
        <button type="submit" class="btn-search-simple" title="Cari"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_query)) { ?>
            <a href="kotak-amal.php" class="btn-reset-simple" title="Reset Pencarian"><i class="fas fa-times"></i></a>
        <?php } ?>
    </div>
</form>

<div class="table-container">
<table id="kotak-amal-table">
    <thead>
        <tr>
            <th>ID Kotak Amal</th>
            <th>Nama Tempat</th>
            <th>Alamat Lengkap</th>
            <th>Provinsi</th>
            <th>Kab/Kota</th>
            <th>Kecamatan</th>
            <th>Kel/Desa</th>
            <th>Nama Pemilik</th>
            <th>Jadwal Ambil</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['ID_KotakAmal']; ?></td>
                <td><?php echo $row['Nama_Toko']; ?></td>
                <td class="alamat-col">
                    <?php 
                        $full_address = htmlspecialchars($row['Alamat_Toko']);
                        // Cari posisi koma pertama
                        $first_comma_pos = strpos($full_address, ',');
                        
                        if ($first_comma_pos !== false) {
                            // Ambil substring sebelum koma pertama (yaitu alamat detail)
                            $detail_address = substr($full_address, 0, $first_comma_pos);
                        } else {
                            // Jika tidak ada koma, anggap seluruhnya adalah alamat detail
                            $detail_address = $full_address;
                        }
                        
                        echo $detail_address;
                    ?>
                </td>
                <td><?php echo $row['ID_Provinsi'] ?? '-'; ?></td>
                <td><?php echo $row['ID_Kabupaten'] ?? '-'; ?></td>
                <td><?php echo $row['ID_Kecamatan'] ?? '-'; ?></td>
                <td><?php echo $row['ID_Kelurahan'] ?? '-'; ?></td>
                <td><?php echo $row['Nama_Pemilik']; ?></td>
                <td><?php echo format_tanggal_indo($row['Jadwal_Pengambilan']); ?></td>
                <td>
                    <a href="detail_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-primary btn-action-icon" title="Lihat Profil & Lokasi"><i class="fas fa-eye"></i></a>
                    
                    <?php if ($row['is_collected_today']) { ?>
                        <span style="color: green; font-weight: bold; font-size: 0.9em; margin-left: 5px;">Diambil</span>
                    <?php } ?>
                    
                    <a href="proses_arsip_kotak_amal.php?id=<?php echo $row['ID_KotakAmal']; ?>" class="btn btn-danger btn-action-icon" title="Arsipkan" onclick="return confirm('Apakah Anda yakin ingin mengarsipkan Kotak Amal ini?');"><i class="fas fa-archive"></i></a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
</div>

<?php
include '../includes/footer.php';
$stmt->close();
$conn->close();
?>