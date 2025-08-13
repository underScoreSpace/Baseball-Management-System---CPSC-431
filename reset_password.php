<?php
require 'db_connect.php';

// ============================
// Validate Token First
// ============================
if (!isset($_GET['token'])) {
    echo "<p style='color:red;'>Invalid or missing token.</p>";
    exit;
}

$token = $_GET['token'];

// Lookup token in database
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    echo "<p style='color:red;'>Invalid or expired token. Please request a new password reset.</p>";
    exit;
}

// ============================
// If Form Submitted (Reset Password)
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $alertType = "error";
        $message = "<a>Passwords do not match.</a>";
    } elseif (strlen($newPassword) < 8) {
        $alertType = "error";
        $message = "<a>Password must be at least 8 characters long.</a>";
    } else {
        // Hash new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user's password
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $update->execute([$newHash, $resetRequest['email']]);

        // Delete the token (one-time use)
        $delete = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $delete->execute([$token]);

        $alertType = "success";
        $message = "<a>Password reset successfully! <a href='index.php'>Login here</a>";
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
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/reset_password.css">
</head>
<body>
<div class="container">
    <h2>Reset Your Password</h2>

    <?php if (isset($message)) echo "<div class='alert $alertType'>$message</div>"; ?>

    <form method="POST">
        New Password: <input type="password" name="new_password" required><br><br>
        Confirm Password: <input type="password" name="confirm_password" required><br><br>
        <button type="submit">Reset Password</button>
    </form>

    <p><a href="index.php">Back to Login</a></p>
</div>
</body>
</html>
