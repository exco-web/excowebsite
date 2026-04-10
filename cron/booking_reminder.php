<?php
require '/home/u916018987/public_html/vendor/autoload.php';

include '/home/u916018987/public_html/connections.php';

// Fetch all confirmed bookings for tomorrow
$stmt = mysqli_prepare($con, "
    SELECT
        b.date, b.time, b.reason,
        COALESCE(u.name,  b.guest_name)  AS name,
        COALESCE(u.email, b.guest_email) AS email
    FROM bookings b
    LEFT JOIN users u ON u.user_id = b.user_id
    WHERE b.date = CURDATE() + INTERVAL 1 DAY
      AND b.status = 'confirmed'
      AND COALESCE(u.email, b.guest_email) IS NOT NULL
");
mysqli_stmt_execute($stmt);
$bookings = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$resend = Resend::client(RESEND_API_KEY);

foreach ($bookings as $booking) {
    try {
        $date   = date('l, F j Y', strtotime($booking['date']));
        $time   = date('H:i', strtotime($booking['time']));
        $name   = htmlspecialchars($booking['name']);
        $reason = htmlspecialchars($booking['reason'] ?? 'your appointment');

        $resend->emails->send([
            'from'    => MAIL_FROM,
            'to'      => $booking['email'],
            'subject' => 'Reminder: Your appointment is tomorrow',
            'html'    => "
                <p>Hi {$name},</p>
                <p>This is a reminder that you have an appointment with <strong>Expert Consult</strong> tomorrow.</p>
                <p>
                    <strong>Date:</strong> {$date}<br>
                    <strong>Time:</strong> {$time}<br>
                    <strong>Reason:</strong> {$reason}
                </p>
                <p>If you need to cancel or reschedule, please contact us as soon as possible.</p>
                <p>We look forward to seeing you.</p>
                <p>Expert Consult</p>
            ",
        ]);
    } catch (Exception $e) {
        // Silent fail
    }
}
