<?php
session_start();
include("connections.php");
include("functions.php");

$error = null;

if (isset($_POST["verify_email"])) {
    csrf_verify();

    $email             = trim($_POST['email'] ?? '');
    $verification_code = trim($_POST['verification_code'] ?? '');

    if ($email && $verification_code) {
        $stmt = mysqli_prepare($con, "UPDATE users SET email_verified_at = NOW() WHERE email = ? AND verification_code = ? AND email_verified_at IS NULL");
        mysqli_stmt_bind_param($stmt, "ss", $email, $verification_code);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: " . BASE_URL . "/accountmade");
            exit();
        } else {
            $error = "Invalid or already used verification code. Please check and try again.";
        }
    } else {
        $error = "Please enter your verification code.";
    }
}

$email_display = htmlspecialchars($_GET['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-minimal.php'; ?>

        <div class="login__page">
            <div class="login__card">
                <i class="fa-solid fa-envelope login__icon" style="font-size:3rem;"></i>
                <h2 class="login__title">Verify your email</h2>
                <p style="color:var(--text-muted); font-size:0.9rem; text-align:center; margin-bottom:1rem;">
                    We've sent a 6-digit code to your email. Check your spam folder if you don't see it.
                </p>

                <?php if ($error): ?>
                    <p class="login__error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="POST" class="login__form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="email" value="<?= $email_display ?>">
                    <input type="text" name="verification_code" placeholder="6-digit code" maxlength="6" autocomplete="one-time-code" required>
                    <input type="submit" name="verify_email" class="main__btn login__submit" value="Verify Email">
                </form>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
