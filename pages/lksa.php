<?php
session_start();
include '../config/database.php';

// Pastikan hanya Pimpinan Pusat yang bisa mengakses halaman ini
if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.");
}

// Logika untuk menampilkan data LKSA
$sql = "SELECT * FROM LKSA";
$result = $conn->query($sql);

// Set sidebar stats ke string kosong agar sidebar tetap tampil
$sidebar_stats = ''; 

include '../includes/header.php'; // <-- LOKASI BARU
?>
<h1 class="dashboard-title">Manajemen LKSA</h1>
<p>Halaman ini memungkinkan Anda untuk mengelola semua data LKSA yang terdaftar (kantor cabang).</p>

<a href="tambah_lksa.php" class="btn btn-success" style="margin-bottom: 20px;">Tambah LKSA (Kantor Cabang)</a>

<table>
    <thead>
        <tr>
            <th>ID LKSA</th>
            <th>Nama LKSA</th>
            <th>Nama Pimpinan</th>
            <th>Alamat</th>
            <th>Tanggal Daftar</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['Id_lksa']; ?></td>
                <td><?php echo $row['Nama_LKSA'] ?? 'N/A'; ?></td>
                <td><?php echo $row['Nama_Pimpinan'] ?? 'Belum Ditunjuk'; ?></td>
                <td><?php echo $row['Alamat']; ?></td>
                <td><?php echo $row['Tanggal_Daftar']; ?></td>
                <td>
                    <a href="edit_lksa.php?id=<?php echo $row['Id_lksa']; ?>" class="btn btn-primary">Edit</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>