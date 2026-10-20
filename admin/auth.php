<?php

/*
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Copyright (c) 2025 Fernando Mercês
 *
 * Google OAuth authentication shared by the administrative pages.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
	$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

	session_set_cookie_params([
		'httponly' => true,
		'samesite' => 'Lax',
		'secure' => $secure,
	]);

	session_start();
}

function googleConfig(string $name): string
{
	$value = getenv($name);

	return $value === false ? '' : trim($value);
}

function googleIsConfigured(): bool
{
	return googleConfig('GOOGLE_CLIENT_ID') !== '' &&
		googleConfig('GOOGLE_CLIENT_SECRET') !== '' &&
		googleConfig('GOOGLE_REDIRECT_URI') !== '' &&
		(googleConfig('GOOGLE_ALLOWED_EMAILS') !== '' || googleConfig('GOOGLE_ALLOWED_DOMAIN') !== '');
}

function googleAuthorizationUrl(): void
{
	if (!googleIsConfigured()) {
		$_SESSION['auth_error'] = 'A autenticação Google ainda não foi configurada.';
		header('Location: /admin/login.php');
		exit;
	}

	$state = bin2hex(random_bytes(32));
	$_SESSION['google_oauth_state'] = $state;

	$params = [
		'client_id' => googleConfig('GOOGLE_CLIENT_ID'),
		'redirect_uri' => googleConfig('GOOGLE_REDIRECT_URI'),
		'response_type' => 'code',
		'scope' => 'openid email profile',
		'access_type' => 'online',
		'include_granted_scopes' => 'true',
		'state' => $state,
		'prompt' => 'select_account',
	];

	header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
	exit;
}

function googleRequest(string $url, array $headers = [], ?string $postData = null): array
{
	if (!function_exists('curl_init')) {
		throw new RuntimeException('A extensão cURL do PHP não está instalada.');
	}

	$curl = curl_init($url);
	$options = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_HTTPHEADER => $headers,
	];

	if ($postData !== null) {
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = $postData;
	}

	curl_setopt_array($curl, $options);
	$body = curl_exec($curl);
	$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);

	if ($body === false || $error !== '') {
		throw new RuntimeException('Falha na comunicação com o Google: ' . $error);
	}

	if ($status < 200 || $status >= 300) {
		throw new RuntimeException('O Google retornou HTTP ' . $status . '.');
	}

	$data = json_decode($body, true);
	if (!is_array($data)) {
		throw new RuntimeException('Resposta inválida do Google.');
	}

	return $data;
}

function googleExchangeCode(string $code): array
{
	$params = [
		'code' => $code,
		'client_id' => googleConfig('GOOGLE_CLIENT_ID'),
		'client_secret' => googleConfig('GOOGLE_CLIENT_SECRET'),
		'redirect_uri' => googleConfig('GOOGLE_REDIRECT_URI'),
		'grant_type' => 'authorization_code',
	];

	return googleRequest(
		'https://oauth2.googleapis.com/token',
		['Content-Type: application/x-www-form-urlencoded'],
		http_build_query($params, '', '&', PHP_QUERY_RFC3986)
	);
}

function googleUserInfo(string $accessToken): array
{
	return googleRequest(
		'https://openidconnect.googleapis.com/v1/userinfo',
		['Authorization: Bearer ' . $accessToken]
	);
}

function googleEmailIsAllowed(string $email): bool
{
	$email = strtolower(trim($email));
	$allowedEmails = array_filter(array_map('trim', explode(',', googleConfig('GOOGLE_ALLOWED_EMAILS'))));

	foreach ($allowedEmails as $allowedEmail) {
		if ($email === strtolower($allowedEmail)) {
			return true;
		}
	}

	$allowedDomain = strtolower(ltrim(googleConfig('GOOGLE_ALLOWED_DOMAIN'), '@'));
	if ($allowedDomain === '') {
		return false;
	}

	$at = strrpos($email, '@');
	return $at !== false && substr($email, $at + 1) === $allowedDomain;
}

function completeGoogleLogin(string $code, string $state): void
{
	$expectedState = $_SESSION['google_oauth_state'] ?? '';
	unset($_SESSION['google_oauth_state']);

	if ($expectedState === '' || !hash_equals($expectedState, $state)) {
		throw new RuntimeException('Estado OAuth inválido.');
	}

	$tokens = googleExchangeCode($code);
	if (empty($tokens['access_token'])) {
		throw new RuntimeException('O Google não retornou um token de acesso.');
	}

	$user = googleUserInfo($tokens['access_token']);
	$email = strtolower(trim((string) ($user['email'] ?? '')));

	if ($email === '' || ($user['email_verified'] ?? false) !== true) {
		throw new RuntimeException('A conta Google não possui e-mail verificado.');
	}

	if (!googleEmailIsAllowed($email)) {
		throw new RuntimeException('A conta Google não está autorizada.');
	}

	session_regenerate_id(true);
	$_SESSION['auth'] = true;
	$_SESSION['auth_provider'] = 'google';
	$_SESSION['user_sub'] = (string) ($user['sub'] ?? '');
	$_SESSION['user_email'] = $email;
	$_SESSION['user_name'] = (string) ($user['name'] ?? $email);
}

function requireAdmin(): void
{
	if (($_SESSION['auth'] ?? false) !== true) {
		header('Location: /admin/login.php');
		exit;
	}
}
