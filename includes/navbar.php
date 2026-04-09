        <nav class="navbar">
            <div class="navbar__container">
                <a href="<?= BASE_URL ?>/" id="navbar__logo"><img src="<?= BASE_URL ?>/images/exco_logo1.svg" alt="" id="navbar__img"></a>
                <div class="navbar__toggle" id="mobile-menu">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
                <ul class="navbar__menu">
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/" class="navbar__links">Home</a>
                    </li>
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/login?redirect=booking" class="navbar__links">Booking</a>
                    </li>
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/announcements" class="navbar__links">Announcements</a>
                    </li>
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/login?redirect=query" class="navbar__links">Query</a>
                    </li>
                    <li class="navbar__btn">
                        <a href="<?= BASE_URL ?>/login" class="button">Log In</a>
                    </li>
                </ul>
            </div>
        </nav>
