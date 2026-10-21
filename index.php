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

$path = trim($_SERVER['REQUEST_URI'], "/");
$ini = parse_ini_file('links.ini', true);

if ($ini === false) {
	die("Could not open .ini");
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
					$escaped = addcslashes((string) $value, "\\'");
					$contents .= "$key = '$escaped'\n";
				}
			}
			$contents .= "\n";
		} else {
			$escaped = addcslashes((string) $link, "\\'");
			$contents .= "$handle = '$escaped'\n";
		}
	}

	return $contents;
}

function recordClick($filename, $path) {
	$fh = fopen($filename, 'c+');
	if ($fh === false || !flock($fh, LOCK_EX)) {
		return null;
	}

	rewind($fh);
	$contents = stream_get_contents($fh);
	$links = parse_ini_string($contents, true);
	if ($links === false || !array_key_exists($path, $links)) {
		flock($fh, LOCK_UN);
		fclose($fh);
		return null;
	}

	if (is_array($links[$path])) {
		$destination = $links[$path]['destination'];
	} else {
		$destination = $links[$path];
		$links[$path] = array('destination' => $destination);
	}

	$links[$path]['clicks'] = (int) ($links[$path]['clicks'] ?? 0) + 1;
	$serialized = serializeLinks($links);

	rewind($fh);
	ftruncate($fh, 0);
	fwrite($fh, $serialized);
	fflush($fh);
	flock($fh, LOCK_UN);
	fclose($fh);

	return $destination;
}

if (array_key_exists($path, $ini)) {
	$destination = recordClick('links.ini', $path);
	if ($destination !== null) {
		header('Location: ' . $destination);
	} elseif (is_array($ini[$path])) {
		header('Location: ' . $ini[$path]["destination"]);
	} else {
		header('Location: ' . $ini[$path]);
	}
} else {
	header('Location: https://www.mentebinaria.com.br'); // default redirect
}
?>
