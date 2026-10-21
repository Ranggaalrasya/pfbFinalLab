<?php
// payment.php - halaman pembayaran dan upload bukti transfer buat member
$pageTitle = 'Payment Page';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// cuma member yang punya janji temu yang boleh bayar
requireMember();

$memberId = $_SESSION['user_id'];
$appointmentId = intval($_GET['appointment_id'] ?? ($_POST['appointment_id'] ?? 0));

if ($appointmentId <= 0) {
    header("Location: dashboard.php");
    exit();
}

// ambil data janji temu & dokter, sekalian pastiin ini beneran janji miliknya si user
$stmt = $conn->prepare("SELECT a.id, a.user_id, a.appointment_date, a.time_slot, a.status,
                               d.doctor_name, d.price_rate,
                               p.id AS payment_id, p.amount, p.proof_image, p.status AS payment_status
                        FROM appointments a
                        JOIN doctors d ON a.doctor_id = d.id
                        LEFT JOIN payments p ON a.id = p.appointment_id
                        WHERE a.id = ? AND a.user_id = ?");
$stmt->bind_param("ii", $appointmentId, $memberId);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo "<p>Janji temu ga ketemu atau kamu ga punya akses ke data ini.</p>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$appt = $res->fetch_assoc();
$stmt->close();

// rincian biaya: tarif dokter + biaya admin platform Rp 10.000
$consultationFee = floatval($appt['price_rate']);
$adminFee = 10000;
$grandTotal = $consultationFee + $adminFee;

$errors = [];

// proses saat member upload bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transferAmount = trim($_POST['transfer_amount'] ?? '');
    
    // validasi jumlah transfer
    if ($transferAmount === '') {
        $errors[] = "Jumlah transfer jangan dikosongin ya.";
    } elseif (!is_numeric($transferAmount)) {
        $errors[] = "Jumlah transfer wajib angka murni bro.";
    }

    // validasi file bukti transfer yang di-upload
    $uploadedFileName = '';
    if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] === UPLOAD_ERR_NO_FILE) {
        if (empty($appt['proof_image'])) {
            $errors[] = "Bukti transfer (Payment Proof) wajib di-upload.";
        } else {
            // kalo ga upload baru, pertahankan gambar bukti yang udah ada
            $uploadedFileName = $appt['proof_image'];
        }
    } else {
        $file = $_FILES['proof_image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // pastikan ekstensinya cuma .jpg, .jpeg, atau .png sesuai soal
        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = "Format file bukti pembayaran cuma boleh .jpg atau .png ya.";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Ada masalah waktu proses upload gambar bukti.";
        } else {
            // bikin nama file unik pake prefix + id appointment + timestamp
            $newFileName = 'proof_' . $appointmentId . '_' . time() . '.' . $ext;
            $uploadTarget = __DIR__ . '/uploads/' . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $uploadTarget)) {
                $uploadedFileName = $newFileName;
            } else {
                $errors[] = "Gagal mindahin file upload ke folder penyimpanan.";
            }
        }
    }

    // kalo ga ada error, simpen / perbarui pembayaran di database
    if (empty($errors)) {
        $amountFloat = floatval($transferAmount);
        $status = 'pending';

        if (!empty($appt['payment_id'])) {
            // update data pembayaran yang udah ada
            $stmtUp = $conn->prepare("UPDATE payments SET amount = ?, proof_image = ?, status = ? WHERE id = ?");
            $stmtUp->bind_param("dssi", $amountFloat, $uploadedFileName, $status, $appt['payment_id']);
            $stmtUp->execute();
            $stmtUp->close();
        } else {
            // masukin data pembayaran baru
            $stmtIn = $conn->prepare("INSERT INTO payments (appointment_id, amount, proof_image, status) VALUES (?, ?, ?, ?)");
            $stmtIn->bind_param("idss", $appointmentId, $amountFloat, $uploadedFileName, $status);
            $stmtIn->execute();
            $stmtIn->close();
        }

        // redirect ke dashboard pasien dengan notifikasi sukses
        header("Location: dashboard.php?msg=payment_submitted");
        exit();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Payment Page</h1>

<!-- kalo ada inputan yang salah -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- KARTU 1: ringkasan rincian biaya (Fee Review) -->
<div class="card" style="margin-bottom: 24px;">
  <h2 class="card-title" style="font-size: 1.15rem; margin-bottom: 16px;">Fee Review</h2>
  <p class="card-info"><strong>Doctor:</strong> <?php echo htmlspecialchars($appt['doctor_name']); ?></p>
  <p class="card-info"><strong>Consultation Fee:</strong> Rp <?php echo number_format($consultationFee, 0, ',', '.'); ?></p>
  <p class="card-info"><strong>Administration Fee:</strong> Rp <?php echo number_format($adminFee, 0, ',', '.'); ?></p>
  <p class="card-info" style="font-size: 1.1rem; margin-top: 10px;"><strong>Grand Total:</strong> Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></p>
</div>

<!-- KARTU 2: form input transfer & upload struk transfer -->
<div class="form-card">
  <form method="POST" action="payment.php" enctype="multipart/form-data">
    <input type="hidden" name="appointment_id" value="<?php echo $appointmentId; ?>">

    <div class="form-group">
      <label for="transfer_amount" class="form-label">Transfer Amount</label>
      <input type="text" id="transfer_amount" name="transfer_amount" class="form-control" 
             value="<?php echo htmlspecialchars($_POST['transfer_amount'] ?? ($appt['amount'] ?? $grandTotal)); ?>" required>
    </div>

    <div class="form-group">
      <label for="proof_image" class="form-label">Upload Payment Proof (.jpg / .png)</label>
      <input type="file" id="proof_image" name="proof_image" class="form-control" accept=".jpg,.jpeg,.png">
      <?php if (!empty($appt['proof_image'])): ?>
        <p style="font-size: 0.85rem; color: #64748b; margin-top: 6px;">
          Bukti yang udah di-upload sebelumnya: <strong><?php echo htmlspecialchars($appt['proof_image']); ?></strong>
        </p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Submit Payment</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
