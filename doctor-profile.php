<?php
// doctor-profile.php - halaman detail profil dokter dan jadwal praktek mingguannya
$pageTitle = 'Doctor Profile';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// ambil ID dokter dari parameter URL
$doctorId = intval($_GET['id'] ?? 0);

if ($doctorId <= 0) {
    header("Location: doctors.php");
    exit();
}

// ambil detail lengkap si dokter + nama spesialisasi dari tabel relasi
$stmt = $conn->prepare("SELECT d.*, s.specialty_name 
                        FROM doctors d 
                        JOIN specialties s ON d.specialty_id = s.id 
                        WHERE d.id = ?");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$doctorRes = $stmt->get_result();

if (!$doctorRes || $doctorRes->num_rows === 0) {
    echo "<p>Dokter ga ditemukan cuy. <a href='doctors.php'>Balik ke daftar dokter</a></p>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$doctor = $doctorRes->fetch_assoc();
$stmt->close();

// ambil jadwal praktek dokter ini, diurutin dari Senin sampe Minggu
$stmtSched = $conn->prepare("SELECT day_of_week, start_time, end_time FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
$stmtSched->bind_param("i", $doctorId);
$stmtSched->execute();
$schedulesRes = $stmtSched->get_result();
?>

<h1 class="page-title">Doctor Profile</h1>

<!-- kartu info biodata dokter -->
<div class="card" style="margin-bottom: 24px;">
  <h2 class="card-title"><?php echo htmlspecialchars($doctor['doctor_name']); ?></h2>
  <p class="card-info"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
  <p class="card-info"><strong>Rating:</strong> <?php echo number_format($doctor['rating'], 1); ?>/5</p>
  <p class="card-info"><strong>Fee:</strong> Rp <?php echo number_format($doctor['price_rate'], 0, ',', '.'); ?></p>
  <p class="card-info"><strong>Clinic Location:</strong> <?php echo htmlspecialchars($doctor['clinic_location']); ?></p>
  <p class="card-info"><strong>Experience/Bio:</strong><br><?php echo nl2br(htmlspecialchars($doctor['bio'] ?? 'Belum ada bio yang ditulis.')); ?></p>
</div>

<!-- tabel jadwal praktek dokter -->
<div class="card" style="margin-bottom: 24px;">
  <h3 style="margin-bottom: 14px; font-size: 1.15rem;">Practice Schedule</h3>
  <div class="table-container" style="margin-top: 0;">
    <table class="custom-table">
      <thead>
        <tr>
          <th>Day</th>
          <th>Start Time</th>
          <th>End Time</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($schedulesRes && $schedulesRes->num_rows > 0): ?>
          <?php while ($sch = $schedulesRes->fetch_assoc()): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($sch['day_of_week']); ?></strong></td>
              <td><?php echo date('H:i', strtotime($sch['start_time'])); ?></td>
              <td><?php echo date('H:i', strtotime($sch['end_time'])); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="3">Dokter ini belum ngatur jadwal praktek.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- tombol buat bikin janji temu -->
<div style="margin-bottom: 30px;">
  <?php if (isMember()): ?>
    <!-- kalo dia member yang udah login, cus langsung ke form booking -->
    <a href="book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
  <?php elseif (isAdmin()): ?>
    <!-- admin tugasnya memanage, bukan berobat, jadi di-disable -->
    <button class="btn btn-outline" disabled title="Admin ga bisa bikin janji temu ya">Book Appointment (Member Only)</button>
  <?php else: ?>
    <!-- kalo masih tamu (guest), suruh login dulu baru dioper ke booking -->
    <a href="login.php?redirect=book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
  <?php endif; ?>
</div>

<?php 
$stmtSched->close();
require_once __DIR__ . '/includes/footer.php'; 
?>
