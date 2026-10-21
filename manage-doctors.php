<?php
// manage-doctors.php - menu sakti admin buat ngatur data dokter dan jadwal praktek mingguan
$pageTitle = 'Manage Doctors';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// satpam: cuma admin yang boleh masuk
requireAdmin();

$errors = [];
$success = '';

// nangkep pesan sukses dari query string (hasil redirect)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success = "Dokter baru berhasil didaftarkan ke sistem!";
    elseif ($_GET['msg'] === 'updated') $success = "Data dokter & jadwal praktek berhasil diperbarui!";
    elseif ($_GET['msg'] === 'deleted') $success = "Dokter berhasil dihapus beserta jadwal prakteknya.";
}

// daftar 7 hari seminggu
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// tarik list spesialisasi buat ngisi pilihan dropdown
$specialtiesRes = $conn->query("SELECT * FROM specialties ORDER BY specialty_name ASC");
$allSpecialties = [];
while ($s = $specialtiesRes->fetch_assoc()) {
    $allSpecialties[] = $s;
}

// aksi kalo admin ngeklik tombol delete dokter
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage-doctors.php?msg=deleted");
        exit();
    } else {
        $errors[] = "Gagal ngehapus dokter: " . $conn->error;
        $stmt->close();
    }
}

// variabel penampung form
$editId = intval($_GET['edit'] ?? 0);
$doctorName = '';
$specialtyId = 0;
$bio = '';
$fee = '';
$rating = 4.5;
$location = '';
$schedulesData = [];

// kalo lagi mode edit, isi form pake data lama dari database
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $dRes = $stmt->get_result();
    if ($d = $dRes->fetch_assoc()) {
        $doctorName = $d['doctor_name'];
        $specialtyId = $d['specialty_id'];
        $bio = $d['bio'];
        $fee = $d['price_rate'];
        $rating = $d['rating'];
        $location = $d['clinic_location'];

        // tarik jadwal praktek dokter yang bersangkutan
        $stmtS = $conn->prepare("SELECT day_of_week, start_time, end_time FROM schedules WHERE doctor_id = ?");
        $stmtS->bind_param("i", $editId);
        $stmtS->execute();
        $sRes = $stmtS->get_result();
        while ($sRow = $sRes->fetch_assoc()) {
            $schedulesData[$sRow['day_of_week']] = [
                'enabled' => true,
                'start' => substr($sRow['start_time'], 0, 5),
                'end' => substr($sRow['end_time'], 0, 5)
            ];
        }
        $stmtS->close();
    }
    $stmt->close();
}

// proses submit form tambah/edit dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postEditId = intval($_POST['edit_id'] ?? 0);
    $doctorName = trim($_POST['doctor_name'] ?? '');
    $specialtyId = intval($_POST['specialty_id'] ?? 0);
    $bio = trim($_POST['bio'] ?? '');
    $fee = trim($_POST['price_rate'] ?? '');
    $rating = floatval($_POST['rating'] ?? 5.0);
    $location = trim($_POST['clinic_location'] ?? '');
    $postedDays = $_POST['days'] ?? [];
    $startTimes = $_POST['start_time'] ?? [];
    $endTimes = $_POST['end_time'] ?? [];

    // validasi form dokter sesuai ketentuan soal
    if (empty($doctorName)) {
        $errors[] = "Nama dokter jangan dikosongin ya.";
    } else {
        // cek keunikan nama dokter
        if ($postEditId > 0) {
            $stmt = $conn->prepare("SELECT id FROM doctors WHERE doctor_name = ? AND id != ?");
            $stmt->bind_param("si", $doctorName, $postEditId);
        } else {
            $stmt = $conn->prepare("SELECT id FROM doctors WHERE doctor_name = ?");
            $stmt->bind_param("s", $doctorName);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Nama dokter udah kedaftar, pake nama lain yang unik ya.";
        }
        $stmt->close();
    }

    if ($specialtyId <= 0) {
        $errors[] = "Pilih salah satu spesialisasi dokternya.";
    }

    if ($fee === '') {
        $errors[] = "Biaya jasa konsultasi (Service Fee) wajib diisi.";
    } elseif (!is_numeric($fee) || floatval($fee) < 0) {
        $errors[] = "Biaya jasa konsultasi harus angka positif ya.";
    }

    if (empty($location)) {
        $errors[] = "Lokasi klinik dokter wajib diisi.";
    }

    // kalo ga ada masalah pada validasi
    if (empty($errors)) {
        $feeFloat = floatval($fee);

        if ($postEditId > 0) {
            // mode update dokter
            $stmt = $conn->prepare("UPDATE doctors SET doctor_name = ?, specialty_id = ?, bio = ?, price_rate = ?, rating = ?, clinic_location = ? WHERE id = ?");
            $stmt->bind_param("sisddsi", $doctorName, $specialtyId, $bio, $feeFloat, $rating, $location, $postEditId);
            $stmt->execute();
            $stmt->close();

            $targetDoctorId = $postEditId;
        } else {
            // mode tambah dokter baru
            $stmt = $conn->prepare("INSERT INTO doctors (doctor_name, specialty_id, bio, price_rate, rating, clinic_location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdds", $doctorName, $specialtyId, $bio, $feeFloat, $rating, $location);
            $stmt->execute();
            $targetDoctorId = $stmt->insert_id;
            $stmt->close();
        }

        // atur ulang jadwal praktek dokter
        // hapus jadwal lama dokter ini dulu
        $stmtDelSch = $conn->prepare("DELETE FROM schedules WHERE doctor_id = ?");
        $stmtDelSch->bind_param("i", $targetDoctorId);
        $stmtDelSch->execute();
        $stmtDelSch->close();

        // masukin jadwal untuk hari-hari yang dicentang
        if (!empty($postedDays) && is_array($postedDays)) {
            $stmtInsSch = $conn->prepare("INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            foreach ($postedDays as $day) {
                $rawStart = !empty($startTimes[$day]) ? $startTimes[$day] : '09:00';
                $rawEnd = !empty($endTimes[$day]) ? $endTimes[$day] : '17:00';
                $st = date('H:i:00', strtotime($rawStart));
                $et = date('H:i:00', strtotime($rawEnd));
                $stmtInsSch->bind_param("isss", $targetDoctorId, $day, $st, $et);
                $stmtInsSch->execute();
            }
            $stmtInsSch->close();
        }

        $redirectMsg = ($postEditId > 0) ? 'updated' : 'added';
        header("Location: manage-doctors.php?msg=" . $redirectMsg);
        exit();
    }
}

