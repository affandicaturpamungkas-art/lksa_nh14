<?php
session_start();
include '../config/database.php';

// Authorization check: Semua yang terkait dengan donasi ZIS
$jabatan = $_SESSION['jabatan'] ?? '';
$id_lksa = $_SESSION['id_lksa'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

// PERUBAHAN: Mengambil data yang Status_Data = 'Archived'
$sql = "SELECT d.*, u.Nama_User FROM Donatur d JOIN User u ON d.ID_user = u.Id_user WHERE d.Status_Data = 'Archived'";

$params = [];
$types = "";

// FIX: Hanya Pimpinan Pusat yang tidak difilter
if ($jabatan != 'Pimpinan' || $id_lksa != 'Pimpinan_Pusat') {
    // Perbaikan SQLI: Menggunakan placeholder
    $sql .= " AND d.ID_LKSA = ?";
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

// Set sidebar stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = '';

include '../includes/header.php';
?>
<h1 class="dashboard-title">Arsip Donatur ZIS</h1>
<p>Daftar donatur yang telah diarsipkan (soft delete). Anda dapat memulihkan atau menghapus permanen dari sini.</p>
<a href="donatur.php" class="btn btn-primary">Kembali ke Manajemen Donatur Aktif</a>


<table>
    <thead>
        <tr>
            <th>ID Donatur</th>
            <th>Nama Donatur</th>
            <th>No. WA</th>
            <th>Dibuat Oleh</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['ID_donatur']; ?></td>
                <td><?php echo $row['Nama_Donatur']; ?></td>
                <td><?php echo $row['NO_WA']; ?></td>
                <td><?php echo $row['Nama_User']; ?></td>
                <td>
                    <a href="proses_restore_donatur.php?id=<?php echo $row['ID_donatur']; ?>" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin memulihkan donatur ini?');">Pulihkan</a>
                    <a href="#" class="btn btn-danger" onclick="alert('Fitur Hapus Permanen dinonaktifkan untuk Donatur. Silakan kontak administrator database.');">Hapus Permanen</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>