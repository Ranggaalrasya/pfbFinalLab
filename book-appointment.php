<?php
// book-appointment.php - Member Appointment Booking
$pageTitle = 'Appointment Booking';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireMember();

$memberId = $_SESSION['user_id'];
$errors = [];
$availableSlots = [];
$slotsChecked = false;

$selectedDoctorId = intval($_GET['doctor_id'] ?? ($_POST['doctor_id'] ?? 0));
$selectedDate = trim($_POST['appointment_date'] ?? ($_GET['date'] ?? ''));
$selectedSlot = trim($_POST['time_slot'] ?? '');

// Fetch all doctors for dropdown
$allDoctors = $conn->query("SELECT id, doctor_name FROM doctors ORDER BY doctor_name ASC");

// Function to calculate slots
function getDoctorAvailableSlots($conn, $doctorId, $date) {
    if ($doctorId <= 0 || empty($date)) return [];
    
    $dayOfWeek = date('l', strtotime($date)); // e.g., 'Monday'
    
    // Check doctor schedule on that day
    $stmt = $conn->prepare("SELECT start_time, end_time FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $doctorId, $dayOfWeek);
    $stmt->execute();
    $schRes = $stmt->get_result();
    
    if (!$schRes || $schRes->num_rows === 0) {
        $stmt->close();
        return [];
    }
    
    $schedule = $schRes->fetch_assoc();
    $stmt->close();
    
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    
    // Generate hourly slots
    $allSlots = [];
    $curr = $startTime;
    while ($curr < $endTime) {
        $allSlots[] = date('H:i:00', $curr);
        $curr = strtotime('+1 hour', $curr);
    }
    
    // Query already booked slots for this doctor on this date
    $stmtBooked = $conn->prepare("SELECT time_slot FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'Canceled'");
    $stmtBooked->bind_param("is", $doctorId, $date);
    $stmtBooked->execute();
    $bookedRes = $stmtBooked->get_result();
    
    $bookedSlots = [];
    while ($row = $bookedRes->fetch_assoc()) {
        $bookedSlots[] = date('H:i:00', strtotime($row['time_slot']));
    }
    $stmtBooked->close();
    
    // Available = All - Booked
    return array_values(array_diff($allSlots, $bookedSlots));
}

// Handle "Show Slots" action
if (isset($_POST['action']) && $_POST['action'] === 'show_slots') {
    $slotsChecked = true;
    if ($selectedDoctorId <= 0) {
        $errors[] = "Please select a doctor.";
    }
    if (empty($selectedDate)) {
        $errors[] = "Please select an appointment date.";
    } elseif ($selectedDate < date('Y-m-d')) {
        $errors[] = "Date Appointment must be present or future date.";
    } else {
        $availableSlots = getDoctorAvailableSlots($conn, $selectedDoctorId, $selectedDate);
        if (empty($availableSlots)) {
            $errors[] = "No available time slots on this date (Doctor is either not practicing or fully booked).";
        }
    }
}

// Handle "Book Now" submission
if (isset($_POST['action']) && $_POST['action'] === 'book_now') {
    if ($selectedDoctorId <= 0) {
        $errors[] = "Please select a doctor.";
    }
    if (empty($selectedDate)) {
        $errors[] = "Please select an appointment date.";
    } elseif ($selectedDate < date('Y-m-d')) {
        $errors[] = "Date Appointment must be present or future date.";
    }

    if (empty($selectedSlot)) {
        $errors[] = "Please select an available time slot.";
    } else {
        // Validate slot strictly
        $validSlots = getDoctorAvailableSlots($conn, $selectedDoctorId, $selectedDate);
        $normalizedSlot = date('H:i:00', strtotime($selectedSlot));
        if (!in_array($normalizedSlot, $validSlots)) {
            $errors[] = "Selected time slot is not available or outside the doctor's shift.";
        }
    }

    if (empty($errors)) {
        $status = 'Waiting';
        $normalizedSlot = date('H:i:00', strtotime($selectedSlot));
        $stmtInsert = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, time_slot, status) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("iisss", $memberId, $selectedDoctorId, $selectedDate, $normalizedSlot, $status);

        if ($stmtInsert->execute()) {
            $newAppointmentId = $stmtInsert->insert_id;
            $stmtInsert->close();
            header("Location: payment.php?appointment_id=" . $newAppointmentId);
            exit();
        } else {
            $errors[] = "Booking error: " . $conn->error;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Appointment Booking</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="book-appointment.php" id="bookingForm">
    <div class="form-group">
      <label for="doctor_id" class="form-label">Select Doctor</label>
      <select id="doctor_id" name="doctor_id" class="form-control" required>
        <option value="">-- Choose Doctor --</option>
        <?php if ($allDoctors): ?>
          <?php while ($d = $allDoctors->fetch_assoc()): ?>
            <option value="<?php echo $d['id']; ?>" <?php echo $selectedDoctorId == $d['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($d['doctor_name']); ?>
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="appointment_date" class="form-label">Date Appointment</label>
      <input type="date" id="appointment_date" name="appointment_date" class="form-control" 
             value="<?php echo htmlspecialchars($selectedDate); ?>" 
             min="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <button type="submit" name="action" value="show_slots" class="btn btn-primary" style="margin-bottom: 20px;">Show Slots</button>

    <?php if ($slotsChecked && !empty($availableSlots)): ?>
      <hr style="margin: 20px 0; border: 0; border-top: 1px solid #e2e8f0;">
      
      <div class="form-group">
        <label for="time_slot" class="form-label">Available Time Slots</label>
        <select id="time_slot" name="time_slot" class="form-control" required>
          <?php foreach ($availableSlots as $slot): ?>
            <option value="<?php echo $slot; ?>" <?php echo $selectedSlot === $slot ? 'selected' : ''; ?>>
              <?php echo date('H:i', strtotime($slot)); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" name="action" value="book_now" class="btn btn-primary">Book Now</button>
    <?php endif; ?>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
