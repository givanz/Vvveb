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
 * Global helper functions.
 *
 * Split into thematic modules under system/functions/; this file is the load
 * manifest so every helper stays available exactly as before the split.
 */

require_once __DIR__ . '/functions/url.php';
require_once __DIR__ . '/functions/settings.php';
require_once __DIR__ . '/functions/meta.php';
require_once __DIR__ . '/functions/text.php';
require_once __DIR__ . '/functions/security.php';
require_once __DIR__ . '/functions/arrays.php';
require_once __DIR__ . '/functions/translation.php';
require_once __DIR__ . '/functions/html.php';
require_once __DIR__ . '/functions/fs.php';
require_once __DIR__ . '/functions/xml.php';
require_once __DIR__ . '/functions/ui.php';
require_once __DIR__ . '/functions/themes.php';
require_once __DIR__ . '/functions/site.php';
require_once __DIR__ . '/functions/debug.php';
require_once __DIR__ . '/functions/loader.php';
