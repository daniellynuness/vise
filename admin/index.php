<?php

/*
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Copyright (c) 2016, 2018, 2021-2022, 2025 Fernando Mercês
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/auth.php';
requireAdmin();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

date_default_timezone_set('America/Sao_Paulo');

define("CONFIG_FILE", "../links.ini");
$shortenerBaseUrl = trim((string) getenv('SHORTENER_BASE_URL'));
if ($shortenerBaseUrl === '') {
	$shortenerBaseUrl = 'https://menteb.in';
}
$shortenerBaseUrl = rtrim($shortenerBaseUrl, '/');

if (!file_exists(CONFIG_FILE)) {
	$fh = fopen(CONFIG_FILE, "w");
	fclose($fh);
}

function validHandle($handle) {
	$handle = trim($handle);
	$handle = strtolower($handle);
	
	$len = strlen($handle);
	if ($len < 1 || $len > 20)
		return false;

	if (!ctype_alnum($handle))
		return false;

	if ($handle == "admin")
		return false;

	return true;
}

function validUrl($url) {
	$url = trim($url);

	$len = strlen($url);
	if ($len < 1 || $len > 300)
		return false;

	if (strpos($url, "https://") !== 0)
		return false;

	return true;
}

function getError($msg) {
	return '<div class="alert alert-danger" role="alert">'. $msg .'</div>';
}

$message = "";

if ($_POST) {
	if ($_POST["handle"] && $_POST["dest"]) {
		$handle = $_POST["handle"];
		$dest = $_POST["dest"];

		$handle = trim($handle);
		$handle = strtolower($handle);

		$dest = trim($dest);

		if (!validHandle($handle))
			$message = getError("Handle inválido. Verifique se usou somente letras minúsculas e números até 20 caracteres.");
		if (!validUrl($dest))
			$message = getError("URL de destino inválida. Ela precisa começar com https:// e ser uma URL válida.");

		if ($message == "") {
			// New links will use INI sections instead
			$now = date('Y-m-d H:i:s');
			$createdBy = addcslashes((string) ($_SESSION['user_email'] ?? 'unknown'), "\\'");

			$section = "[$handle]" . "\n" .
				"destination = '$dest'" . "\n" .
				"created_at = $now"   . "\n" .
				"updated_at = $now"   . "\n" .
				"clicks = 0"           . "\n" .
				"created_by = '$createdBy'"  . "\n" .
				"updated_by = '$createdBy'"  . "\n";

			$fh = fopen(CONFIG_FILE, "a");
			if ($fh == false)
				die("fopen()");
			if (fwrite($fh, $section) === false) {
				$message = getError("Erro interno. O link não foi salvo.");
			}
			fclose($fh);
		}
	}
}

$path = trim($_SERVER['REQUEST_URI'], "/");
$links = parse_ini_file(CONFIG_FILE, true);

if ($links === false) {
	die("Could not open links.ini");
}

$entries = array();

foreach ($links as $key => $value) {
	if (is_array($value)) {
		array_push($entries, 
		'
			<tr>
				<td><a href="'. htmlspecialchars($shortenerBaseUrl . '/' . rawurlencode($key), ENT_QUOTES, 'UTF-8') .'" target="_blank" rel="noopener noreferrer">'. htmlspecialchars($key, ENT_QUOTES, 'UTF-8') .'</a></td>
				<td>'. htmlspecialchars((string) ($value["destination"] ?? ''), ENT_QUOTES, 'UTF-8') .'</td>
				<td>'. htmlspecialchars((string) ($value["updated_at"] ?? '-'), ENT_QUOTES, 'UTF-8') .'</td>
				<td>'. htmlspecialchars((string) ($value["clicks"] ?? 0), ENT_QUOTES, 'UTF-8') .'</td>
				<td>'. htmlspecialchars((string) ($value["created_by"] ?? ''), ENT_QUOTES, 'UTF-8') .'</td>
				<td>'. htmlspecialchars((string) ($value["updated_by"] ?? $value["created_by"] ?? ''), ENT_QUOTES, 'UTF-8') .'</td>
				<td><a class="btn btn-sm btn-secondary" href="edit.php?handle='. urlencode($key) .'" role="button">Editar</a></td>
			</tr>
		');
	} else {
	array_push($entries, 
		'
		<tr>
				<td><a href="'. htmlspecialchars($shortenerBaseUrl . '/' . rawurlencode($key), ENT_QUOTES, 'UTF-8') .'" target="_blank" rel="noopener noreferrer">'. htmlspecialchars($key, ENT_QUOTES, 'UTF-8') .'</a></td>
				<td>'. htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') .'</td>
				<td>-</td>
				<td>0</td>
				<td>-</td>
				<td>-</td>
				<td><a class="btn btn-sm btn-secondary" href="edit.php?handle='. urlencode($key) .'" role="button">Editar</a></td>
			</tr>
		');
	}
}

$with = implode("\n", array_reverse($entries));
$contents = file_get_contents("tpl/main.tpl");

if (empty($message)) {
	$contents = str_replace("_MESSAGE_", "", $contents);
} else {
	$contents = str_replace("_MESSAGE_", $message, $contents);
}

$contents = str_replace("_LINKS_", $with, $contents);
$contents = str_replace("_USER_EMAIL_", htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'), $contents);

echo $contents;

?>
