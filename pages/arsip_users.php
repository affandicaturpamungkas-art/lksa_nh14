<?php
session_start();
include '../config/database.php';
// Set sidebar_stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = ''; 
include '../includes/header.php';

// Verifikasi otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses halaman ini.
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// Persiapan query SQL dasar untuk mengambil data pengguna yang DIARSIPKAN.
$sql = "SELECT * FROM User WHERE Status = 'Archived'";

$params = [];
$types = "";

// Logika untuk menyesuaikan query berdasarkan jabatan dan ID LKSA pengguna yang sedang login.
if ($jabatan == 'Pimpinan' && $id_lksa == 'Pimpinan_Pusat') {
    // Pimpinan Pusat dapat melihat semua pengguna yang diarsip.
} elseif ($jabatan == 'Pimpinan' && $id_lksa !== 'Pimpinan_Pusat') {
    // Pimpinan cabang hanya dapat melihat pengguna yang diarsip di LKSA-nya.
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " AND Id_lksa = ?";
    $params[] = $id_lksa;
    $types = "s";
} elseif ($jabatan == 'Kepala LKSA') {
    // Kepala LKSA hanya dapat melihat pengguna yang diarsip dengan jabatan di bawahnya di LKSA-nya.
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " AND Id_lksa = ? AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
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
    /* Custom Style untuk Tampilan Arsip yang Elegan */
    .table-archive {
        font-size: 0.95em;
        table-layout: fixed;
        width: 100%;
        border-collapse: separate; /* Penting untuk border-radius */
        border-spacing: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); /* Shadow lebih halus dan dalam */
        border-radius: 10px;
        overflow: hidden;
    }
    .table-archive th, .table-archive td {
        vertical-align: middle;
        padding: 14px 20px; /* Padding ditingkatkan */
        text-align: left;
        border-bottom: 1px solid #F3F4F6; /* Border sangat tipis */
    }
    .table-archive thead th {
        background-color: var(--primary-color); /* Dark Navy */
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .table-archive tbody tr:hover {
        background-color: #F9FAFB; /* Subtle hover effect */
    }
    .table-archive tbody tr:last-child td {
        border-bottom: none;
    }
    .table-archive td:nth-child(4) { /* Kolom Aksi */
        text-align: right; 
    }

    /* Style untuk tombol aksi dengan ikon */
    .btn-action-icon {
        padding: 8px 15px;
        margin-left: 10px;
        border-radius: 6px; 
        font-size: 0.85em; 
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.2s;
    }
    .btn-restore {
        background-color: #10B981; /* Emerald Green */
        color: white;
    }
    .btn-delete-permanent {
        background-color: #EF4444; /* Red */
        color: white;
    }
    .btn-action-icon:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .profile-img-small {
        width: 40px; /* Lebih kecil, lebih elegan */
        height: 40px; 
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #E5E7EB; 
        box-shadow: 0 0 0 2px #fff, 0 2px 5px rgba(0,0,0,0.1); /* Border luar putih dan shadow */
        flex-shrink: 0;
    }
    .user-info-cell {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .user-text-detail {
        line-height: 1.3;
        overflow: hidden;
    }
    .user-text-detail strong {
        font-size: 1.05em; /* Sedikit diperbesar */
        color: var(--primary-color); 
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .user-text-detail small {
        color: #6B7280; 
        font-weight: 500;
        font-size: 0.8em; /* Sedikit dikecilkan */
    }
    .job-title {
        font-weight: 600; 
        color: #06B6D4; 
        background-color: #E0F7FA;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9em;
    }
</style>

<h1 class="dashboard-title"><i class="fas fa-archive" style="color: #F97316;"></i> Arsip Pengguna</h1>
<p style="color: #555;">Daftar akun pengguna yang telah **diarsipkan (soft delete)**. Gunakan opsi di bawah untuk memulihkan data atau menghapusnya secara permanen.</p>

<div style="display: flex; justify-content: flex-start; margin-bottom: 25px;">
    <a href="users.php" class="btn btn-primary" style="background-color: #6B7280; color: white;"><i class="fas fa-arrow-left"></i> Kembali ke Pengguna Aktif</a>
</div>

<table class="table-archive">
    <thead>
        <tr>
            <th style="width: 35%;">Detail Pengguna</th>
            <th style="width: 20%;">Jabatan</th>
            <th style="width: 15%;">ID LKSA</th>
            <th style="width: 30%; text-align: right;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { 
            $base_url_assets = "../assets/img/";
            // Menggunakan foto default yang lebih baik jika foto user tidak ada (asumsi yayasan.png ada)
            $foto_src = $base_url_assets . ($row['Foto'] ?? 'yayasan.png');
        ?>
            <tr>
                <td>
                    <div class="user-info-cell">
                        <?php if ($row['Foto'] && file_exists($base_url_assets . $row['Foto'])) { ?>
                            <img src="<?php echo htmlspecialchars($foto_src); ?>" alt="Foto Profil" class="profile-img-small">
                        <?php } else { ?>
                            <i class="fas fa-user-circle" style="font-size: 40px; color: #ccc; flex-shrink: 0;"></i>
                        <?php } ?>
                        <div class="user-text-detail">
                            <strong><?php echo $row['Nama_User']; ?></strong>
                            <small>ID: <?php echo $row['Id_user']; ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="job-title"><?php echo $row['Jabatan']; ?></span>
                </td>
                <td>
                    <span style="font-weight: 600; color: #1F2937;"><?php echo $row['Id_lksa']; ?></span>
                </td>
                <td>
                    <a href="proses_restore_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-action-icon btn-restore" title="Pulihkan" onclick="return confirm('Apakah Anda yakin ingin memulihkan pengguna ini?');">
                        <i class="fas fa-undo"></i> Pulihkan
                    </a>
                    <a href="hapus_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-action-icon btn-delete-permanent" title="Hapus Permanen" onclick="return confirm('PERINGATAN! Apakah Anda yakin ingin MENGHAPUS PERMANEN pengguna ini? Tindakan ini tidak dapat dibatalkan.');">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>