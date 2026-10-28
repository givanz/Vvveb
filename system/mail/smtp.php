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

/**
 * Minimal SMTP client: plain, TLS (via STARTTLS), SSL (implicit TLS) and AUTH LOGIN support.
 *
 * Security notes:
 *  - address/subject fields are sanitized against header injection (CR/LF/NUL)
 *  - when the server advertises STARTTLS the negotiation is mandatory; a failed
 *    handshake aborts the send instead of falling back to plaintext (which would
 *    leak AUTH LOGIN credentials)
 *  - message lines starting with "." are dot-stuffed per RFC 5321
 */
class Smtp {
	/** @var bool */
	protected bool $debug = false;

	/** @var array<array-key, mixed> */
	protected array $log = [];

	/** @var array<array-key, mixed> */
	protected array $socketOptions = [
		/*
		'ssl' => [
			'allow_self_signed' => true,
			'verify_peer'       => false,
			'verify_peer_name'  => false,
			//'ciphers' => 'TLSv1.3|TLSv1.2|TLSv1|SSLv3',
			//'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
			//'crypto_type' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
		],
		 */
	];

	/** @var array<array-key, mixed> */
	protected array $option = [
		'port'         => 25,
		'timeout'      => 5,
		'max_attempts' => 3,
		'verp'         => false,
		'encryption'   => 'tls',
	];

	/**
	 * @param array<array-key, mixed> &$option
	 *
	 * @return void
	 */
	public function __construct(array &$option = []) {
		$option += $this->option;
		$this->option = &$option;

		if (isset($this->option['debug'])) {
			$this->debug = $this->option['debug'];
		}

		if (!defined('EOL')) {
			define('EOL', "\r\n");
		}
	}

	/**
	 * Remove characters that allow SMTP header injection (CR, LF, NUL).
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function sanitizeHeaderValue($value): string {
		return str_replace(["\r", "\n", "\0"], '', (string)$value);
	}

	/**
	 * Format one or more addresses for a header value ("Name"less form):
	 * each address is angle-bracketed and multiple addresses are comma separated.
	 *
	 * @param string|array<array-key, mixed> $addresses
	 *
	 * @return string
	 */
	protected function formatAddressList($addresses): string {
		return implode(', ', array_map(
			fn ($address) => '<' . $this->sanitizeHeaderValue($address) . '>',
			(array)$addresses
		));
	}

	/**
	 * Resolve SMTP connection parameters from host and encryption option.
	 *
	 * Supports an explicit $encryption mode ('tls' for STARTTLS, 'ssl' for
	 * implicit TLS) with fallback to legacy 'tls://'/'ssl://' URL prefixes
	 * in $host for backward compatibility with stored settings.
	 *
	 * @param string $host       Hostname, optionally with tls:// or ssl:// prefix.
	 * @param string $encryption Explicit mode: '', 'none', 'tls' or 'ssl'.
	 *
	 * @throws \Exception If no hostname remains after parsing.
	 *
	 * @return array{hostname: string, scheme: string, transport: string, starttls: bool}
	 */
	public static function parseHost(string $host, string $encryption = ''): array {
		$hostOption = trim($host);
		$encryption = strtolower(trim($encryption));

		if ($encryption === 'none') {
			$encryption = '';
		}

		$scheme = '';

		if ($encryption === 'tls' || $encryption === 'ssl') {
			$scheme = $encryption;
		} elseif (preg_match('#^([a-z][a-z0-9+.-]*)://#i', $hostOption, $m)) {
			$scheme = strtolower($m[1]);
		}

		if ($scheme === 'tls' || $scheme === 'ssl') {
			$hostname = (string)preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $hostOption);
		} else {
			$scheme   = '';
			$hostname = $hostOption;
		}

		$hostname = trim($hostname, " \t\n\r\0\x0B/");

		if ($hostname === '') {
			throw new \Exception('Smtp host required!');
		}

