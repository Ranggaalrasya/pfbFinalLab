<?php
// index.php - ini halaman depan (landing page) DocHelp
$pageTitle = 'Home';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// ambil 3 dokter secara acak dari database sesuai permintaan soal
$sql = "SELECT d.id, d.doctor_name, d.bio, d.price_rate, d.rating, d.clinic_location, s.specialty_name 
        FROM doctors d 
        JOIN specialties s ON d.specialty_id = s.id 
        ORDER BY RAND() 
        LIMIT 3";
$result = $conn->query($sql);
?>

<<<<<<< HEAD
<!-- dekorasi hero banner atas -->
<div class="hero-banner">
  <div>
    <span class="hero-pill">&#x1FA7A; Platform Janji Temu Dokter Terpercaya</span>
    <h1 class="page-title" style="margin-bottom: 10px;">DocHelp - Doctor Appointment Booking</h1>
    <p class="section-desc" style="margin-bottom: 0;">Solusi praktis konsultasi kesehatan bareng dokter spesialis pilihan. Cepat, nyaman, dan terpercaya.</p>
    
    <!-- dekorasi statistik pencapaian DocHelp -->
    <div class="hero-stats">
      <div class="stat-box">
        <div class="stat-number">50+</div>
        <div class="stat-label">Dokter Spesialis</div>
      </div>
      <div class="stat-box">
        <div class="stat-number">4.9/5</div>
        <div class="stat-label">Rating Kepuasan</div>
      </div>
      <div class="stat-box">
        <div class="stat-number">24/7</div>
        <div class="stat-label">Booking Fleksibel</div>
      </div>
      <div class="stat-box">
        <div class="stat-number">100%</div>
        <div class="stat-label">Terverifikasi</div>
      </div>
    </div>
  </div>
</div>

<div class="hero-section" style="margin-top: 10px; margin-bottom: 18px;">
  <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a;">Meet some of our doctors!</h2>
  <p class="section-desc" style="margin-bottom: 16px;">Pilih dokter terbaik sesuai kebutuhan kesehatanmu hari ini.</p>
</div>

=======
<!-- bagian judul utama / sambutan -->
<div class="hero-section">
  <h1 class="page-title">DocHelp - Doctor Appointment Booking</h1>
  <p class="section-desc">Meet some of our doctors!</p>
</div>

>>>>>>> 547b7aaeffa3c8eab2aaeff26c599cb1bab36230
<!-- nampilin kartu dokter dalam bentuk grid yang rapi -->
<div class="cards-grid">
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($doctor = $result->fetch_assoc()): ?>
<<<<<<< HEAD
      <!-- kartu per dokter dengan efek hover halus -->
      <div class="card card-hover">
        <div class="doctor-avatar-badge">
          <?php echo strtoupper(substr($doctor['doctor_name'], 0, 1)); ?>
        </div>
=======
      <!-- kartu per dokter -->
      <div class="card">
>>>>>>> 547b7aaeffa3c8eab2aaeff26c599cb1bab36230
        <h2 class="card-title"><?php echo htmlspecialchars($doctor['doctor_name']); ?></h2>
        <p class="card-info"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
        <p class="card-info"><strong>Rating:</strong> &#x2B50; <?php echo number_format($doctor['rating'], 1); ?>/5</p>
        <p class="card-info"><strong>Fee:</strong> Rp <?php echo number_format($doctor['price_rate'], 0, ',', '.'); ?></p>
        <p class="card-info"><strong>Location:</strong> &#x1F4CD; <?php echo htmlspecialchars($doctor['clinic_location']); ?></p>
        <div class="card-actions">
          <!-- tombol buat kepoin profil lengkap sama jadwal dokternya -->
          <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="btn btn-primary">View Profile</a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <!-- pesan kalo databasenya masih kosong melompong -->
    <p>Belum ada dokter yang terdaftar nih.</p>
  <?php endif; ?>
</div>

<!-- dekorasi section keunggulan DocHelp -->
<section class="features-section">
  <div style="text-align: center; max-width: 600px; margin: 0 auto 24px;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Kenapa Memilih DocHelp?</h2>
    <p style="color: #64748b; font-size: 0.95rem;">Layanan kesehatan modern yang mengutamakan kenyamanan dan kepastian jadwal Anda.</p>
  </div>

  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon">&#x1F6E1;&#xFE0F;</div>
      <h3 class="feature-title">Dokter Berlisensi</h3>
      <p class="feature-text">Semua tenaga medis telah melalui verifikasi kualifikasi resmi dengan ulasan transparan dari pasien.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">&#x1F4C5;</div>
      <h3 class="feature-title">Jadwal Slot Real-Time</h3>
      <p class="feature-text">Lihat slot jam yang benar-benar kosong secara langsung dan bebas antre panjang di lokasi klinik.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">&#x1F4B3;</div>
      <h3 class="feature-title">Pembayaran Transparan</h3>
      <p class="feature-text">Rincian biaya konsultasi jelas tanpa biaya tersembunyi, dengan bukti transfer terverifikasi aman.</p>
    </div>
  </div>
</section>

<!-- dekorasi banner Call-To-Action di bawah -->
<div class="cta-banner">
  <div class="cta-text">
    <h3>Siap Berkonsultasi dengan Dokter Pilihanmu?</h3>
    <p>Daftar sekarang atau langsung cari dokter spesialis yang sesuai dengan keluhanmu.</p>
  </div>
  <div>
    <?php if (isLoggedIn()): ?>
      <a href="doctors.php" class="btn btn-white">Lihat Semua Dokter</a>
    <?php else: ?>
      <a href="register.php" class="btn btn-white">Daftar Sekarang</a>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
