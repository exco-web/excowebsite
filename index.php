<?php
session_start();
include("connections.php");
include("functions.php");

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
    }
}
$logged_in = $user_data !== null;

$recent_announcements = mysqli_query($con, "SELECT id, title, tag, created_at FROM announcements ORDER BY created_at DESC LIMIT 3");
$recent_announcements = $recent_announcements ? mysqli_fetch_all($recent_announcements, MYSQLI_ASSOC) : [];

$tag_labels = [
    'community_update' => 'Community Update',
    'important_info'   => 'Important Info',
    'client_outreach'  => 'Client Outreach',
];
?>
<!DOCTYPE html>
<html lang="en">
    <?php
        $page_title = 'Home';
        $page_description = 'Expert Consult offers professional accounting and financial consulting services. Book a consultation online today.';
        include 'includes/head.php';
    ?>
    <body>
        <?php if ($logged_in): ?>
            <?php include 'includes/navbar-loggedin.php'; ?>
        <?php else: ?>
            <?php include 'includes/navbar.php'; ?>
        <?php endif; ?>

        <div class="everything">
            <div class="main">
                <div class="main__container">
                    <div class="main__content">
                        <div id="main__banner">
                            <?php include 'includes/banner.php'; ?>
                        </div>
                        <div style="filter: drop-shadow(0 0 2px rgba(0, 0, 0,   0.75))">
                            <h1>
                                <span class="animate brand--expert">EXPERT</span>
                                <span class="animate brand--consult">CONSULT</span>
                            </h1>
                            <?php if ($logged_in): ?>
                                <h3 class="animate">Welcome back, <?= htmlspecialchars($user_data['name']) ?>.</h3>
                            <?php endif; ?>
                        </div>
                        <?php if ($logged_in): ?>
                            <p class="animateLong">You now have full access to the website.</p>
                        <?php else: ?>
                            <h3 class="animateLong">Cutting-edge consulting for a fast-moving world.</h3>
                            <p class="animateLong">We combine deep industry expertise with forward-thinking strategy to help businesses adapt, grow, and lead. Whether you're navigating change or planning your next move, our consultants are ready to help.</p>
                            <div class="animateSlide" style="margin-top:1rem;">
                                <a href="<?= BASE_URL ?>/login" class="main__btn main__btn--dark">Get Started</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="main__img--container">
                        <img src="<?= BASE_URL ?>/images/pic1.svg" class="animateLong" alt="Expert Consult illustration" id="main__img">
                    </div>

                    <?php if ($recent_announcements): ?>
                    <div class="main__announcements">
                        <h3 class="main__announcements__heading">Latest Updates</h3>
                        <div class="main__announcements__list">
                            <?php foreach ($recent_announcements as $a): ?>
                                <a href="<?= BASE_URL ?>/announcements?id=<?= (int)$a['id'] ?>" class="main__announcements__item">
                                    <?php if (!empty($a['tag'])): ?>
                                        <span class="announcements__tag announcements__tag--<?= htmlspecialchars($a['tag']) ?>"><?= htmlspecialchars($tag_labels[$a['tag']] ?? $a['tag']) ?></span>
                                    <?php endif; ?>
                                    <p class="main__announcements__title"><?= htmlspecialchars($a['title']) ?></p>
                                    <p class="main__announcements__date"><?= date('j M Y', strtotime($a['created_at'])) ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= BASE_URL ?>/announcements" class="main__announcements__all">View all &rarr;</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Services section-->
            <?php include 'includes/services.php'?>
        </div>

        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
