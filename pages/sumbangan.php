<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

// Gabungkan (JOIN) tabel Sumbangan dengan tabel Donatur untuk mendapatkan Nama Donatur
$sql = "SELECT s.*, d.Nama_Donatur FROM Sumbangan s LEFT JOIN Donatur d ON s.ID_donatur = d.ID_donatur";

$params = [];
$types = "";

if ($_SESSION['jabatan'] != 'Pimpinan') {
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " WHERE s.ID_LKSA = ?";
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
    /* Tambahan style sederhana untuk tombol ikon */
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
    }
    .btn-detail {
        background-color: #06B6D4; /* Sesuai dengan primary/lksa color */
    }
    .btn-edit {
        background-color: #6B7280; /* Gray/Cancel color */
    }
</style>
<h1 class="dashboard-title">Manajemen Sumbangan</h1>
<p>Lihat dan kelola semua transaksi sumbangan ZIS.</p>
<a href="tambah_sumbangan.php" class="btn btn-success">Input Sumbangan Baru</a>

<table class="responsive-table">
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Donatur</th>
            <th>Total ZIS</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td data-label="ID Kwitansi"><?php echo $row['ID_Kwitansi_ZIS']; ?></td>
                <td data-label="Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                <td data-label="Total ZIS">Rp <?php echo number_format($row['Zakat_Profesi'] + $row['Zakat_Maal'] + $row['Infaq'] + $row['Sedekah'] + $row['Fidyah']); ?></td>
                <td data-label="Tanggal"><?php echo $row['Tgl']; ?></td>
                <td data-label="Aksi">
                    <a href="detail_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-primary btn-action-icon btn-detail" title="Detail"><i class="fas fa-eye"></i></a>
                    <a href="edit_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-primary btn-action-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="hapus_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-danger btn-action-icon" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus sumbangan ini?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>