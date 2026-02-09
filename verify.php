<?php
session_start();
include("includes/db.php"); // adjust path if needed

ob_start(); // Start output buffering

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // check if code exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_code=? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_verified'] == 0) {
            // verify account
            $update = $conn->prepare("UPDATE users SET is_verified=1, verification_code=NULL WHERE id=?");
            $update->bind_param("i", $user['id']);
            $update->execute();

            $message = "<h2>Email Verified Successfully!</h2>";
            $message .= "<p><a href='login.php'>Click here to Login</a></p>";
        } else {
            $message = "<h2>Email already verified!</h2>";
            $message .= "<p><a href='login.php'>Go to Login</a></p>";
        }
    } else {
        $message = "<h2>Invalid or Expired Verification Link ❌</h2>";
    }
} else {
    $message = "<h2>No verification code provided!</h2>";
}
$conn->close();
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="css/verify.css"> <!-- Link to your CSS file -->
</head>
<body>
    <div class="verify-container">
        <?php if (isset($message)) echo $message; ?>
    </div>
<?php include 'loader.html'; ?>
</body>
</html>