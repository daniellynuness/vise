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

if (array_key_exists($path, $ini)) {
	// INI section support
	if (is_array($ini[$path])) {
		header('Location: ' . $ini[$path]["destination"]);
	} else {
		header('Location: ' . $ini[$path]);
	}
} else {
	header('Location: https://www.mentebinaria.com.br'); // default redirect
}
?>
