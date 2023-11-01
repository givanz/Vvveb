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

use function Vvveb\__;
use function Vvveb\arrayPath;

class Validator {
	private $rules;

	private $defaultMessages = [];

	/*
		[
			'notEmpty' => '%s is empty', 
			'allowed_values' => '%s is invalid, valid options are %s', 
			'maxLength' => '%s is longer than %d'
		];
	*/
	function __construct($rules) {
		$this->defaultMessages = [
			'notEmpty'            => __('%s is empty'),
			'allowedValues'       => __('%s is invalid, valid options are %s'),
			'maxLength'           => __('%s is longer than %d'),
			'minLength'           => __('%s is shorter than %d'),
			'match'               => __('%s does not match %s'),
			'captcha'             => __('%s is invalid'),
			'session'             => __('%s is invalid'),
			'email'               => __('%s invalid email'),
			'passwordComplexity'  => __('%s not complex enough, include uppercase letters and digits'),
		];

		$this->rules($rules);
	}

	function rules($ruleFiles) {
		$rules = [];

		foreach ($ruleFiles as $rule) {
			if (strpos($rule,'plugins.') !== false) {
				//$plugin = 'plugins.()'
				$options = \Vvveb\pregMatch('/plugins\.(?<plugin>[^\.]+)\.(?<rule>[^\.]+)/', $rule);
				$file    = DIR_PLUGINS . $options['plugin'] . '/' . APP . '/validate/' . $options['rule'] . '.php';
			} else {
				$file = DIR_APP . 'validate/' . $rule . '.php';
			}
			$rules = array_merge($rules, include($file));
		}

		$this->rules = $rules;
	}

	//remove keys that are not in the validation list
	function filter($input) {
		$validKeys = array_keys($this->rules);

		return array_filter($input, function ($key) use ($validKeys) {
			return in_array($key,$validKeys);
		}, ARRAY_FILTER_USE_KEY);
	}

	function validate($input) {
		$errors       = [];
		$errorMessage = false;

		foreach ($this->rules as $inputName => $rules) {
			$name  = \Vvveb\humanReadable($inputName);
			$value = $input[$inputName] ?? arrayPath($input, $inputName);

			if ($value !== null && $value !== false) {
				foreach ($rules as $rule => $options) {
					if (is_array($options)) {
						$ruleName    = key($options);
						$ruleOptions = $rule[$ruleName] ?? [];
					} else {
						$ruleName    = $rule;
						$ruleOptions = $options;
					}

					$ruleMethod  = 'rule' . $ruleName;

					$message = '';

					if (isset($this->defaultMessages[$ruleName])) {
						$message = $this->defaultMessages[$ruleName];
					}

					if (isset($options['message'])) {
						$message = $options['message'];
					}

					if (isset($options['name'])) {
						$name = $options['name'];
					}

					if (method_exists($this, $ruleMethod)) {
						$errorMessage = $this->$ruleMethod($value, $ruleOptions, $name, $message, $input);
					}

					if ($errorMessage) {
						$errors[$inputName] = $errorMessage;
					}
				}
			} else {
				if (isset($rules['notEmpty'])) {
					$rule    = $rules['notEmpty'];
					$message = $rule['message'] ?? __('%s is empty');
					$name    = $rule['name'] ?? $name;
				}
				$errors[$inputName] = sprintf($message, $name);
			}
		}

		return empty($errors) ? true : $errors;
	}

	function getJSON() {
		foreach ($this->rules as $inputName => &$rules) {
			foreach ($rules as $rule => &$value) {
				$ruleName = $rule;

				if (! is_array($value)) {
					if (empty($value)) {
						$value = [];
					} else {
						$value = [$value];
					}
				}

				if (! isset($value['message']) && isset($this->defaultMessages[$ruleName])) {
					$rules[$ruleName]['message'] = $this->defaultMessages[$ruleName];
				}
			}
		}

		return json_encode($this->rules);
	}

	/* rules */

	function ruleOptional($value, $options, $name, $message, $input) {
		return false;
	}

	function ruleNotEmpty($value, $options, $name, $message, $input) {
		if ($value === '' || $value === false || $value === null) {
			return sprintf(__($message), $name);
		}

		return false;
	}

	function ruleEmail($value, $options, $name, $message, $input) {
		if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return sprintf(__($message), $name);
		}

		return false;
	}

	function rule($value, $options, $name, $message, $input) {
		if (! in_array($value, $options)) {
			return sprintf(__($message), $name, implode(', ',$options));
		}

		return false;
	}

	function ruleMaxLength($value, $options, $name, $message, $input) {
		if (! is_string($value) || (strlen($value) > $options)) {
			return  sprintf(__($message), $name, $options);
		}

		return false;
	}

	function ruleMinLength($value, $options, $name, $message, $input) {
		if (! is_string($value) || (strlen($value) < $options)) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	function ruleAllowedValues($value, $options, $name, $message, $input) {
		if (! is_string($value) || (strlen($value) > $options)) {
			return  sprintf(__($message), $name, implode(', ' ,$options));
		}

		return false;
	}

	function ruleSession($value, $options, $name, $message, $input) {
		if (\Vvveb\session($options) != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	function ruleCaptcha($value, $options, $name, $message, $input) {
		if ($input[$options] != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	function ruleMatch($value, $options, $name, $message, $input) {
		if ($input[$options] != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	function rulePasswordComplexity($value, $options, $name, $message, $input) {
		switch ($options) {
			case 'low':
				$regex =
				'/^' . 						  //  Start anchor
				'(?=.*[A-Z])' .				  //  Ensure string has one uppercase letters.
				'(?=.*[0-9])' .   			  //  Ensure string has one digits.
				'(?=.*[a-z].*[a-z].*[a-z])' . //  Ensure string has three lowercase letters.
				'.*$/';

			break;

			case 'medium':
				$regex =
				'/^' . 						  //  Start anchor
				'(?=.*[A-Z].*[A-Z])' .		  //  Ensure string has two uppercase letters.
				'(?=.*[!@#$&*%])' .			  //  Ensure string has one special case letter.
				'(?=.*[0-9].*[0-9])' .   	  //  Ensure string has two digits.
				'(?=.*[a-z].*[a-z].*[a-z])' . //  Ensure string has three lowercase letters.
				'.*$/';

			case 'high':
				$regex =
				'/^' . 					  	  //  Start anchor
				'(?=.*[A-Z].*[A-Z])' .		  //  Ensure string has two uppercase letters.
				'(?=.*[!@#$&*%])' .			  //  Ensure string has one special case letter.
				'(?=.*[0-9].*[0-9])' .   	  //  Ensure string has two digits.
				'(?=.*[a-z].*[a-z].*[a-z])' . //  Ensure string has three lowercase letters.
				'.*$/';

			break;
		}

		if (($result = preg_match($regex, $value)) == false) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}
}
