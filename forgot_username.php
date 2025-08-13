<?php
require 'db_connect.php';
require 'vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================
// Handle Form Submission
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Lookup user by email
        $stmt = $pdo->prepare("SELECT username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Send username to user's email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ajadevtesting@gmail.com'; // (TESTING ONLY) NEED TO REMOVE THIS DON'T FORGET
                $mail->Password = 'xwurtlnyffpvtwjf'; // (TESTING ONLY) NEED TO REMOVE THIS DON'T FORGET
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('ajadevtesting@gmail.com', 'Baseball League Support');
                $mail->addAddress($email);
                $mail->Subject = "Your Username Recovery";
                $mail->Body    = "Hello,\n\nHere is your username: " . $user['username'] . "\n\nThank you.";

                $mail->send();
                $alertType = "success";
                $message = "<a>If an account exists, an email with your username has been sent.</a>";
            } catch (Exception $e) {
                $alertType = "error";
                $message = "<a>Failed to send email. Error: {$mail->ErrorInfo}</a>";
            }
        } else {
            $alertType = "success";
            $message = "<a>If an account exists, an email with your username has been sent.</a>";
        }
    } else {
        $alertType = "error";
        $message = "Please enter your email address.";
    }
}
?>

<!-- ============================ -->
<!-- HTML Form -->
<!-- ============================ -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Username</title>
    <link rel="stylesheet" href="assets/css/forgot_user_or_pass.css">
</head>
<body>
<div class="container">
    <h2>Forgot Username</h2>

    <?php if (isset($message)) echo "<div class='alert $alertType'>$message</div>"; ?>

    <form method="POST">
        Email Address:
        <input type="email" name="email" required><br><br>
        <button type="submit">Recover Username</button>
    </form>

    <p><a href="index.php">Back to Login</a></p>
</div>
</body>
</html>