<?php
session_start();
include '../config/database.php';

// Fungsi untuk mengunggah foto (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_donatur) {
    // --- PERBAIKAN: Mengganti hardcode path dengan path relatif yang dinamis ---
    $target_dir = __DIR__ . '/../assets/img/';
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif");

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => "Maaf, hanya file JPG, JPEG, PNG, & GIF yang diizinkan."];
    }

    if ($file["size"] > 5000000) { // 5MB
        return ['error' => "Maaf, ukuran file terlalu besar."];
    }

    // Format nama: donatur_nama_uniqid.ext
    // 1. Hapus karakter non-alfanumerik/spasi
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_donatur); 
    // 2. Ganti spasi dengan underscore
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_jabatan = "donatur"; // Gunakan "donatur" sebagai prefix

    // 3. Gabungkan dan tambahkan uniqid() singkat (5 karakter terakhir)
    $unique_filename = strtolower($safe_jabatan . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        return ['error' => "Maaf, terjadi kesalahan saat mengunggah file Anda."];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $id_lksa = $_POST['id_lksa'] ?? '';
    $id_user = $_POST['id_user'] ?? '';
    $nama_donatur = $_POST['nama_donatur'] ?? '';
    $no_wa = $_POST['no_wa'] ?? '';
    $email = $_POST['email'] ?? '';
    $alamat_lengkap = $_POST['alamat_lengkap'] ?? '';
    $status_donasi = $_POST['status_donasi'] ?? '';
    $tgl_rutinitas = null; // Contoh jika status rutin

    // Membuat ID Donatur yang unik sesuai format LKSA_NH_thbltgl_XXX
    $tgl_id = date('ymd');
    $counter_sql = "SELECT COUNT(*) AS total FROM Donatur WHERE ID_donatur LIKE 'LKSA_NH_{$tgl_id}_%'";
    $result = $conn->query($counter_sql);
    $row = $result->fetch_assoc();
    $counter = $row['total'] + 1;
    $id_donatur = "LKSA_NH_" . $tgl_id . "_" . str_pad($counter, 3, '0', STR_PAD_LEFT);
    
    $foto_path = null;
    if (!empty($_FILES['foto']['name'])) {
        $upload_result = handle_upload($_FILES['foto'], $nama_donatur); // Panggil fungsi dengan nama donatur
        if (isset($upload_result['error'])) {
            die($upload_result['error']);
        }
        $foto_path = $upload_result['filename'];
    }

    $status_data_active = 'Active'; // Tambahkan status data default

    // PERUBAHAN: Menambahkan kolom Status_Data (untuk arsip)
    $sql = "INSERT INTO Donatur (ID_donatur, ID_LKSA, ID_user, Nama_Donatur, NO_WA, Alamat_Lengkap, Email, Foto, Status, Tgl_Rutinitas, Status_Data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssss", $id_donatur, $id_lksa, $id_user, $nama_donatur, $no_wa, $alamat_lengkap, $email, $foto_path, $status_donasi, $tgl_rutinitas, $status_data_active);

    if ($stmt->execute()) {
        header("Location: donatur.php?status=success");
        exit;
    } else {
        die("Error saat menyimpan donatur: " . $stmt->error);
    }
} else {
    header("Location: tambah_donatur.php");
    exit;
}
$conn->close();
?>