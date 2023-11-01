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

class Mail {
	protected $option = [];

	public function __construct(&$option = []) {
		$option                   = $this->option + $option;
		$this->option             = &$option;
		$this->option['boundary'] = '----=_NextPart_' . md5(time());

		if (! defined('EOL')) {
			define('EOL', "\r\n");
		}
	}

	private function header() {
		$date     = date('D, d M Y H:i:s O');
		$sender   = base64_encode($this->option['sender']);
		$reply_to = base64_encode($this->option['reply_to']);

		$header  = 'MIME-Version: 1.0' . EOL;
		$header .= "Date: $date" . EOL;
		$header .= "From: =?UTF-8?B?$date?= <{$this->option['from']}>" . EOL;

		if (! $this->option['reply_to']) {
			$header .= "Reply-To: =?UTF-8?B?$sender?= <{$this->option['from']}>" . EOL;
		} else {
			$header .= "Reply-To: =?UTF-8?B?$reply_to?= <{$this->option['reply_to']}>" . EOL;
		}

		$header .= 'Return-Path: ' . $this->option['from'] . EOL;
		$header .= 'X-Mailer: Vvveb PHP/' . phpversion() . EOL;
		$header .= 'Content-Type: multipart/mixed; boundary="' . $this->option['boundary'] . '"' . EOL . EOL;

		return $this->option['header'] = $header;
	}

	private function attachments() {
		$attachments = '';

		if (isset($this->option['attachments'])) {
			foreach ($this->option['attachments'] as $attachment) {
				if (is_file($attachment)) {
					$name   = basename($attachment);
					$id     = urlencode(basename($attachment));
					$contnt = file_get_contents($attachment);

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

	private function message() {
		if (! isset($this->option['html'])) {
			$message  = '--' . $this->option['boundary'] . EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['text']),950) . EOL;
		} else {
			$message  = '--' . $this->option['boundary'] . EOL;
			$message .= 'Content-Type: multipart/alternative; boundary="' . $this->option['boundary'] . '_alt"' . EOL . EOL;
			$message .= '--' . $this->option['boundary'] . '_alt' . EOL;
			$message .= 'Content-Type: text/plain; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;

			if ($this->option['text']) {
				$message .= chunk_split(base64_encode($this->option['text']), 950) . EOL;
			} else {
				$message .= chunk_split(base64_encode(strip_tags($this->option['html']), 950), '<a>') . EOL;
			}

			$message .= $this->attachments();

			$message .= '--' . $this->option['boundary'] . '_alt' . EOL;
			$message .= 'Content-Type: text/html; charset="utf-8"' . EOL;
			$message .= 'Content-Transfer-Encoding: base64' . EOL . EOL;
			$message .= chunk_split(base64_encode($this->option['html']), 950) . EOL;
			$message .= '--' . $this->option['boundary'] . '_alt--' . EOL;
		}

		return $message .= '--' . $this->option['boundary'] . '--' . EOL;
	}

	public function send() {
		if (is_array($this->option['to'])) {
			$to = implode(',', $this->option['to']);
		} else {
			$to = $this->option['to'];
		}

		$header  = $this->header();
		$message = $this->message();

		ini_set('sendmail_from', $this->option['from']);
		$subject = '=?UTF-8?B?' . base64_encode($this->option['subject']) . '?=';

		mail($to, $subject, $message, $header, $this->option['parameter'] ?? '');
	}
}
