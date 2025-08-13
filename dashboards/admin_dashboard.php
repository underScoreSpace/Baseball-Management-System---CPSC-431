<?php
require '../auth_check.php';
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SESSION['role'] !== 'Admin') {
    echo "<p style='color:red;'>Access denied.</p>";
    exit;
}

$adminId = $_SESSION['user_id'];

// ============================
// Fetch Admin Info for Greeting
// ============================
$adminStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$adminStmt->execute([$adminId]);
$admin = $adminStmt->fetch();

// ============================
// Handle Actions
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['generate_manager_invite'])) {
        $email = trim($_POST['email']);

        if (!empty($email)) {
            $inviteCode = bin2hex(random_bytes(5));
            $stmt = $pdo->prepare("INSERT INTO manager_registration_codes (email, code) VALUES (?, ?)");
            $stmt->execute([$email, $inviteCode]);

            $subject = "You're Invited to Register as a Manager";
            $link = "http://localhost/baseball_team/manager_register.php?code=$inviteCode";
            $body = "Hello,\n\nYou’ve been invited to register as a team manager.\n$link\n\nInvite Code: $inviteCode\n\nThis code can only be used once.";

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ajadevtesting@gmail.com';
                $mail->Password = 'xwurtlnyffpvtwjf';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('ajadevtesting@gmail.com', 'Baseball League Admin');
                $mail->addAddress($email);
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
            } catch (Exception $e) {
                // Optional: log email failure
            }
        }

        header("Location: admin_dashboard.php");
        exit;
    }

    if (isset($_POST['cancel_invite_code'])) {
        $code = $_POST['cancel_invite_code'];
        $pdo->prepare("DELETE FROM manager_registration_codes WHERE code = ? AND is_used = 0")->execute([$code]);
        header("Location: admin_dashboard.php");
        exit;
    }
}

// ============================
// Fetch Data for Tables
// ============================
$pendingInvites = $pdo->query("SELECT email, code, created_at FROM manager_registration_codes WHERE is_used = 0 ORDER BY created_at DESC")->fetchAll();

$activeManagers = $pdo->query("
    SELECT u.username, u.first_name, u.last_name, u.email, t.team_name
    FROM users u
    LEFT JOIN team_managers tm ON u.id = tm.user_id
    LEFT JOIN teams t ON tm.team_id = t.id
    WHERE u.role = 'Manager'
    ORDER BY u.username ASC
")->fetchAll();

$allTeams = $pdo->query("SELECT team_name FROM teams ORDER BY team_name ASC")->fetchAll();
?>

<!-- ========================= -->
<!-- Begin HTML Output -->
<!-- ========================= -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin_dash.css">
</head>
<body>
<div class="container">

    <div class="header-bar">
        <h2>Admin Dashboard</h2>
        <div class="top-links">
            <a href="../manage_account.php">Manage Account</a> |
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <p>Welcome, Admin <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>!</p>

    <hr>

    <!-- Generate Invite -->
    <h3>Generate Manager Invite Code</h3>
    <form method="POST">
        Manager Email: <input type="email" name="email" required><br><br>
        <input type="hidden" name="generate_manager_invite" value="1">
        <button type="submit">Generate Invite Code</button>
    </form>

    <hr>

    <!-- Pending Invites -->
    <h3>Pending Manager Invites</h3>
    <?php if (empty($pendingInvites)): ?>
        <p>No pending invites.</p>
    <?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Email</th>
            <th>Invite Code</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php foreach ($pendingInvites as $invite): ?>
        <tr>
            <td><?php echo htmlspecialchars($invite['email']); ?></td>
            <td><?php echo htmlspecialchars($invite['code']); ?></td>
            <td><?php echo htmlspecialchars($invite['created_at']); ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="cancel_invite_code" value="<?php echo htmlspecialchars($invite['code']); ?>">
                    <button type="submit" onclick="return confirm('Cancel this invite?')">Cancel</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <hr>

    <!-- Active Managers -->
    <h3>List of Active Managers</h3>
    <?php if (empty($activeManagers)): ?>
        <p>No managers registered yet.</p>
    <?php else: ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Assigned Team</th>
        </tr>
        <?php foreach ($activeManagers as $manager): ?>
        <tr>
            <td><?php echo htmlspecialchars($manager['username']); ?></td>
            <td><?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></td>
            <td><?php echo htmlspecialchars($manager['email']); ?></td>
            <td><?php echo htmlspecialchars($manager['team_name'] ?? 'Unassigned'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <hr>

    <!-- All Teams -->
    <h3>List of All Teams</h3>
    <?php if (empty($allTeams)): ?>
        <p>No teams created yet.</p>
    <?php else: ?>
    <table border="1" cellpadding="8">
        <tr><th>Team Name</th></tr>
        <?php foreach ($allTeams as $team): ?>
        <tr><td><?php echo htmlspecialchars($team['team_name']); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

</div>
</body>
</html>