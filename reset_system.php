<?php
include 'includes/db.php';
session_start();

// Only super-admin can trigger reset
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super-admin') {
    die("Unauthorized access.");
}

// STEP 1: Find the first created super-admin
$query = $conn->query("SELECT id FROM users WHERE role='super-admin' ORDER BY created_at ASC LIMIT 1");

if ($query->num_rows == 0) {
    die("No super-admin found. Cannot reset the system.");
}

$first_superadmin = $query->fetch_assoc();
$survivor_id = $first_superadmin['id'];

// STEP 2: Remove all other users except the first super-admin
$conn->query("DELETE FROM users WHERE id != $survivor_id");

// STEP 3: Clear junkshop tables (must disable FK checks)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$conn->query("TRUNCATE TABLE transaction_items");
$conn->query("TRUNCATE TABLE transactions");
$conn->query("TRUNCATE TABLE scrap_types");

// Reset auto-increment (do this while FK checks are OFF)
$conn->query("ALTER TABLE scrap_types AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE transactions AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE transaction_items AUTO_INCREMENT = 1");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// STEP 5: Confirmation message
echo "System reset complete. Only the first super-admin account was preserved.";
?>
