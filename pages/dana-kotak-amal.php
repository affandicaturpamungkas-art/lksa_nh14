<?php
session_start();
include '../config/database.php';
include '../includes/header.php';

// Verifikasi otorisasi: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

$id_user = $_SESSION['id_user'];
$id_lksa = $_SESSION['id_lksa'];

// --- 1. Ambil daftar kotak amal untuk dropdown (Kueri 1)
$sql_kotak_amal = "SELECT ID_KotakAmal, Nama_Toko FROM KotakAmal";
if ($_SESSION['jabatan'] != 'Pimpinan') {
    // Gunakan Prepared Statement untuk filter Id_lksa
    $sql_kotak_amal .= " WHERE Id_lksa = ?"; 
    $stmt_ka = $conn->prepare($sql_kotak_amal);
    $stmt_ka->bind_param("s", $id_lksa);
    $stmt_ka->execute();
    $result_kotak_amal = $stmt_ka->get_result();
    $stmt_ka->close();
} else {
    $result_kotak_amal = $conn->query($sql_kotak_amal);
}

// --- 2. Ambil riwayat pengambilan dana (Kueri 2)
$sql_history = "SELECT dka.*, ka.Nama_Toko, u.Nama_User
                FROM Dana_KotakAmal dka
                LEFT JOIN KotakAmal ka ON dka.ID_KotakAmal = ka.ID_KotakAmal
                LEFT JOIN User u ON dka.Id_user = u.Id_user";
                
$params = [];
$types = "";
                
if ($_SESSION['jabatan'] != 'Pimpinan') {
    // Gunakan Prepared Statement untuk filter Id_lksa
    $sql_history .= " WHERE dka.Id_lksa = ?";
    $params[] = $id_lksa;
    $types = "s";
}
$sql_history .= " ORDER BY dka.Tgl_Ambil DESC";

// Eksekusi Kueri 2
$stmt_history = $conn->prepare($sql_history);

if (!empty($params)) {
    $stmt_history->bind_param($types, ...$params);
}

$stmt_history->execute();
$result_history = $stmt_history->get_result();

?>
<style>
    /* Style tambahan untuk tombol ikon yang sederhana */
    .btn-action-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border-radius: 5px;
        font-size: 0.9em;
    }
    .btn-edit {
        background-color: #6B7280; /* Gray/Cancel color */
    }
</style>
<h1 class="dashboard-title">Pengambilan Kotak Amal</h1>
<p>Catat pengambilan dana dari kotak amal dan lihat riwayatnya.</p>

<div class="form-container">
    <div class="form-section">
        <h2>Catat Pengambilan Baru</h2>
        <form action="proses_dana_kotak_amal.php" method="POST">
            <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($id_user); ?>">

            <div class="form-group">
                <label>Pilih Kotak Amal:</label>
                <select name="id_kotak_amal" required>
                    <option value="">-- Pilih Kotak Amal --</option>
                    <?php 
                    // Pastikan pointer result_kotak_amal diulang untuk dropdown
                    if (isset($result_kotak_amal) && $result_kotak_amal->num_rows > 0) {
                        $result_kotak_amal->data_seek(0);
                        while ($row_ka = $result_kotak_amal->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($row_ka['ID_KotakAmal']); ?>">
                                <?php echo htmlspecialchars($row_ka['Nama_Toko']); ?>
                            </option>
                        <?php }
                    } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jumlah Uang (Rp):</label>
                <input type="number" name="jumlah_uang" required>
            </div>

            <div class="form-actions" style="justify-content: flex-start;">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Pengambilan</button>
            </div>
        </form>
    </div>
</div>

<h2>Riwayat Pengambilan</h2>
<table class="responsive-table">
    <thead>
        <tr>
            <th>ID Kwitansi</th>
            <th>Nama Toko</th>
            <th>Jumlah Uang</th>
            <th>Tanggal Ambil</th>
            <th>Petugas</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row_hist = $result_history->fetch_assoc()) { ?>
            <tr>
                <td data-label="ID Kwitansi"><?php echo $row_hist['ID_Kwitansi_KA']; ?></td>
                <td data-label="Nama Toko"><?php echo $row_hist['Nama_Toko']; ?></td>
                <td data-label="Jumlah Uang">Rp <?php echo number_format($row_hist['JmlUang']); ?></td>
                <td data-label="Tanggal Ambil"><?php echo $row_hist['Tgl_Ambil']; ?></td>
                <td data-label="Petugas"><?php echo $row_hist['Nama_User']; ?></td>
                <td data-label="Aksi">
                    <a href="edit_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-primary btn-action-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                    <a href="hapus_dana_kotak_amal.php?id=<?php echo $row_hist['ID_Kwitansi_KA']; ?>" class="btn btn-danger btn-action-icon" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data pengambilan ini?');"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$stmt_history->close();
$conn->close();
?>