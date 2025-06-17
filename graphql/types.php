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

return
	[
		'product' => [
			'name'       => 'total',
			'properties' => [
				'price_currency' => [
					'name'        => 'price_currency',
					'description' => '',
					'type'        => 'String',
				],
			],
		],
		'total' => [
			'name'       => 'total',
			'properties' => [
				'key' => [
					'name'        => 'key',
					'description' => '',
					'type'        => 'String',
				],
				'title' => [
					'name'        => 'title',
					'description' => '',
					'type'        => 'String',
				],
				'value' => [
					'name'        => 'value',
					'description' => '',
					'type'        => 'Float',
				],
				'value_formatted' => [
					'name'        => 'value_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'text' => [
					'name'        => 'text',
					'description' => '',
					'type'        => 'String',
				],
			],
		],
		'cartOptionValue' => [
			'name'       => 'cartOptionValue',
			'properties' => [
				'product_option_value_id' => [
					'name'        => 'product_option_value_id',
					'description' => '',
					'type'        => 'Id',
				],
				'product_option_id' => [
					'name'        => 'product_option_id',
					'description' => '',
					'type'        => 'Id',
				],
				'product_id' => [
					'name'        => 'product_id',
					'description' => '',
					'type'        => 'Id',
				],
				'option_id' => [
					'name'        => 'option_id',
					'description' => '',
					'type'        => 'Id',
				],
				'option_value_id' => [
					'name'        => 'option_value_id',
					'description' => '',
					'type'        => 'Id',
				],
				'quantity' => [
					'name'        => 'quantity',
					'description' => '',
					'type'        => 'Int',
				],
				'subtract' => [
					'name'        => 'subtract',
					'description' => '',
					'type'        => 'Int',
				],
				'price_operator' => [
					'name'        => 'price_operator',
					'description' => '',
					'type'        => 'String',
				],
				'price' => [
					'name'        => 'price',
					'description' => '',
					'type'        => 'Float',
				],
				'points' => [
					'name'        => 'points',
					'description' => '',
					'type'        => 'Int',
				],
				'points_operator' => [
					'name'        => 'points_operator',
					'description' => '',
					'type'        => 'String',
				],
				'weight_operator' => [
					'name'        => 'weight_operator',
					'description' => '',
					'type'        => 'String',
				],
				'weight' => [
					'name'        => 'weight',
					'description' => '',
					'type'        => 'Float',
				],
				'image' => [
					'name'        => 'image',
					'description' => '',
					'type'        => 'String',
				],
				'name' => [
					'name'        => 'name',
					'description' => '',
					'type'        => 'String',
				],
				'option' => [
					'name'        => 'option',
					'description' => '',
					'type'        => 'String',
				],
				'price_tax' => [
					'name'        => 'price_tax',
					'description' => '',
					'type'        => 'Float',
				],
				'price_formatted' => [
					'name'        => 'price_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'price_currency' => [
					'name'        => 'price_currency',
					'description' => '',
					'type'        => 'String',
				],
				'weight_formatted' => [
					'name'        => 'price_tax',
					'description' => '',
					'type'        => 'Float',
				],
			],
		],
		'cartProduct' => [
			'name'       => 'cartProduct',
			'properties' => [
				'name' => [
					'name'        => 'name',
					'description' => '',
					'type'        => 'String',
				],
				'key' => [
					'name'        => 'key',
					'description' => '',
					'type'        => 'String',
				],
				'product_id' => [
					'name'        => 'product_id',
					'description' => '',
					'type'        => 'Id',
				],
				'product_variant_id' => [
					'name'        => 'product_variant_id',
					'description' => '',
					'type'        => 'Int',
				],
				'price' => [
					'name'        => 'price',
					'description' => '',
					'type'        => 'Float',
				],
				'price_tax' => [
					'name'        => 'price_tax',
					'description' => '',
					'type'        => 'Float',
				],
				'price_currency' => [
					'name'        => 'price_currency',
					'description' => '',
					'type'        => 'String',
				],
				'total' => [
					'name'        => 'total',
					'description' => '',
					'type'        => 'Float',
				],
				'total_tax' => [
					'name'        => 'total_tax',
					'description' => '',
					'type'        => 'Float',
				],
				'price_tax_formatted' => [
					'name'        => 'price_tax_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'price_formatted' => [
					'name'        => 'price_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'total_formatted' => [
					'name'        => 'total_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'total_tax_formatted' => [
					'name'        => 'total_tax_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'slug' => [
					'name'        => 'slug',
					'description' => '',
					'type'        => 'String',
				],
				'url' => [
					'name'        => 'url',
					'description' => '',
					'type'        => 'String',
				],
				'image' => [
					'name'        => 'image',
					'description' => '',
					'type'        => 'String',
				],
				'quantity' => [
					'name'        => 'quantity',
					'description' => '',
					'type'        => 'Int',
				],
				'option_value' => [
					'name'        => 'option_value',
					'description' => '',
					'type'        => '[CartOptionValueType]',
				],
			],
		],
		'cart' => [
			'name'       => 'cart',
			'properties' => [
				'cart_id' => [
					'name'        => 'cart_id',
					'description' => '',
					'type'        => 'Id',
				],
				'encrypted_cart_id' => [
					'name'        => 'encrypted_cart_id',
					'description' => '',
					'type'        => 'String',
				],
				'products' => [
					'name'        => 'products',
					'description' => '',
					'type'        => '[CartProductType]',
				],
				'totals' => [
					'name'        => 'totals',
					'description' => '',
					'type'        => '[TotalType]',
				],
				'total_items' => [
					'name'        => 'total_items',
					'description' => '',
					'type'        => 'Int',
				],
				'total_price' => [
					'name'        => 'total_price',
					'description' => '',
					'type'        => 'Float',
				],
				'total_tax' => [
					'name'        => 'total_tax',
					'description' => '',
					'type'        => 'Float',
				],
				'total' => [
					'name'        => 'total',
					'description' => '',
					'type'        => 'Float',
				],
				'total_formatted' => [
					'name'        => 'total_formatted',
					'description' => '',
					'type'        => 'String',
				],
				'price_currency' => [
					'name'        => 'price_currency',
					'description' => '',
					'type'        => 'String',
				],
				'weight_unit' => [
					'name'        => 'weight_unit',
					'description' => '',
					'type'        => 'String',
				],
				'length_unit' => [
					'name'        => 'length_unit',
					'description' => '',
					'type'        => 'String',
				],
				'cart_url' => [
					'name'        => 'cart_url',
					'description' => '',
					'type'        => 'String',
				],
				'checkout_url' => [
					'name'        => 'checkout_url',
					'description' => '',
					'type'        => 'String',
				],
			],
		],
	];
