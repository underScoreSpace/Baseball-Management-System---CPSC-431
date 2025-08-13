<?php
require 'db_connect.php';
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================
// If Form Submitted
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Check if email exists in users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate reset token
        $token = bin2hex(random_bytes(20));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Insert into password_resets
        $insert = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$email, $token, $expires]);

        // Send email with reset link
        $resetLink = "http://localhost/Baseball_League_Management-main/reset_password.php?token=$token";

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ajadevtesting@gmail.com'; // your email
            $mail->Password = 'xwurtlnyffpvtwjf';         // your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('ajadevtesting@gmail.com', 'Baseball League Support');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Hello,\n\nWe received a request to reset your password.\n";
            $mail->Body .= "Click this link to reset your password:\n$resetLink\n\n";
            $mail->Body .= "This link will expire in 1 hour.";

            $mail->send();
            $alertType = "success";
            $message = "<a>Reset link sent! Please check your email.</a>";
        } catch (Exception $e) {
            $alertType = "error";
            $message = "<a>Failed to send email. Please try again later.</a>";
        }
    } else {
        $alertType = "error";
        $message = "<a>No account found with that email address.</a>";
    }
}
?>

<!-- ========================= -->
<!-- HTML Form -->
<!-- ========================= -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/forgot_user_or_pass.css">
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>

    <?php if (isset($message)) echo "<div class='alert $alertType'>$message</div>"; ?>

    <form method="POST">
        Enter your email:
        <input type="email" name="email" required><br><br>
        <button type="submit">Send Password Reset Link</button>
    </form>

    <p><a href="index.php">Back to Login</a></p>
</div>
</body>
</html>