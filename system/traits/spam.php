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

namespace Vvveb\System\Traits;

trait Spam {
	//these must be empty, they are hidden in html and only bots will fill them
	//todo: add dynamic field name

	protected $spamFields =  [
		'firstname-empty',
		'lastname-empty',
		'subject-empty',
		'contact-form',
	];

	function checkIfSpam(&$message) {
		return $message;
	}

	function isSpam(&$message) {
		$spam = false;

		foreach ($this->spamFields as $field) {
			if (isset($message[$field]) && ! empty($message[$field])) {
				return $spam = true;
			}
		}

		return $spam;
	}

	function removeSpamCatchFields(&$message) {
		foreach ($this->spamFields as $field) {
			if (isset($message[$field])) {
				unset($message[$field]);
			}
		}

		return $message;
	}
}
