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

namespace Vvveb;

/**
 * Debug output function - dumps variables with syntax highlighting.
 *
 * @param mixed ...$variables The variables to dump.
 *
 * @return void
 */
function d(mixed ...$variables): void {
	foreach ($variables as $variable) {
		echo highlight_string("<?php\n" . var_export($variable, true), true);
	}
}

/**
 * Debug and die - dumps variables with syntax highlighting and stops execution.
 *
 * @param mixed ...$variables The variables to dump.
 *
 * @return never
 */
function dd(mixed ...$variables): never {
	d(...$variables);

	exit();
}

/**
 * Log an error message.
 *
 * @param string $message The error message to log.
 *
 * @return void
 */
function logError(string $message): void {
	error_log($message);
}
