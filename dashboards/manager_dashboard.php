<?php
require '../auth_check.php';

if ($_SESSION['role'] !== 'Manager') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}

$managerId = $_SESSION['user_id'];

// ============================
// Fetch Manager Info Fresh
// ============================
$userStmt = $pdo->prepare("
    SELECT username, email, first_name, last_name, phone_number
    FROM users
    WHERE id = ?
");
$userStmt->execute([$managerId]);
$manager = $userStmt->fetch();

// ============================
// Fetch Manager's Team Info
// ============================
$teamStmt = $pdo->prepare("SELECT team_id FROM team_managers WHERE user_id = ?");
$teamStmt->execute([$managerId]);
$teamId = $teamStmt->fetchColumn();

$teamName = null;
if ($teamId) {
    $nameStmt = $pdo->prepare("SELECT team_name FROM teams WHERE id = ?");
    $nameStmt->execute([$teamId]);
    $teamName = $nameStmt->fetchColumn();
}

// ============================
// Handle Role Reassignment
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_role'])) {
    $newRole = $_POST['new_role'];
    $userId = $_POST['user_id'];

    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);

    if ($newRole === 'Player') {
        $exists = $pdo->prepare("SELECT * FROM players WHERE user_id = ?");
        $exists->execute([$userId]);
        if ($exists->rowCount() === 0) {
            $pdo->prepare("INSERT INTO players (user_id, jersey_number, position, team_id) VALUES (?, 0, 'Unknown', ?)")->execute([$userId, $teamId]);
        } else {
            $pdo->prepare("UPDATE players SET status = 'Active', team_id = ? WHERE user_id = ?")->execute([$teamId, $userId]);
        }
    } elseif ($newRole === 'Coach') {
        $exists = $pdo->prepare("SELECT * FROM team_coaches WHERE user_id = ?");
        $exists->execute([$userId]);
        if ($exists->rowCount() === 0) {
            $pdo->prepare("INSERT INTO team_coaches (user_id, team_id, status) VALUES (?, ?, 'Active')")->execute([$userId, $teamId]);
        } else {
            $pdo->prepare("UPDATE team_coaches SET status = 'Active', team_id = ? WHERE user_id = ?")->execute([$teamId, $userId]);
        }
    } elseif ($newRole === 'Manager') {
        $exists = $pdo->prepare("SELECT * FROM team_managers WHERE user_id = ?");
        $exists->execute([$userId]);
        if ($exists->rowCount() === 0) {
            $pdo->prepare("INSERT INTO team_managers (user_id, team_id, status) VALUES (?, ?, 'Active')")->execute([$userId, $teamId]);
        } else {
            $pdo->prepare("UPDATE team_managers SET status = 'Active', team_id = ? WHERE user_id = ?")->execute([$teamId, $userId]);
        }
    }
    $alertType = "success";
    $messageRole = "<a>Role updated successfully.</a>";
}

// ============================
// Handle Invite Code Generation
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate_player_invite']) || isset($_POST['generate_coach_invite']))) {
    if (isset($_POST['generate_player_invite'])) {
        $newCode = bin2hex(random_bytes(5));
        $stmt = $pdo->prepare("INSERT INTO player_registration_codes (team_id, code) VALUES (?, ?)");
        $stmt->execute([$teamId, $newCode]);
        $alertType = "success";
        $message = "<a>New Player Invite Code Generated: <strong>$newCode</strong></a>";
    }
    if (isset($_POST['generate_coach_invite'])) {
        $newCode = bin2hex(random_bytes(5));
        $stmt = $pdo->prepare("INSERT INTO coach_registration_codes (team_id, code) VALUES (?, ?)");
        $stmt->execute([$teamId, $newCode]);
        $alertType = "success";
        $message = "<a>New Coach Invite Code Generated: <strong>$newCode</strong></a>";
    }
}

