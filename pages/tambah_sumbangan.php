<?php
session_start();
include '../config/database.php';
// include '../includes/header.php'; // Pindahkan ke bawah

// Authorization check
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

// Ambil data donatur untuk dropdown
$sql_donatur = "SELECT ID_donatur, Nama_Donatur FROM Donatur";
$result_donatur = $conn->query($sql_donatur);

$sidebar_stats = ''; // Pastikan sidebar tampil

include '../includes/header.php'; // LOKASI BARU
?>
<div class="form-container">
    <h1>Input Sumbangan Baru</h1>
    <form action="proses_sumbangan.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($_SESSION['id_user']); ?>">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>">

        <div class="form-section">
            <h2>Informasi Donatur</h2>
            <div class="form-group">
                <label>Pilih Donatur:</label>
                <select name="id_donatur" required>
                    <option value="">-- Pilih Donatur --</option>
                    <?php while ($row_donatur = $result_donatur->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars($row_donatur['ID_donatur']); ?>">
                            <?php echo htmlspecialchars($row_donatur['Nama_Donatur']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h2>Detail Sumbangan (dalam Rupiah)</h2>
            <p class="form-description">Masukkan angka 0 jika tidak ada sumbangan untuk jenis tersebut.</p>
            <div class="form-grid">
                <div class="form-group">
                    <label>Zakat Profesi:</label>
                    <input type="number" name="zakat_profesi" value="0">
                </div>
                <div class="form-group">
                    <label>Zakat Maal:</label>
                    <input type="number" name="zakat_maal" value="0">
                </div>
                <div class="form-group">
                    <label>Infaq:</label>
                    <input type="number" name="infaq" value="0">
                </div>
                <div class="form-group">
                    <label>Sedekah:</label>
                    <input type="number" name="sedekah" value="0">
                </div>
                <div class="form-group" style="grid-column: span 2;"> <label>Fidyah:</label>
                    <input type="number" name="fidyah" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Natura (Barang/Bukan Uang):</label>
                <input type="text" name="natura" placeholder="Contoh: 10 kg beras">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Simpan Sumbangan</button>
            <a href="sumbangan.php" class="btn btn-cancel">Batal</a>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>