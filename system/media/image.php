<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Image driver dispatcher — loads the best available Image implementation.
 *
 * Priority: Imagick > GD > Mockup stub.
 */
if (extension_loaded('imagick')) {
	require_once __DIR__ . '/imagick.php';
} elseif (extension_loaded('gd')) {
	require_once __DIR__ . '/gd.php';
} else {
	require_once __DIR__ . '/mockup.php';
}
