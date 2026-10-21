<?php
// register.php - halaman registrasi akun baru (khusus calon member DocHelp)
$pageTitle = 'Register';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// kalo udah login ya ngapain daftar lagi kan, cus balikin ke index
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errors = [];
$username = '';
$email = '';
$phone = '';

// proses form saat tombol register ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Cek username: wajib isi, ga boleh ada angka/simbol aneh, dan harus unik
    if (empty($username)) {
        $errors[] = "Username jangan dikosongin ya.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $username)) {
        $errors[] = "Username cuma boleh pake huruf dan spasi aja bro.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username ini udah kepake orang lain, cari nama lain yuk.";
        }
        $stmt->close();
    }

    // 2. Cek email: format valid, wajib domain @gmail.com, dan belum pernah dipake
    if (empty($email)) {
        $errors[] = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        $errors[] = "Email harus beneran valid dan wajib berakhiran @gmail.com ya.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email ini udah kedaftar sebelumnya, coba pake email lain.";
        }
        $stmt->close();
    }

    // 3. Cek nomor HP: wajib angka murni, panjang 10 sampe 13 digit
    if (empty($phone)) {
        $errors[] = "Nomor handphone jangan lupa diisi.";
    } elseif (!ctype_digit($phone)) {
        $errors[] = "Nomor HP isinya cuma boleh angka aja, ga pake strip (-).";
    } elseif (strlen($phone) < 10 || strlen($phone) > 13) {
        $errors[] = "Nomor HP panjangnya harus antara 10 sampe 13 digit ya.";
    }

    // 4. Cek password: minimal 8 karakter biar aman
    if (empty($password)) {
        $errors[] = "Password jangan lupa diisi.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password minimal harus 8 karakter ya bro.";
    }

    // 5. Cek konfirmasi password: harus cocok persis sama password di atas
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password beda nih sama password utamanya.";
    }

    // kalo semua validasi lolos tanpa drama error, simpen ke database!
    if (empty($errors)) {
        // enkripsi password pake bcrypt (udah otomatis ada salt aman bawaan PHP)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = 'Member';

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $phone, $hashed_password, $role);

        if ($stmt->execute()) {
            $stmt->close();
            // sukses daftar, arahin langsung ke halaman login
            header("Location: login.php?registered=1");
            exit();
        } else {
            $errors[] = "Waduh gagal nyimpen ke database: " . $conn->error;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Register</h1>

<!-- kalo ada inputan yang ga sesuai kriteria, jejerin error-nya di sini -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- kartu form pendaftaran -->
<div class="form-card">
  <form method="POST" action="register.php">
    <div class="form-group">
      <label for="username" class="form-label">Username</label>
      <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>

    <div class="form-group">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@gmail.com" required>
    </div>

    <div class="form-group">
      <label for="phone" class="form-label">Phone Number</label>
      <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" placeholder="081234567890" required>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" minlength="8" required>
    </div>

    <div class="form-group">
      <label for="confirm_password" class="form-label">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
    </div>

    <button type="submit" class="btn btn-primary">Register</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
