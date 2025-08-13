<?php
session_start();
require 'db_connect.php';
require 'validation.php';

// ============================
// Check if invite code exists
// ============================
if (!isset($_GET['code'])) {
    echo "<p style='color:red;'>Invalid or missing invitation code.</p>";
    exit;
}

$inviteCode = trim($_GET['code']);

// ============================
// Validate Invite Code
// ============================
$stmt = $pdo->prepare("
    SELECT * FROM manager_registration_codes
    WHERE code = ? AND is_used = 0
");
$stmt->execute([$inviteCode]);
$invite = $stmt->fetch();

if (!$invite) {
    echo "<p style='color:red;'>Invalid or already used invitation code.</p>";
    exit;
}

// ============================
// Handle Manager Registration Form
// ============================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number'] ?? null);
    $team_name = trim($_POST['team_name']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // ============================
    // Basic Validation
    // ============================
    if (!validateFirstName($first_name) || !validateLastName($last_name)) {
        $error = "First and Last names must only contain letters.";
    } elseif (!validatePassword($password)) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (empty($team_name)) {
        $error = "Team name is required.";
    } else {
        // Check for duplicate username
        $checkUsername = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkUsername->execute([$username]);

        if ($checkUsername->fetch()) {
            $error = "Username already taken. Please choose another.";
        }
    }

    // ========== Save to database if no error ==========
    if (!$error) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, first_name, last_name, email, phone_number, password_hash, role)
            VALUES (?, ?, ?, ?, ?, ?, 'Manager')
        ");
        if ($stmt->execute([$username, $first_name, $last_name, $invite['email'], $phone_number, $hash])) {
            $newManagerId = $pdo->lastInsertId();

            // Ensure team exists or create it
            $teamStmt = $pdo->prepare("SELECT id FROM teams WHERE team_name = ?");
            $teamStmt->execute([$team_name]);
            $team = $teamStmt->fetch();

            if ($team) {
                $team_id = $team['id'];
            } else {
                $createTeam = $pdo->prepare("INSERT INTO teams (team_name) VALUES (?)");
                $createTeam->execute([$team_name]);
                $team_id = $pdo->lastInsertId();
            }

            // Insert into team_managers with status = 'Active'
            $insertManager = $pdo->prepare("
                INSERT INTO team_managers (user_id, team_id, status)
                VALUES (?, ?, 'Active')
            ");
            $insertManager->execute([$newManagerId, $team_id]);

            // Mark invite code as used
            $updateInvite = $pdo->prepare("
                UPDATE manager_registration_codes SET is_used = 1 WHERE id = ?
            ");
            $updateInvite->execute([$invite['id']]);

            echo "<p style='color:green;'>Manager account created successfully! <a href='index.php'>Login here</a></p>";
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Registration</title>
    <link rel="stylesheet" href="assets/css/manager_register.css">
</head>
<body>
<div class="container">
    <h2>Manager Registration</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        Username: <input type="text" name="username" required><br><br>
        First Name: <input type="text" name="first_name" required><br><br>
        Last Name: <input type="text" name="last_name" required><br><br>
        Email: <input type="email" name="email" value="<?php echo htmlspecialchars($invite['email']); ?>" readonly required><br><br>
        Phone Number (optional): <input type="text" name="phone_number"><br><br>
        Team Name: <input type="text" name="team_name" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        Confirm Password: <input type="password" name="confirm_password" required><br><br>

        <button type="submit">Register as Manager</button>
    </form>

    <p><a href="index.php">← Back to Login</a></p>
</div>
</body>
</html>