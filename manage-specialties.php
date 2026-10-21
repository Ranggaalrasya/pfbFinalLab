<?php
// manage-specialties.php - Admin Specialty Management
$pageTitle = 'Manage Specialties';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAdmin();

$errors = [];
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM specialties WHERE id = ?");
    $stmt->bind_param("i", $delId);
    if ($stmt->execute()) {
        $success = "Specialty deleted successfully.";
    } else {
        $errors[] = "Failed to delete specialty: " . $conn->error;
    }
    $stmt->close();
}

// Handle Add / Edit
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $specialtyName = trim($_POST['specialty_name'] ?? '');
    $postEditId = intval($_POST['edit_id'] ?? 0);

    if (empty($specialtyName)) {
        $errors[] = "Specialty Name must be filled.";
    } else {
        // Check uniqueness
        if ($postEditId > 0) {
            $stmt = $conn->prepare("SELECT id FROM specialties WHERE specialty_name = ? AND id != ?");
            $stmt->bind_param("si", $specialtyName, $postEditId);
        } else {
            $stmt = $conn->prepare("SELECT id FROM specialties WHERE specialty_name = ?");
            $stmt->bind_param("s", $specialtyName);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Specialty name already exists.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        if ($postEditId > 0) {
            $stmt = $conn->prepare("UPDATE specialties SET specialty_name = ? WHERE id = ?");
            $stmt->bind_param("si", $specialtyName, $postEditId);
            $stmt->execute();
            $stmt->close();
            $success = "Specialty updated successfully.";
            $editId = 0;
            $specialtyName = '';
        } else {
            $stmt = $conn->prepare("INSERT INTO specialties (specialty_name) VALUES (?)");
            $stmt->bind_param("s", $specialtyName);
            $stmt->execute();
            $stmt->close();
            $success = "Specialty added successfully.";
            $specialtyName = '';
        }
    }
}

// Fetch all specialties
$result = $conn->query("SELECT * FROM specialties ORDER BY id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Admin - Manage Specialties</h1>

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
                <a href="manage-specialties.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this specialty?');">Delete</a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="3" style="text-align: center; padding: 20px;">No specialties found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
