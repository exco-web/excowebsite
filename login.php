<?php
session_start();
include("connections.php");
include("functions.php");

$allowed_redirects = ['index.php', 'query.php', 'account.php', 'booking.php'];
$redirect = (isset($_GET['redirect']) && in_array($_GET['redirect'], $allowed_redirects))
    ? $_GET['redirect']
    : 'index.php';

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    csrf_verify();

    $redirect = (isset($_POST['redirect']) && in_array($_POST['redirect'], $allowed_redirects))
        ? $_POST['redirect']
        : 'index.php';

    $email = $_POST["Email"];
    $password = $_POST["Password"];

    $rate_error = check_rate_limit('login');
    if ($rate_error) {
        $error = $rate_error;
    } elseif(!empty($email) && !empty($password)){
        $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if($result && mysqli_num_rows($result) > 0){
            $user_data = mysqli_fetch_assoc($result);
            if(!empty($user_data["email_verified_at"])){
                if(password_verify($password, $user_data["password"])){
                    clear_rate_limit('login');
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_data['user_id'];
                    header("Location: " . BASE_URL . "/" . $redirect);
                    die();
                } else {
                    increment_rate_limit('login');
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Email hasn't been verified. Sign up again to get an account.";
            }
        } else {
            increment_rate_limit('login');
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
                    <a href="<?= BASE_URL ?>/signup.php">Create an account</a>
                </div>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
