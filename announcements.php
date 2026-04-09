<?php
session_start();
include("connections.php");
include("functions.php");

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $id   = $_SESSION['user_id'];
    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
    }
}
$is_admin = $user_data && $user_data['role'] === 'admin';

$allowed_tags = ['', 'community_update', 'important_info', 'client_outreach'];
$tag_labels   = [
    ''                 => 'General',
    'community_update' => 'Community Update',
    'important_info'   => 'Important Info',
    'client_outreach'  => 'Client Outreach',
];

// Admin: create announcement
$create_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $is_admin) {
    csrf_verify();
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tag     = in_array($_POST['tag'] ?? '', $allowed_tags) ? $_POST['tag'] : '';
    if ($title && $content) {
        $stmt = mysqli_prepare($con, "INSERT INTO announcements (user_id, title, tag, content, viewcount) VALUES (?, ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "ssss", $user_data['user_id'], $title, $tag, $content);
        mysqli_stmt_execute($stmt);
        header("Location: " . BASE_URL . "/announcements");
        exit();
    } else {
        $create_error = "Title and content are required.";
    }
}

// Admin: delete announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && $is_admin) {
    csrf_verify();
    $del_id = (int)$_POST['announcement_id'];
    $stmt   = mysqli_prepare($con, "DELETE FROM announcements WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    mysqli_stmt_execute($stmt);
    header("Location: " . BASE_URL . "/announcements");
    exit();
}

// Single announcement view
$single = null;
if (isset($_GET['id'])) {
    $view_id = (int)$_GET['id'];
    $stmt    = mysqli_prepare($con, "SELECT * FROM announcements WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $view_id);
    mysqli_stmt_execute($stmt);
    $single = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($single && empty($_SESSION['viewed_announcements'][$view_id])) {
        $stmt2 = mysqli_prepare($con, "UPDATE announcements SET viewcount = viewcount + 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $view_id);
        mysqli_stmt_execute($stmt2);
        $_SESSION['viewed_announcements'][$view_id] = true;
    }
}

// Fetch all announcements
$announcements = mysqli_query($con, "SELECT * FROM announcements ORDER BY created_at DESC");
$all = $announcements ? mysqli_fetch_all($announcements, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'Announcements'; include 'includes/head.php'; ?>
    <body>
        <?php if ($user_data): ?>
            <?php include 'includes/navbar-loggedin.php'; ?>
        <?php else: ?>
            <?php include 'includes/navbar.php'; ?>
        <?php endif; ?>

        <div class="everything">
        <div class="announcements__page">

            <?php if ($single): ?>
                <!-- Single announcement view -->
                <div class="announcements__single">
                    <a href="<?= BASE_URL ?>/announcements" class="announcements__back">&larr; All Announcements</a>
                    <div class="announcements__single__card">
                        <h1 class="announcements__single__title"><?= htmlspecialchars($single['title']) ?></h1>
                        <?php if (!empty($single['tag'])): ?>
                            <span class="announcements__tag announcements__tag--<?= htmlspecialchars($single['tag']) ?>"><?= htmlspecialchars($tag_labels[$single['tag']] ?? $single['tag']) ?></span>
                        <?php endif; ?>
                        <p class="announcements__meta">
                            <?= date('j F Y', strtotime($single['created_at'])) ?>
                            &middot; <i class="fas fa-eye"></i> <?= (int)$single['viewcount'] ?> views
                        </p>
                        <div class="announcements__single__body"><?= nl2br(htmlspecialchars($single['content'])) ?></div>
                        <?php if ($is_admin): ?>
                            <form method="POST" style="margin-top:2rem;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?= (int)$single['id'] ?>">
                                <button type="submit" class="admin__remove">Delete Announcement</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- List view -->
                <a href="<?= BASE_URL ?>/" class="announcements__back">&larr; Back To Home</a>
                <div class="announcements__header">
                    <h1>Announcements</h1>
                </div>

                <?php if ($is_admin): ?>
                    <div class="announcements__create">
                        <h2 class="announcements__create__title">New Announcement</h2>
                        <?php if ($create_error): ?>
                            <p style="color:var(--accent); font-size:0.85rem; margin-bottom:0.75rem;"><?= htmlspecialchars($create_error) ?></p>
                        <?php endif; ?>
                        <form method="POST" class="announcements__create__form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="create">
                            <input type="text" name="title" placeholder="Title" maxlength="100" required>
                            <select name="tag" class="admin__select">
                                <?php foreach ($tag_labels as $val => $label): ?>
                                    <option value="<?= $val ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="content" placeholder="Content..." rows="16" required></textarea>
                            <button type="submit" class="admin__save" style="align-self:flex-start;">Post</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($all): ?>
                    <div class="announcements__list">
                        <?php foreach ($all as $a): ?>
                            <div class="announcements__card">
                                <div class="announcements__card__body">
                                    <h2 class="announcements__card__title">
                                        <a href="<?= BASE_URL ?>/announcements?id=<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a>
                                    </h2>
                                    <?php if (!empty($a['tag'])): ?>
                                        <span class="announcements__tag announcements__tag--<?= htmlspecialchars($a['tag']) ?>"><?= htmlspecialchars($tag_labels[$a['tag']] ?? $a['tag']) ?></span>
                                    <?php endif; ?>
                                    <p class="announcements__meta">
                                        <?= date('j F Y', strtotime($a['created_at'])) ?>
                                        &middot; <i class="fas fa-eye"></i> <?= (int)$a['viewcount'] ?> views
                                    </p>
                                    <p class="announcements__card__preview"><?= htmlspecialchars(mb_strimwidth($a['content'], 0, 160, '…')) ?></p>
                                </div>
                                <div class="announcements__card__footer">
                                    <a href="<?= BASE_URL ?>/announcements?id=<?= (int)$a['id'] ?>" class="announcements__read-more">Read more &rarr;</a>
                                    <?php if ($is_admin): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="admin__remove" style="font-size:0.75rem; padding:3px 10px;">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="announcements__empty">No announcements yet.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        </div><!-- /.everything -->

        <?php include 'includes/footer.php'; ?>
        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
