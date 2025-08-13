<?php
require '../auth_check.php';

if ($_SESSION['role'] !== 'User') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$joinError = '';
$joinSuccess = '';

// ============================
// Handle Invite Code Submission
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_code'])) {
    $code = trim($_POST['invite_code']);

    // Check if it's a player code
    $stmt = $pdo->prepare("SELECT team_id FROM player_registration_codes WHERE code = ? AND is_used = 0");
    $stmt->execute([$code]);
    $playerInvite = $stmt->fetch();

    // Check if it's a coach code if not a player code
    if (!$playerInvite) {
        $stmt = $pdo->prepare("SELECT team_id FROM coach_registration_codes WHERE code = ? AND is_used = 0");
        $stmt->execute([$code]);
        $coachInvite = $stmt->fetch();
    }

    if ($playerInvite) {
        $teamId = $playerInvite['team_id'];

        // Update user's role
        $pdo->prepare("UPDATE users SET role = 'Player' WHERE id = ?")->execute([$userId]);

        // Create player record
        $pdo->prepare("INSERT INTO players (user_id, team_id, jersey_number, position) VALUES (?, ?, 0, 'Unknown')")->execute([$userId, $teamId]);

        // Mark code as used
        $pdo->prepare("UPDATE player_registration_codes SET is_used = 1 WHERE code = ?")->execute([$code]);

        $_SESSION['role'] = 'Player'; // Update session role
        header("Location: ../dashboards/player_dashboard.php");
        exit;

    } elseif (!empty($coachInvite)) {
        $teamId = $coachInvite['team_id'];

        $pdo->prepare("UPDATE users SET role = 'Coach' WHERE id = ?")->execute([$userId]);
        $pdo->prepare("INSERT INTO team_coaches (user_id, team_id, status) VALUES (?, ?, 'Active')")->execute([$userId, $teamId]);
        $pdo->prepare("UPDATE coach_registration_codes SET is_used = 1 WHERE code = ?")->execute([$code]);

        $_SESSION['role'] = 'Coach';
        header("Location: ../dashboards/coach_dashboard.php");
        exit;

    } else {
        $joinError = "Invalid or already used invite code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fan Dashboard</title>
    <link rel="stylesheet" href="../assets/css/fan_dash.css">
</head>
<body>
<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <p>You are logged in as a fan. More features coming soon!</p>

    <hr>

    <h3>Join a Team</h3>
    <p>If you received a player or coach invite code, enter it here to officially join a team:</p>

    <?php if ($joinError): ?>
        <p style="color:red;"><?php echo htmlspecialchars($joinError); ?></p>
    <?php elseif ($joinSuccess): ?>
        <p style="color:green;"><?php echo htmlspecialchars($joinSuccess); ?></p>
    <?php endif; ?>

    <form method="POST">
        Invite Code: <input type="text" name="invite_code" required>
        <button type="submit">Join</button>
    </form>

    <hr>
    <a href="../logout.php">Logout</a>
</div>
</body>
</html>