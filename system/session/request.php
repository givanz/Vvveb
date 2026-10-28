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

namespace Vvveb\System\Session;

/**
 * Session placeholder if no driver is provided.
 * Used for apps like GraphQl where session is handled externally.
 */
class Request {
	/** @var array<string, mixed> */
	private array $data = [];

	public function __construct() {}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get(string $key): mixed {
		return $this->data[$key] ?? null;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set(string $key, mixed $value): void {
		$this->data[$key] = $value;
	}

	/**
	 * @param string $key
	 */
	public function delete(string $key): void {
		unset($this->data[$key]);
	}

	public function close(): void {}

	/**
	 * @param string|null $id
	 */
	public function sessionId(?string $id = null): ?string {
		return null;
	}

	/**
	 * @param bool $deleteOldSession
	 */
	public function regenerateId(bool $deleteOldSession = false): void {}

	/**
	 * @return bool
	 */
	public function gc(): bool {
		$this->data = [];

		return true;
	}
}
