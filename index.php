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

<!-- bagian judul utama / sambutan -->
<div class="hero-section">
  <h1 class="page-title">DocHelp - Doctor Appointment Booking</h1>
  <p class="section-desc">Meet some of our doctors!</p>
</div>

<!-- nampilin kartu dokter dalam bentuk grid yang rapi -->
<div class="cards-grid">
  <?php if ($result && $result->num_rows > 0): ?>
    <?php while ($doctor = $result->fetch_assoc()): ?>
      <!-- kartu per dokter -->
      <div class="card">
        <h2 class="card-title"><?php echo htmlspecialchars($doctor['doctor_name']); ?></h2>
        <p class="card-info"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
        <p class="card-info"><strong>Rating:</strong> <?php echo number_format($doctor['rating'], 1); ?>/5</p>
        <p class="card-info"><strong>Fee:</strong> Rp <?php echo number_format($doctor['price_rate'], 0, ',', '.'); ?></p>
        <p class="card-info"><strong>Location:</strong> <?php echo htmlspecialchars($doctor['clinic_location']); ?></p>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
