<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'newmatt');

// Ensure PHP uses Manila time for all date/time functions
if (!ini_get('date.timezone')) {
	date_default_timezone_set('Asia/Manila');
}

// Start session for the whole app
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function get_db_connection(): mysqli {
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($mysqli->connect_errno) {
		die('Database connection failed: ' . $mysqli->connect_error);
	}
	$mysqli->set_charset('utf8mb4');
	return $mysqli;
}

function h(?string $value): string {
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_post(): bool {
	return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

?>

