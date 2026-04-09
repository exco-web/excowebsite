<?php
require 'vendor/autoload.php';

session_start();
include("connections.php");
include("functions.php");

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    csrf_verify();

    $name = $_POST["Name"];
    $email = $_POST["Email"];
    $password = $_POST["Password"];
    $number = $_POST["Number"];

    $rate_error = check_rate_limit('signup', 10, 3600);
    if ($rate_error) {
        $error = $rate_error;
    } elseif(!empty($email) && !empty($password) && !empty($number) && !empty($name)){
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif (!preg_match('/[0-9!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]/', $password)) {
            $error = "Password must contain at least one number or symbol.";
        } else {
        $stmt = mysqli_prepare($con, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) == 0){
            increment_rate_limit('signup');
            try {
                $verification_code = (string)random_int(100000, 999999);
                $user_id = random_num(20);
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = mysqli_prepare($con, "INSERT INTO users (user_id,name,email,number,password,verification_code,email_verified_at) VALUES (?,?,?,?,?,?,NULL)");
                mysqli_stmt_bind_param($stmt2, "ssssss", $user_id, $name, $email, $number, $hashed, $verification_code);
                mysqli_stmt_execute($stmt2);

                $resend = Resend::client(RESEND_API_KEY);
                $resend->emails->send([
                    'from'    => MAIL_FROM,
                    'to'      => $email,
                    'subject' => 'Verify your Expert Consult account',
                    'html'    => '<p>Hi ' . htmlspecialchars($name) . ',</p><p>Your verification code is: <b style="font-size:30px;">' . $verification_code . '</b></p><p>Enter this code on the verification page to activate your account.</p><p>Expert Consult</p>',
                ]);

                header("Location: " . BASE_URL . "/email-verification?email=" . urlencode($email));
                exit();
            } catch (Exception $e) {
                $error = "Could not send verification email. Please try again.";
            }
        } else {
            $error = "This user already exists";
        }
        } // end password length check
    } else {
        $error = "Please enter valid information";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'Create Account'; include 'includes/head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <body>
        <?php include 'includes/navbar.php'; ?>

        <div class="login__page">
            <div class="login__card">
                <i class="fa-solid fa-circle-user login__icon"></i>
                <h2 class="login__title">Create Account</h2>

                <?php if(isset($error)): ?>
                    <p class="login__error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="post" class="login__form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="text" id="Name" name="Name" placeholder="Full name" required>
                    <input type="email" id="Email" name="Email" placeholder="Email" required>
                    <input type="text" id="Number" name="Number" placeholder="Phone number">
                    <input type="password" id="Password" name="Password" placeholder="Password" required>
                    <input type="submit" class="main__btn login__submit" value="Sign Up" name="submit">
                </form>

                <div class="login__footer">
                    <a href="<?= BASE_URL ?>/">Cancel</a>
                    <a href="<?= BASE_URL ?>/login">Already have an account?</a>
                </div>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
        <script>
            const phoneInputField = document.querySelector("#Number");
            const phoneInput = window.intlTelInput(phoneInputField, {
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            });

            phoneInputField.closest("form").addEventListener("submit", function () {
                phoneInputField.value = phoneInput.getNumber();
            });
        </script>
    </body>
</html>
