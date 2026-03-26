<?php
// Bootstrap: laad configuratie, sessiebeheer en databaseverbinding
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
// Zorg dat logs map bestaat en maak logbestand log.txt aan indien nodig
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
	@mkdir($logsDir, 0755, true);
}
// Zorg voor een leeg log.txt als het nog niet bestaat
$logFile = $logsDir . '/log.txt';
if (!file_exists($logFile)) {
	@file_put_contents($logFile, "");
}

// Register globale error/exception handlers die user-context loggen
set_error_handler(function($severity, $message, $file, $line) {
	$msg = "PHP error ($severity): $message in $file:$line";
	if (function_exists('log_error_with_user')) {
		log_error_with_user($msg, 'PHP_ERROR');
	}
	// Laat het native handler ook uitvoeren (voor display/reporting according to settings)
	return false;
});

set_exception_handler(function($e) {
	$msg = sprintf("Uncaught exception %s: %s in %s:%d\nStack: %s", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
	if (function_exists('log_error_with_user')) {
		log_error_with_user($msg, 'UNCAUGHT_EXCEPTION');
	}
	http_response_code(500);
	// Geen directe output hier (voorkom headers-already-sent). De handler logt de fout en stuurt een 500.
	exit;
});

register_shutdown_function(function() {
	$err = error_get_last();
	if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		$msg = sprintf("Shutdown fatal error: %s in %s:%d", $err['message'], $err['file'], $err['line']);
		if (function_exists('log_error_with_user')) {
			log_error_with_user($msg, 'FATAL');
		}
	}
});


