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

/*
 * Code adapted from Opencart 2, GPL 2 license.
 */

namespace Vvveb\System\Mail;

class Smtp {
	protected $option = [
		'port'          => 25,
		'timeout'       => 5,
		'max_attempts'  => 3,
		'verp'          => false,
	];

	public function __construct(array &$option = []) {
		$option       = $option + $this->option;
		$this->option = &$option;

		if (! defined('EOL')) {
			define('EOL', "\r\n");
		}
	}

	public function attachments() {
	}

	public function send() {
		foreach (['host', 'user', 'password', 'port', 'timeout'] as $key) {
			if (! isset($this->option[$key])) {
				throw new \Exception("Smtp $key required!");
			}
		}

		if (is_array($this->option['to'])) {
			$to = implode(',', $this->option['to']);
		} else {
			$to = $this->option['to'];
		}

		$serverName = ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? getenv('SERVER_NAME'));

		$boundary = '----=_NextPart_' . md5(time());

		$header = 'MIME-Version: 1.0' . EOL;
		$header .= 'To: <' . $to . '>' . EOL;
		$header .= 'Subject: =?UTF-8?B?' . base64_encode($this->option['subject']) . '?=' . EOL;
		$header .= 'Date: ' . date('D, d M Y H:i:s O') . EOL;
		$header .= 'From: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . EOL;