// ambil seluruh dokter untuk ditampilin di tabel admin
$doctorsList = $conn->query("SELECT d.*, s.specialty_name 
                            FROM doctors d 
                            JOIN specialties s ON d.specialty_id = s.id 
                            ORDER BY d.id DESC");

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Admin - Manage Doctors</h1>

<!-- alert error -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- alert notifikasi sukses -->
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- form input data dokter & jadwal -->
<div class="form-card" style="max-width: 900px;">
  <form method="POST" action="manage-doctors.php">
    <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">

    <div class="form-group">
      <label for="doctor_name" class="form-label">Doctor Name</label>
      <input type="text" id="doctor_name" name="doctor_name" class="form-control" value="<?php echo htmlspecialchars($doctorName); ?>" required>
    </div>

    <div class="form-group">
      <label for="specialty_id" class="form-label">Specialty</label>
      <select id="specialty_id" name="specialty_id" class="form-control" required>
        <option value="">-- Select Specialty --</option>
        <?php foreach ($allSpecialties as $sp): ?>
          <option value="<?php echo $sp['id']; ?>" <?php echo $specialtyId == $sp['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($sp['specialty_name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="bio" class="form-label">Bio</label>
      <textarea id="bio" name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($bio); ?></textarea>
    </div>

    <div class="form-group">
      <label for="price_rate" class="form-label">Service Fee</label>
      <input type="text" id="price_rate" name="price_rate" class="form-control" value="<?php echo htmlspecialchars($fee); ?>" required>
    </div>

    <div class="form-group">
      <label for="rating" class="form-label">Rating (0-5)</label>
      <input type="number" step="0.1" min="0" max="5" id="rating" name="rating" class="form-control" value="<?php echo htmlspecialchars($rating); ?>">
    </div>

    <div class="form-group">
      <label for="clinic_location" class="form-label">Clinic Location</label>
      <input type="text" id="clinic_location" name="clinic_location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" required>
    </div>

    <!-- konfigurasi jadwal kerja mingguan dokter -->
    <div class="form-group">
      <label class="form-label">Practice Schedule</label>
      <p class="section-desc" style="margin-bottom: 8px; font-size: 0.9rem;">Centang hari praktek dan tentukan jam mulai serta jam selesainya.</p>
      
      <table class="schedule-grid-table">
        <thead>
          <tr>
            <th style="width: 25%;">Day</th>
            <th style="width: 35%;">Start Time</th>
            <th style="width: 35%;">End Time</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daysOfWeek as $d): ?>
            <?php 
              $isDayChecked = isset($schedulesData[$d]['enabled']);
              $stVal = $schedulesData[$d]['start'] ?? '09:00';
              $etVal = $schedulesData[$d]['end'] ?? '17:00';
            ?>
            <tr>
              <td>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                  <input type="checkbox" name="days[]" value="<?php echo $d; ?>" <?php echo $isDayChecked ? 'checked' : ''; ?>>
                  <?php echo $d; ?>
                </label>
              </td>
              <td>
                <input type="time" name="start_time[<?php echo $d; ?>]" class="form-control" value="<?php echo $stVal; ?>">
              </td>
              <td>
                <input type="time" name="end_time[<?php echo $d; ?>]" class="form-control" value="<?php echo $etVal; ?>">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <button type="submit" class="btn btn-primary">
      <?php echo $editId > 0 ? 'Update Doctor' : 'Add Doctor'; ?>
    </button>
    <?php if ($editId > 0): ?>
      <a href="manage-doctors.php" class="btn btn-outline" style="margin-left: 8px;">Cancel</a>
    <?php endif; ?>
  </form>
</div>

<!-- tabel katalog seluruh dokter -->
<div class="table-container">
  <table class="custom-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Doctor Name</th>
        <th>Specialty</th>
        <th>Fee</th>
        <th>Rating</th>
        <th>Location</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($doctorsList && $doctorsList->num_rows > 0): ?>
        <?php while ($doc = $doctorsList->fetch_assoc()): ?>
          <tr>
            <td><?php echo $doc['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($doc['doctor_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($doc['specialty_name']); ?></td>
            <td>Rp <?php echo number_format($doc['price_rate'], 0, ',', '.'); ?></td>
            <td><?php echo number_format($doc['rating'], 1); ?></td>
            <td><?php echo htmlspecialchars($doc['clinic_location']); ?></td>
            <td>
              <div class="table-actions">
                <a href="manage-doctors.php?edit=<?php echo $doc['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="manage-doctors.php?delete=<?php echo $doc['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin mau menghapus dokter ini? Semua jadwalnya juga bakal ikut terhapus!');">Delete</a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align: center; padding: 20px;">Belum ada dokter yang terdaftar.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
