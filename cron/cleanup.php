<?php
include '../connections.php';

mysqli_query($con, "UPDATE bookings SET status = 'completed' WHERE TIMESTAMP(date, time) < NOW() AND status NOT IN ('completed', 'cancelled')");
mysqli_query($con, "DELETE FROM users WHERE email_verified_at IS NULL AND created_at < NOW() - INTERVAL 24 HOUR");
