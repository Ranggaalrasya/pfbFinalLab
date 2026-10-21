<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - DocHelp' : 'DocHelp - Doctor Appointment Booking'; ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <div class="nav-container">
    <div class="nav-brand">
      <a href="index.php" class="brand-link">DocHelp</a>
    </div>

    <div class="nav-links">
      <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
      <a href="doctors.php" class="<?php echo $currentPage === 'doctors.php' ? 'active' : ''; ?>">Doctors</a>

      <?php if (isMember()): ?>
        <a href="book-appointment.php" class="<?php echo $currentPage === 'book-appointment.php' ? 'active' : ''; ?>">Book</a>
        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
      <?php elseif (isAdmin()): ?>
        <a href="manage-specialties.php" class="<?php echo $currentPage === 'manage-specialties.php' ? 'active' : ''; ?>">Specialty</a>
        <a href="manage-doctors.php" class="<?php echo $currentPage === 'manage-doctors.php' ? 'active' : ''; ?>">Manage Doctors</a>
        <a href="manage-appointments.php" class="<?php echo $currentPage === 'manage-appointments.php' ? 'active' : ''; ?>">Manage Appointments</a>
        <a href="manage-payments.php" class="<?php echo $currentPage === 'manage-payments.php' ? 'active' : ''; ?>">Manage Payments</a>
      <?php endif; ?>
    </div>

    <div class="nav-auth">
      <?php if (isLoggedIn()): ?>
        <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline">Login</a>
        <a href="register.php" class="btn btn-primary">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="main-container">
