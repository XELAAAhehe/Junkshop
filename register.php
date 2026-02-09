<?php
include('includes/db.php');
// NEW: Load the secrets file
require 'includes/smtp_config.php'; 

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer-master/src/Exception.php';
require 'includes/PHPMailer-master/src/PHPMailer.php';
require 'includes/PHPMailer-master/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $verification_code = bin2hex(random_bytes(16));

    // Fix: Corrected SQL syntax for checking existing user
    $stmtCheck = $conn->prepare("SELECT * FROM users WHERE email=? OR username=? LIMIT 1");
    $stmtCheck->bind_param("ss", $email, $username);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        $_SESSION['error'] = "Email or username already exists!";
    } else {
        $checkSuperAdmin = $conn->query("SELECT * FROM users WHERE role='super-admin' LIMIT 1");
        $role = ($checkSuperAdmin->num_rows == 0) ? 'super-admin' : 'staff'; 

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $email, $username, $password, $role, $verification_code);

        if ($stmt->execute()) {
            // UPDATED: Uses the BASE_URL from the config file
            $verification_link = BASE_URL . "/verify.php?code=$verification_code";

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST; // Used constant
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER; // Used constant
                $mail->Password   = SMTP_PASS; // Used constant
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT; // Used constant

                $mail->setFrom(SMTP_USER, 'Junkshop System');
                $mail->addAddress($email, $full_name);

                $mail->isHTML(true);
                $mail->Subject = "Verify Your Email";
                $mail->Body = "
                    Hi $full_name,<br><br>
                    Please click the link below to verify your email:<br>
                    <a href='$verification_link'>$verification_link</a><br><br>
                    Thanks!
                ";
                $mail->AltBody = "Hi $full_name,\n\nClick this link to verify your email:\n$verification_link";

                $mail->send();
                $_SESSION['success'] = "Registered successfully! Check your email to verify your account.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send verification email. Error: {$mail->ErrorInfo}";
            }

        } else {
            $_SESSION['error'] = "Registration failed. Try again!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link rel="stylesheet" href="css/register.css">
</head>
<body>
<div class="register-wrapper">
    <div class="register-card">
        <div class="logo-circle">
            <img src="junk_manager_logo.png" alt="Junkshop Logo">
        </div>
        <h2>Create Account</h2>
        <p class="subtitle">Register to access the junkshop system</p>

        <?php
            if(isset($_SESSION['error'])) { 
                echo "<p class='msg error'>".$_SESSION['error']."</p>"; 
                unset($_SESSION['error']); 
            }
            if(isset($_SESSION['success'])) { 
                echo "<p class='msg success'>".$_SESSION['success']."</p>"; 
                unset($_SESSION['success']); 
            }
        ?>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p class="login-link">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
<?php include 'loader.html'; ?>
</body>
</html>
