<?php

/*
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Copyright (c) 2025 Fernando Mercês
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 */

require_once __DIR__ . '/auth.php';
requireAdmin();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

date_default_timezone_set('America/Sao_Paulo');

define('CONFIG_FILE', '../links.ini');

function validHandle($handle) {
	$handle = trim($handle);
	return strlen($handle) >= 1 && strlen($handle) <= 20 && ctype_alnum($handle) && $handle !== 'admin';
}

function validUrl($url) {
	$url = trim($url);
	return strlen($url) >= 1 && strlen($url) <= 300 && strpos($url, 'https://') === 0;
}

function escapeIniValue($value) {
	return addcslashes((string) $value, "\\'");
}

function serializeLinks(array $links) {
	$contents = '';

	foreach ($links as $handle => $link) {
		if (is_array($link)) {
			$contents .= "[$handle]\n";
			foreach ($link as $key => $value) {
				if ($key === 'clicks') {
					$contents .= "$key = " . (int) $value . "\n";
				} else {
					$contents .= "$key = '" . escapeIniValue($value) . "'\n";
				}
			}
			$contents .= "\n";
		} else {
			$contents .= "$handle = '" . escapeIniValue($link) . "'\n";
		}
	}

	return $contents;
}

$handle = trim((string) ($_GET['handle'] ?? $_POST['handle'] ?? ''));
if (!validHandle($handle)) {
	die('Link inválido.');
}

$links = parse_ini_file(CONFIG_FILE, true);
if ($links === false || !array_key_exists($handle, $links)) {
	die('Link não encontrado.');
}

$current = $links[$handle];
$destination = is_array($current) ? (string) ($current['destination'] ?? '') : (string) $current;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$newDestination = trim((string) ($_POST['dest'] ?? ''));

	if (!validUrl($newDestination)) {
		$message = '<div class="alert alert-danger" role="alert">URL de destino inválida. Ela precisa começar com https:// e ter até 300 caracteres.</div>';
	} else {
		$fh = fopen(CONFIG_FILE, 'c+');
		if ($fh === false || !flock($fh, LOCK_EX)) {
			die('Não foi possível abrir links.ini para edição.');
		}

		rewind($fh);
		$contents = stream_get_contents($fh);
		$lockedLinks = parse_ini_string($contents, true);

		if ($lockedLinks === false || !array_key_exists($handle, $lockedLinks)) {
			flock($fh, LOCK_UN);
			fclose($fh);
			die('Link não encontrado.');
		}

		if (!is_array($lockedLinks[$handle])) {
			$lockedLinks[$handle] = [
				'destination' => $lockedLinks[$handle],
				'clicks' => 0,
			];
		}

		$now = date('Y-m-d H:i:s');
		$updatedBy = (string) ($_SESSION['user_email'] ?? 'unknown');
		$lockedLinks[$handle]['destination'] = $newDestination;
		$lockedLinks[$handle]['updated_at'] = $now;
		$lockedLinks[$handle]['updated_by'] = $updatedBy;

		$serialized = serializeLinks($lockedLinks);
		rewind($fh);
		ftruncate($fh, 0);
		if (fwrite($fh, $serialized) === false) {
			flock($fh, LOCK_UN);
			fclose($fh);
			die('Não foi possível salvar a edição.');
		}
		fflush($fh);
		flock($fh, LOCK_UN);
		fclose($fh);

		header('Location: index.php');
		exit;
	}
}

$template = file_get_contents('tpl/edit.tpl');
$template = str_replace('_MESSAGE_', $message, $template);
$template = str_replace('_HANDLE_', htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'), $template);
$template = str_replace('_DESTINATION_', htmlspecialchars($destination, ENT_QUOTES, 'UTF-8'), $template);
$template = str_replace('_USER_EMAIL_', htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'), $template);

echo $template;

?>
