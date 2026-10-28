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

class Response {
	/** @var array<string, mixed> */
	private array $headers = [];

	private bool $done = false;

	public bool $output = true;

	/** @var list<callable> */
	public array $filters = [];

	private string $type = '';

	private int $status = 200;

	private string $callback = 'callback';

	/** @var array<string, string> */
	private array $typeHeaders = [
		'html'         => 'text/html',
		'xml'          => 'text/xml',
		'text'         => 'text/plain',
		'json'         => 'application/json',
		'jsonp'        => 'application/javascript',
		'activityjson' => 'application/activity+json',
		'ldjson'       => 'application/ld+json',
		'jrdjson'      => 'application/jrd+json',
	];

	protected static ?self $instance = null;

	/**
	 * @return self
	 */
	final public static function getInstance(): self {
		if (is_null(static::$instance)) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	private function __construct() {
		$this->addHeader('X-Powered-By', 'Vvveb ' . V_VERSION);
	}

	public function addFilter(callable $filter): void {
		$this->filters[] = $filter;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setStatus(int $status): int {
		return $this->status = $status;
	}

	public function addHeader(string $header, ?string $value = null): void {
		$this->headers[$header] = $value;
	}

	public function removeHeader(string $header): void {
		unset($this->headers[$header]);
	}

	/** @return array<string, ?string> */
	public function getHeaders(): array {
		return $this->headers;
	}

	/** @return never */
	public function redirect(string $url, int $status = 302): never {
		header('Location: ' . str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $url), true, $status);

		exit();
	}

	public function getType(): string {
		return $this->type;
	}

	public function setType(string $type): bool {
		$contentType = $this->typeHeaders[$type] ?? false;

		if (!$contentType) {
			return false;
		}

		$this->addHeader('Content-Type', $contentType);
		$this->type = $type;

		return true;
	}

	/**
	 * @param array<array-key, mixed>|object|null $data
	 *
	 * @return string|false
	 */
	public function output(array|object|string|null $data = null): string|false {
		if ($this->done) {
			return false;
		}

		if (!headers_sent()) {
			foreach ($this->headers as $name => $value) {
				if ($value) {
					$header = "$name: $value";
				} else {
					$header = $name;
				}

				header($header, true);
			}

			if ($this->status !== 200) {
				header(' ', true, $this->status);
			}
		}

		$this->done = true;

		if ($this->type === 'text' && $data !== null) {
			//$data may be an array (see signature): encode it instead of echoing "Array"
			$output = is_string($data) ? $data : json_encode($data);
		} else {
			if (in_array($this->type, ['json', 'jsonp', 'ldjson', 'activityjson', 'jrdjson']) && $data !== null && (!defined('CLI'))) {
				if (is_array($data) || is_object($data)) {
					$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

					if ($this->type === 'jsonp') {
						$data = "/**/{$this->callback}($data)";
					}
				}

				$output = $data;
			} else {
				$view = View :: getInstance();

				if ($this->type) {
					$view->setType($this->type);
				}

				$output = $view->render(true, $this->output && !$this->filters);
			}
		}

		if ($this->filters) {
			foreach ($this->filters as $filter) {
				$output = $filter($output);
			}
		}

		if ($this->output) {
			echo $output;
		}

		return $output;
	}
}
