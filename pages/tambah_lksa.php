<?php
session_start();
include '../config/database.php';

if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak.");
}

// Pastikan sidebar_stats diatur ke string kosong sebelum memuat header
$sidebar_stats = ''; 

include '../includes/header.php'; // LOKASI BARU
?>
<div class="form-container">
    <h1><i class="fas fa-building" style="color: #06B6D4;"></i> Tambah LKSA Baru (Kantor Cabang)</h1>
    
    <form action="proses_lksa.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-section">
            <h2>Informasi Utama</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama LKSA Lengkap:</label>
                    <input type="text" name="nama_lksa" required placeholder="Contoh: LKSA Nur Hidayah Surakarta">
                </div>
                <div class="form-group">
                    <label>Alamat Lokasi (Untuk Kode ID LKSA):</label>
                    <input type="text" name="alamat_lksa" required placeholder="Contoh: Surakarta, Jakarta Barat">
                    <small style="color: #6B7280; display: block; margin-top: 5px;">Alamat ini akan digunakan untuk membuat ID LKSA unik (cth: SURAKARTA_NH_001).</small>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2>Informasi Kontak</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor WA:</label>
                    <input type="text" name="nomor_wa_lksa" placeholder="Contoh: 0812xxxxxx">
                </div>
                <div class="form-group">
                    <label>Email LKSA:</label>
                    <input type="email" name="email_lksa" placeholder="Contoh: admin@lksa.com">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Logo LKSA</h2>
            <div class="form-group">
                <label>Unggah Logo LKSA (Opsional, Max 5MB):</label>
                <input type="file" name="logo" accept="image/*">
                <small style="color: #6B7280; display: block; margin-top: 5px;">Unggah logo resmi LKSA yang didaftarkan.</small>
            </div>
        </div>

        <div class="form-actions">
            <a href="lksa.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Daftarkan LKSA</button>
        </div>
    </form>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>