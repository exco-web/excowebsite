<?php
session_start();
include("connections.php");

if(isset($_SESSION['user_id'])){
	unset($_SESSION['user_id']);
}

header("Location: " . BASE_URL . "/login.php");
?>