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

namespace Vvveb\System;

class Email {
	/** @var object The mail driver instance. */
	private object $driver;

	/** @var array<string, mixed> Mail configuration options. */
	protected array $option = [];

	/** @var list<string> Attached file paths. */
	protected array $attachments = [];

	/**
	 * Get singleton instance of the Email class.
	 *
	 * @param array<array-key, mixed> $additionalOptions
	 *
	 * @return self
	 */
	public static function getInstance(array $additionalOptions = []): self {
		static $inst = null;

		if ($inst === null) {
			$driver  = \Vvveb\config('mail.driver', 'mail');
			$options = \Vvveb\config("mail.$driver", []);
			$options += $additionalOptions;
			$inst    = new self($driver, $options);
		}

		return $inst;
	}

	/**
	 * Initialize the mail driver.
	 *
	 * @param string               $driver  Mail driver name or fully-qualified class name.
	 * @param array<string, mixed> &$option Mail options passed by reference.
	 *
	 * @throws \Exception If the driver class does not exist.
	 *
	 * @return object The instantiated mail driver.
	 */
	public function __construct(string $driver = 'mail', array &$option = []) {
		if (strpos($driver, '\\')) {
			$class = $driver;
		} else {
			$class = '\Vvveb\System\\Mail\\' . $driver;
		}

		if (class_exists($class)) {
			$this->option = &$option;
			$this->driver = new $class($option);
		} else {
			throw new \Exception("Could not load mail driver '$driver'");
		}
	}

	/**
	 * Set the recipient email address.
	 *
	 * @param array<array-key, mixed>|string $to Recipient email.
	 *
	 * @return void
	 */
	public function setTo(array|string $to): void {
		$this->option['to'] = $to;
	}

	/**
	 * Set the sender email address.
	 *
	 * @param string $from Sender email.
	 *
	 * @return void
	 */
	public function setFrom(string $from): void {
		$this->option['from'] = $from;
	}

	/**
	 * Set the sender display name.
	 *
	 * @param string $sender Sender name.
	 *
	 * @return void
	 */
	public function setSender(string $sender): void {
		$this->option['sender'] = $sender;
	}

	/**
	 * Set the reply-to email address.
	 *
	 * @param string $reply_to Reply-to email.
	 *
	 * @return void
	 */
	public function setReplyTo(string $reply_to): void {
		$this->option['reply_to'] = $reply_to;
	}

	/**
	 * Set the email subject.
	 *
	 * @param string $subject Email subject.
	 *
	 * @return void
	 */
	public function setSubject(string $subject): void {
		$this->option['subject'] = $subject;
	}

	/**
	 * Set the plain text body.
	 *
	 * @param string $text Plain text content.
	 *
	 * @return void
	 */
	public function setText(string $text): void {
		$this->option['text'] = $text;
	}

	/**
	 * Set the HTML body.
	 *
	 * @param string $html HTML content.
	 *
	 * @return void
	 */
	public function setHtml(string $html): void {
		$this->option['html'] = $html;
	}

	/**
	 * Add an attachment file path.
	 *
	 * @param string $filename Path to the file to attach.
	 *
	 * @return void
	 */
	public function addAttachment(string $filename): void {
		$this->option['attachments'][] = $filename;
	}

	/**
	 * Validate required fields and send the email.
	 *
	 * @throws \Exception If a required field is missing.
	 *
	 * @return bool
	 */
	public function send(): bool {
		if (!$this->option['to']) {
			throw new \Exception('Email to required!');
		}

		if (!$this->option['from']) {
			throw new \Exception('Email from required!');
		}

		if (!$this->option['sender']) {
			throw new \Exception('Email sender required!');
		}

		if (!$this->option['subject']) {
			throw new \Exception('Email subject required!');
		}

		if (!$this->option['html']) {
			throw new \Exception('Email message required!');
		}

		$option = &$this->option;

		[$option, $this->driver] = Event::trigger(__CLASS__, __FUNCTION__, $option, $this->driver);

		return $this->driver->send();
	}
}
