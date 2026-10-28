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
	/** @var array<string, array<string, mixed>> Validation rules keyed by input name. */
	private array $rules = [];

	/** @var array<string, string> Default error messages for each rule type. */
	private array $defaultMessages = [];

	/**
	 * Initialize the validator with rule file paths.
	 *
	 * @param list<string> $ruleFiles List of rule file identifiers to load.
	 */
	public function __construct(array $ruleFiles) {
		$this->defaultMessages = [
			'notEmpty'           => __('%s is empty'),
			'allowedValues'      => __('%s is invalid, valid options are %s'),
			'maxLength'          => __('%s is longer than %d'),
			'minLength'          => __('%s is shorter than %d'),
			'match'              => __('%s does not match %s'),
			'digit'              => __('%s is invalid'),
			'captcha'            => __('%s is invalid'),
			'session'            => __('%s is invalid'),
			'email'              => __('%s invalid email'),
			'integer'            => __('%s not numeric'),
			'passwordComplexity' => __('%s not complex enough, include uppercase letters and digits'),
		];

		$this->rules($ruleFiles);
	}

	/**
	 * Load validation rules from PHP files.
	 *
	 * @param list<string> $ruleFiles Rule file identifiers.
	 *
	 * @return void
	 */
	public function rules(array $ruleFiles): void {
		$rules = [];

		foreach ($ruleFiles as $rule) {
			if (str_contains($rule, 'plugins.')) {
				$options = \Vvveb\pregMatch('/plugins\.(?<plugin>[^\.]+)\.(?<rule>[^\.]+)/', $rule);
				$file    = DIR_PLUGINS . $options['plugin'] . '/' . APP . '/validate/' . $options['rule'] . '.php';
			} else {
				$file = DIR_APP . 'validate/' . $rule . '.php';
			}
			$rules = array_merge($rules, include ($file));
		}

		$this->rules = $rules;
	}

	/**
	 * Remove a validation rule by input name.
	 *
	 * @param string $rule Input name to remove.
	 *
	 * @return void
	 */
	public function removeRule(string $rule): void {
		unset($this->rules[$rule]);
	}

	/**
	 * Filter input to only include keys present in the validation rules.
	 *
	 * @param array<string, mixed> $input Raw input data.
	 *
	 * @return array<string, mixed> Filtered input.
	 */
	public function filter(array $input): array {
		$validKeys = array_keys($this->rules);
		$filter    = [];

		foreach ($validKeys as $key) {
			if (str_contains($key, '.')) {
				$keys                       = explode('.', $key);
				$filter[$keys[0]][$keys[1]] = arrayPath($input, $key);
			} else {
				if (isset($input[$key])) {
					$filter[$key] = $input[$key];
				}
			}
		}

		return $filter;
	}

	/**
	 * Validate input data against loaded rules.
	 *
	 * @param array<string, mixed> $input Input data to validate.
	 *
	 * @return true|array<string, string> True if valid, or array<array-key, mixed> of input-name => error-message pairs.
	 */
	public function validate(array $input): true|array {
		$errors       = [];
		$errorMessage = false;

		foreach ($this->rules as $inputName => $rules) {
			$name  = __(\Vvveb\humanReadable($inputName));
			$value = $input[$inputName] ?? arrayPath($input, $inputName);

			if ($value !== null && $value !== false) {
				foreach ($rules as $rule => $options) {
					//message and name are field metadata, not rules
					if ($rule === 'message' || $rule === 'name') {
						continue;
					}

					$errorMessage = false;

					//rules can be declared as 'ruleName' => options
					//or as a list of ['ruleName' => options] pairs
					if (method_exists($this, 'rule' . $rule)) {
						$ruleName    = $rule;
						$ruleOptions = $options;
					} elseif (is_array($options)) {
						$ruleName    = key($options);
						$ruleOptions = $options[$ruleName] ?? [];
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
					} elseif (is_array($ruleOptions) && isset($ruleOptions['message'])) {
						$message = $ruleOptions['message'];
					}

					if (isset($options['name'])) {
						$name = $options['name'];
					}

					if (method_exists($this, $ruleMethod)) {
						$errorMessage = $this->$ruleMethod($value, $ruleOptions, $name, $message, $input);
					} else {
						//unknown rules must fail closed instead of silently skipping validation
						$errorMessage = sprintf('Unknown validation rule "%s" for "%s"', $ruleName, $name);
					}

					if ($errorMessage) {
						$errors[$inputName] = $errorMessage;
					}
				}
			} else {
				$message = $this->defaultMessages['notEmpty'];
				$name    = $rules['name'] ?? $name;

				$errors[$inputName] = sprintf($message, $name);
			}
		}

		return empty($errors) ? true : $errors;
	}

	/**
	 * Get validation rules as JSON with default messages injected.
	 *
	 * @return string JSON-encoded rules.
	 */
	public function getJSON(): string {
		foreach ($this->rules as $inputName => &$rules) {
			foreach ($rules as $rule => &$value) {
				$ruleName = $rule;

				if (!is_array($value)) {
					if (empty($value)) {
						$value = [];
					} else {
						$value = [$value];
					}
				}

				if (!isset($value['message']) && isset($this->defaultMessages[$ruleName])) {
					$rules[$ruleName]['message'] = $this->defaultMessages[$ruleName];
				}
			}
		}

		return json_encode($this->rules);
	}

	// rules

	/**
	 * Rule: allow optional/empty values.
	 *
	 * @param mixed                $value   The input value.
	 * @param mixed                $options Rule options.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return false Always returns false (no error).
	 */
	public function ruleOptional(mixed $value, mixed $options, string $name, string $message, array $input): false {
		return false;
	}

	/**
	 * Rule: value must not be empty.
	 *
	 * @param mixed                $value   The input value.
	 * @param mixed                $options Rule options.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleNotEmpty(mixed $value, mixed $options, string $name, string $message, array $input): string|false {
		if ($value === '' || $value === false || $value === null) {
			return sprintf(__($message), $name);
		}

		return false;
	}

	/**
	 * Rule: value must be a valid email address.
	 *
	 * @param mixed                $value   The input value.
	 * @param mixed                $options Rule options.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleEmail(mixed $value, mixed $options, string $name, string $message, array $input): string|false {
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return sprintf(__($message), $name);
		}

		return false;
	}

	/**
	 * Rule: value must be in the allowed options list.
	 *
	 * @param mixed                $value   The input value.
	 * @param array<int, mixed>    $options Allowed values.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function rule(mixed $value, array $options, string $name, string $message, array $input): string|false {
		if (!in_array($value, $options)) {
			return sprintf(__($message), $name, implode(', ', $options));
		}

		return false;
	}

	/**
	 * Rule: string length must not exceed the maximum.
	 *
	 * @param mixed                $value   The input value.
	 * @param int                  $options Maximum length.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleMaxLength(mixed $value, int $options, string $name, string $message, array $input): string|false {
		if (!is_string($value) || (strlen($value) > $options)) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: string length must meet the minimum.
	 *
	 * @param mixed                $value   The input value.
	 * @param int                  $options Minimum length.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleMinLength(mixed $value, int $options, string $name, string $message, array $input): string|false {
		if (!is_string($value) || (strlen($value) < $options)) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: value must be one of the allowed values.
	 *
	 * @param mixed                   $value   The input value.
	 * @param array<array-key, mixed> $options Allowed values list.
	 * @param string                  $name    Human-readable field name.
	 * @param string                  $message Error message template.
	 * @param array<string, mixed>    $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleAllowedValues(mixed $value, array $options, string $name, string $message, array $input): string|false {
		if (!in_array($value, $options)) {
			return sprintf(__($message), $name, implode(', ', $options));
		}

		return false;
	}

	/**
	 * Rule: value must match a session value.
	 *
	 * @param mixed                $value   The input value.
	 * @param string               $options Session key to compare against.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleSession(mixed $value, string $options, string $name, string $message, array $input): string|false {
		if (\Vvveb\session($options) != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: value must match the captcha input.
	 *
	 * @param mixed                $value   The input value.
	 * @param string               $options Name of the captcha input field.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleCaptcha(mixed $value, string $options, string $name, string $message, array $input): string|false {
		if (($input[$options] ?? null) != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: value must match another input field value.
	 *
	 * @param mixed                $value   The input value.
	 * @param string               $options Name of the field to match.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleMatch(mixed $value, string $options, string $name, string $message, array $input): string|false {
		if (($input[$options] ?? null) != $value) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: value must contain only decimal digits.
	 *
	 * @param mixed                $value   The input value.
	 * @param mixed                $options Rule options.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleDigit(mixed $value, mixed $options, string $name, string $message, array $input): string|false {
		if (!is_scalar($value) || !ctype_digit((string)$value)) {
			return sprintf(__($message), $name);
		}

		return false;
	}

	/**
	 * Rule: value must be numeric.
	 *
	 * @param mixed                $value   The input value.
	 * @param mixed                $options Rule options.
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function ruleNumeric(mixed $value, mixed $options, string $name, string $message, array $input): string|false {
		if (!is_numeric($value)) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}

	/**
	 * Rule: password must meet complexity requirements.
	 *
	 * @param string               $value   The password value.
	 * @param string               $options Complexity level ('low', 'medium', 'high').
	 * @param string               $name    Human-readable field name.
	 * @param string               $message Error message template.
	 * @param array<string, mixed> $input   Full input array.
	 *
	 * @return string|false Error message or false if valid.
	 */
	public function rulePasswordComplexity(string $value, string $options, string $name, string $message, array $input): string|false {
		$regex = match ($options) {
			'low' => '/^' . 						  //  Start anchor
				'(?=.*[A-Z])' .				  //  Ensure string has one uppercase letters.
				'(?=.*[0-9])' .   			  //  Ensure string has one digits.
				'(?=.*[a-z].*[a-z].*[a-z])' . //  Ensure string has three lowercase letters.
				'.*$/',

			'medium', 'high' => '/^' . 						  //  Start anchor
				'(?=.*[A-Z].*[A-Z])' .		  //  Ensure string has two uppercase letters.
				'(?=.*[!@#$&*%])' .			  //  Ensure string has one special case letter.
				'(?=.*[0-9].*[0-9])' .   	  //  Ensure string has two digits.
				'(?=.*[a-z].*[a-z].*[a-z])' . //  Ensure string has three lowercase letters.
				'.*$/',

			default => '',
		};

		if (!$regex || !preg_match($regex, $value)) {
			return sprintf(__($message), $name, $options);
		}

		return false;
	}
}
