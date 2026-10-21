<?php
// manage-specialties.php - menu kelola spesialisasi dokter (CRUD lengkap buat admin)
$pageTitle = 'Manage Specialties';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// gembok akses: cuma admin yang boleh masuk sini
requireAdmin();

$errors = [];
$success = '';

// notifikasi pesan sukses dari hasil aksi sebelumnya (pake pola PRG)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success = "Spesialisasi baru berhasil ditambahkan cuy!";
    elseif ($_GET['msg'] === 'updated') $success = "Data spesialisasi berhasil diperbarui!";
    elseif ($_GET['msg'] === 'deleted') $success = "Spesialisasi berhasil dihapus dari sistem.";
}

// aksi buat ngehapus data spesialisasi
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM specialties WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: manage-specialties.php?msg=deleted");
        exit();
    } else {
        $errors[] = "Gagal ngehapus spesialisasi: " . $conn->error;
        $stmt->close();
    }
}

// cek apakah admin lagi ngeklik tombol edit salah satu spesialisasi
$editId = intval($_GET['edit'] ?? 0);
$specialtyName = '';

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT specialty_name FROM specialties WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $specialtyName = $row['specialty_name'];
    }
    $stmt->close();
}

// proses submit form (tambah baru atau update yang udah ada)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialtyName = trim($_POST['specialty_name'] ?? '');
    $postEditId = intval($_POST['edit_id'] ?? 0);

    if (empty($specialtyName)) {
        $errors[] = "Nama spesialisasi jangan dikosongin dong.";
    } else {
        // pastiin nama spesialisasi belum ada kembarannya di database
        if ($postEditId > 0) {
            $stmt = $conn->prepare("SELECT id FROM specialties WHERE specialty_name = ? AND id != ?");
            $stmt->bind_param("si", $specialtyName, $postEditId);
        } else {
            $stmt = $conn->prepare("SELECT id FROM specialties WHERE specialty_name = ?");
            $stmt->bind_param("s", $specialtyName);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Nama spesialisasi ini udah ada di database, bikin nama lain ya.";
        }
        $stmt->close();
    }

    // kalo validasi aman, eksekusi query update atau insert
    if (empty($errors)) {
        if ($postEditId > 0) {
            $stmt = $conn->prepare("UPDATE specialties SET specialty_name = ? WHERE id = ?");
            $stmt->bind_param("si", $specialtyName, $postEditId);
            $stmt->execute();
            $stmt->close();
            header("Location: manage-specialties.php?msg=updated");
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO specialties (specialty_name) VALUES (?)");
            $stmt->bind_param("s", $specialtyName);
            $stmt->execute();
            $stmt->close();
            header("Location: manage-specialties.php?msg=added");
            exit();
        }
    }
}

// tarik semua data spesialisasi buat ditampilin di tabel
$result = $conn->query("SELECT * FROM specialties ORDER BY id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Admin - Manage Specialties</h1>

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

<!-- alert sukses -->
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- form input tambah / edit spesialisasi -->
<div class="form-card">
  <form method="POST" action="manage-specialties.php">
    <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
    <div class="form-group">
      <label for="specialty_name" class="form-label">Specialty Name</label>
      <input type="text" id="specialty_name" name="specialty_name" class="form-control" 
             value="<?php echo htmlspecialchars($specialtyName); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">
      <?php echo $editId > 0 ? 'Update Specialty' : 'Add Specialty'; ?>
    </button>
    <?php if ($editId > 0): ?>
      <a href="manage-specialties.php" class="btn btn-outline" style="margin-left: 8px;">Cancel</a>
    <?php endif; ?>
  </form>
</div>

<!-- tabel daftar spesialisasi -->
<div class="table-container">
  <table class="custom-table">
    <thead>
      <tr>
        <th style="width: 15%;">ID</th>
        <th>Specialty Name</th>
        <th style="width: 25%;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['specialty_name']); ?></td>
            <td>
              <div class="table-actions">
                <a href="manage-specialties.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                <a href="manage-specialties.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin mau hapus spesialisasi ini? Semua dokter terkait juga bakal kena lho!');">Delete</a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="3" style="text-align: center; padding: 20px;">Belum ada data spesialisasi nih.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
