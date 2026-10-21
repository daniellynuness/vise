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

define('ACCESS_LOG', __DIR__ . '/../access.log');

$records = [];
if (is_readable(ACCESS_LOG)) {
	$lines = file(ACCESS_LOG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines !== false) {
		foreach (array_reverse($lines) as $line) {
			$record = json_decode($line, true);
			if (is_array($record)) {
				$records[] = $record;
			}
			if (count($records) >= 100) {
				break;
			}
		}
	}
}

$rows = [];
foreach ($records as $record) {
	$rows[] = '<tr>' .
		'<td>' . htmlspecialchars((string) ($record['timestamp'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'<td>' . htmlspecialchars((string) ($record['handle'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'<td>' . htmlspecialchars((string) ($record['destination'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'<td>' . htmlspecialchars((string) ($record['ip'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'<td>' . htmlspecialchars((string) ($record['user_agent'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'<td>' . htmlspecialchars((string) ($record['referer'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>' .
		'</tr>';
}

$template = file_get_contents('tpl/accesses.tpl');
$template = str_replace('_USER_EMAIL_', htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'), $template);
$template = str_replace('_ACCESS_ROWS_', implode("\n", $rows), $template);

echo $template;

?>