		return [
			'hostname'  => $hostname,
			'scheme'    => $scheme,
			'transport' => $scheme === 'ssl' ? 'ssl' : 'tcp',
			'starttls'  => $scheme === 'tls',
		];
	}

	/**
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function send(): bool {
		foreach (['host', 'port', 'timeout', 'to', 'from', 'sender', 'subject'] as $key) {
			if (!isset($this->option[$key])) {
				throw new \Exception("Smtp $key required!");
			}
		}

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

		$recipients = (array)$this->option['to'];

		$serverName = ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? getenv('SERVER_NAME')) ?: 'localhost';
		$fromDomain = strrchr($this->option['from'], '@') ?: '@localhost';
		$messageId  = base_convert(str_replace(['.', ' '], '', microtime()), 10, 36) . '.' . base_convert(bin2hex(random_bytes(8)), 16, 36) . $fromDomain;

		$boundary = '----=_NextPart_' . md5(time() . '');

		$header = 'MIME-Version: 1.0' . EOL;
		$header .= 'To: ' . $this->formatAddressList($recipients) . EOL;
		$header .= 'Subject: =?UTF-8?B?' . base64_encode($this->option['subject']) . '?=' . EOL;
		$header .= 'Date: ' . date('D, d M Y H:i:s O') . EOL;
		$header .= 'From: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . EOL;

		if (empty($this->option['reply_to'])) {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['sender']) . '?= <' . $this->option['from'] . '>' . EOL;
		} else {
			$header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->option['reply_to']) . '?= <' . $this->option['reply_to'] . '>' . EOL;
		}

		$header .= 'Message-ID: <' . $messageId . '>' . EOL;
		$header .= 'Return-Path: ' . $this->option['from'] . EOL;
		$header .= 'X-Mailer: PHP/' . PHP_VERSION . EOL;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . EOL . EOL;

		$message = '--' . $boundary . EOL;

		if (empty($this->option['html'])) {
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['text'] ?? ''), 950) . EOL;
		} else {
			$message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . EOL . EOL;
			$message .= '--' . $boundary . '_alt' . EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;

			if ($this->option['text'] ?? false) {
				$message .= chunk_split(base64_encode($this->option['text']), 950) . EOL;
			} else {
				$message .= chunk_split(base64_encode(strip_tags($this->option['html'])), 950) . EOL;
			}

			$message .= '--' . $boundary . '_alt' . EOL;
			$message .= 'Content-Type: text/html; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['html']), 950) . EOL;
			$message .= '--' . $boundary . '_alt--' . EOL;
		}

		if (!empty($this->option['attachments'])) {
			foreach ($this->option['attachments'] as $attachment) {
				if (is_file($attachment)) {
					$content = file_get_contents($attachment);
					$name    = $this->sanitizeHeaderValue(basename($attachment));

					$message .= '--' . $boundary . EOL;
					$message .= 'Content-Type: application/octet-stream; name="' . $name . '"' . EOL;
					$message .= 'Content-Transfer-Encoding: base64' . EOL;
					$message .= 'Content-Disposition: attachment; filename="' . $name . '"' . EOL;
					$message .= 'Content-ID: <' . urlencode($name) . '>' . EOL;
					$message .= 'X-Attachment-Id: ' . urlencode($name) . EOL . EOL;
					$message .= chunk_split(base64_encode($content));
				}
			}
		}

		$message .= '--' . $boundary . '--' . EOL;

		$parsed = self::parseHost(
			(string)$this->option['host'],
			(string)$this->option['encryption']
		);

		$hostname    = $parsed['hostname'];
		$transport   = $parsed['transport'];
		$useStartTls = $parsed['starttls'];

		$port = trim((string)$this->option['port']);

		if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) {
			throw new \Exception('Smtp port invalid!');
		}

		$context = stream_context_create();

		if ($this->socketOptions) {
			stream_context_set_options($context, $this->socketOptions);
		}

		$handle = stream_socket_client("$transport://$hostname:$port", $errno, $errstr, $this->option['timeout'], STREAM_CLIENT_CONNECT, $context);

		if (!$handle) {
			throw new \Exception("Could not connect to $hostname:$port - $errstr ($errno)");
		}

		if (substr(PHP_OS, 0, 3) != 'WIN') {
			stream_set_timeout($handle, $this->option['timeout'], 0);
		}

		while ($line = fgets($handle, 515)) {
			if (substr($line, 3, 1) == ' ') {
				break;
			}
		}

		fwrite($handle, 'EHLO ' . $serverName . EOL);

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

		if ($useStartTls) {
			fwrite($handle, 'STARTTLS' . EOL);

			$this->handleReply($handle, 220, 'STARTTLS not accepted from server!');

			//abort rather than continue unencrypted; AUTH LOGIN credentials
			//must never be sent over plaintext
			if (!stream_socket_enable_crypto($handle, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
				fclose($handle);

				throw new \Exception('TLS negotiation failed!');
			}
		}

		if (!empty($this->option['user']) && !empty($this->option['password'])) {
			fwrite($handle, 'EHLO ' . $serverName . EOL);

			$this->handleReply($handle, 250, 'EHLO not accepted from server!');

			fwrite($handle, 'AUTH LOGIN' . EOL);

			$this->handleReply($handle, 334, 'AUTH LOGIN not accepted from server!');

			fwrite($handle, base64_encode($this->option['user']) . EOL);

			$this->handleReply($handle, 334, 'Username not accepted from server!');

			fwrite($handle, base64_encode($this->option['password']) . EOL);

			$this->handleReply($handle, 235, 'Password not accepted from server!' . $reply);
		} else {
			fwrite($handle, 'HELO ' . $serverName . EOL);

			$this->handleReply($handle, 250, 'HELO not accepted from server!');
		}

		if ($this->option['verp']) {
			//XVERP is an ESMTP parameter, separated from the address by a space
			fwrite($handle, 'MAIL FROM: <' . $this->option['from'] . '> XVERP' . EOL);
		} else {
			fwrite($handle, 'MAIL FROM: <' . $this->option['from'] . '>' . EOL);
		}

		$this->handleReply($handle, 250, 'MAIL FROM not accepted from server!');

		foreach ($recipients as $recipient) {
			fwrite($handle, 'RCPT TO: <' . $recipient . '>' . EOL);

			$reply = $this->handleReply($handle, false, 'RCPT TO');

			if ((substr($reply, 0, 3) != 250) && (substr($reply, 0, 3) != 251)) {
				throw new \Exception('RCPT TO not accepted from server!');
			}
		}

		fwrite($handle, 'DATA' . EOL);

		$this->handleReply($handle, 354, 'DATA not accepted from server!' . $reply);

		// According to rfc 821 we should not send more than 1000 including the CRLF
		$message = str_replace(EOL, "\n", $header . $message);
		$message = str_replace("\r", "\n", $message);

		$lines = explode("\n", $message);

		foreach ($lines as $line) {
			$results = (empty($line)) ? [''] : str_split($line, 998);

			foreach ($results as $result) {
				//dot-stuffing per rfc 5321: lines starting with "." get an extra dot
				if ($result && $result[0] === '.') {
					$result = '.' . $result;
				}

				fwrite($handle, $result . EOL);
			}
		}

		fwrite($handle, '.' . EOL);
		$this->handleReply($handle, 250, 'DATA not accepted from server!' . $reply);
		/*
		fputs($handle, 'QUIT' . EOL);
		$this->handleReply($handle, 221, 'QUIT not accepted from server!');
		*/
		fclose($handle);

		if ($this->debug && $this->log) {
			$this->log += $lines;
			file_put_contents(DIR_STORAGE . 'logs/mail_log', implode(EOL, $this->log), FILE_APPEND);
		}

		return true;
	}

	/**
	 * Read a (possibly multi line) server reply and optionally validate its status code.
	 *
	 * @param mixed        $handle
	 * @param int|false    $status_code Expected status code or false to skip validation.
	 * @param string|false $error_text  Exception message on status mismatch.
	 * @param int          $counter     Retry counter for empty reads.
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */
	private function handleReply(mixed $handle, int|false $status_code = false, string|false $error_text = false, int $counter = 0): string {
		if (!is_resource($handle)) {
			throw new \InvalidArgumentException('SMTP reply requires a stream resource.');
		}

		$reply = '';

		while (($line = fgets($handle, 515)) !== false) {
			$reply .= $line;

			if (substr($line, 3, 1) == ' ') {
				break;
			}
		}

		// Wait for response
		if (!$line && !feof($handle) && $counter < $this->option['max_attempts']) {
			sleep(1);

			$counter++;

			return $this->handleReply($handle, $status_code, $error_text, $counter);
		}

		if ($status_code) {
			if (substr($reply, 0, 3) != $status_code) {
				throw new \Exception($error_text);
			}
		}

		if ($this->debug) {
			$this->log[] = $reply;
		}

		return $reply;
	}
}