// ============================
// Handle Cancel Invite Code
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_code'], $_POST['code_type'])) {
    $cancelCode = $_POST['cancel_code'];
    $codeType = $_POST['code_type'];

    if ($codeType === 'Player') {
        $deleteStmt = $pdo->prepare("DELETE FROM player_registration_codes WHERE code = ? AND team_id = ?");
        $deleteStmt->execute([$cancelCode, $teamId]);
    } elseif ($codeType === 'Coach') {
        $deleteStmt = $pdo->prepare("DELETE FROM coach_registration_codes WHERE code = ? AND team_id = ?");
        $deleteStmt->execute([$cancelCode, $teamId]);
    }
    $alertType = "success";
    $message = "<a>Invite code <strong>$cancelCode</strong> canceled successfully.</a>";
}

// ============================
// Fetch Users + Invites
// ============================
$userStmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.role, u.first_name, u.last_name
    FROM users u
    LEFT JOIN players p ON u.id = p.user_id
    LEFT JOIN team_coaches tc ON u.id = tc.user_id
    WHERE ((p.team_id = :team_id AND u.role IN ('Player', 'Coach', 'User'))
    OR (tc.team_id = :team_id AND u.role IN ('Coach', 'User')))
    AND u.id != :manager_id
    ORDER BY u.username ASC
");
$userStmt->execute(['team_id' => $teamId, 'manager_id' => $managerId]);
$teamUsers = $userStmt->fetchAll();

$inviteStmt = $pdo->prepare("
    SELECT code, 'Player' AS type, created_at FROM player_registration_codes
    WHERE team_id = ? AND is_used = 0
    UNION ALL
    SELECT code, 'Coach' AS type, created_at FROM coach_registration_codes
    WHERE team_id = ? AND is_used = 0
    ORDER BY created_at DESC
");
$inviteStmt->execute([$teamId, $teamId]);
$invites = $inviteStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="../assets/css/manager_dash.css">
</head>
<body>
<div class="container">
    <div class="header-bar">
        <h2>Manager Dashboard</h2>
        <div class="top-links">
            <a href="../manage_account.php">Manage Account</a> |
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <p>Welcome, Manager <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>!</p>

    <?php if ($teamId && $teamName): ?>
        <p>Managing: <strong><?php echo htmlspecialchars($teamName); ?></strong></p>
    <?php endif; ?>

    <hr>

    <h3>Assign Roles to Team Members</h3>
    <?php if (isset($messageRole)) echo "<div class='alert $alertType'>$messageRole</div>"; ?>
    <?php if (empty($teamUsers)): ?>
        <p>No team members found.</p>
    <?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Current Role</th>
            <th>Assign New Role</th>
        </tr>
        <?php foreach ($teamUsers as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td>
                <form method="POST">
                    <select name="new_role" required>
                        <option value="Player" <?php if ($user['role'] === 'Player') echo 'selected'; ?>>Player</option>
                        <option value="Coach" <?php if ($user['role'] === 'Coach') echo 'selected'; ?>>Coach</option>
                        <option value="Manager" <?php if ($user['role'] === 'Manager') echo 'selected'; ?>>Manager</option>
                    </select>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit">Update Role</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <hr>

    <h3 id="invite-section">Generate Invite Codes</h3>
    <?php if (isset($message)) echo "<div class='alert $alertType'>$message</div>"; ?>
    <form method="POST" action="#invite-section">
        <button type="submit" name="generate_player_invite">Generate Player Invite Code</button>
        <button type="submit" name="generate_coach_invite">Generate Coach Invite Code</button>
    </form>

    <hr>

    <h3>Available Invite Codes</h3>
    <?php if (empty($invites)): ?>
        <p>No active invite codes.</p>
    <?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Code</th>
            <th>Type</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($invites as $invite): ?>
        <tr>
            <td><?php echo htmlspecialchars($invite['code']); ?></td>
            <td><?php echo htmlspecialchars($invite['type']); ?></td>
            <td><?php echo htmlspecialchars($invite['created_at']); ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="cancel_code" value="<?php echo htmlspecialchars($invite['code']); ?>">
                    <input type="hidden" name="code_type" value="<?php echo htmlspecialchars($invite['type']); ?>">
                    <button type="submit" onclick="return confirm('Are you sure you want to cancel this invite?')">Cancel</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
