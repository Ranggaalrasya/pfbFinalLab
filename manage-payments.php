<?php
// manage-payments.php - Admin Manage Payments
$pageTitle = 'Manage Payments';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$errors = [];
$success = '';

// Handle Status Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $paymentId = intval($_POST['payment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowedStatuses = ['pending', 'approved', 'rejected'];

    if ($paymentId > 0 && in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $paymentId);
        if ($stmt->execute()) {
            $success = "Payment #{$paymentId} status updated to {$newStatus}.";
        } else {
            $errors[] = "Failed to update payment: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all payments
$sql = "SELECT p.id AS payment_id, p.amount, p.proof_image, p.status AS payment_status,
               a.id AS appt_id,
               u.name AS patient_name,
               d.doctor_name
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.id
        JOIN users u ON a.user_id = u.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY p.id DESC";
$paymentsList = $conn->query($sql);

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Admin - Payment Verification</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="table-container">
  <table class="custom-table">
    <thead>
      <tr>
        <th>ID Payment</th>
        <th>Appointment</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Amount</th>
        <th>Payment Proof</th>
        <th>Status</th>
        <th style="width: 200px;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($paymentsList && $paymentsList->num_rows > 0): ?>
        <?php while ($row = $paymentsList->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['payment_id']; ?></td>
            <td>#<?php echo $row['appt_id']; ?></td>
            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
            <td>Rp <?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
            <td>
              <?php if (!empty($row['proof_image']) && file_exists(__DIR__ . '/uploads/' . $row['proof_image'])): ?>
                <a href="uploads/<?php echo htmlspecialchars($row['proof_image']); ?>" target="_blank">
                  <img src="uploads/<?php echo htmlspecialchars($row['proof_image']); ?>" alt="Proof" style="max-height: 80px; max-width: 100px; border-radius: 4px; border: 1px solid #cbd5e1; object-fit: cover;">
                </a>
              <?php else: ?>
                <span style="color: #94a3b8; font-size: 0.85rem;">No proof file</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge-<?php echo strtolower($row['payment_status']); ?>">
                <?php echo htmlspecialchars($row['payment_status']); ?>
              </span>
            </td>
            <td>
              <form method="POST" action="manage-payments.php" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                <select name="status" class="form-control" style="width: auto; padding: 4px 8px; font-size: 0.85rem;">
                  <option value="pending" <?php echo $row['payment_status'] === 'pending' ? 'selected' : ''; ?>>pending</option>
                  <option value="approved" <?php echo $row['payment_status'] === 'approved' ? 'selected' : ''; ?>>approved</option>
                  <option value="rejected" <?php echo $row['payment_status'] === 'rejected' ? 'selected' : ''; ?>>rejected</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: 20px;">No payments found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
