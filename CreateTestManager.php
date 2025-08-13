<?php
require 'db_connect.php';

$username = 'manager1';
$email = 'manager@example.com';
$password = 'password'; // plain text
$role = 'Manager';

// Hash the password with PHP
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into the database
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $email, $password_hash, $role]);

echo "Manager account created!";