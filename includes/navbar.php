<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Junk Shop Navigation</title>
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="css/navbar.css" />
</head>
<body>
 
<nav class="navbar" id="navbar">
  <a href="dashboard.php" class="brand">
    <div class="nav-logo">
      <img src="junk_manager_logo.png" alt="Junk Manager Logo" class="logo" />
    </div>
    <div>
      <div class="brand-text">MY JUNKSHOP MANAGER</div>
      <div class="brand-tagline">TURNING TRASH INTO CASH</div>
    </div>
  </a>

  <ul class="nav-links" id="navLinks">
    <li class="nav-item">
      <a href="profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Profile') ?>
      </a>
    </li>
    <li class="nav-item">
      <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
    </li>
    <li class="nav-item">
      <a href="add_scrap.php" class="nav-link <?= $current_page == 'add_scrap.php' ? 'active' : '' ?>">
        <i class="fas fa-plus-circle"></i> Add Scrap
      </a>
    </li>
    <li class="nav-item">
      <a href="sell_scrap.php" class="nav-link <?= $current_page == 'sell_scrap.php' ? 'active' : '' ?>">
        <i class="fas fa-cash-register"></i> Sell Scrap
      </a>
    </li>
    <li class="nav-item">
      <a href="transaction_history.php" class="nav-link <?= $current_page == 'transaction_history.php' ? 'active' : '' ?>">
        <i class="fas fa-history"></i> Transactions
      </a>
    </li>
    <?php if (!empty($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super-admin')) { ?>
      <li class="nav-item">
        <a href="scrap_ui.php" class="nav-link <?= $current_page == 'scrap_ui.php' ? 'active' : '' ?>">
          <i class="fas fa-boxes"></i> Manage Scrap
        </a>
      </li>
      <li class="nav-item">
        <a href="user_management.php" class="nav-link <?= $current_page == 'user_management.php' ? 'active' : '' ?>">
          <i class="fas fa-users-cog"></i> User Management
        </a>
      </li>
    <?php } ?>
    <li class="nav-item">
      <a href="inventory.php" class="nav-link <?= $current_page == 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-warehouse"></i> Inventory
      </a>
    </li>
    <li class="nav-item">
      <a href="report.php" class="nav-link <?= $current_page == 'report.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i> Reports
      </a>
    </li>
    <li class="nav-item">
      <a href="logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </li>
  </ul>
</nav>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="hamburger" id="hamburger" aria-label="Toggle navigation menu" role="button" tabindex="0">
  <span></span>
  <span></span>
  <span></span>
</div>
<script src="js/navbar.js"></script>

<?php if ($_SESSION['role'] === 'super-admin'): ?>
    <script src="js/reset_trigger.js"></script>
<?php endif; ?>

</body>
</html>
