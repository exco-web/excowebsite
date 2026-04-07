            <div class="services">
                <div class="services__container">
                    <h1>Our Services</h1>
                    <div class="services__content">
                        <div class="services__card">
                            <a href="<?= BASE_URL ?>/booking.php" class="card__link"></a>
                            <h2>Book a time to enter</h2>
                            <p>We are only open on weekdays</p>
                            <a href="<?= BASE_URL ?>/booking.php" class="button">Get Started</a>
                        </div>
                        <div class="services__card">
                            <?php $__email_href = isset($_SESSION['user_id']) ? BASE_URL . '/query.php' : BASE_URL . '/login.php?redirect=query.php'; ?>
                            <a href="<?= $__email_href ?>" class="card__link"></a>
                            <h2>Send us an email</h2>
                            <p>We'll get back to you as soon as possible</p>
                            <a href="<?= $__email_href ?>" class="button">Email Us</a>
                        </div>
                    </div>
                </div>
            </div>