<?php
session_start();
include("connections.php");

// Change these:
$email    = 'your@email.com';
$password = 'yourplaintextpassword';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($con, "UPDATE users SET password = ? WHERE email = ?");
mysqli_stmt_bind_param($stmt, "ss", $hash, $email);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    echo "Password updated successfully. <a href='/excowebsite/login.php'>Log in</a>";
} else {
    echo "No user found with that email.";
}
