<?php
include '/home/u916018987/public_html/connections.php';

$completed = mysqli_query($con, "SELECT id FROM bookings WHERE TIMESTAMP(date, time) < NOW() AND status NOT IN ('completed', 'cancelled')");
$completed_ids = [];
while ($r = mysqli_fetch_assoc($completed)) $completed_ids[] = $r['id'];

mysqli_query($con, "UPDATE bookings SET status = 'completed' WHERE TIMESTAMP(date, time) < NOW() AND status NOT IN ('completed', 'cancelled')");
echo "Bookings updated: " . mysqli_affected_rows($con) . "\n";

foreach ($completed_ids as $bid) {
    mysqli_query($con, "INSERT INTO booking_logs (booking_id, action, changed_by, details) VALUES ({$bid}, 'completed', 'system', 'Auto-completed by cron')");
}

mysqli_query($con, "DELETE FROM users WHERE email_verified_at IS NULL AND created_at < NOW() - INTERVAL 24 HOUR");
echo "Unverified users deleted: " . mysqli_affected_rows($con) . "\n";

mysqli_query($con, "DELETE FROM rate_limits WHERE window_start < NOW() - INTERVAL 1 HOUR");
echo "Rate limits cleared: " . mysqli_affected_rows($con) . "\n";

mysqli_query($con, "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE locked_until IS NOT NULL AND locked_until < NOW()");
echo "Locked accounts cleared: " . mysqli_affected_rows($con) . "\n";

echo "Cleanup ran at: " . date('Y-m-d H:i:s') . "\n";
