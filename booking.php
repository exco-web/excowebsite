<?php
session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

$booking_error = null;
$booking_success = null;

$allowed_times = ['10:00:00','11:00:00','12:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];

// Fetch existing booking dates for calendar highlighting
$booked_stmt = mysqli_prepare($con, "SELECT date FROM bookings WHERE user_id = ? AND date >= CURDATE() AND status != 'cancelled'");
mysqli_stmt_bind_param($booked_stmt, "s", $user_data['user_id']);
mysqli_stmt_execute($booked_stmt);
$booked_result = mysqli_stmt_get_result($booked_stmt);
$booked_dates = [];
while ($r = mysqli_fetch_assoc($booked_result)) {
    $booked_dates[] = $r['date'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'], $_POST['time'])) {
    csrf_verify();

    $user_id = $user_data['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $d = DateTime::createFromFormat('Y-m-d', $date);
    $today = new DateTime('today');
    $max_date = (new DateTime('today'))->modify('+1 month');
    if (!$d || $d <= $today) {
        $booking_error = "Bookings must be made at least one day in advance.";
    } elseif ($d > $max_date) {
        $booking_error = "Bookings can only be made up to one month in advance.";
    } elseif ((int)$d->format('N') >= 6) {
        $booking_error = "Bookings are only available on weekdays.";
    } elseif (!in_array($time, $allowed_times)) {
        $booking_error = "Invalid time slot selected.";
    } else {
        $limit_stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND date >= CURDATE() AND status != 'cancelled'");
        mysqli_stmt_bind_param($limit_stmt, "s", $user_id);
        mysqli_stmt_execute($limit_stmt);
        mysqli_stmt_bind_result($limit_stmt, $active_count);
        mysqli_stmt_fetch($limit_stmt);
        mysqli_stmt_close($limit_stmt);

        if ($active_count >= 2) {
            $booking_error = "You already have 2 active bookings. Please cancel one before making another.";
        } else {
            $day_stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND date = ? AND status != 'cancelled'");
            mysqli_stmt_bind_param($day_stmt, "ss", $user_id, $date);
            mysqli_stmt_execute($day_stmt);
            mysqli_stmt_bind_result($day_stmt, $same_day_count);
            mysqli_stmt_fetch($day_stmt);
            mysqli_stmt_close($day_stmt);

            if ($same_day_count > 0) {
                $booking_error = "You already have a booking on this day.";
            } else {
                $allowed_reasons = ['Initial Consultation', 'Follow-up Appointment', 'Document Review', 'Legal Advice', 'Financial Planning', 'Other'];
                $reason = isset($_POST['reason']) && in_array($_POST['reason'], $allowed_reasons) ? $_POST['reason'] : null;
                if (!$reason) {
                    $booking_error = "Please select a reason for your visit.";
                } else {
                    $stmt = mysqli_prepare($con, "INSERT INTO bookings (user_id, date, time, reason) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "ssss", $user_id, $date, $time, $reason);
                    mysqli_stmt_execute($stmt);
                    $booking_success = "Booking confirmed for " . $d->format('l, F j Y') . " at " . date('H:i', strtotime($time)) . ".";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="everything">
        <div class="booking__page">
            <?php if ($booking_success): ?>
                <p style="color:#4caf50; margin-bottom:1rem;"><?= htmlspecialchars($booking_success) ?></p>
            <?php endif; ?>
            <?php if ($booking_error): ?>
                <p style="color:#fe4534; margin-bottom:1rem;"><?= htmlspecialchars($booking_error) ?></p>
            <?php endif; ?>
            <?php include 'includes/booking-table.php'; ?>
        </div>

        <!-- Services section-->
        <?php include 'includes/services.php'; ?>
        </div><!-- /.everything -->

        <?php include 'includes/footer.php'; ?>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
