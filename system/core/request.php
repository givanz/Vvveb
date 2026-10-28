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

namespace Vvveb\System\Core;

class Request {
	/** @var array<string, mixed> */
	public array $get = [];

	/** @var array<string, mixed> */
	public array $post = [];

	/** @var array<string, mixed> */
	public array $request = [];

	/** @var array<string, mixed> */
	public array $cookie = [];

	/** @var array<string, mixed> */
	public array $files = [];

	/** @var array<string, mixed> */
	public array $server = [];

	private string $method;

	private string $contentType;

	protected static ?self $instance = null;

	public static function getInstance(): self {
		if (is_null(static::$instance)) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	private function __construct() {
		$this->get     = &$_GET;
		$this->post    = &$_POST;
		$this->request = &$_REQUEST;
		$this->cookie  = &$_COOKIE;
		$this->files   = &$_FILES;
		$this->server  = &$_SERVER;

		$this->post         = $this->filter($this->post, false);
		$this->get          = $this->filter($this->get);
		$this->request      = $this->filter($this->request);
		$this->cookie       = $this->filter($this->cookie);
		$this->files        = $this->filter($this->files);
		$this->method       = strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
		$this->contentType  = strtolower($_SERVER['CONTENT_TYPE'] ?? 'text/html');

		if ($this->method === 'put' || $this->method === 'patch') {
			parse_str(file_get_contents('php://input'), $post);
			$this->post = $this->filter($post, false);
		}

		if ($this->contentType == 'application/json') {
			$rawInput = file_get_contents('php://input');
			if ($rawInput) {
				//print_r($rawInput);
				$json = json_decode($rawInput, true);
				//malformed json decodes to null: keep post an array
				$this->post = is_array($json) ? $json : [];
			}
		}

		$this->request = array_merge($this->request, $this->get, $this->post);
	}

	/**
	 * Recursively filter array keys and values.
	 *
	 * @param array<array-key, mixed>|string $data       The data to filter.
	 * @param bool                           $filterText Whether to apply text filtering via \Vvveb\filterText.
	 *
	 * @return array<array-key, mixed>|string Filtered data.
	 */
	public function filter(array|string $data, bool $filterText = true): array|string {
		if (!is_array($data)) {
			return $filterText ? \Vvveb\filterText($data) : $data;
		}

		$filtered = [];

		foreach ($data as $key => $value) {
			$key            = substr($this->filter((string)$key, $filterText), 0, 255);
			$filtered[$key] = $this->filter($value, $filterText);
		}

		return $filtered;
	}

	public function getMethod(): string {
		return $this->method;
	}

	public function isAjax(): bool {
		return isset($this->server['HTTP_X_REQUESTED_WITH']) && $this->server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}
}
