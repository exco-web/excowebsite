<?php
session_start();
include("connections.php");
include("functions.php");
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-minimal.php'; ?>

        <br><br>
        <h1 style="margin:auto; color:#cecece; text-align:center; max-width:60%;">
            Account made. You can login now.
        </h1>
        <br>
        <div style="text-align:center">
            <a href="<?= BASE_URL ?>/login" class="main__btn">Log In</a>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
