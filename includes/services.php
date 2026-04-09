            <div class="services">
                <div class="services__container">
                    <h1>Our Services</h1>
                    <div class="services__content">
                        <div class="services__card">
                            <img src="<?= BASE_URL ?>/images/appointment.jpg" alt="Book a professional accounting and financial consulting appointment with Expert Consult">
                            <a href="<?= BASE_URL ?>/booking" class="card__link"></a>
                            <h2>Book a time to enter</h2>
                            <p>We are only open on weekdays</p>
                            <a href="<?= BASE_URL ?>/booking" class="button">Get Started</a>
                        </div>
                        <div class="services__card">
                            <?php $__email_href = isset($_SESSION['user_id']) ? BASE_URL . '/query' : BASE_URL . '/login?redirect=query'; ?>
                            <img src="<?= BASE_URL ?>/images/email.jpg" alt="Contact Expert Consult by email for accounting, tax and financial consulting advice">
                            <a href="<?= $__email_href ?>" class="card__link"></a>
                            <h2>Send us an email</h2>
                            <p>We'll get back to you as soon as possible</p>
                            <a href="<?= $__email_href ?>" class="button">Email Us</a>
                        </div>
                    </div>
                </div>
            </div>