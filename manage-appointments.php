<?php
// manage-appointments.php - panel kontrol admin buat ngeliat dan ngubah status janji temu pasien
$pageTitle = 'Manage Appointments';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// kunci buat admin doang
requireAdmin();

$errors = [];
$success = '';

// nangkep pesan sukses setelah aksi (pola PRG)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $success = "Status janji temu berhasil diupdate!";
    elseif ($_GET['msg'] === 'deleted') $success = "Data janji temu berhasil dihapus dari sistem.";
}

// proses update status janji temu (Waiting -> Completed / Canceled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $apptId = intval($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowedStatuses = ['Waiting', 'Completed', 'Canceled'];

    if ($apptId > 0 && in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $apptId);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: manage-appointments.php?msg=updated");
            exit();
        } else {
            $errors[] = "Gagal ngeupdate status janji temu: " . $conn->error;
            $stmt->close();
        }
    }
}

// proses hapus data janji temu
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage-appointments.php?msg=deleted");
        exit();
    } else {
        $errors[] = "Gagal ngehapus janji temu: " . $conn->error;
        $stmt->close();
    }
}

// ambil seluruh data booking pasien, gabungin sama nama pasien & nama dokter
$sql = "SELECT a.id, a.appointment_date, a.time_slot, a.status,
               u.name AS patient_name,
               d.doctor_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.time_slot DESC";
$appointmentsList = $conn->query($sql);

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Admin - Appointment List</h1>

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

<!-- alert sukses -->
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- tabel rekap semua booking janji temu -->
<div class="table-container">
  <table class="custom-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th style="width: 250px;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($appointmentsList && $appointmentsList->num_rows > 0): ?>
        <?php while ($row = $appointmentsList->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
            <td><?php echo date('H:i', strtotime($row['time_slot'])); ?></td>
            <td>
              <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                <?php echo htmlspecialchars($row['status']); ?>
              </span>
            </td>
            <td>
              <!-- form cepat buat ganti status atau hapus langsung dari tabel -->
              <form method="POST" action="manage-appointments.php" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                <select name="status" class="form-control" style="width: auto; padding: 4px 8px; font-size: 0.85rem;">
                  <option value="Waiting" <?php echo $row['status'] === 'Waiting' ? 'selected' : ''; ?>>Waiting</option>
                  <option value="Completed" <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                  <option value="Canceled" <?php echo $row['status'] === 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                <a href="manage-appointments.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin mau hapus data janji temu ini?');">Delete</a>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align: center; padding: 20px;">Belum ada bookingan janji temu nih.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
