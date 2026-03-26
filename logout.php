<?php
include 'includes/bootstrap.php';

// Require POST and CSRF token for logout to prevent CSRF logouts
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
	// niet-permissible logout-verzoek -> redirect naar dashboard
	header('Location: dashboard.php');
	exit;
}

// perform logout
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}
session_destroy();

header("Location: index.php");