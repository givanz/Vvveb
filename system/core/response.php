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
	private $headers = [];

	private $done = false;

	private $type = ''; //html, json, xml

	private $typeHeaders = ['html' => 'text/html', 'xml' => 'text/xml', 'json' => 'application/json'];

	protected static $instance;

	public static function getInstance() {
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	private function __construct() {
		$this->addHeader('X-Powered-By', 'Vvveb'/* . V_VERSION*/);
	}

	public function addHeader($header, $value) {
		$this->headers[$header] = $value;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $url), true, $status);

		exit();
	}

	public function getType($type) {
		return $this->type;
	}

	public function setType($type) {
		$contentType = $this->typeHeaders[$type] ?? false;

		if ($contentType) {
			$this->addHeader('Content-Type', $contentType);
			$this->type = $type;
		}
	}

	public function output($data = false) {
		if ($this->done) {
			return false;
		}

		if (! headers_sent()) {
			foreach ($this->headers as $header => $value) {
				header("$header: $value", true);
			}
		}

		if (is_array($data) && $this->type == 'json') {
			echo json_encode($data);
		} else {
			$view = View :: getInstance();

			if ($this->type) {
				$view->setType($this->type);
			}

			$view->render();
		}

		$this->done = true;
	}
}
