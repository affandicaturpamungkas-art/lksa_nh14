<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Verifikasi otorisasi: Hanya Pimpinan dan Kepala LKSA yang bisa mengakses halaman ini.
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$jabatan = $_SESSION['jabatan'];
$id_lksa = $_SESSION['id_lksa'];

// Persiapan query SQL dasar untuk mengambil data pengguna yang AKTIF.
$sql = "SELECT * FROM User WHERE Status = 'Active'";

// Logika untuk menyesuaikan query berdasarkan jabatan dan ID LKSA pengguna yang sedang login.
if ($jabatan == 'Pimpinan' && $id_lksa == 'Pimpinan_Pusat') {
    // Pimpinan Pusat dapat melihat semua pengguna yang aktif.
    // Menampilkan semua kecuali dirinya sendiri
    $sql .= " AND Id_user != '" . $_SESSION['id_user'] . "'"; 
} elseif ($jabatan == 'Pimpinan' && $id_lksa !== 'Pimpinan_Pusat') {
    // Pimpinan cabang hanya dapat melihat pengguna yang aktif di LKSA-nya (kecuali dirinya sendiri).
    $sql .= " AND Id_lksa = '$id_lksa' AND Id_user != '" . $_SESSION['id_user'] . "'";
} elseif ($jabatan == 'Kepala LKSA') {
    // Kepala LKSA hanya dapat melihat pengguna dengan jabatan di bawahnya di LKSA-nya.
    $sql .= " AND Id_lksa = '$id_lksa' AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
}

$result = $conn->query($sql);
?>
<h1 class="dashboard-title">Manajemen Pengguna</h1>
<p>Anda dapat mengelola akun pengguna di sistem.</p>
<a href="tambah_pengguna.php" class="btn btn-success">Tambah Pengguna Baru</a>
<a href="arsip_users.php" class="btn btn-cancel" style="background-color: #F97316; margin-left: 10px;">Lihat Arsip Pengguna</a>

<table class="responsive-table">
    <thead>
        <tr>
            <th>Nama User</th>
            <th>Jabatan</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td data-label="Nama User"><?php echo $row['Nama_User']; ?></td>
                <td data-label="Jabatan"><?php echo $row['Jabatan']; ?></td>
                <td data-label="Foto">
                    <?php if ($row['Foto']) { ?>
                        <img src="../assets/img/<?php echo htmlspecialchars($row['Foto']); ?>" alt="Foto Profil" style="width: 50px; height: 50px; object-fit: cover;">
                    <?php } else { ?>
                        Tidak Ada
                    <?php } ?>
                </td>
                <td data-label="Aksi">
                    <a href="edit_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-primary">Edit</a>
                    <a href="proses_arsip_pengguna.php?id=<?php echo $row['Id_user']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin mengarsipkan pengguna ini?');">Arsipkan</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>