<?php
// dashboard.php - dashboard pasien buat mantau status janji temu dan status pembayaran
$pageTitle = 'Patient Dashboard';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// cuma member yang boleh buka dashboard pasien ini
requireMember();

$memberId = $_SESSION['user_id'];

// ambil seluruh riwayat appointment punya member yang lagi login, gabungin sama dokter & status pembayaran
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

<!-- alert konfirmasi kalo baru aja beres upload struk pembayaran -->
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_submitted'): ?>
  <div class="alert alert-success">Pembayaran berhasil dikirim! Admin kita bakal ngecek dan verifikasi struk kamu secepatnya ya.</div>
<?php endif; ?>

<!-- tabel rangkuman janji temu pasien -->
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
            // cek status bayar: kalo belum ada record pembayaran, kasih label "Not Paid"
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
              <!-- tombol buat bayar atau re-upload bukti transfer -->
              <a href="payment.php?appointment_id=<?php echo $row['appt_id']; ?>" class="btn btn-outline btn-sm">
                Pay / Update Proof
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 24px;">Kamu belum punya janji temu sama sekali nih. <a href="book-appointment.php">Yuk bikin janji sekarang</a>.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php 
$stmt->close();
require_once __DIR__ . '/includes/footer.php'; 
?>
