<?php
session_start();
require 'db_connect.php';

// ============================
// Handle Login Form Submission
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        switch ($_SESSION['role']) {
            case 'Admin':
                header("Location: dashboards/admin_dashboard.php");
                break;
            case 'Manager':
                header("Location: dashboards/manager_dashboard.php");
                break;
            case 'Coach':
                header("Location: dashboards/coach_dashboard.php");
                break;
            case 'Player':
                header("Location: dashboards/player_dashboard.php");
                break;
            case 'User':
            default:
                header("Location: dashboards/fan_dashboard.php");
                break;
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid username or password.";
        header("Location: index.php");
        exit;
    }
}
?>

<!-- ============================ -->
<!-- Login Form HTML -->
<!-- ============================ -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Baseball League Management</title>
    <link rel="stylesheet" href="assets/css/index_style.css">
</head>
<body>

<div class="container">
<h2>Login</h2>

<?php
// Show Error if Set
if (isset($_SESSION['login_error'])) {
    echo "<p style='color:red;'>" . htmlspecialchars($_SESSION['login_error']) . "</p>";
    unset($_SESSION['login_error']);
}
?>

<form method="POST">
    Username: <input type="text" name="username" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>

<!-- ============================ -->
<!-- Helpful Links -->
<!-- ============================ -->
<p>Don't have an account? <a href="RegisterNewUser.php">Register here</a></p>
<p><a href="forgot_password.php">Forgot your password?</a></p>
<p><a href="forgot_username.php">Forgot your username?</a></p>