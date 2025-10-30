<?php
session_start();
include '../config/database.php';
// include '../includes/header.php'; // Pindahkan ke bawah

// Authorization check: Semua yang terkait dengan donasi ZIS
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Pegawai') {
    die("Akses ditolak.");
}

$sidebar_stats = ''; // Pastikan sidebar tampil

include '../includes/header.php'; // LOKASI BARU
?>
<div class="form-container">
    <h1>Tambah Donatur Baru</h1>
    <form action="proses_donatur.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tambah">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($_SESSION['id_lksa']); ?>">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($_SESSION['id_user']); ?>">
        
        <div class="form-section">
            <h2>Data Donatur</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Donatur:</label>
                    <input type="text" name="nama_donatur" required>
                </div>
                <div class="form-group">
                    <label>Nomor WhatsApp:</label>
                    <input type="text" name="no_wa">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Status Donasi:</label>
                    <select name="status_donasi">
                        <option value="Rutin">Rutin</option>
                        <option value="Insidental">Insidental</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Alamat Lengkap:</label>
                <textarea name="alamat_lengkap" rows="4" cols="50"></textarea>
            </div>
            <div class="form-group">
                <label>Foto (Opsional):</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Simpan Donatur</button>
            <a href="donatur.php" class="btn btn-cancel">Batal</a>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>