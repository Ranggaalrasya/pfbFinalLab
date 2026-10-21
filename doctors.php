<?php
// doctors.php - ini halaman katalog dokter lengkap dengan fitur cari & filter spesialisasi
$pageTitle = 'Doctor Listing';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// tangkap kata kunci pencarian sama filter spesialisasi dari URL ($_GET)
$searchName = trim($_GET['search_name'] ?? '');
$specialtyFilter = intval($_GET['specialty_id'] ?? 0);

// ambil semua daftar spesialisasi buat ngisi opsi dropdown filter
$specResult = $conn->query("SELECT id, specialty_name FROM specialties ORDER BY specialty_name ASC");

// susun query SQL secara dinamis tergantung filter apa yang lagi dipilih
$sql = "SELECT d.id, d.doctor_name, d.bio, d.price_rate, d.rating, d.clinic_location, s.specialty_name 
        FROM doctors d 
        JOIN specialties s ON d.specialty_id = s.id 
        WHERE 1=1";
$params = [];
$types = "";

// kalo user ngetik nama dokter, tambahin klausa LIKE
if (!empty($searchName)) {
    $sql .= " AND d.doctor_name LIKE ?";
    $params[] = "%" . $searchName . "%";
    $types .= "s";
}

// kalo user milih spesialisasi tertentu di dropdown
if ($specialtyFilter > 0) {
    $sql .= " AND d.specialty_id = ?";
    $params[] = $specialtyFilter;
    $types .= "i";
}

$sql .= " ORDER BY d.doctor_name ASC";

// eksekusi query pake prepared statement biar aman
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$doctorsResult = $stmt->get_result();
?>

<h1 class="page-title">Doctor Listing</h1>

<!-- kotak form buat nyari dokter atau nyaring berdasarkan spesialisasi -->
<div class="form-card" style="margin-bottom: 24px;">
  <form method="GET" action="doctors.php">
    <div class="form-group">
      <label for="search_name" class="form-label">Find Doctor Name</label>
      <input type="text" id="search_name" name="search_name" class="form-control" value="<?php echo htmlspecialchars($searchName); ?>" placeholder="e.g. Theo">
    </div>

    <div class="form-group">
      <label for="specialty_id" class="form-label">Filter Specialty</label>
      <select id="specialty_id" name="specialty_id" class="form-control">
        <option value="0">All Specialty</option>
        <?php if ($specResult): ?>
          <?php while ($spec = $specResult->fetch_assoc()): ?>
            <option value="<?php echo $spec['id']; ?>" <?php echo $specialtyFilter == $spec['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($spec['specialty_name']); ?>
            </option>
          <?php endwhile; ?>
        <?php endif; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Filter</button>
    <?php if (!empty($searchName) || $specialtyFilter > 0): ?>
      <!-- tombol reset buat ngebersihin filter pencarian dengan sekali klik -->
      <a href="doctors.php" class="btn btn-outline" style="margin-left: 8px;">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- list kartu dokter yang berhasil ditemukan -->
<div class="doctors-list" style="display: flex; flex-direction: column; gap: 20px;">
  <?php if ($doctorsResult && $doctorsResult->num_rows > 0): ?>
    <?php while ($doc = $doctorsResult->fetch_assoc()): ?>
      <div class="card">
        <h2 class="card-title"><?php echo htmlspecialchars($doc['doctor_name']); ?></h2>
        <p class="card-info"><strong>Specialty:</strong> <?php echo htmlspecialchars($doc['specialty_name']); ?></p>
        <p class="card-info"><strong>Rating:</strong> <?php echo number_format($doc['rating'], 1); ?>/5</p>
        <p class="card-info"><strong>Consultation Fee:</strong> Rp <?php echo number_format($doc['price_rate'], 0, ',', '.'); ?></p>
        <?php if (!empty($doc['bio'])): ?>
          <p class="card-info" style="margin-top: 6px;"><?php echo htmlspecialchars($doc['bio']); ?></p>
        <?php endif; ?>
        <div class="card-actions">
          <a href="doctor-profile.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary">Doctor Details</a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <!-- tampilan kalo dokternya ga ketemu sama sekali -->
    <div class="card">
      <p>Yah dokter yang kamu cari ga ketemu nih bro. Coba kata kunci lain ya.</p>
    </div>
  <?php endif; ?>
</div>

<?php 
$stmt->close();
require_once __DIR__ . '/includes/footer.php'; 
?>
