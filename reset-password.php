<?php
session_start();
include("connections.php");
include("functions.php");

$error   = null;
$success = null;
$token   = trim($_GET['token'] ?? '');

// Validate token
$user = null;
if ($token) {
    $stmt = mysqli_prepare($con, "SELECT user_id, name FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = $result ? mysqli_fetch_assoc($result) : null;
}

if (!$user) {
    $error = "This reset link is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_verify();

    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[0-9!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]/', $password)) {
        $error = "Password must contain at least one number or symbol.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($con, "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ss", $hashed, $user['user_id']);
        mysqli_stmt_execute($stmt);
        $success = "Password updated. You can now log in.";
        $user = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-minimal.php'; ?>

        <div class="login__page">
            <div class="login__card">
                <i class="fa-solid fa-lock login__icon"></i>
                <h2 class="login__title">Reset Password</h2>

                <?php if ($success): ?>
                    <p style="color:#4caf50; font-size:0.9rem; text-align:center; margin-bottom:1rem;"><?= htmlspecialchars($success) ?></p>
                    <a href="<?= BASE_URL ?>/login" class="main__btn login__submit" style="text-align:center; text-decoration:none;">Go to Login</a>
                <?php elseif ($error && !$user): ?>
                    <p class="login__error"><?= htmlspecialchars($error) ?></p>
                    <div class="login__footer">
                        <a href="<?= BASE_URL ?>/forgot-password">Request a new link</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <p class="login__error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                    <form method="POST" class="login__form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="password" name="password" placeholder="New password" required>
                        <input type="password" name="confirm"  placeholder="Confirm password" required>
                        <input type="submit" class="main__btn login__submit" value="Reset Password">
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
