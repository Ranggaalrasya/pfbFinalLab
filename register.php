<?php
// register.php - DocHelp User Registration
$pageTitle = 'Register';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errors = [];
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Username validation
    if (empty($username)) {
        $errors[] = "Username must be filled.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $username)) {
        $errors[] = "Username must only contain letters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username is already registered. Please choose another.";
        }
        $stmt->close();
    }

    // 2. Email validation
    if (empty($email)) {
        $errors[] = "Email must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($email), '@gmail.com')) {
        $errors[] = "Email must be valid and must end with @gmail.com";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email is already registered. Please use another email.";
        }
        $stmt->close();
    }

    // 3. Phone validation
    if (empty($phone)) {
        $errors[] = "Phone number must be filled.";
    } elseif (!ctype_digit($phone)) {
        $errors[] = "Phone number must be only numbers.";
    } elseif (strlen($phone) < 10 || strlen($phone) > 13) {
        $errors[] = "Phone number must be between 10 and 13 characters.";
    }

    // 4. Password validation
    if (empty($password)) {
        $errors[] = "Password must be filled.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must have a minimum length of 8 characters.";
    }

    // 5. Confirm password
    if ($password !== $confirm_password) {
        $errors[] = "Confirm Password must match the Password.";
    }

    // If all valid, insert to database with salted hash
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = 'Member';

        $stmt = $conn->prepare("INSERT INTO users (name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $phone, $hashed_password, $role);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: login.php?registered=1");
            exit();
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Register</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul style="margin-left: 20px;">
      <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

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
