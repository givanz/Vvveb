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

namespace Vvveb\System\Traits;

trait Field {
	/** @var array<string, mixed> */
	protected array $settings = [];

	/** @var array<string, mixed> */
	protected array $validation = [];

	/** @var array<string, mixed> */
	protected array $presentation = [];

	/** @var array<string, mixed> */
	protected array $defaultSettings = [];

	/** @var array<string, mixed> */
	protected array $defaultValidation = [];

	/** @var array<string, mixed> */
	protected array $defaultPresentation = [];

	/**
	 * Extend current field configuration by merging defaults.
	 *
	 * @return void
	 */
	public function extend(): void {
		$this->settings     = $this->defaultSettings + $this->settings;
		$this->validation   = $this->defaultValidation + $this->validation;
		$this->presentation = $this->defaultPresentation + $this->presentation;
	}
}
