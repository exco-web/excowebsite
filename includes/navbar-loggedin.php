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
                        <a href="<?= BASE_URL ?>/booking" class="navbar__links">Booking</a>
                    </li>
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/announcements" class="navbar__links">Announcements</a>
                    </li>
                    <li class="navbar__item">
                        <a href="<?= BASE_URL ?>/query" class="navbar__links">Query</a>
                    </li>
                    <div class="dropdown">
                        <button class="dropbtn"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($user_data['name']) ?></button>
                        <div class="dropdown-content">
                            <a href="<?= BASE_URL ?>/account">Account</a>
                            <a href="<?= BASE_URL ?>/logout">Logout</a>
                        </div>
                    </div>
                </ul>
            </div>
        </nav>
