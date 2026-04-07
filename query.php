<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email   = trim($_POST['email'] ?? '');
    $number  = trim($_POST['number'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($email && $subject && $message) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.elasticemail.com';
            $mail->SMTPAuth   = true;
            $mail->AuthType   = 'LOGIN';
            $mail->Username   = 'exco.website@gmail.com';
            $mail->Password   = '598CE37DE7598B017D3D7DDF9FA345FD781D';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 2525;

            $mail->setFrom('exco.website@gmail.com', 'EXPERT CONSULT WEBSITE');
            $mail->addAddress('exco.website@gmail.com');
            $mail->addReplyTo($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = "Email: " . htmlspecialchars($email) . "<br>Phone number: " . htmlspecialchars($number) . "<br>Subject: " . htmlspecialchars($subject) . "<br><br>Message:<br>" . nl2br(htmlspecialchars($message));
            $mail->send();
            $success = "Message sent successfully!";
        } catch (Exception $e) {
            $error = "Message could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="everything">
        <div class="query__page">
            <div class="query__header">
                <h1>Get in Touch</h1>
                <p>Have a question? Send us a message and we'll get back to you as soon as possible.</p>
            </div>

            <div class="query__card">
                <?php if ($success): ?>
                    <p style="color:var(--success); font-size:0.9rem; margin-bottom:0.75rem;"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p style="color:var(--accent); font-size:0.9rem; margin-bottom:0.75rem;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form class="query__form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="query__form__row">
                        <input type="email" placeholder="Email" name="email" value="<?= htmlspecialchars($user_data["email"]) ?>">
                        <input type="text" placeholder="Phone Number" name="number" value="<?= htmlspecialchars($user_data["number"]) ?>">
                    </div>
                    <input type="text" placeholder="Subject" name="subject" autocomplete="off">
                    <textarea name="message" placeholder="Your message..." rows="8"></textarea>
                    <button type="submit" class="admin__save" style="margin-top:0.25rem; padding:0.5rem 1.5rem; font-size:0.9rem; align-self:center;">Send Message</button>
                </form>
            </div>
        </div>

        <?php include 'includes/services.php'; ?>
        </div><!-- /.everything -->
        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
