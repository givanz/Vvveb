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

namespace Vvveb\Controller;

/**
 * 500 error page controller.
 *
 * Does not extend Base — instantiated standalone by FrontController::notFound().
 */
#[\AllowDynamicProperties]
class Error500 {// extends Base
	/** @var array<string, mixed> */
	public array $global = [];

	/**
	 * Get the global template variables (unused — kept for interface compatibility).
	 *
	 * @return array<string, mixed>
	 */
	public function getGlobal(): array {
		return $this->global;
	}

	/**
	 * Render the 500 error page.
	 *
	 * @return void
	 */
	public function index(): void {}
}
