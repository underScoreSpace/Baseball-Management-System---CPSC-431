<?php
require '../auth_check.php';

if ($_SESSION['role'] !== 'User') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}
?>

<h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
<p>You have registered successfully and are logged in as a general user.</p>
<p>This site is currently focused on team operations (players, coaches, managers), but more fan features are coming soon!</p>

<a href="../logout.php">Logout</a>