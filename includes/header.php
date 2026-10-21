<?php
// includes/header.php
// ini file navbar dan kepala html, di-include di tiap halaman biar ga nulis ulang-ulang
require_once __DIR__ . '/auth.php';

// nyatet halaman mana yang lagi dibuka biar tombol nav-nya bisa dikasih highlight aktif
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
<!-- navbar utama DocHelp -->
<nav class="navbar">
  <div class="nav-container">
    <!-- logo DocHelp, diklik balik ke beranda -->
    <div class="nav-brand">
      <a href="index.php" class="brand-link">DocHelp</a>
    </div>

    <!-- link navigasi di tengah (menyesuaikan siapa yang lagi login) -->
    <div class="nav-links">
      <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
      <a href="doctors.php" class="<?php echo $currentPage === 'doctors.php' ? 'active' : ''; ?>">Doctors</a>

      <?php if (isMember()): ?>
        <!-- menu khusus member: buat booking & liat status janji -->
        <a href="book-appointment.php" class="<?php echo $currentPage === 'book-appointment.php' ? 'active' : ''; ?>">Book</a>
        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
      <?php elseif (isAdmin()): ?>
        <!-- menu khusus admin buat kelola semua data platform -->
        <a href="manage-specialties.php" class="<?php echo $currentPage === 'manage-specialties.php' ? 'active' : ''; ?>">Specialty</a>
        <a href="manage-doctors.php" class="<?php echo $currentPage === 'manage-doctors.php' ? 'active' : ''; ?>">Manage Doctors</a>
        <a href="manage-appointments.php" class="<?php echo $currentPage === 'manage-appointments.php' ? 'active' : ''; ?>">Manage Appointments</a>
        <a href="manage-payments.php" class="<?php echo $currentPage === 'manage-payments.php' ? 'active' : ''; ?>">Manage Payments</a>
      <?php endif; ?>
    </div>

    <!-- tombol login/register (kalo guest) atau sapaan user + logout (kalo udah login) -->
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

<!-- pembungkus konten utama tiap halaman -->
<main class="main-container">
