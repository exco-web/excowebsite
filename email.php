<?php
session_start();
include("connections.php");
include("functions.php");
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar.php'; ?>

        <div class="main">
            <div class="main__container">
                <div class="main__content">
                    <h1>COMING SOON!</h1>
                    <a href="<?= BASE_URL ?>/" class="main__btn">Back to Home</a>
                </div>
            </div>
        </div>

        <!-- Services section-->
        <div class="services">
            <h1>Our Services</h1>
            <div class="services__container">
                <div class="services__card">
                    <a href="<?= BASE_URL ?>/booking" class="card__link"></a>
                    <h2>Book a time to enter</h2>
                    <p>We are only open on weekdays</p>
                    <a href="<?= BASE_URL ?>/booking" class="button">Get Started</a>
                </div>
                <div class="services__card">
                    <a href="<?= BASE_URL ?>/login?redirect=query" class="card__link"></a>
                    <h2>Send us an email</h2>
                    <p>We'll get back to you as soon as possible</p>
                    <a href="<?= BASE_URL ?>/login?redirect=query" class="button">Email Us</a>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
