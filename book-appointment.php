<?php
// book-appointment.php - halaman buat member reservasi janji temu sama dokter pilihan
$pageTitle = 'Appointment Booking';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// kunci pintu: cuma member yang berhak bikin janji
requireMember();

$memberId = $_SESSION['user_id'];
$errors = [];
$availableSlots = [];
$slotsChecked = false;

// tangkap inputan dokter, tanggal, dan slot jam yang dipilih
$selectedDoctorId = intval($_GET['doctor_id'] ?? ($_POST['doctor_id'] ?? 0));
$selectedDate = trim($_POST['appointment_date'] ?? ($_GET['date'] ?? ''));
$selectedSlot = trim($_POST['time_slot'] ?? '');

// tarik semua dokter yang terdaftar buat isi opsi dropdown
$allDoctors = $conn->query("SELECT id, doctor_name FROM doctors ORDER BY doctor_name ASC");

// fungsi pinter buat ngitung slot jam yang beneran masih kosong
function getDoctorAvailableSlots($conn, $doctorId, $date) {
    if ($doctorId <= 0 || empty($date)) return [];
    
    // cari tau ini hari apa (Senin, Selasa, dst)
    $dayOfWeek = date('l', strtotime($date));
    
    // cek jam praktek dokter di hari tersebut
    $stmt = $conn->prepare("SELECT start_time, end_time FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $doctorId, $dayOfWeek);
    $stmt->execute();
    $schRes = $stmt->get_result();
    
    if (!$schRes || $schRes->num_rows === 0) {
        $stmt->close();
        return []; // dokter ga praktek di hari ini
    }
    
    $schedule = $schRes->fetch_assoc();
    $stmt->close();
    
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    
    // generate slot per 1 jam
    $allSlots = [];
    $curr = $startTime;
    while ($curr < $endTime) {
        $allSlots[] = date('H:i:00', $curr);
        $curr = strtotime('+1 hour', $curr);
    }
    
    // cek siapa aja yang udah booking dokter ini di tanggal yang sama (yang belum di-cancel)
    $stmtBooked = $conn->prepare("SELECT time_slot FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'Canceled'");
    $stmtBooked->bind_param("is", $doctorId, $date);
    $stmtBooked->execute();
    $bookedRes = $stmtBooked->get_result();
    
    $bookedSlots = [];
    while ($row = $bookedRes->fetch_assoc()) {
        $bookedSlots[] = date('H:i:00', strtotime($row['time_slot']));
    }
    $stmtBooked->close();
    
    // slot kosong = semua slot dikurangin slot yang udah dibooking orang
    return array_values(array_diff($allSlots, $bookedSlots));
}

// pas tombol "Show Slots" dipencet
if (isset($_POST['action']) && $_POST['action'] === 'show_slots') {
    $slotsChecked = true;
    if ($selectedDoctorId <= 0) {
        $errors[] = "Pilih dokternya dulu dong.";
    }
    if (empty($selectedDate)) {
        $errors[] = "Pilih tanggal janjinya juga ya.";
    } elseif ($selectedDate < date('Y-m-d')) {
        $errors[] = "Tanggal appointment harus hari ini atau tanggal di masa depan ya cuy!";
    } else {
        $availableSlots = getDoctorAvailableSlots($conn, $selectedDoctorId, $selectedDate);
        if (empty($availableSlots)) {
            $errors[] = "Waduh ga ada slot jam yang kosong di tanggal ini (antara dokternya libur atau udah full booking).";
        }
    }
}

// pas tombol "Book Now" ditekan
if (isset($_POST['action']) && $_POST['action'] === 'book_now') {
    if ($selectedDoctorId <= 0) {
        $errors[] = "Dokter wajib dipilih.";
    }
    if (empty($selectedDate)) {
        $errors[] = "Tanggal appointment wajib diisi.";
    } elseif ($selectedDate < date('Y-m-d')) {
        $errors[] = "Tanggal appointment ga boleh tanggal masa lalu ya.";
    }

    if (empty($selectedSlot)) {
        $errors[] = "Pilih salah satu jam yang masih kosong.";
    } else {
        // validasi ketat biar ga ada yang curang nyuntik slot yang udah diambil
        $validSlots = getDoctorAvailableSlots($conn, $selectedDoctorId, $selectedDate);
        $normalizedSlot = date('H:i:00', strtotime($selectedSlot));
        if (!in_array($normalizedSlot, $validSlots)) {
            $errors[] = "Slot jam yang kamu pilih udah ga tersedia atau di luar jam kerja dokter.";
        }
    }

    // kalo validasi aman, simpen data appointment ke database
    if (empty($errors)) {
        $status = 'Waiting';
        $normalizedSlot = date('H:i:00', strtotime($selectedSlot));
        $stmtInsert = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, time_slot, status) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("iisss", $memberId, $selectedDoctorId, $selectedDate, $normalizedSlot, $status);

        if ($stmtInsert->execute()) {
            $newAppointmentId = $stmtInsert->insert_id;
            $stmtInsert->close();
            // langsung oper member ke halaman pembayaran
            header("Location: payment.php?appointment_id=" . $newAppointmentId);
            exit();
        } else {
            $errors[] = "Gagal ngebooking: " . $conn->error;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Appointment Booking</h1>

<!-- alert kalo ada kesalahan input -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- KARTU 1: milih dokter & tanggal -->
<div class="form-card">
  <form method="POST" action="book-appointment.php" id="slotsForm">
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

    <button type="submit" name="action" value="show_slots" class="btn btn-primary">Show Slots</button>
  </form>
</div>

<!-- KARTU 2: muncul pas slot jam udah dicek dan ada yang kosong -->
<?php if ($slotsChecked && !empty($availableSlots)): ?>
  <div class="form-card">
    <form method="POST" action="book-appointment.php" id="bookingForm">
      <input type="hidden" name="doctor_id" value="<?php echo $selectedDoctorId; ?>">
      <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

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
    </form>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
