<?php
require 'includes/PHPMailer-master/src/Exception.php';
require 'includes/PHPMailer-master/src/PHPMailer.php';
require 'includes/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('includes/db.php');
// NEW: Load the secrets file
require 'includes/smtp_config.php';

session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Generate reset token
        $token = bin2hex(random_bytes(50));
        $tokenHash = hash("sha256", $token);
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Save token in database
        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
        $stmt->bind_param("sss", $tokenHash, $expires, $email);
        $stmt->execute();

        // Send email
        $mail = new PHPMailer(true);
        try {
            // FIXED: Use constants from smtp_config.php
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_USER, 'Junkshop System');
            $mail->addAddress($email, $user['full_name']);

            // FIXED: Use BASE_URL so this works online
            $resetLink = BASE_URL . "/reset_password.php?token=$token";

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body    = "Hello <b>{$user['full_name']}</b>,<br><br>
                             Click <a href='$resetLink'>here</a> to reset your password.<br>
                             This link will expire in 1 hour.";

            $mail->send();
            $message = "<p class='success'>✅ Please check your email inbox or spam folder and follow the steps.</p>";
        } catch (Exception $e) {
            $message = "<p class='error'>❌ Email failed: {$mail->ErrorInfo}</p>";
        }
    } else {
        $message = "<p class='error'>⚠️ Sorry! No account associated with this email.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/forgot_password.css"> </head>
<body>
    <div class="auth-container">
        <div class="logo-circle">
            <img src="junk_manager_logo.png" alt="Junkshop Logo">
        </div>

        <h2>Forgot Password</h2>
            <p class="subtitle">Enter your registered email and we’ll send you a reset link.</p>

            <?php echo $message; ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Send Reset Link</button>
            </form>

            <div class="extra-links">
                <p><a href="login.php">Back to Login</a></p>
            </div>
    </div>
<?php include 'loader.html'; ?>
</body>
</html>