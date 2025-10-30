<?php
session_start();
include '../config/database.php';
// Note: $base_url is defined in '../includes/header.php'

// Authorization check: Hanya Pimpinan, Kepala LKSA, dan Petugas Kotak Amal yang bisa mengakses
if ($_SESSION['jabatan'] != 'Pimpinan' && $_SESSION['jabatan'] != 'Kepala LKSA' && $_SESSION['jabatan'] != 'Petugas Kotak Amal') {
    die("Akses ditolak.");
}

// Ambil ID pengguna dan LKSA dari sesi
$id_user = $_SESSION['id_user'];
$id_lksa = $_SESSION['id_lksa'];

$sidebar_stats = ''; // Pastikan sidebar tampil

include '../includes/header.php'; 

// --- ROBUST PATH FIX: Menghitung jalur root-relative yang stabil ---
$script_path = $_SERVER['PHP_SELF']; 
$api_path = str_replace(basename($script_path), 'get_wilayah.php', $script_path);
// -----------------------------------------------------------------
?>
<div class="form-container">
    <h1>Tambah Kotak Amal Baru</h1>
    <form action="proses_kotak_amal.php" method="POST" enctype="multipart/form-data" id="kotakAmalForm">
        <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($id_user); ?>">
        <input type="hidden" name="id_lksa" value="<?php echo htmlspecialchars($id_lksa); ?>">
        
        <input type="hidden" name="alamat_toko" id="alamat_toko_hidden_final">
        
        <div class="form-section">
            <h2>Informasi Tempat</h2>
            <div class="form-group">
                <label>Nama Tempat:</label>
                <input type="text" name="nama_toko" required>
            </div>
        </div>

        <div class="form-section">
            <h2>Dapatkan Lokasi Sekarang</h2>
            <div class="form-group">
                <p>Klik tombol di bawah ini untuk mengambil Latitude dan Longitude otomatis dari perangkat Anda.</p>
                
                <button type="button" id="getLocationButton" class="btn btn-primary" style="background-color: #F97316; margin-bottom: 15px;">
                    <i class="fas fa-location-arrow"></i> Simpan Lokasi Sekarang
                </button>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Latitude:</label>
                    <input type="text" id="latitude" name="latitude" readonly required placeholder="Otomatis terisi setelah tombol diklik.">
                </div>
                <div class="form-group">
                    <label>Longitude:</label>
                    <input type="text" id="longitude" name="longitude" readonly required placeholder="Otomatis terisi setelah tombol diklik.">
                </div>
            </div>
            <small>Koordinat ini akan tersimpan saat Anda menekan tombol "Simpan Kotak Amal".</small>
        </div>

        <div class="form-section">
            <h2>Informasi Pemilik</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Pemilik:</label>
                    <input type="text" name="nama_pemilik">
                </div>
                <div class="form-group">
                    <label>Nomor WA Pemilik:</label>
                    <input type="text" name="wa_pemilik">
                </div>
            </div>
            <div class="form-group">
                <label>Email Pemilik:</label>
                <input type="email" name="email_pemilik">
            </div>
        </div>
        
        <div class="form-section">
            <h2>Informasi Lainnya</h2>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Jadwal Pengambilan (Tanggal Mulai):</label>
                    <input type="date" name="jadwal_pengambilan" required> 
                </div>
                <div class="form-group">
                    <label>Unggah Foto:</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label>Alamat Lengkap (Manual):</label>
                <textarea name="alamat_detail_manual" id="alamat_detail_manual" rows="4" required placeholder="Masukkan Alamat Lengkap: Nama Jalan, RT/RW, Desa, Kecamatan, Kabupaten, Provinsi"></textarea>
            </div>
            
            <div class="form-group">
                <label>Keterangan:</label>
                <textarea name="keterangan" rows="4" cols="50"></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Kotak Amal</button>
            <a href="kotak-amal.php" class="btn btn-cancel"><i class="fas fa-times-circle"></i> Batal</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('kotakAmalForm');
    const finalAddressInput = document.getElementById('alamat_toko_hidden_final');
    const manualAddressInput = document.getElementById('alamat_detail_manual');
    
    // --- Geolocation Logic (Dipertahankan) ---
    const getLocationButton = document.getElementById('getLocationButton');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    function getLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error("Browser tidak mendukung geolocation."));
            }
            const options = { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 };
            navigator.geolocation.getCurrentPosition(pos => resolve(pos.coords), err => reject(err), options);
        });
    }

    getLocationButton.addEventListener('click', async () => {
        try {
            Swal.fire({ title: 'Mengambil Lokasi...', text: 'Mohon tunggu sebentar. Pastikan izin lokasi diaktifkan.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            const coords = await getLocation();
            const { latitude, longitude } = coords;
            latitudeInput.value = latitude.toFixed(8);
            longitudeInput.value = longitude.toFixed(8);
            Swal.fire({ icon: 'success', title: 'Lokasi Berhasil Diambil!', text: `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}. Data siap disimpan.`, confirmButtonColor: '#10B981', });
        } catch (err) {
            Swal.close();
            let errorMessage = 'Tidak bisa mendapatkan lokasi. Pastikan izin lokasi diaktifkan di browser Anda.';
            if (err.code === 1) { errorMessage = 'Anda menolak izin untuk mengakses lokasi.'; } 
            else if (err.code === 2) { errorMessage = 'Lokasi tidak tersedia atau gagal mendapatkan lokasi.'; } 
            else if (err.code === 3) { errorMessage = 'Waktu pengambilan lokasi habis. Coba lagi.'; } 
            else { errorMessage = `Terjadi kesalahan saat mengambil lokasi.`; }
            Swal.fire('Error!', errorMessage, 'error');
        }
    });

    // ====================================================================
    // === Logic Submit yang Disederhanakan ===
    // ====================================================================
    
    form.addEventListener('submit', (e) => {
        
        const alamatDetail = manualAddressInput.value.trim();
        
        if (!alamatDetail) {
            e.preventDefault(); 
            Swal.fire('Peringatan', 'Mohon isi Alamat Lengkap (Manual) tempat Kotak Amal.', 'warning');
            return; 
        }
        
        // Mengirim alamat manual ke hidden field yang digunakan oleh proses_kotak_amal.php
        finalAddressInput.value = alamatDetail;

        // Form akan disubmit secara otomatis
    });

});
</script>

<?php
include '../includes/footer.php';
$conn->close();
?>