<?php
include '../connections.php';

mysqli_query($con, "UPDATE bookings SET status = 'completed' WHERE TIMESTAMP(date, time) < NOW() AND status NOT IN ('completed', 'cancelled')");
mysqli_query($con, "DELETE FROM users WHERE email_verified_at IS NULL AND created_at < NOW() - INTERVAL 24 HOUR");
mysqli_query($con, "DELETE FROM rate_limits WHERE window_start < NOW() - INTERVAL 1 HOUR");
mysqli_query($con, "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE locked_until IS NOT NULL AND locked_until < NOW()");
