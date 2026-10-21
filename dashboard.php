<?php
// dashboard.php - Patient Dashboard
$pageTitle = 'Patient Dashboard';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireMember();

$memberId = $_SESSION['user_id'];

// Query all appointments for this member
$sql = "SELECT a.id AS appt_id, a.appointment_date, a.time_slot, a.status AS consultation_status,
               d.doctor_name,
               p.id AS payment_id, p.status AS payment_status
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN payments p ON a.id = p.appointment_id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.time_slot DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $memberId);
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Patient Dashboard</h1>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_submitted'): ?>
  <div class="alert alert-success">Payment submitted successfully! Our admin will review your payment shortly.</div>
<?php endif; ?>

<div class="table-container">
  <table class="custom-table">
    <thead>
      <tr>
        <th>Doctor</th>
        <th>Date</th>
        <th>Time</th>
        <th>Consultation Status</th>
        <th>Payment Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php 
            $payStatus = !empty($row['payment_status']) ? htmlspecialchars($row['payment_status']) : 'Not Paid';
            $badgeClass = 'badge-' . strtolower(str_replace(' ', '', $payStatus));
          ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($row['doctor_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
            <td><?php echo date('H:i', strtotime($row['time_slot'])); ?></td>
            <td>
              <span class="badge badge-<?php echo strtolower($row['consultation_status']); ?>">
                <?php echo htmlspecialchars($row['consultation_status']); ?>
              </span>
            </td>
            <td>
              <span class="badge <?php echo $badgeClass; ?>">
                <?php echo $payStatus; ?>
              </span>
            </td>
            <td>
              <a href="payment.php?appointment_id=<?php echo $row['appt_id']; ?>" class="btn btn-outline btn-sm">
                Pay / Update Proof
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 24px;">No appointments found. <a href="book-appointment.php">Book an appointment now</a>.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php 
$stmt->close();
require_once __DIR__ . '/includes/footer.php'; 
?>
