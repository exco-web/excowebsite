<?php
include '../connections.php';

mysqli_query($con, "DELETE FROM bookings WHERE TIMESTAMP(date, time) < NOW()");
mysqli_query($con, "DELETE FROM users WHERE email_verified_at IS NULL AND created_at < NOW() - INTERVAL 24 HOUR");