		if (empty($this->option['reply_to'])) {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . EOL;
		} else {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['reply_to']) . '?= <' . $this->option['reply_to'] . '>' . EOL;
		}

		$header .= 'Return-Path: ' . $this->option['from'] . EOL;
		$header .= 'X-Mailer: PHP/' . phpversion() . EOL;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . EOL . EOL;

		$message = '--' . $boundary . EOL;

		if (empty($this->option['html'])) {
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['text']), 950) . EOL;
		} else {
			$message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . EOL . EOL;
			$message .= '--' . $boundary . '_alt' . EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;

			if ($this->option['text']) {
				$message .= chunk_split(base64_encode($this->option['text']), 950) . EOL;
			} else {
				$message .= chunk_split(base64_encode(strip_tags($this->option['html']), 950), '<a>') . EOL;
			}

			$message .= '--' . $boundary . '_alt' . EOL;
			$message .= 'Content-Type: text/html; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['html']), 950) . EOL;
			$message .= '--' . $boundary . '_alt--' . EOL;
		}

		if (! empty($this->option['attachments'])) {
			foreach ($this->option['attachments'] as $attachment) {
				if (is_file($attachment)) {
					$handle = fopen($attachment, 'r');

					$content = fread($handle, filesize($attachment));

					fclose($handle);

					$message .= '--' . $boundary . EOL;
					$message .= 'Content-Type: application/octet-stream; name="' . basename($attachment) . '"' . EOL;
					$message .= 'Content-Transfer-Encoding: base64' . EOL;
					$message .= 'Content-Disposition: attachment; filename="' . basename($attachment) . '"' . EOL;
					$message .= 'Content-ID: <' . urlencode(basename($attachment)) . '>' . EOL;
					$message .= 'X-Attachment-Id: ' . urlencode(basename($attachment)) . EOL . EOL;
					$message .= chunk_split(base64_encode($content));
				}
			}
		}

		$message .= '--' . $boundary . '--' . EOL;

		if (substr($this->option['host'], 0, 3) == 'tls') {
			$hostname = substr($this->option['host'], 6);
		} else {
			$hostname = $this->option['host'];
		}

		$handle = fsockopen($hostname, $this->option['port'], $errno, $errstr, $this->option['timeout']);

		if ($handle) {
			if (substr(PHP_OS, 0, 3) != 'WIN') {
				socket_set_timeout($handle, $this->option['timeout'], 0);
			}

			while ($line = fgets($handle, 515)) {
				if (substr($line, 3, 1) == ' ') {
					break;
				}
			}

			fputs($handle, 'EHLO ' . $serverName . EOL);

			$reply = '';

			while ($line = fgets($handle, 515)) {
				$reply .= $line;

				if (substr($reply, 0, 3) == 220 && substr($line, 3, 1) == ' ') {
					$reply = '';

					continue;
				} elseif (substr($line, 3, 1) == ' ') {
					break;
				}
			}

			if (substr($reply, 0, 3) != 250) {
				throw new \Exception('EHLO not accepted from server!' . $reply);
			}

			if (substr($this->option['host'], 0, 3) == 'tls') {
				fputs($handle, 'STARTTLS' . EOL);

				$this->handleReply($handle, 220, 'STARTTLS not accepted from server!');

				stream_socket_enable_crypto($handle, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			}

			if (! empty($this->option['user']) && ! empty($this->option['password'])) {
				fputs($handle, 'EHLO ' . $serverName . EOL);

				$this->handleReply($handle, 250, 'EHLO not accepted from server!');

				fputs($handle, 'AUTH LOGIN' . EOL);

				$this->handleReply($handle, 334, 'AUTH LOGIN not accepted from server!');

				fputs($handle, base64_encode($this->option['user']) . EOL);

				$this->handleReply($handle, 334, 'Username not accepted from server!');

				fputs($handle, base64_encode($this->option['password']) . EOL);

				$this->handleReply($handle, 235, 'Password not accepted from server!' . $reply);
			} else {
				fputs($handle, 'HELO ' . $serverName . EOL);

				$this->handleReply($handle, 250, 'HELO not accepted from server!');
			}

			if ($this->option['verp']) {
				fputs($handle, 'MAIL FROM: <' . $this->option['from'] . '>XVERP' . EOL);
			} else {
				fputs($handle, 'MAIL FROM: <' . $this->option['from'] . '>' . EOL);
			}

			$this->handleReply($handle, 250, 'MAIL FROM not accepted from server!');

			if (! is_array($this->option['to'])) {
				fputs($handle, 'RCPT TO: <' . $this->option['to'] . '>' . EOL);

				$reply = $this->handleReply($handle, false, 'RCPT TO [!array]');

				if ((substr($reply, 0, 3) != 250) && (substr($reply, 0, 3) != 251)) {
					throw new \Exception('RCPT TO not accepted from server!');
				}
			} else {
				foreach ($this->option['to'] as $recipient) {
					fputs($handle, 'RCPT TO: <' . $recipient . '>' . EOL);

					$reply = $this->handleReply($handle, false, 'RCPT TO [array]');

					if ((substr($reply, 0, 3) != 250) && (substr($reply, 0, 3) != 251)) {
						throw new \Exception('RCPT TO not accepted from server!');
					}
				}
			}

			fputs($handle, 'DATA' . EOL);

			$this->handleReply($handle, 354, 'DATA not accepted from server!' . $reply);

			// According to rfc 821 we should not send more than 1000 including the CRLF
			$message = str_replace(EOL, "\n", $header . $message);
			$message = str_replace("\r", "\n", $message);

			$lines = explode("\n", $message);

			foreach ($lines as $line) {
				$results = (empty($line)) ? [''] : str_split($line, 998);

				foreach ($results as $result) {
					fputs($handle, $result . EOL);
				}
			}

			fputs($handle, '.' . EOL);
			$this->handleReply($handle, 250, 'DATA not accepted from server!' . $reply);
			fputs($handle, 'QUIT' . EOL);
			$this->handleReply($handle, 221, 'QUIT not accepted from server!');
			fclose($handle);

			return true;
		} else {
			throw new \Exception('' . $errstr . ' (' . $errno . ')');

			return false;
		}
	}

	private function handleReply($handle, $status_code = false, $error_text = false, $counter = 0) {
		$reply = '';

		while (($line = fgets($handle, 515)) !== false) {
			$reply .= $line;

			if (substr($line, 3, 1) == ' ') {
				break;
			}
		}

		// Wait for response
		if (! $line && empty($reply) && $counter < $this->option['max_attempts']) {
			sleep(1);

			$counter++;

			return $this->handleReply($handle, $status_code, $error_text, $counter);
		}

		if ($status_code) {
			if (substr($reply, 0, 3) != $status_code) {
				throw new \Exception($error_text);
			}
		}

		return $reply;
	}
}
