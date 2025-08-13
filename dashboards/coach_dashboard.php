<?php
require '../auth_check.php';

if ($_SESSION['role'] !== 'Coach') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}

$coachId = $_SESSION['user_id'];

// ============================
// Retrieve coach's assigned team_id
// ============================
$teamQuery = $pdo->prepare("SELECT team_id FROM team_coaches WHERE user_id = ?");
$teamQuery->execute([$coachId]);
$teamId = $teamQuery->fetchColumn();

if (!$teamId) {
    echo "<p style='color:red;'>You are not assigned to any team. Please contact your manager.</p>";
    exit;
}

// ============================
// Fetch coach profile info
// ============================
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$coachId]);
$coach = $stmt->fetch();

// ============================
// Handle stat updates (coach updates player stats)
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stats'])) {
    $playerId = $_POST['player_id'];
    $games_played = $_POST['games_played'];
    $at_bats = $_POST['at_bats'];
    $hits = $_POST['hits'];
    $home_runs = $_POST['home_runs'];

    $stmt = $pdo->prepare("
        UPDATE players
        SET games_played = ?, at_bats = ?, hits = ?, home_runs = ?
        WHERE id = ? AND team_id = ?
    ");
    $stmt->execute([$games_played, $at_bats, $hits, $home_runs, $playerId, $teamId]);

    $successMessage = "Stats updated successfully for player ID $playerId.";
}

// ============================
// Fetch players list
// ============================
$sortOrder = "ASC";
if (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') {
    $sortOrder = "DESC";
}

$playersStmt = $pdo->prepare("
    SELECT p.*, u.first_name, u.last_name
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.status = 'Active'
    ORDER BY u.first_name $sortOrder, u.last_name $sortOrder
");
$playersStmt->execute([$teamId]);
$players = $playersStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coach Dashboard</title>
    <link rel="stylesheet" href="../assets/css/coach_dash.css">
</head>
<body>
<div class="container">

    <div class="header-bar">
        <h2>Coach Dashboard</h2>
        <div class="top-links">
            <a href="../manage_account.php">Manage Account</a> |
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <p>Welcome, Coach <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?>!</p>

    <hr>

    <h3>Edit Stats for Your Team's Players</h3>

    <?php if (isset($successMessage)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($successMessage); ?></p>
    <?php endif; ?>

    <?php if (empty($players)): ?>
        <p>No active players found on your team.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>
                    <a href="?sort=<?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'name_desc' : 'name_asc'; ?>">
                        Full Name
                    </a>
                </th>
                <th>Games Played</th>
                <th>At Bats</th>
                <th>Hits</th>
                <th>Home Runs</th>
                <th>Action</th>
            </tr>
            <?php foreach ($players as $player): ?>
                <tr>
                    <form method="POST">
                        <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                        <td><input type="number" name="games_played" value="<?php echo $player['games_played']; ?>"></td>
                        <td><input type="number" name="at_bats" value="<?php echo $player['at_bats']; ?>"></td>
                        <td><input type="number" name="hits" value="<?php echo $player['hits']; ?>"></td>
                        <td><input type="number" name="home_runs" value="<?php echo $player['home_runs']; ?>"></td>
                        <td>
                            <input type="hidden" name="player_id" value="<?php echo $player['id']; ?>">
                            <input type="hidden" name="update_stats" value="1">
                            <button type="submit">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>