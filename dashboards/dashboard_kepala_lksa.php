<?php
include 'config/database.php';
// Memastikan helpers tersedia
include 'config/db_helpers.php';

$id_lksa = $_SESSION['id_lksa'];

// --- Menggunakan fungsi helper untuk kueri LKSA ---

// 1. Total User LKSA
$sql_user = "SELECT COUNT(*) AS total FROM User WHERE Id_lksa = ?";
$total_user_lksa = fetch_single_param_value($conn, $sql_user, $id_lksa);

// 2. Total Donatur LKSA
$sql_donatur = "SELECT COUNT(*) AS total FROM Donatur WHERE ID_LKSA = ?";
$total_donatur_lksa = fetch_single_param_value($conn, $sql_donatur, $id_lksa);

// 3. Total Sumbangan ZIS LKSA
$sql_sumbangan = "SELECT SUM(Zakat_Profesi + Zakat_Maal + Infaq + Sedekah + Fidyah) AS total FROM Sumbangan WHERE ID_LKSA = ?";
$total_sumbangan_lksa = fetch_single_param_value($conn, $sql_sumbangan, $id_lksa);

// 4. Total Dana Kotak Amal LKSA
$sql_dana_ka = "SELECT SUM(JmlUang) AS total FROM Dana_KotakAmal WHERE Id_lksa = ?";
$total_dana_kotak_amal_lksa = fetch_single_param_value($conn, $sql_dana_ka, $id_lksa);

// LOGIC BARU UNTUK SIDEBAR
$id_user = $_SESSION['id_user'] ?? '';
$user_info_sql = "SELECT Nama_User, Foto FROM User WHERE Id_user = ?";
$stmt_user_info = $conn->prepare($user_info_sql);
$stmt_user_info->bind_param("s", $id_user);
$stmt_user_info->execute();
$user_info = $stmt_user_info->get_result()->fetch_assoc();
$stmt_user_info->close();

$nama_user = $user_info['Nama_User'] ?? 'Pengguna';
$foto_user = $user_info['Foto'] ?? '';
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/"; // Definisikan $base_url
$foto_path = $foto_user ? $base_url . 'assets/img/' . $foto_user : $base_url . 'assets/img/yayasan.png'; // Use Yayasan logo as default if none

// Total Pegawai dan Petugas KA (Untuk Sidebar)
$sql_sidebar_pegawai = "SELECT COUNT(*) AS total FROM User WHERE Id_lksa = ? AND Jabatan IN ('Pegawai', 'Petugas Kotak Amal')";
$sidebar_total_pegawai = fetch_single_param_value($conn, $sql_sidebar_pegawai, $id_lksa);
$sidebar_total_donatur_lksa = $total_donatur_lksa; // Re-use total donatur dari atas

// Menetapkan variabel $sidebar_stats untuk digunakan di header.php
$sidebar_stats = '
<div class="sidebar-stats-card card-user" style="border-left-color: #1E3A8A;">
    <h4>Total Pegawai LKSA</h4>
    <p>' . number_format($sidebar_total_pegawai) . '</p>
</div>
<div class="sidebar-stats-card card-donatur" style="border-left-color: #10B981;">
    <h4>Total Donatur Terdaftar</h4>
    <p>' . number_format($sidebar_total_donatur_lksa) . '</p>
</div>
<div class="sidebar-stats-card card-sumbangan" style="border-left-color: #6366F1;">
    <h4>Total Sumbangan ZIS LKSA</h4>
    <p>Rp ' . number_format($total_sumbangan_lksa) . '</p>
</div>
<div class="sidebar-stats-card card-kotak-amal" style="border-left-color: #F59E0B;">
    <h4>Total Dana Kotak Amal LKSA</h4>
    <p>Rp ' . number_format($total_dana_kotak_amal_lksa) . '</p>
</div>
';

include 'includes/header.php'; // <-- LOKASI BARU
?>
<p>Anda dapat mengelola data di LKSA Anda, termasuk pengguna dan donatur.</p>

<h2 class="dashboard-title">Ringkasan Finansial LKSA</h2>
<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="stats-card card-sumbangan">
        <i class="fas fa-sack-dollar"></i>
        <div class="stats-card-content">
            <h3>Total Sumbangan ZIS</h3>
            <span class="value">Rp <?php echo number_format($total_sumbangan_lksa); ?></span>
        </div>
    </div>
    <div class="stats-card card-kotak-amal">
        <i class="fas fa-box-open"></i>
        <div class="stats-card-content">
            <h3>Total Dana Kotak Amal</h3>
            <span class="value">Rp <?php echo number_format($total_dana_kotak_amal_lksa); ?></span>
        </div>
    </div>
</div>

<h2 class="dashboard-title">Ringkasan Operasional LKSA</h2>
<div class="stats-grid" style="grid-template-columns: 1fr;">
    <div class="stats-card card-donatur">
        <i class="fas fa-hand-holding-heart"></i>
        <div class="stats-card-content">
            <h3>Jumlah Donatur Terdaftar</h3>
            <span class="value"><?php echo number_format($total_donatur_lksa); ?></span>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
$conn->close();
?>