<?php
// login.php - DocHelp User Login
$pageTitle = 'Login';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($email)) {
        $error = "Email must be filled.";
    } elseif (empty($password)) {
        $error = "Password must be filled.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Login success: populate session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Handle Remember Me cookie (1 week = 7 * 24 * 3600 seconds)
                if ($remember) {
                    setcookie('dochelp_user_id', (string)$user['id'], time() + (7 * 24 * 60 * 60), "/");
                }

                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No account found with this email address.";
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Login</h1>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="login.php">
    <div class="form-group">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>

    <div class="form-group form-check">
      <input type="checkbox" id="remember" name="remember">
      <label for="remember">Remember me (cookie)</label>
    </div>

    <button type="submit" class="btn btn-primary">Login</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
