<?php
require 'auth_check.php';
require 'db_connect.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$errors = [];
$success = '';

// ============================
// Handle updates
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone_number']) ?: null;

    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';

    // Validate name
    if (!preg_match('/^[A-Za-z]+$/', $firstName) || !preg_match('/^[A-Za-z]+$/', $lastName)) {
        $errors[] = "First and last names must only contain letters.";
    }

    // Update basic info
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $phone, $userId]);
    }

    // Player-specific updates
    if ($role === 'Player') {
        $jersey = $_POST['jersey_number'] ?? null;
        $position = $_POST['position'] ?? null;
        $stmt = $pdo->prepare("UPDATE players SET jersey_number = ?, position = ? WHERE user_id = ?");
        $stmt->execute([$jersey, $position, $userId]);
    }

    // Handle password change
    if (!empty($oldPass) && !empty($newPass)) {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (password_verify($oldPass, $user['password_hash'])) {
            if (strlen($newPass) >= 8) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $userId]);
                $success = "Password updated successfully.";
            } else {
                $errors[] = "New password must be at least 8 characters.";
            }
        } else {
            $errors[] = "Old password is incorrect.";
        }
    }

    if (empty($errors) && !$success) {
        $success = "Account updated successfully.";
    }
}

// ============================
// Fetch user info
// ============================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Player-specific info
$playerData = [];
if ($role === 'Player') {
    $stmt = $pdo->prepare("SELECT jersey_number, position FROM players WHERE user_id = ?");
    $stmt->execute([$userId]);
    $playerData = $stmt->fetch();
}

// Determine dashboard link
$dashLink = [
    'Admin' => 'dashboards/admin_dashboard.php',
    'Manager' => 'dashboards/manager_dashboard.php',
    'Coach' => 'dashboards/coach_dashboard.php',
    'Player' => 'dashboards/player_dashboard.php',
    'User' => 'dashboards/user_dashboard.php'
][$role] ?? 'index.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Account</title>
    <link rel="stylesheet" href="assets/css/manage_account.css">
</head>
<body>
    <div class="container">
        <h2>Manage Account (<?php echo htmlspecialchars($role); ?>)</h2>
        <p><a href="<?php echo $dashLink; ?>">← Back to Dashboard</a></p>
        <hr>

        <?php foreach ($errors as $e): ?>
            <p style="color: red;"><?php echo htmlspecialchars($e); ?></p>
        <?php endforeach; ?>

        <?php if ($success): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST">
            Username: <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly><br><br>
            Email: <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly><br><br>
            First Name: <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required><br><br>
            Last Name: <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required><br><br>
            Phone Number: <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"><br><br>

            <?php if ($role === 'Player'): ?>
                Jersey Number: <input type="number" name="jersey_number" value="<?php echo htmlspecialchars($playerData['jersey_number'] ?? ''); ?>"><br><br>
                Position: <input type="text" name="position" value="<?php echo htmlspecialchars($playerData['position'] ?? ''); ?>"><br><br>
            <?php endif; ?>

            <h4>Change Password</h4>
            Old Password: <input type="password" name="old_password"><br><br>
            New Password: <input type="password" name="new_password"><br><br>

            <button type="submit">Update Account</button>
        </form>
    </div>
</body>
</html>