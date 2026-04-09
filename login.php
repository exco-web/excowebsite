<?php
session_start();
include("connections.php");
include("functions.php");

$allowed_redirects = ['index', 'query', 'account', 'booking'];
$redirect = (isset($_GET['redirect']) && in_array($_GET['redirect'], $allowed_redirects))
    ? $_GET['redirect']
    : 'index.php';

// Ensure required columns and table exist
mysqli_query($con, "CREATE TABLE IF NOT EXISTS rate_limits (
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL,
    PRIMARY KEY (ip, action)
)");
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_attempts INT NOT NULL DEFAULT 0");
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL");

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    csrf_verify();

    $redirect = (isset($_POST['redirect']) && in_array($_POST['redirect'], $allowed_redirects))
        ? $_POST['redirect']
        : 'index.php';

    $email    = $_POST["Email"];
    $password = $_POST["Password"];

    $ip_error = check_ip_rate_limit($con, 'login', 20, 900);
    if ($ip_error) {
        $error = $ip_error;
    } elseif (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result    = mysqli_stmt_get_result($stmt);
        $user_data = $result ? mysqli_fetch_assoc($result) : null;

        if ($user_data) {
            // Check account lockout
            if (!empty($user_data['locked_until']) && strtotime($user_data['locked_until']) > time()) {
                $mins = ceil((strtotime($user_data['locked_until']) - time()) / 60);
                $error = "Account locked. Try again in {$mins} minute(s) or reset your password.";
            } elseif (empty($user_data['email_verified_at'])) {
                $error = "Email hasn't been verified. Sign up again to get an account.";
            } elseif (password_verify($password, $user_data["password"])) {
                // Success — clear all counters
                clear_ip_rate_limit($con, 'login');
                $reset = mysqli_prepare($con, "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?");
                mysqli_stmt_bind_param($reset, "s", $user_data['user_id']);
                mysqli_stmt_execute($reset);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_data['user_id'];
                header("Location: " . BASE_URL . "/" . $redirect);
                die();
            } else {
                // Wrong password
                increment_ip_rate_limit($con, 'login');
                $new_attempts = $user_data['failed_attempts'] + 1;
                if ($new_attempts >= 10) {
                    $lock = mysqli_prepare($con, "UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE user_id = ?");
                    mysqli_stmt_bind_param($lock, "is", $new_attempts, $user_data['user_id']);
                    mysqli_stmt_execute($lock);
                    $error = "Too many failed attempts. Account locked for 30 minutes.";
                } else {
                    $upd = mysqli_prepare($con, "UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($upd, "is", $new_attempts, $user_data['user_id']);
                    mysqli_stmt_execute($upd);
                    $error = "Invalid email or password.";
                }
            }
        } else {
            increment_ip_rate_limit($con, 'login');
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter valid information";
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
                <i class="fa-solid fa-circle-user login__icon"></i>
                <h2 class="login__title">Sign In</h2>

                <?php if(isset($error)): ?>
                    <p class="login__error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form action="" method="post" class="login__form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <input type="email" id="Email" name="Email" placeholder="Email" required>
                    <input type="password" id="Password" name="Password" placeholder="Password" required>
                    <input type="submit" class="main__btn login__submit" value="Login">
                </form>

                <div class="login__footer">
                    <a href="<?= BASE_URL ?>/">Cancel</a>
                    <a href="<?= BASE_URL ?>/forgot-password.php">Forgot password?</a>
                    <a href="<?= BASE_URL ?>/signup.php">Create an account</a>
                </div>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
