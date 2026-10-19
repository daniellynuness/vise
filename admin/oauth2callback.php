<?php

require_once __DIR__ . '/auth.php';

if (isset($_GET['error'])) {
	$_SESSION['auth_error'] = 'O login Google foi cancelado.';
	header('Location: /admin/login.php');
	exit;
}

$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');

try {
	if ($code === '' || $state === '') {
		throw new RuntimeException('Resposta OAuth incompleta.');
	}

	completeGoogleLogin($code, $state);
	header('Location: /admin/');
	exit;
} catch (Throwable $exception) {
	error_log('Google OAuth error: ' . $exception->getMessage());
	$_SESSION['auth'] = false;
	$_SESSION['auth_error'] = 'Não foi possível concluir o login Google.';
	header('Location: /admin/login.php');
	exit;
}

