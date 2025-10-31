<?php
session_start();
include '../config/database.php';
// Set $sidebar_stats agar tidak ada error di header
$sidebar_stats = ''; 
include '../includes/header.php';

// Authorization check: Pimpinan, Kepala LKSA, dan Petugas Kotak Amal
if (!in_array($_SESSION['jabatan'] ?? '', ['Pimpinan', 'Kepala LKSA', 'Petugas Kotak Amal'])) {
    die("Akses ditolak.");
}

$id_kotak_amal = $_GET['id'] ?? '';
if (empty($id_kotak_amal)) {
    die("ID Kotak Amal tidak ditemukan.");
}

// Ambil data Kotak Amal
$sql = "SELECT * FROM KotakAmal WHERE ID_KotakAmal = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_kotak_amal);
$stmt->execute();
$result = $stmt->get_result();
$data_ka = $result->fetch_assoc();
$stmt->close();

if (!$data_ka) {
    die("Data Kotak Amal tidak ditemukan.");
}

$latitude = $data_ka['Latitude'] ?? -7.5583; // Default Solo
$longitude = $data_ka['Longitude'] ?? 110.8252; // Default Solo
$map_link = "https://maps.google.com/maps?q={$latitude},{$longitude}&z=15&output=embed";

$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/lksa_nh/";
$foto_ka = $data_ka['Foto'] ?? '';
// Menggunakan gambar dari data, fallback ke yayasan.png
$foto_path = $foto_ka ? $base_url . 'assets/img/' . $foto_ka : $base_url . 'assets/img/yayasan.png'; 
?>
<style>
    /* Variabel Warna Elegan */
    :root {
        --ka-primary: #334155; /* Dark Slate - Teks utama */
        --ka-accent: #B45309; /* Copper/Deep Orange - Aksen utama */
        --profile-bg: #FFFBEB; /* Very Light Cream - Background profil */
        --profile-border: #D97706; /* Muted Gold */
        --profile-shadow: rgba(180, 83, 9, 0.2);
        --btn-edit: #047857; /* Deep Emerald Green */
        /* Warna Aksen Data Row - Muted Deep Tones */
        --row-contact: #1E40AF; /* Deep Blue */
        --row-schedule: #A16207; /* Muted Gold/Brown */
        --row-location: #059669; /* Forest Green */
        --row-coordinate: #7C3AED; /* Deep Purple */
    }

    .detail-card {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    .profile-info, .location-info {
        flex: 1 1 45%;
        padding: 25px;
        border-radius: 15px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); /* Shadow lebih tipis */
        background-color: #FFFFFF; 
        border: 1px solid #F1F5F9; /* Border sangat halus */
        border-left: 6px solid var(--ka-accent); 
    }
    .profile-info h2, .location-info h2 {
        color: var(--ka-primary);
        font-size: 1.5em;
        margin-top: 0;
        border-bottom: 2px solid #E5E7EB; 
        padding-bottom: 10px;
    }
    .map-frame {
        width: 100%;
        height: 350px;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-top: 15px;
    }
    
    /* GAYA DATA ROW ELEGAN (Tidak Berubah) */
    .data-row {
        padding: 15px 20px; 
        border-radius: 12px; 
        margin-bottom: 12px;
        background-color: #FFFFFF; 
        border-left: 5px solid; 
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); 
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
    }
    .data-row:hover {
        transform: translateY(-3px); 
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .data-label-group {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        border-bottom: none; 
    }
    .data-label-icon {
        margin-right: 10px;
        color: var(--ka-accent); 
        font-size: 1.2em;
        width: 20px; 
        text-align: center;
    }
    .data-label {
        font-weight: 600; 
        color: var(--ka-primary);
        font-size: 0.9em; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
    }
    .data-value {
        font-weight: 500;
        color: #4B5563; 
        padding-left: 35px; 
        font-size: 1.0em;
        line-height: 1.5;
    }
    /* Warna Aksen Khusus Baris Data */
    .data-row[data-type="contact"] { border-color: var(--row-contact); } 
    .data-row[data-type="contact"] .data-label-icon { color: var(--row-contact); }
    .data-row[data-type="schedule"] { border-color: var(--row-schedule); } 
    .data-row[data-type="schedule"] .data-label-icon { color: var(--row-schedule); }
    .data-row[data-type="location"] { border-color: var(--row-location); } 
    .data-row[data-type="location"] .data-label-icon { color: var(--row-location); }
    .data-row[data-type="coordinate"] { border-color: var(--row-coordinate); } 
    .data-row[data-type="coordinate"] .data-label-icon { color: var(--row-coordinate); }
    .data-row[data-type="note"] { border-color: #9CA3AF; } 
    .data-row[data-type="note"] .data-label-icon { color: #9CA3AF; }
    /* END: GAYA DATA ROW ELEGAN */
    
    /* GAYA PROFIL HEADER */
    .header-content-wrapper { display: flex; flex-direction: column; }
    .kotak-amal-header-profile { 
        display: flex; flex-direction: column; align-items: center; gap: 5px; margin-bottom: 30px; 
        padding: 30px 40px; border-radius: 20px; background: var(--profile-bg); border: 2px solid var(--profile-border); 
        box-shadow: 0 10px 30px var(--profile-shadow); max-width: 320px; text-align: center; 
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .kotak-amal-header-profile:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 15px 40px var(--profile-shadow); }
    
    /* MODIFIKASI FOTO PROFIL */
    .header-profile-img { 
        width: 150px; /* Ukuran lebih besar */
        height: 100px; /* Menyesuaikan tinggi, bisa juga auto */
        object-fit: cover;
        border-radius: 8px; /* Sudut sedikit membulat (tidak bulat penuh) */
        border: 4px solid #fff; /* Border putih */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Bayangan lembut */
        flex-shrink: 0;
        margin-bottom: 15px; /* Jarak dari teks di bawah */
    }
    
    .header-profile-text { margin-top: 10px; display: flex; flex-direction: column; align-items: center; }
    .header-profile-name { font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 1.4em; margin: 0; color: var(--ka-primary); }
    .header-profile-id { color: #6B7280; font-size: 0.9em; font-weight: 500; display: block; margin-top: 5px; letter-spacing: 0.5px; }
    .btn-edit-profile-ka { 
        background-color: var(--btn-edit); color: white; font-size: 0.9em; padding: 8px 18px; border-radius: 10px; 
        margin-top: 15px; display: inline-flex; align-items: center; gap: 8px; font-weight: 700; text-decoration: none; 
        transition: background-color 0.2s, transform 0.2s; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); 
    }
    .btn-edit-profile-ka:hover { background-color: #0c9c6f; transform: translateY(-2px); }
    .profile-info { margin-top: 0; }
</style>

<div class="header-content-wrapper">
    <h1 class="dashboard-title"><i class="fas fa-search-location"></i> Profil & Lokasi Kotak Amal</h1>

    <div class="kotak-amal-header-profile">
        <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Kotak Amal" class="header-profile-img">
        <div class="header-profile-text">
            <p class="header-profile-name"><?php echo htmlspecialchars($data_ka['Nama_Toko']); ?></p>
            <small class="header-profile-id"><?php echo htmlspecialchars($data_ka['ID_KotakAmal']); ?></small>
            
            <a href="edit_kotak_amal.php?id=<?php echo $data_ka['ID_KotakAmal']; ?>" class="btn btn-edit-profile-ka" title="Edit Data Kotak Amal">
                <i class="fas fa-edit"></i> Edit Data
            </a>
        </div>
    </div>
    </div>


<div class="detail-card">
    
    <div class="profile-info">
        <h2><i class="fas fa-address-card"></i> Data Toko & Kontak</h2>
        
        <div style="margin-top: 20px;"> 
            
            <div class="data-row" data-type="contact">
                <div class="data-label-group">
                    <i class="fas fa-user data-label-icon"></i>
                    <span class="data-label">Nama Pemilik:</span>
                </div>
                <span class="data-value"><?php echo htmlspecialchars($data_ka['Nama_Pemilik'] ?? 'Belum Tercatat'); ?></span>
            </div>
            
            <div class="data-row" data-type="contact">
                <div class="data-label-group">
                    <i class="fas fa-whatsapp data-label-icon"></i>
                    <span class="data-label">Nomor WhatsApp:</span>
                </div>
                <span class="data-value"><?php echo htmlspecialchars($data_ka['WA_Pemilik'] ?? 'Belum Tercatat'); ?></span>
            </div>
            
            <div class="data-row" data-type="contact">
                <div class="data-label-group">
                    <i class="fas fa-envelope data-label-icon"></i>
                    <span class="data-label">Email:</span>
                </div>
                <span class="data-value"><?php echo htmlspecialchars($data_ka['Email'] ?? 'Belum Tercatat'); ?></span>
            </div>
            
            <div class="data-row" data-type="schedule">
                <div class="data-label-group">
                    <i class="fas fa-calendar-alt data-label-icon"></i>
                    <span class="data-label">Jadwal Pengambilan:</span>
                </div>
                <span class="data-value"><?php echo htmlspecialchars($data_ka['Jadwal_Pengambilan'] ?? 'Tidak Rutin'); ?></span>
            </div>
            
            <div class="data-row" data-type="note">
                <div class="data-label-group">
                    <i class="fas fa-sticky-note data-label-icon"></i>
                    <span class="data-label">Keterangan Tambahan:</span>
                </div>
                <span class="data-value"><?php echo htmlspecialchars($data_ka['Ket'] ?? 'Tidak ada keterangan.'); ?></span>
            </div>
            
        </div> 
        
    </div>
    
    <div class="location-info">
        <h2><i class="fas fa-map-marker-alt"></i> Lokasi & Peta</h2>
        
        <div class="data-row" data-type="location">
            <div class="data-label-group">
                <i class="fas fa-map-pin data-label-icon"></i>
                <span class="data-label">Alamat Lengkap:</span>
            </div>
            <span class="data-value"><?php echo htmlspecialchars($data_ka['Alamat_Toko'] ?? 'Koordinat Belum Dicatat'); ?></span>
        </div>
        
        <div class="data-row" data-type="coordinate">
            <div class="data-label-group">
                <i class="fas fa-globe data-label-icon"></i>
                <span class="data-label">Latitude:</span>
            </div>
            <span class="data-value"><?php echo htmlspecialchars($latitude); ?></span>
        </div>
        
        <div class="data-row" data-type="coordinate">
            <div class="data-label-group">
                <i class="fas fa-globe data-label-icon"></i>
                <span class="data-label">Longitude:</span>
            </div>
            <span class="data-value"><?php echo htmlspecialchars($longitude); ?></span>
        </div>
        
        <p style="margin-top: 25px; font-weight: 600; color: var(--ka-primary);">Tampilan Peta:</p>
        <iframe src="<?php echo $map_link; ?>" class="map-frame" allowfullscreen="" loading="lazy"></iframe>
        
        <a href="kotak-amal.php" class="btn btn-cancel" style="margin-top: 15px; width: 100%;"><i class="fas fa-arrow-left"></i> Kembali ke Manajemen Kotak Amal</a>
    </div>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>