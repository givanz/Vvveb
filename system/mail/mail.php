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

// Code adapted from Opencart 2, GPL 2 license.

namespace Vvveb\System\Mail;

class Mail {
	/** @var array<array-key, mixed> */
	protected array $option = [];

	/**
	 * @param array<array-key, mixed> &$option
	 *
	 * @return void
	 */
	public function __construct(array &$option = []) {
		$option                   = $this->option + $option;
		$this->option             = &$option;
		$this->option['boundary'] = '----=_NextPart_' . md5(time() . '');

		if (!defined('EOL')) {
			define('EOL', "\r\n");
		}
	}

	/**
	 * Remove characters that allow header injection (CR, LF, NUL).
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function sanitizeHeaderValue($value): string {
		return str_replace(["\r", "\n", "\0"], '', (string)$value);
	}

	/**
	 * Apply configured address overrides (mail.sender_override,
	 * mail.from_address, mail.reply_to), same as the smtp driver.
	 *
	 * @return void
	 */
	protected function resolveAddresses(): void {
		if (!empty($this->option['from_address'])) {
			$this->option['from'] = $this->option['from_address'];
		}

		if (!empty($this->option['sender_override'])) {
			$this->option['sender'] = $this->option['sender_override'];
		}

		//guard against header injection through user supplied fields
		foreach (['from', 'sender', 'reply_to', 'to'] as $key) {
			if (isset($this->option[$key])) {
				$this->option[$key] = is_array($this->option[$key])
					? array_map([$this, 'sanitizeHeaderValue'], $this->option[$key])
					: $this->sanitizeHeaderValue($this->option[$key]);
			}
		}
	}

	/**
	 * @return string
	 */
	private function header(): string {
		$date     = date('D, d M Y H:i:s O');
		$sender   = base64_encode($this->option['sender']);
		$reply_to = base64_encode($this->option['reply_to'] ?? '');

		$header  = 'MIME-Version: 1.0' . EOL;
		$header .= "Date: $date" . EOL;
		$header .= "From: =?UTF-8?B?$sender?= <{$this->option['from']}>" . EOL;

		if (!($this->option['reply_to'] ?? '')) {
			$header .= "Reply-To: =?UTF-8?B?$sender?= <{$this->option['from']}>" . EOL;
		} else {
			$header .= "Reply-To: =?UTF-8?B?$reply_to?= <{$this->option['reply_to']}>" . EOL;
		}

		$header .= 'Return-Path: ' . $this->option['from'] . EOL;
		$header .= 'X-Mailer: Vvveb PHP/' . PHP_VERSION . EOL;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $this->option['boundary'] . '"' . EOL . EOL;

		return $this->option['header'] = $header;
	}

	/**
	 * @return string
	 */
	private function attachments(): string {
		$attachments = '';

		if (isset($this->option['attachments'])) {
			foreach ($this->option['attachments'] as $attachment) {
				if (is_file($attachment)) {
					$name    = $this->sanitizeHeaderValue(basename($attachment));
					$id      = urlencode($name);
					$content = file_get_contents($attachment);

					$attachments .= '--' . $this->option['boundary'] . EOL;
					$attachments .= "Content-Type: application/octet-stream; name=\"$name\"" . EOL;
					$attachments .= 'Content-Transfer-Encoding: base64' . EOL;
					$attachments .= "Content-Disposition: attachment; filename=\"$name\"" . EOL;
					$attachments .= 'Content-ID: <$id>' . EOL;
					$attachments .= 'X-Attachment-Id: ' . $id . EOL . EOL;
					$attachments .= chunk_split(base64_encode($content));
				}
			}
		}

		return $attachments;
	}

	/**
	 * @return string
	 */
	private function message(): string {
		$message  = '--' . $this->option['boundary'] . EOL;

		if (!isset($this->option['html'])) {
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['text'] ?? ''), 950) . EOL;
		} else {
			//$message  = '--' . $this->option['boundary'] . EOL;
			$message .= 'Content-Type: multipart/alternative; boundary="' . $this->option['boundary'] . '_alt"' . EOL . EOL;
			$message .= '--' . $this->option['boundary'] . '_alt' . EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;

			if ($this->option['text'] ?? false) {
				$message .= chunk_split(base64_encode($this->option['text']), 950) . EOL;
			} else {
				$message .= chunk_split(base64_encode(strip_tags($this->option['html'])), 950) . EOL;
			}

			$message .= '--' . $this->option['boundary'] . '_alt' . EOL;
			$message .= 'Content-Type: text/html; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['html']), 950) . EOL;
			$message .= '--' . $this->option['boundary'] . '_alt--' . EOL;
		}

		//attachments are top-level parts, appended after the body
		$message .= $this->attachments();

		return $message .= '--' . $this->option['boundary'] . '--' . EOL;
	}

	/**
	 * @return bool
	 */
	public function send(): bool {
		$this->resolveAddresses();

		if (is_array($this->option['to'])) {
			$to = implode(',', $this->option['to']);
		} else {
			$to = $this->option['to'];
		}

		$header  = $this->header();
		$message = $this->message();

		ini_set('sendmail_from', $this->option['from']);
		$subject = '=?UTF-8?B?' . base64_encode($this->option['subject']) . '?=';

		//no sendmail binary → mail() emits "Sendmail exited with non-zero
		//exit code" warning; fail cleanly with a logged error instead
		$sendmail = trim((string)ini_get('sendmail_path'));
		$binary   = $sendmail !== '' ? strtok($sendmail, ' ') : '';

		if ($binary === '' || !is_executable($binary)) {
			\Vvveb\logError("Mail driver: sendmail binary '$binary' not found or not executable, email to $to not sent. Install sendmail or switch the mail driver to smtp.");

			return false;
		}

		//capture mail() warnings (e.g. sendmail exit code) into the error
		//log instead of leaking them to the output
		$warning = null;
		set_error_handler(function($no, $str) use (&$warning) {
			$warning = $str;

			return true;
		});

		try {
			$result = \mail($to, $subject, $message, $header, $this->option['parameter'] ?? '');
		} finally {
			restore_error_handler();
		}

		if (!$result) {
			\Vvveb\logError('Mail driver: ' . ($warning ?? 'mail() failed') . " (to $to).");
		}

		return $result;
	}
}
