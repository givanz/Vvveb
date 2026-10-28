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

namespace Vvveb;

/**
 * Convert a date to a human-friendly relative format.
 *
 * @param string|int $date Date string or Unix timestamp
 *
 * @return string|int Human-friendly date string, or original timestamp if too far in the future
 */
function friendlyDate(string|int $date): string|int {
	$fileformats = [
		1          => static fn ($i) => __('%d second', '%d seconds', $i),
		60         => static fn ($i) => __('%d minute', '%d minutes', $i),
		3600       => static fn ($i) => __('%d hour', '%d hours', $i),
		86400      => static fn ($i) => __('%d day', '%d days', $i),
		604800     => static fn ($i) => __('%d week', '%d weeks', $i),
		2592000    => static fn ($i) => __('%d month', '%d months', $i),
		31536000   => static fn ($i) => __('%d year', '%d years', $i),
		315360000  => static fn ($i) => __('%d decade', '%d decades', $i),
		3153600000 => static fn ($i) => __('%d century', '%d centuries', $i),
	];

	$time_direction = __(' ago');

	if (is_string($date)) {
		$timestamp = strtotime($date);

		if ($timestamp === false) {
			return $date;
		}

		$date = $timestamp;
	}

	$diff           = time() - $date + 10;

	if ($diff < 0) {
		$time_direction = __(' from now');
		$diff           = abs($diff);
	}

	$lastTime = 1;
	//keep the closure, it is called with the computed unit count below
	$lastText = $fileformats[1];

	foreach ($fileformats as $time => $text) {
		if ($diff < $time) {
			$units = floor($diff / $lastTime);

			return sprintf($lastText($units), $units) . $time_direction;
		}
		$lastText = $text;
		$lastTime = $time;
	}

	return $date;
}

/**
 * Returns Bootstrap CSS class for order status badge.
 *
 * @param int $order_status_id Order status ID (1-7)
 *
 * @return string CSS class string for the badge
 */
function orderStatusBadgeClass(int $order_status_id = 1): string {
	$classes = [
		1 => 'bg-primary-subtle text-body', //pending
		2 => 'bg-info-subtle text-body', //processing
		3 => 'bg-primary', //processed
		4 => 'bg-success', //complete
		5 => 'bg-danger', //canceled
		6 => 'bg-secondary', //archived
		7 => 'bg-warning', //requires_action
	];

	return $classes[$order_status_id] ?? 'bg-warning text-dark';
}

/**
 * Returns Bootstrap CSS class for payment status badge.
 *
 * @param int $payment_status_id Payment status ID (1-10)
 *
 * @return string CSS class string for the badge
 */
function paymentStatusBadgeClass(int $payment_status_id = 1): string {
	$classes = [
		1  => 'bg-warning-subtle text-body', //not_paid
		2  => 'bg-success-subtle text-body', //awaiting
		3  => 'bg-danger-subtle text-body', //captured
		4  => 'bg-success', //paid
		5  => 'bg-danger', //canceled
		6  => 'bg-danger', //refunded
		7  => 'bg-danger', //partially_refunded
		8  => 'bg-danger', //chargeback
		9  => 'bg-danger', //requires_action
		10 => 'bg-danger', //fraud
	];

	return $classes[$payment_status_id] ?? 'bg-warning text-dark';
}

/**
 * Returns Bootstrap CSS class for shipping status badge.
 *
 * @param int $shipping_status_id Shipping status ID (1-10)
 *
 * @return string CSS class string for the badge
 */
function shippingStatusBadgeClass(int $shipping_status_id = 1): string {
	$classes = [
		1  => 'bg-warning-subtle text-body', //not_fulfilled
		2  => 'bg-success-subtle text-body', //fulfilled
		3  => 'bg-success-subtle text-body', //partially_fulfilled
		4  => 'bg-success', //shipped
		5  => 'bg-success-subtle text-body', //partially_shipped
		6  => 'bg-danger-subtle text-body', //returned
		7  => 'bg-danger-subtle text-body', //partially_returned
		8  => 'bg-success', //delivered
		9  => 'bg-danger', //canceled
		10 => 'bg-warning', //requires_action
	];

	return $classes[$shipping_status_id] ?? 'bg-warning text-dark';
}

