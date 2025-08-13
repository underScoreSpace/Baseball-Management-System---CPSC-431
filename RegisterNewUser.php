<?php
session_start();
require 'db_connect.php';
require 'validation.php';

// ============================
// If form submitted (POST)
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number'] ?? null);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $invite_code = trim($_POST['invite_code'] ?? null);

    // ============================
    // Basic Validation
    // ============================
    if (!validateFirstName($first_name) || !validateLastName($last_name)) {
        echo "<p style='color:red;'>First and Last names must only contain letters.</p>";
        exit;
    }

    if (!validatePassword($password)) {
        echo "<p style='color:red;'>Password must be at least 8 characters long.</p>";
        exit;
    }

    // ========== Set default role ==========
    $role = 'User'; // Default for public registration
    $teamId = null;

    // ========== Check if Invite Code provided ==========
    if (!empty($invite_code)) {
        // First check if a Player code
        $codeCheck = $pdo->prepare("
            SELECT * FROM player_registration_codes 
            WHERE code = ? AND is_used = 0
        ");
        $codeCheck->execute([$invite_code]);
        $codeData = $codeCheck->fetch();

        if ($codeData) {
            $role = 'Player';
            $teamId = $codeData['team_id'];
        } else {
            // Otherwise check for a Coach code
            $codeCheck = $pdo->prepare("
                SELECT * FROM coach_registration_codes 
                WHERE code = ? AND is_used = 0
            ");
            $codeCheck->execute([$invite_code]);
            $codeData = $codeCheck->fetch();

            if ($codeData) {
                $role = 'Coach';
                $teamId = $codeData['team_id'];
            } else {
                echo "<p style='color:red;'>Invalid or already used invite code!</p>";
                exit;
            }
        }
    }

    // ========== Hash password ==========
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // ========== Insert into users table ==========
    try {
        $stmt = $pdo->prepare("
        INSERT INTO users (username, email, phone_number, password_hash, role, first_name, last_name)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
        $stmt->execute([$username, $email, $phone_number, $hash, $role, $first_name, $last_name]);

        $newUserId = $pdo->lastInsertId();

        // ========== If they are a Player ==========
        if ($role === 'Player') {
            $insertPlayer = $pdo->prepare("
            INSERT INTO players (user_id, jersey_number, position, team_id)
            VALUES (?, 0, 'Unknown', ?)
        ");
            $insertPlayer->execute([$newUserId, $teamId]);

            $markUsed = $pdo->prepare("UPDATE player_registration_codes SET is_used = 1 WHERE id = ?");
            $markUsed->execute([$codeData['id']]);
        }

        // ========== If they are a Coach ==========
        if ($role === 'Coach') {
            $insertCoach = $pdo->prepare("
            INSERT INTO team_coaches (user_id, team_id, status)
            VALUES (?, ?, 'Active')
        ");
            $insertCoach->execute([$newUserId, $teamId]);

            $markUsed = $pdo->prepare("UPDATE coach_registration_codes SET is_used = 1 WHERE id = ?");
            $markUsed->execute([$codeData['id']]);
        }
        $alertType = "success";
        $message = "Registered successfully. <a href='index.php'>Login here</a>";

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $alertType = "error";
            $message = "Username or email already exists. Please choose another.";
        } else {
            $alertType = "error";
            $message = "Registration failed due to a server error.";
        }
    }

}
?>

<!-- ============================ -->
<!-- Registration Form -->
<!-- ============================ -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <link rel="stylesheet" href="assets/css/register_new_user.css">
</head>
<body>
<div class="container">
    <h2>Register</h2>

    <?php if (isset($message)) echo "<div class='alert $alertType'>$message</div>"; ?>

    <form method="POST">
        Username: <input type="text" name="username" required><br><br>
        First Name: <input type="text" name="first_name" required><br><br>
        Last Name: <input type="text" name="last_name" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Phone Number (optional): <input type="text" name="phone_number"><br><br>
        Password: <input type="password" name="password" required><br><br>
        Confirm Password: <input type="password" name="confirm_password" required><br><br>
        Invite Code (if you were given one): <input type="text" name="invite_code"><br><br>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="index.php">Back to Login</a></p>
</div>
</body>
</html>