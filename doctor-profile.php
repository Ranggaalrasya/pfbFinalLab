<?php
// doctor-profile.php - DocHelp Doctor Profile
$pageTitle = 'Doctor Profile';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$doctorId = intval($_GET['id'] ?? 0);

if ($doctorId <= 0) {
    header("Location: doctors.php");
    exit();
}

// Fetch doctor details
$stmt = $conn->prepare("SELECT d.*, s.specialty_name 
                        FROM doctors d 
                        JOIN specialties s ON d.specialty_id = s.id 
                        WHERE d.id = ?");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$doctorRes = $stmt->get_result();

if (!$doctorRes || $doctorRes->num_rows === 0) {
    echo "<p>Doctor not found. <a href='doctors.php'>Return to listing</a></p>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$doctor = $doctorRes->fetch_assoc();
$stmt->close();

// Fetch practice schedules
$stmtSched = $conn->prepare("SELECT day_of_week, start_time, end_time FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
$stmtSched->bind_param("i", $doctorId);
$stmtSched->execute();
$schedulesRes = $stmtSched->get_result();
?>

<h1 class="page-title">Doctor Profile</h1>

<div class="card" style="margin-bottom: 24px;">
  <h2 class="card-title"><?php echo htmlspecialchars($doctor['doctor_name']); ?></h2>
  <p class="card-info"><strong>Specialty:</strong> <?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
  <p class="card-info"><strong>Rating:</strong> <?php echo number_format($doctor['rating'], 1); ?>/5</p>
  <p class="card-info"><strong>Fee:</strong> Rp <?php echo number_format($doctor['price_rate'], 0, ',', '.'); ?></p>
  <p class="card-info"><strong>Clinic Location:</strong> <?php echo htmlspecialchars($doctor['clinic_location']); ?></p>
  <p class="card-info"><strong>Experience/Bio:</strong><br><?php echo nl2br(htmlspecialchars($doctor['bio'] ?? 'No bio provided.')); ?></p>
</div>

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
            <td colspan="3">No schedule registered for this doctor.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="margin-bottom: 30px;">
  <?php if (isLoggedIn()): ?>
    <a href="book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
  <?php else: ?>
    <a href="login.php?redirect=book-appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Appointment</a>
  <?php endif; ?>
</div>

<?php 
$stmtSched->close();
require_once __DIR__ . '/includes/footer.php'; 
?>