/**
 * Returns Bootstrap CSS class for comment status badge.
 *
 * @param int $status Comment status ID (0-3)
 *
 * @return string CSS class string for the badge
 */
function commentStatusBadgeClass(int $status = 0): string {
	$classes = [
		0 => 'bg-primary-subtle text-body', //pending
		1 => 'bg-success-subtle text-body', //approved
		2 => 'bg-warning-subtle text-body', //spam
		3 => 'bg-danger-subtle text-body', //trash
	];

	return $classes[$status] ?? 'bg-secondary-subtle text-dark';
}

/**
 * Return all available field type definitions.
 *
 * @return array<array-key, mixed> Associative array of field types grouped by category.
 */
function getFieldTypes(): array {
	return
	[
		'basic' => [
			'text' => [
				'name' => __('Text'),
				'icon' => 'text-size',
			],
			'textarea' => [
				'name' => __('Textarea'),
				'icon' => 'file-text',
			],
			'number' => [
				'name' => __('Number'),
				'icon' => 'file-text',
			],
			'range' => [
				'name' => __('Range'),
				'icon' => 'git-commit',
			],
			'email' => [
				'name' => __('Email'),
				'icon' => 'mail',
			],
			'url' => [
				'name' => __('Url'),
				'icon' => 'link',
			],
			'tel' => [
				'name' => __('Telephone'),
				'icon' => 'phone-call',
			],
			'password' => [
				'name' => __('Password'),
				'icon' => 'lock-open',
			],
			'select' => [
				'name' => __('Select'),
				'icon' => 'circle-chevron-down',
			],
		],
		'content' => [
			'image' => [
				'name' => __('Image'),
				'icon' => 'photo',
			],
			'file' => [
				'name' => __('File'),
				'icon' => 'file',
			],
			'wysiwyg' => [
				'name' => __('Wysiwyg Editor'),
				'icon' => 'pencil',
			],
			'oembed' => [
				'name' => __('oEmbed'),
				'icon' => 'video',
			],
			'gallery' => [
				'name' => __('Gallery'),
				'icon' => 'photo',
			],
		],
		'choice' => [
			'select' => [
				'name' => __('Select'),
				'icon' => 'circle-chevron-down',
			],
			'checkbox' => [
				'name' => __('Checkbox'),
				'icon' => 'checkbox',
			],
			'radio' => [
				'name' => __('Radio Button'),
				'icon' => 'circle-check',
			],
			'toggle' => [
				'name' => __('Toggle'),
				'icon' => 'toggle-left',
			],
		],
		'relational' => [
			'link' => [
				'name' => __('Link'),
				'icon' => 'link',
			],
			'post' => [
				'name' => __('Post'),
				'icon' => 'file-text',
			],
			'product' => [
				'name' => __('Product'),
				'icon' => 'box',
			],
			'relationship' => [
				'name' => __('Relationship'),
				'icon' => 'link',
			],
			'taxonomy' => [
				'name' => __('Taxonomy'),
				'icon' => 'grid-dots',
			],
			'user' => [
				'name' => __('User'),
				'icon' => 'user',
			],
		],
		'widgets' => [
			'google_map' => [
				'name' => __('Google Map'),
				'icon' => 'map',
			],
			'color' => [
				'name' => __('Color'),
				'icon' => 'icon-color-palette-outline',
			],
			'time' => [
				'name' => __('Time'),
				'icon' => 'clock',
			],
			'date' => [
				'name' => __('Date'),
				'icon' => 'calendar-month',
			],
			'date_time' => [
				'name' => __('Date time'),
				'icon' => 'calendar-off',
			],
			'month' => [
				'name' => __('Month'),
				'icon' => 'calendar',
			],
			'week' => [
				'name' => __('week'),
				'icon' => 'calendar-month',
			],
		],
		'advanced' => [
			'message' => [
				'name' => __('Message'),
				'icon' => 'circle-chevron-down',
			],
			'accordion' => [
				'name' => __('Accordion'),
				'icon' => 'circle-chevron-down',
			],
			'tab' => [
				'name' => __('Tab'),
				'icon' => 'circle-chevron-down',
			],
		],
	];
}

