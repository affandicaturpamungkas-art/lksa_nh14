<?php
session_start();
include '../config/database.php';

if ($_SESSION['jabatan'] != 'Pimpinan' || $_SESSION['id_lksa'] != 'Pimpinan_Pusat') {
    die("Akses ditolak.");
}

// Fungsi untuk mengunggah file logo (MENGGUNAKAN LOGIKA NAMA BARU)
function handle_upload($file, $nama_lksa) {
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

    // Format nama: lksa_nama_uniqid.ext
    $safe_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $nama_lksa); 
    $safe_name = str_replace(' ', '_', trim($safe_name)); 
    $safe_type = "lksa";

    $unique_filename = strtolower($safe_type . '_' . $safe_name . '_' . substr(uniqid(), -5)) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['filename' => $unique_filename];
    } else {
        return ['error' => "Maaf, terjadi kesalahan saat mengunggah file Anda."];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'tambah';
    $id_lksa = $_POST['id_lksa'] ?? null;
    $nama_lksa = $_POST['nama_lksa'] ?? '';
    $alamat_lksa = $_POST['alamat_lksa'] ?? '';
    $nomor_wa_lksa = $_POST['nomor_wa_lksa'] ?? '';
    $email_lksa = $_POST['email_lksa'] ?? '';
    $logo_path = null;

    if ($action == 'tambah') {
        
        // Logika untuk membuat ID LKSA yang unik
        $prefix = preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '_', $alamat_lksa));
        $prefix = strtoupper(substr($prefix, 0, 10)); // Batasi panjang prefix
        
        $counter_sql = "SELECT COUNT(*) AS total FROM LKSA WHERE Id_lksa LIKE '{$prefix}_NH_%'";
        $result = $conn->query($counter_sql);
        $row = $result->fetch_assoc();
        $counter = $row['total'] + 1;
        $id_lksa = $prefix . "_NH_" . str_pad($counter, 3, '0', STR_PAD_LEFT);

        // Menangani unggahan logo
        if (!empty($_FILES['logo']['name'])) {
            $upload_result = handle_upload($_FILES['logo'], $nama_lksa);
            if (isset($upload_result['error'])) {
                die($upload_result['error']);
            }
            $logo_path = $upload_result['filename'];
        }

        // Langkah 1: Masukkan data LKSA baru
        $lksa_sql = "INSERT INTO LKSA (Id_lksa, Nama_LKSA, Alamat, Nomor_WA, Email, Logo, Tanggal_Daftar, Nama_Pimpinan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $lksa_stmt = $conn->prepare($lksa_sql);
        $tgl_daftar = date('Y-m-d');
        $nama_pimpinan_default = ""; // Set kosong untuk di-update nanti

        $lksa_stmt->bind_param("ssssssss", $id_lksa, $nama_lksa, $alamat_lksa, $nomor_wa_lksa, $email_lksa, $logo_path, $tgl_daftar, $nama_pimpinan_default);
        
        if (!$lksa_stmt->execute()) {
            die("Error saat menambahkan LKSA: " . $lksa_stmt->error);
        }
        $lksa_stmt->close();

    } elseif ($action == 'edit') {
        
        $logo_lama = $_POST['logo_lama'] ?? null;
        $final_logo_path = $logo_lama;

        // Menangani unggahan logo baru
        if (!empty($_FILES['logo']['name'])) {
            $upload_result = handle_upload($_FILES['logo'], $nama_lksa);
            if (isset($upload_result['error'])) {
                die($upload_result['error']);
            }
            $final_logo_path = $upload_result['filename'];

            // Hapus logo lama jika ada
            // --- PERBAIKAN: Mengganti hardcode path dengan path relatif ---
            if ($logo_lama) {
                 $file_path_lama = __DIR__ . "/../assets/img/" . $logo_lama;
                if (file_exists($file_path_lama)) {
                    unlink($file_path_lama);
                }
            }
        }
        
        // Perbarui data LKSA (Nama_LKSA dan Alamat bisa diubah di sini)
        $update_sql = "UPDATE LKSA SET Nama_LKSA = ?, Alamat = ?, Nomor_WA = ?, Email = ?, Logo = ? WHERE Id_lksa = ?";
        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt === false) {
             die("Error saat menyiapkan kueri UPDATE: " . $conn->error);
        }
        
        $update_stmt->bind_param("ssssss", $nama_lksa, $alamat_lksa, $nomor_wa_lksa, $email_lksa, $final_logo_path, $id_lksa);

        if (!$update_stmt->execute()) {
            die("Error saat memperbarui LKSA: " . $update_stmt->error);
        }
        $update_stmt->close();

    }
    
    // Redirect ke halaman LKSA (berlaku untuk tambah dan edit)
    header("Location: lksa.php");
    exit;

}

$conn->close();
?>