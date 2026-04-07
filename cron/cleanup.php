<?php
include '../connections.php';

mysqli_query($con, "DELETE FROM bookings WHERE TIMESTAMP(date, time) < NOW()");
