<?php
session_start();
include '../config/database.php';
// include '../includes/header.php'; // Pindahkan ke bawah

if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA') {
    die("Akses ditolak.");
}

$sql_lksa = "SELECT Id_lksa, Nama_LKSA FROM LKSA"; // Ambil juga Nama_LKSA
$result_lksa = $conn->query($sql_lksa);

$sidebar_stats = ''; // Pastikan sidebar tampil

include '../includes/header.php'; // LOKASI BARU
?>
<div class="form-container">
    <h1>Tambah Pengguna Baru</h1>
    <form action="proses_pengguna.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tambah">
        <div class="form-section">
            <h2>Data Pengguna</h2>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Nama User:</label>
                    <input type="text" name="nama_user" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
            </div>
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Jabatan:</label>
                    <select name="jabatan" required>
                        <?php 
                        $is_pimpinan_pusat = ($_SESSION['jabatan'] == 'Pimpinan' && $_SESSION['id_lksa'] == 'Pimpinan_Pusat');
                        
                        if ($is_pimpinan_pusat) { ?>
                            <option value="Pimpinan">Pimpinan (Cabang)</option> 
                            <option value="Kepala LKSA">Kepala LKSA</option>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } elseif ($_SESSION['jabatan'] == 'Pimpinan') { ?>
                            <option value="Kepala LKSA">Kepala LKSA</option>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } elseif ($_SESSION['jabatan'] == 'Kepala LKSA') { ?>
                            <option value="Pegawai">Pegawai</option>
                            <option value="Petugas Kotak Amal">Petugas Kotak Amal</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID LKSA:</label>
                    <?php 
                    if ($_SESSION['jabatan'] == 'Kepala LKSA' || ($_SESSION['jabatan'] == 'Pimpinan' && $_SESSION['id_lksa'] != 'Pimpinan_Pusat')) { 
                        // Kepala LKSA dan Pimpinan Cabang hanya bisa membuat user di LKSA/cabang-nya sendiri
                    ?>
                        <input type="text" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>" readonly required>
                    <?php } else { ?>
                        <select name="id_lksa" required>
                            <option value="">-- Pilih LKSA --</option>
                            <?php while ($row_lksa = $result_lksa->fetch_assoc()) { ?>
                                <option value="<?php echo htmlspecialchars($row_lksa['Id_lksa']); ?>">
                                    <?php echo htmlspecialchars($row_lksa['Id_lksa']); ?> (<?php echo htmlspecialchars($row_lksa['Nama_LKSA'] ?? 'N/A'); ?>)
                                </option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </div>
            </div>

            <div class="form-group">
                <label>Foto:</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Simpan</button>
            <a href="users.php" class="btn btn-cancel">Batal</a>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>