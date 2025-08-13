<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// ============================
// Protect Against Unauthorized Access
// ============================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit;
}
?>