/**
 * Return the fully qualified class name for a field type.
 *
 * @param string $fieldType The field type identifier.
 *
 * @return string|null The fully qualified class name, or null if not found.
 */
function fieldTypeClass(string $fieldType): ?string {
	$field      = ucfirst($fieldType);
	$fieldClass = "Vvveb\System\Fields\\$field";

	if (!class_exists($fieldClass)) {
		return null;
	}

	return $fieldClass;
}

/**
 * Returns available post types for a given type category.
 *
 * @param string $type The type category (e.g. 'post', 'product').
 *
 * @return array<array-key, mixed>
 */
function postTypes(string $type): array {
	$defaultTypes = [
		'post' => [
			'post' => [
				'name'     => 'Post',
				'source'   => 'default',
				'site_id'  => '0',
				'type'     => 'post',
				'plural'   => 'posts',
				'icon'     => 'file-text',
				'comments' => true,
			],
			'page' => [
				'name'     => 'Page',
				'source'   => 'default',
				'site_id'  => '0',
				'type'     => 'page',
				'plural'   => 'pages',
				'icon'     => 'file',
				'comments' => false,
			],
		],
		'product' => [
			'product' => [
				'name'    => 'Product',
				'type'    => 'product',
				'site_id' => '0',
				'source'  => 'default',
				'plural'  => 'products',
				'icon'    => 'box',
			],
		],
	];

	$default               = $defaultTypes[$type] ?? [];
	$typeName              = ucfirst($type);
	[$pluginTypes]         = System\Event::trigger('Vvveb\postTypes', "custom$typeName", []);
	array_walk($pluginTypes, function(&$type, $key): void {
		$type['source'] = 'plugin';
		$type['name']   = ucfirst($key);
	});

	$userTypes = getSetting($type, 'types', []);

	$params            = ['module' => "settings/{$type}-type"];
	$paramsList        = ['module' => "settings/{$type}-types"];

	array_walk($userTypes, function(&$type, $key) use ($params, $paramsList): void {
		$type['source']     = 'user';
		$type['name']       = ucfirst($key);
		$type['url']        = \Vvveb\url($params + ['type' => $type['type']]);
		$type['delete-url'] = \Vvveb\url($paramsList + ['action' => 'delete', 'type[]' => $type['type']]);
	});

	return $default + $pluginTypes + $userTypes;
}

/**
 * Formats invoice number by replacing template placeholders with data.
 *
 * @param string              $format The format string containing {placeholders} like {year}, {month}, {rand-no-6}
 * @param array<string,mixed> $data   Array of replacement values
 *
 * @return string The formatted invoice string
 */
function invoiceFormat(string $format, array $data): string {
	if (!$format) {
		return '';
	}
	$data += ['date_added' => time(), 'year' => date('Y'), 'year2' => date('y'), 'month_name' => date('M'), 'month' => date('m'), 'day' => date('d')];

	return preg_replace_callback('/{(.+?)}/', function($matches) use ($data) {
		$key = $matches[1];

		if (isset($data[$key])) {
			return $data[$key];
		}

		if (str_contains($key, 'rand-no-')) {
			$length = (int)str_replace('rand-no-', '', $key);

			return randomDigits($length);
		}

		if (str_contains($key, 'rand-str-')) {
			$length = (int)str_replace('rand-str-', '', $key);

			return strtoupper(System\Functions\Str::random($length));
		}

		return $matches[0];
	}, $format);
}
