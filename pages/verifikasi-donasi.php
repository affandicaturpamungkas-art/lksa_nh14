<?php
set_time_limit(300); // Menambah batas waktu eksekusi skrip menjadi 300 detik (5 menit)
session_start();
include '../config/database.php';
include '../includes/header.php';

// Authorization check: Hanya Pimpinan dan Kepala LKSA yang bisa akses
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$id_lksa = $_SESSION['id_lksa'];

$sql = "SELECT s.*, d.Nama_Donatur FROM Sumbangan s JOIN Donatur d ON s.ID_donatur = d.ID_donatur";
if ($_SESSION['jabatan'] == 'Kepala LKSA') {
    $sql .= " WHERE s.Id_lksa = '$id_lksa'";
}
$result = $conn->query($sql);

?>
<h1 class="dashboard-title">Verifikasi Donasi</h1>
<p>Daftar sumbangan yang menunggu verifikasi.</p>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
    <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
        Verifikasi berhasil! Sumbangan telah disetujui.
    </div>
<?php } ?>

<table>
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Nama Donatur</th>
            <th>Total Sumbangan</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['ID_Kwitansi_ZIS']; ?></td>
                <td><?php echo $row['Nama_Donatur']; ?></td>
                <td>Rp <?php echo number_format($row['Zakat_Profesi'] + $row['Zakat_Maal'] + $row['Infaq'] + $row['Sedekah'] + $row['Fidyah']); ?></td>
                <td><?php echo $row['Tgl']; ?></td>
                <td>
                    <?php if ($row['Status_Verifikasi'] == 'Terverifikasi') { ?>
                        <span style="color: green; font-weight: bold;">Terverifikasi</span>
                        <a href="cetak_kwitansi.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-primary" target="_blank">Cetak Kwitansi</a>
                    <?php } else { ?>
                        <a href="edit_sumbangan.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-primary">Edit</a>
                        <a href="proses_verifikasi.php?id=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin memverifikasi donasi ini?');">Verifikasi</a>
                    <?php } ?>
                    <a href="wa-blast-form.php?id_kwitansi=<?php echo $row['ID_Kwitansi_ZIS']; ?>" class="btn btn-secondary">WA Blast</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>