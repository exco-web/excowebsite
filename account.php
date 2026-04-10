<?php
session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_date'])) {
    csrf_verify();
    $remove_date = $_POST['remove_date'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $remove_date)) {
        $cutoff = new DateTime('now + 24 hours');
        $booking_dt = new DateTime($remove_date . ' 23:59:59');
        if ($booking_dt < $cutoff) {
            $remove_error = "Bookings within 24 hours cannot be cancelled. Please contact us directly.";
        } else {
            $stmt = mysqli_prepare($con, "DELETE FROM bookings WHERE user_id = ? AND date = ?");
            mysqli_stmt_bind_param($stmt, "ss", $user_data['user_id'], $remove_date);
            mysqli_stmt_execute($stmt);
        }
    }
}

$bookings_result = null;
$stmt = mysqli_prepare($con, "SELECT date, time, status, reason FROM bookings WHERE user_id = ? ORDER BY date ASC, time ASC");
mysqli_stmt_bind_param($stmt, "s", $user_data['user_id']);
mysqli_stmt_execute($stmt);
$bookings_result = mysqli_stmt_get_result($stmt);

$email = $user_data['email'];
$row = [];
$stmt = mysqli_prepare($con, "SELECT * FROM individuals WHERE IndPrimeEmail = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? (mysqli_fetch_assoc($result) ?? []) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'My Account'; include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="everything">
        <div class="account">
            <h1><i class="fas fa-user-circle"> </i>
                <div style="font-size:2rem"><?= htmlspecialchars($user_data["email"]) ?></div>
            </h1>
            <p style="color:#666; font-size:0.8rem;">Status: <?= htmlspecialchars(ucfirst($user_data['role'])) ?></p>
            <?php if ($user_data['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin" class="main__btn main__btn--dark" style="margin:1rem 0 0 0;">Enter Admin View</a>
            <?php endif; ?>

            <div class="profile__card">
                <div class="profile__field">
                    <span class="profile__label">Title</span>
                    <span class="profile__value"><?= htmlspecialchars($row["Title"] ?? '—') ?></span>
                </div>
                <div class="profile__field">
                    <span class="profile__label">Name</span>
                    <span class="profile__value"><?= htmlspecialchars($row["IndID2"] ?? '—') ?></span>
                </div>
                <div class="profile__field">
                    <span class="profile__label">Phone</span>
                    <span class="profile__value"><?= htmlspecialchars($row["IndMobile"] ?? '—') ?></span>
                </div>
                <div class="profile__field">
                    <span class="profile__label">Email</span>
                    <span class="profile__value"><?= htmlspecialchars($row["IndPrimeEmail"] ?? '—') ?></span>
                </div>
                <div class="profile__field profile__field--full">
                    <span class="profile__label">Address</span>
                    <span class="profile__value">
                        <?php
                            $addr = array_filter([
                                $row["IndStreet1"] ?? '',
                                $row["IndStreet2"] ?? '',
                                $row["IndStreet3"] ?? '',
                                $row["IndCity"]     ?? '',
                                $row["IndProvince"] ?? '',
                                $row["IndPcode"]    ?? '',
                            ]);
                            echo $addr ? htmlspecialchars(implode(', ', $addr)) : '—';
                        ?>
                    </span>
                </div>
            </div>

            <div class="account__section">
                <h3 class="account__section-title">Your Bookings</h3>
                <?php if (isset($remove_error)): ?>
                    <p style="color:var(--accent); font-size:0.85rem; margin-bottom:0.5rem;"><?= htmlspecialchars($remove_error) ?></p>
                <?php endif; ?>
                <div class="table__wrapper" style="padding:0; margin: 0;">
                    <table class="content__table content__table--bookings">
                        <?php if ($bookings_result && mysqli_num_rows($bookings_result) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                <tr>
                                    <td><?= date('l, F j Y', strtotime($booking['date'])) ?> &middot; <?= date('H:i', strtotime($booking['time'])) ?></td>
                                    <td><?= htmlspecialchars($booking['reason'] ?? '—') ?></td>
                                    <td><span class="bookings__status bookings__status--<?= htmlspecialchars($booking['status']) ?>"><?= htmlspecialchars(ucfirst($booking['status'])) ?></span></td>
                                    <td style="text-align:right;">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="remove_date" value="<?= htmlspecialchars($booking['date']) ?>">
                                            <button type="submit" class="booking__remove">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4"><a href="<?= BASE_URL ?>/booking" style="color:#fe4534;">No bookings yet. Make one here.</a></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="account__section">
                <h3 class="account__section-title">Your Forms</h3>
                <p style="color:#666; font-size:0.9rem;">No forms yet.</p>
            </div>

            <div style="padding: 2rem 0;">
                <a href="<?= BASE_URL ?>/" class="main__btn" style="margin:0;">Back to Home</a>
            </div>
        </div>

        <!-- Services section-->
        <?php include 'includes/services.php'; ?>
        </div><!-- /.everything -->

        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
