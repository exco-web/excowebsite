<?php 

function check_login($con){
	if(isset($_SESSION['user_id'])){
		$id = $_SESSION['user_id'];
		$stmt = mysqli_prepare($con, "SELECT * FROM users WHERE user_id = ? LIMIT 1");
		mysqli_stmt_bind_param($stmt, "s", $id);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		if($result && mysqli_num_rows($result) > 0){
			$user_data = mysqli_fetch_assoc($result);
			return $user_data;
		}
	}

	//redirect to login
	header("Location: " . BASE_URL . "/login");
	die;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify() {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid request.');
    }
}

function get_client_ip() {
    // Use Cloudflare's real IP header if present, fall back to REMOTE_ADDR
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function check_ip_rate_limit($con, $action, $max = 10, $window = 900) {
    $ip = get_client_ip();
    $stmt = mysqli_prepare($con, "SELECT attempts, window_start FROM rate_limits WHERE ip = ? AND action = ?");
    mysqli_stmt_bind_param($stmt, "ss", $ip, $action);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$row) return null;

    $elapsed = time() - strtotime($row['window_start']);
    if ($elapsed > $window) {
        $del = mysqli_prepare($con, "DELETE FROM rate_limits WHERE ip = ? AND action = ?");
        mysqli_stmt_bind_param($del, "ss", $ip, $action);
        mysqli_stmt_execute($del);
        return null;
    }

    if ($row['attempts'] >= $max) {
        $remaining = $window - $elapsed;
        return "Too many attempts. Please wait " . ceil($remaining / 60) . " minute(s).";
    }

    return null;
}

function increment_ip_rate_limit($con, $action) {
    $ip = get_client_ip();
    $stmt = mysqli_prepare($con, "
        INSERT INTO rate_limits (ip, action, attempts, window_start)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempts = attempts + 1
    ");
    mysqli_stmt_bind_param($stmt, "ss", $ip, $action);
    mysqli_stmt_execute($stmt);
}

function clear_ip_rate_limit($con, $action) {
    $ip = get_client_ip();
    $stmt = mysqli_prepare($con, "DELETE FROM rate_limits WHERE ip = ? AND action = ?");
    mysqli_stmt_bind_param($stmt, "ss", $ip, $action);
    mysqli_stmt_execute($stmt);
}

function check_rate_limit($key, $max = 5, $window = 900) {
    $attempts_key = 'rl_attempts_' . $key;
    $time_key     = 'rl_time_' . $key;

    $now = time();
    if (!isset($_SESSION[$time_key]) || ($now - $_SESSION[$time_key]) > $window) {
        $_SESSION[$attempts_key] = 0;
        $_SESSION[$time_key]     = $now;
    }

    if ($_SESSION[$attempts_key] >= $max) {
        $remaining = $window - ($now - $_SESSION[$time_key]);
        return "Too many attempts. Please wait " . ceil($remaining / 60) . " minute(s).";
    }

    return null;
}

function increment_rate_limit($key) {
    $_SESSION['rl_attempts_' . $key] = ($_SESSION['rl_attempts_' . $key] ?? 0) + 1;
}

function clear_rate_limit($key) {
    unset($_SESSION['rl_attempts_' . $key], $_SESSION['rl_time_' . $key]);
}

function random_num($length){
	$text = "";
	if($length<5){
		$length = 5;
	}
	$len = rand(4,$length);
	for ($i=0; $i < $len; $i++) { 
		// code...
		$text .= rand(0,9);
	}
	
	return $text;
}


 ?>