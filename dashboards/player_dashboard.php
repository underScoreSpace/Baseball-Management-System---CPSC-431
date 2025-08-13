<?php
require '../auth_check.php';

if ($_SESSION['role'] !== 'Player') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}

$userId = $_SESSION['user_id'];

// ============================
// Fetch player profile
// ============================
$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.first_name, u.last_name, u.phone_number, players.*, teams.team_name
    FROM users u
    JOIN players ON u.id = players.user_id
    LEFT JOIN teams ON players.team_id = teams.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$player = $stmt->fetch();

if (!$player) {
    echo "<p style='color:red;'>Player profile not found.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Dashboard</title>
    <link rel="stylesheet" href="../assets/css/player_dash.css">
</head>
<body>
<div class="container">

    <div class="header-bar">
        <h2>Player Dashboard</h2>
        <div class="top-links">
            <a href="../manage_account.php">Manage Account</a> |
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <p>Welcome, <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>!</p>
    <p>Team: <strong><?php echo htmlspecialchars($player['team_name'] ?? 'Unassigned'); ?></strong></p>

    <hr>

    <!-- ============================ -->
    <!-- Section: My Season Stats -->
    <!-- ============================ -->
    <h3>My Season Stats</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Games Played</th>
            <th>At Bats</th>
            <th>Hits</th>
            <th>Home Runs</th>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars($player['games_played']); ?></td>
            <td><?php echo htmlspecialchars($player['at_bats']); ?></td>
            <td><?php echo htmlspecialchars($player['hits']); ?></td>
            <td><?php echo htmlspecialchars($player['home_runs']); ?></td>
        </tr>
    </table>

    <hr>

    <!-- ============================ -->
    <!-- Section: My Teammates -->
    <!-- ============================ -->
    <h3>My Teammates</h3>

    <?php
    $teammatesStmt = $pdo->prepare("
        SELECT first_name, last_name, jersey_number, position
        FROM users
        JOIN players ON users.id = players.user_id
        WHERE players.team_id = ? AND users.id != ? AND players.status = 'Active'
        ORDER BY last_name ASC, first_name ASC
    ");
    $teammatesStmt->execute([$player['team_id'], $userId]);
    $teammates = $teammatesStmt->fetchAll();
    ?>

    <?php if (empty($teammates)): ?>
        <p>No teammates found on your team yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Full Name</th>
                <th>Jersey Number</th>
                <th>Position</th>
            </tr>
            <?php foreach ($teammates as $teammate): ?>
                <tr>
                    <td><?php echo htmlspecialchars($teammate['first_name'] . ' ' . $teammate['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($teammate['jersey_number']); ?></td>
                    <td><?php echo htmlspecialchars($teammate['position']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>