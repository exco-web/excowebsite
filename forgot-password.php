<?php
require 'vendor/autoload.php';

session_start();
include("connections.php");
include("functions.php");

$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    $rate_error = check_rate_limit('forgot_pw', 5, 900);
    if ($rate_error) {
        $error = $rate_error;
    } elseif ($email) {
        increment_rate_limit('forgot_pw');

        $stmt = mysqli_prepare($con, "SELECT user_id, name FROM users WHERE email = ? AND email_verified_at IS NOT NULL LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = $result ? mysqli_fetch_assoc($result) : null;

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expiry  = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt2 = mysqli_prepare($con, "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt2, "sss", $token, $expiry, $user['user_id']);
            mysqli_stmt_execute($stmt2);

            $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/reset-password?token=' . $token;

            try {
                $resend = Resend::client(RESEND_API_KEY);
                $resend->emails->send([
                    'from'    => MAIL_FROM,
                    'to'      => $email,
                    'subject' => 'Reset your Expert Consult password',
                    'html'    => '
                        <p>Hi ' . htmlspecialchars($user['name']) . ',</p>
                        <p>We received a request to reset your password. Click the link below to set a new one:</p>
                        <p><a href="' . $link . '">' . $link . '</a></p>
                        <p>This link expires in 1 hour. If you didn\'t request this, you can ignore this email.</p>
                        <p>Expert Consult</p>
                    ',
                ]);
            } catch (Exception $e) {
                // Silent — don't reveal if email failed
            }
        }

        // Always show success to prevent email enumeration
        $success = "If that email is registered, you'll receive a reset link shortly.";
    } else {
        $error = "Please enter your email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'Forgot Password'; include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-minimal.php'; ?>

        <div class="login__page">
            <div class="login__card">
                <i class="fa-solid fa-lock login__icon"></i>
                <h2 class="login__title">Forgot Password</h2>
                <p style="color:var(--text-muted); font-size:0.9rem; text-align:center; margin-bottom:1rem;">Enter your email and we'll send you a reset link.</p>

                <?php if ($success): ?>
                    <p style="color:#4caf50; font-size:0.9rem; text-align:center;"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p class="login__error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" class="login__form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="submit" class="main__btn login__submit" value="Send Reset Link">
                </form>
                <?php endif; ?>

                <div class="login__footer">
                    <a href="<?= BASE_URL ?>/login">Back to Login</a>
                </div>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
