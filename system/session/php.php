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

use function Vvveb\isSecure;
use Vvveb\System\Sites;

class Php {
	/** @var array<string, mixed> */
	private array $options = [
		'gc_maxlifetime'  => 3600 * 24 * 30, //month
		'cookie_httponly' => 1,
		'cookie_samesite' => 'Lax',
		'use_strict_mode' => 1,
		'use_trans_sid'   => 0,
	];

	/**
	 * @param array<string, mixed> $options
	 */
	public function __construct(array $options) {
		$status = session_status();

		if ($status === PHP_SESSION_ACTIVE || $status === PHP_SESSION_DISABLED || headers_sent()) {
			return;
		}

		$this->options = $options + $this->options;

		@ini_set('session.gc_maxlifetime', (string)$this->options['gc_maxlifetime']);
		@ini_set('session.cookie_httponly', (string)($this->options['cookie_httponly'] ?? 1));
		@ini_set('session.use_cookies', (string)($this->options['use_cookies'] ?? 1));
		@ini_set('session.cookie_samesite', (string)($this->options['cookie_samesite'] ?? 'Lax'));
		@ini_set('session.use_strict_mode', (string)($this->options['use_strict_mode'] ?? 1));
		@ini_set('session.use_trans_sid', (string)($this->options['use_trans_sid'] ?? 0));

		//mark the session cookie Secure when the current request is https
		if (isSecure()) {
			@ini_set('session.cookie_secure', '1');
		}

		if (defined('SITE_URL') && SITE_URL) {
			$host = SITE_URL;
		} else {
			$host = Sites :: getHost();
		}

		if (!V_SUBDIR_INSTALL && V_SHARED_SESSION) {
			//try to set cookie for all subdomains
			$domain = preg_replace('/^[^\.]*\./', '', $host);
			$name   = str_replace('.', '', $domain);
			// skip if ip number
			if ((!is_numeric($name)) && ($name != 'localhost')) {
				$params = [
					'lifetime' => (int)($this->options['cookie_lifetime'] ?? 0),
					'path'     => '/',
					'domain'   => '.' . $domain,
					'secure'   => (bool)ini_get('session.cookie_secure'),
					'httponly' => (bool)($this->options['cookie_httponly'] ?? 1),
				];
				$samesite = $this->options['cookie_samesite'] ?? 'Lax';
				if ($samesite) {
					$params['samesite'] = $samesite;
				}
				session_set_cookie_params($params);
			}
		}

		$id = $options['id'] ?? '';

		if (is_string($id) && preg_match('/\A[a-zA-Z0-9,-]{22,256}\z/', $id)) {
			session_id($id);
		}

		session_name('vvveb');
		session_start();
	}

	public function __destruct() {
		//return $this->close();
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get(string $key): mixed {
		return $_SESSION[$key] ?? null;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set(string $key, mixed $value): void {
		$_SESSION[$key] = $value;
	}

	/**
	 * @param string $key
	 */
	public function delete(string $key): void {
		unset($_SESSION[$key]);
	}

	/**
	 * @return bool
	 */
	public function close(): bool {
		return session_write_close();
	}

	/**
	 * @param string|null $id
	 *
	 * @return string
	 */
	public function sessionId(?string $id = null): string {
		return session_id($id);
	}

	/**
	 * @param bool $deleteOldSession
	 *
	 * @return bool
	 */
	public function regenerateId(bool $deleteOldSession = false): bool {
		return session_regenerate_id($deleteOldSession);
	}

	/**
	 * @return bool
	 */
	public function gc(): bool {
		//handled by php
		return true;
	}
}
