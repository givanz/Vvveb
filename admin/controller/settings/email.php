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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use function Vvveb\config;
use Vvveb\Controller\Base;
use function Vvveb\email;
use function Vvveb\setConfig;
use Vvveb\System\Event;

class Email extends Base {
	/**
	 * Send a test email to verify mail configuration.
	 *
	 * @return void
	 */
	public function test(): void {
		$to  = $this->request->post['to'] ?? false;

		if ($to) {
			$message = __('This is a test email to confirm that email is properly configured!');
			$error   = __('Error sending mail!');

			try {
				if (email($to, __('Test email'), ['html' => $message, 'txt' => $message], [], [], null, SITE_ID, ['debug' => true])) {
					$this->view->success[] = sprintf(__('Email sent to %s!'), $to);
				} else {
					//$this->session->set('errors', $error);
					$this->view->errors[] = $error;
				}
			} catch (\Exception $e) {
				$error .= "\n" . $e->getMessage();
				//$this->session->set('errors', $error);
				$this->view->errors[] = $error;
			}
		}

		$this->index();
	}

	/**
	 * Save email settings.
	 *
	 * @return void
	 */
	public function save(): void {
		$settings = $this->request->post['settings'] ?? false;

		if ($settings) {
			$settings = self::normalizeSmtpSettings($settings);
			$errors   = self::validateSmtpSettings($settings);

			if ($errors !== []) {
				$this->view->errors = $errors;
				$this->index($settings);

				return;
			}

			$results = setConfig('mail', $settings);

			if ($results) {
				$this->view->success[] = __('Settings saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		} else {
			$this->view->errors[] = __('No settings received!');
		}

		$this->index();
	}

	/**
	 * Display email settings and available mail drivers.
	 *
	 * @param array<string, mixed>|null $settings Pre-filled settings to display
	 *                                            instead of the stored config, used to
	 *                                            redisplay submitted values after a
	 *                                            validation error.
	 *
	 * @return void
	 */
	public function index(?array $settings = null): void {
		$settings ??= config('mail', []);
		$settings   = self::splitLegacySmtpScheme($settings);

		$drivers          = ['mail' => 'Mail', 'smtp' => 'Smtp'];
		[$drivers] 	      = Event::trigger(__CLASS__, __FUNCTION__, $drivers);

		$this->view->email                   = $settings;
		$this->view->drivers                 = $drivers;
		$this->view->smtp_encryption_options = self::encryptionOptions();
	}

	/**
	 * Encryption modes for the SMTP encryption dropdown.
	 *
	 * @return array<string, string> Value => label pairs.
	 */
	public static function encryptionOptions(): array {
		return [
			''    => __('None'),
			'tls' => __('TLS (STARTTLS)'),
			'ssl' => __('SSL (SMTPS)'),
		];
	}

	/**
	 * Normalize submitted SMTP settings: strip any URL scheme pasted into
	 * the hostname into the encryption field so only a clean hostname is
	 * stored. A pasted tls:///ssl:// scheme fills an empty dropdown, an
	 * explicit dropdown choice wins otherwise.
	 *
	 * @param array<string, mixed> $settings Submitted mail settings.
	 *
	 * @return array<string, mixed> Normalized mail settings.
	 */
	public static function normalizeSmtpSettings(array $settings): array {
		if (!isset($settings['smtp']) || !is_array($settings['smtp'])) {
			$settings['smtp'] = [];
		}

		$smtp       = $settings['smtp'];
		$host       = trim((string)($smtp['host'] ?? ''));
		$encryption = strtolower(trim((string)($smtp['encryption'] ?? '')));
		$scheme     = '';

		if (preg_match('#^([a-z][a-z0-9+.-]*)://#i', $host, $m)) {
			$scheme = strtolower($m[1]);
		}

		if (in_array($scheme, ['tls', 'ssl'], true)) {
			if ($encryption !== 'tls' && $encryption !== 'ssl') {
				$encryption = $scheme;
			}

			$host = (string)preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $host);
		}

		if (!in_array($encryption, ['tls', 'ssl'], true)) {
			$encryption = '';
		}

		$smtp['host']       = trim($host, " \t\n\r\0\x0B/");
		$smtp['encryption'] = $encryption;

		if (isset($smtp['port'])) {
			$smtp['port'] = trim((string)$smtp['port']);
		}

		if (isset($smtp['timeout'])) {
			$smtp['timeout'] = trim((string)$smtp['timeout']);
		}

		$settings['smtp'] = $smtp;

		return $settings;
	}

	/**
	 * Validate SMTP settings when the smtp driver is selected.
	 *
	 * @param array<string, mixed> $settings Normalized mail settings.
	 *
	 * @return list<string> Error messages, empty when valid.
	 */
	public static function validateSmtpSettings(array $settings): array {
		$errors = [];

		if (($settings['driver'] ?? 'mail') !== 'smtp') {
			return $errors;
		}

		$smtp = (isset($settings['smtp']) && is_array($settings['smtp'])) ? $settings['smtp'] : [];
		$host = (string)($smtp['host'] ?? '');

		if ($host === '') {
			$errors[] = __('SMTP hostname is required!');
		} elseif (!preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9.-]*[a-z0-9])?|\[[0-9a-f:.]+\])$/i', $host)) {
			$errors[] = __('SMTP hostname is invalid! Use a plain hostname such as mail.example.com.');
		}

		$port = (string)($smtp['port'] ?? '');

		if ($port === '' || !ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) {
			$errors[] = __('SMTP port must be a number between 1 and 65535!');
		}

		$timeout = (string)($smtp['timeout'] ?? '');

		if ($timeout !== '' && (!is_numeric($timeout) || (float)$timeout <= 0)) {
			$errors[] = __('SMTP timeout must be a positive number!');
		}

		return $errors;
	}

	/**
	 * Split a legacy scheme-prefixed smtp host (tls:///ssl://) from stored
	 * config into the encryption field for display. Idempotent: stored
	 * settings are only cleaned on the next save.
	 *
	 * @param array<string, mixed> $settings Stored mail settings.
	 *
	 * @return array<string, mixed> Display mail settings.
	 */
	public static function splitLegacySmtpScheme(array $settings): array {
		if (!isset($settings['smtp']) || !is_array($settings['smtp'])) {
			$settings['smtp'] = [];
		}

		$encryption = (string)($settings['smtp']['encryption'] ?? '');
		$host       = (string)($settings['smtp']['host'] ?? '');

		if ($encryption === '' && preg_match('#^(tls|ssl)://#i', $host, $m)) {
			$settings['smtp']['encryption'] = strtolower($m[1]);
			$settings['smtp']['host']       = trim((string)preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $host), " \t\n\r\0\x0B/");
		}

		$settings['smtp']['encryption'] ??= '';

		return $settings;
	}
}
