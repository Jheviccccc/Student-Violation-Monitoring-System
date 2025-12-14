<?php
require_once __DIR__ . '/config.php';

function current_user(): ?array {
	return $_SESSION['user'] ?? null;
}

function require_login(): void {
	if (!current_user()) {
		header('Location: index.php');
		exit;
	}
}

function require_role(string $role): void {
	require_login();
	$user = current_user();
	if (!$user) {
		header('Location: index.php');
		exit;
	}
	if ($user['role'] === 'admin') {
		return; // admin always allowed
	}
	if ($user['role'] !== $role) {
		// Redirect students to their portal; others back to login
		if ($user['role'] === 'student') {
			header('Location: student.php');
			exit;
		}
		header('Location: index.php');
		exit;
	}
}

?>

