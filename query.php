<?php
session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

$gmail_url = 'https://mail.google.com/mail/?view=cm&to=decidio%40gmail.com';
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'Contact Us'; include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="everything">
        <div class="query__page">
            <div class="query__header">
                <h1>Get in Touch</h1>
                <p>Have a question? Click below to send us an email directly.</p>
            </div>

            <div class="query__card" style="align-items:center; text-align:center;">
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
                    This will open Gmail with our address pre-filled. Write your message and send it from your own inbox.
                </p>
                <a href="<?= $gmail_url ?>" target="_blank" class="admin__save" style="text-decoration:none; padding:0.6rem 2rem; font-size:0.95rem;">
                    Open Gmail
                </a>
            </div>
        </div>

        <?php include 'includes/services.php'; ?>
        </div><!-- /.everything -->
        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
