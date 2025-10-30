<?php
session_start();
include '../config/database.php';

// Authorization check: Semua yang terkait dengan donasi ZIS
$jabatan = $_SESSION['jabatan'] ?? '';
$id_lksa = $_SESSION['id_lksa'] ?? '';
if (!in_array($jabatan, ['Pimpinan', 'Kepala LKSA', 'Pegawai'])) {
    die("Akses ditolak.");
}

// PERUBAHAN: Hanya Pimpinan Pusat yang dapat melihat semua (Global View)
$sql = "SELECT d.*, u.Nama_User FROM Donatur d JOIN User u ON d.ID_user = u.Id_user WHERE d.Status_Data = 'Active'";

$params = [];
$types = "";

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
<h1 class="dashboard-title">Manajemen Donatur ZIS</h1>
<p>Kelola data donatur.</p>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
    <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
        Data donatur berhasil disimpan!
    </div>
<?php } ?>

<a href="tambah_donatur.php" class="btn btn-success">Tambah Donatur</a>
<a href="arsip_donatur.php" class="btn btn-cancel" style="background-color: #F97316; margin-left: 10px;">Lihat Arsip Donatur</a>


<table class="responsive-table">
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
                <td data-label="ID Donatur"><?php echo $row['ID_donatur']; ?></td>
                <td data-label="Nama Donatur"><?php echo $row['Nama_Donatur']; ?></td>
                <td data-label="No. WA"><?php echo $row['NO_WA']; ?></td>
                <td data-label="Dibuat Oleh"><?php echo $row['Nama_User']; ?></td>
                <td data-label="Aksi">
                    <a href="edit_donatur.php?id=<?php echo $row['ID_donatur']; ?>" class="btn btn-primary">Edit</a>
                    <a href="proses_arsip_donatur.php?id=<?php echo $row['ID_donatur']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin mengarsipkan donatur ini?');">Arsipkan</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php
include '../includes/footer.php';
$conn->close();
?>