<?php
// Merged into login.php — redirect to login with query destination
include("connections.php");
header("Location: " . BASE_URL . "/login.php?redirect=query.php");
exit();
