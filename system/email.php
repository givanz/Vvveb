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
	private $driver;

	protected $option;

	protected $attachments = [];

	public static function getInstance() {
		static $inst = null;

		if ($inst === null) {
			$driver  = \Vvveb\config('mail.driver', 'mail');
			$options = \Vvveb\config("mail.$driver", []);
			$inst    = new self($driver, $options);
		}

		return $inst;
	}

	public function __construct($driver = 'mail', &$option = []) {
		$class = '\\Vvveb\\System\\Mail\\' . $driver;

		if (class_exists($class)) {
			$this->option = &$option;
			$this->driver = new $class($option);
		} else {
			throw new \Exception("Could not load mail driver '$driver'");
		}

		return $this->driver;
	}

	public function setTo($to) {
		$this->option['to'] = $to;
	}

	public function setFrom($from) {
		$this->option['from'] = $from;
	}

	public function setSender($sender) {
		$this->option['sender'] = $sender;
	}

	public function setReplyTo($reply_to) {
		$this->option['reply_to'] = $reply_to;
	}

	public function setSubject($subject) {
		$this->option['subject'] = $subject;
	}

	public function setText($text) {
		$this->option['text'] = $text;
	}

	public function setHtml($html) {
		$this->option['html'] = $html;
	}

	public function addAttachment($filename) {
		$this->option['attachments'][] = $filename;
	}

	public function send() {
		if (! $this->option['to']) {
			throw new \Exception('Email to required!');
		}

		if (! $this->option['from']) {
			throw new \Exception('Email from required!');
		}

		if (! $this->option['sender']) {
			throw new \Exception('Email sender required!');
		}

		if (! $this->option['subject']) {
			throw new \Exception('Email subject required!');
		}

		if (! $this->option['html']) {
			throw new \Exception('Email message required!');
		}

		$option = &$this->option;

		list($option, $this->driver) = Event::trigger(__CLASS__, __FUNCTION__, $option, $this->driver);

		return $this->driver->send();
	}
}
