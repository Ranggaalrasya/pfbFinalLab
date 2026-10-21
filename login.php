<?php
// login.php - ini halaman buat login user (baik member maupun admin)
$pageTitle = 'Login';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// kalo user-nya udah login, ya ga usah login lagi, langsung lempar ke beranda
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$email = '';
$successMsg = '';
$redirect = trim($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));

// kalo baru kelar register, munculin ucapan selamat datang
if (isset($_GET['registered'])) {
    $successMsg = "Registrasi berhasil cuy! Silakan langsung login pake akun barumu.";
}

// pas form login di-submit pake method POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    // validasi form login: ga boleh ada yang kosong
    if (empty($email)) {
        $error = "Email wajib diisi ya!";
    } elseif (empty($password)) {
        $error = "Password juga wajib diisi bro!";
    } else {
        // cari user-nya di database pake prepared statement biar aman dari SQL injection
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            // verifikasi password pake hash bcrypt yang ada salt-nya
            if (password_verify($password, $user['password'])) {
                //hore berhasil login! masukin data ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // kalo centang 'Remember me', pasang cookie selama 1 minggu (7 hari)
                if ($remember) {
                    setcookie('dochelp_user_id', (string)$user['id'], time() + (7 * 24 * 60 * 60), "/");
                }

                // arahkan tujuan: kalo ada rute sebelumnya (misal pas mau booking), balikin ke sana
                $targetUrl = 'index.php';
                if (!empty($redirect) && !preg_match('/^https?:\/\//i', $redirect)) {
                    $targetUrl = $redirect;
                }

                header("Location: " . $targetUrl);
                exit();
            } else {
                $error = "Password yang kamu masukin salah nih, coba cek lagi ya.";
            }
        } else {
            $error = "Akun dengan email ini ga ketemu di database kita.";
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Login</h1>

<!-- alert notifikasi sukses / error -->
<?php if (!empty($successMsg)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- form login yang didesain simpel & bersih -->
<div class="form-card">
  <form method="POST" action="login.php">
    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

    <div class="form-group">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>

    <!-- checkbox remember me buat nyimpen cookie -->
    <div class="form-group form-check">
      <input type="checkbox" id="remember" name="remember">
      <label for="remember">Remember me (cookie)</label>
    </div>

    <button type="submit" class="btn btn-primary">Login</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
