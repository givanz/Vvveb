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

use Vvveb\System\Sites;

class Php {
	private $options = [
		'gc_maxlifetime'  => 3600 * 24 * 30, //month
		'gc_divisor'      => 50000,
		'gc_probability'  => 1,
		'cookie_httponly' => 'On',
	];

	public function __construct($options) {
		$status = session_status();

		if ($status === PHP_SESSION_ACTIVE || $status === PHP_SESSION_DISABLED || headers_sent()) {
			return;
		}

		$this->options += $options;

		@ini_set('session.gc_maxlifetime', $this->options['gc_maxlifetime']);
		@ini_set('session.gc_divisor', $this->options['gc_divisor']);
		@ini_set('session.gc_probability', $this->options['gc_probability']);
		@ini_set('session.cookie_httponly', $this->options['cookie_httponly']);

		if (defined('SITE_URL') && SITE_URL) {
			$host = SITE_URL;
		} else {
			$host = Sites :: getHost();
		}

		if (! V_SUBDIR_INSTALL && V_SHARED_SESSION) {
			//try to set cookie for all subdomains
			$domain = preg_replace('/^[^\.]*\./', '', $host);
			@ini_set('session.cookie_domain', ".$domain");
			session_set_cookie_params(0, '/', ".$domain");
			$sessionName = session_name(str_replace('.', '', $domain));
		}

		return session_start();
	}

	public function __destruct() {
		return $this->close();
	}

	public function get($key) {
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}
	}

	public function set($key, $value) {
		$_SESSION[$key] = $value;
	}

	public function delete($key) {
		unset($_SESSION[$key]);
	}

	public function close() {
		return session_write_close();
	}

	public function sessionId($id = null) {
		return session_id($id);
	}

	public function gc() {
		//handled by php
		return true;
	}
}
