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

use function Vvveb\__;

//use current path
if (APP == 'admin') {
	$admin_path = Vvveb\Url([]);
} else {
	$admin_path = \Vvveb\adminPath();
}

return
[
	'name'            => __('Products'),
	'url'             => $admin_path . '?module=product/products',
	'icon'		          => 'icon-cube-outline',
	'show_on_modules' => ['Product/products', 'Product/product', 'Product/categories'],
	'class'           => 'align-middle columns-2',

	'items' => [
		'products' => [
			'name'   => __('Products'),
			'url'    => $admin_path . '?module=product/products',
			'module' => 'product/products',
			'action' => 'index',

			'icon' => 'la la-box',
		],

		'addpage' => [
			'name'   => __('Add new'),
			'url'    => $admin_path . '?module=product/product',
			'module' => 'product/product',
			'action' => 'save',
			'icon'   => 'la la-plus-circle',
		],

		'taxonomy-heading' => [
			'name'    => __('Taxonomy'),
			'heading' => true,
		],
		/*
		'categories' => [
			'name'   => __('Categories'),
			'url'    => $admin_path . '?module=product/categories',
			'module' => 'product/categories',
			'action' => 'index',
			'icon'   => 'la la-boxes',
		],
*/
		'relations-heading' => [
			'name'    => __('Relations'),
			'heading' => true,
		],

		'manufacturers' => [
			'name'   => __('Manufacturers'),
			'url'    => $admin_path . '?module=product/manufacturers',
			'module' => 'product/manufacturers',
			'action' => 'index',
			'icon'   => 'la la-industry',
		],

		'vendors' => [
			'name'   => __('Vendors'),
			'url'    => $admin_path . '?module=product/vendors',
			'module' => 'product/vendors',
			'action' => 'index',
			'icon'   => 'la la-store',
		],
		/*
		'configuration-heading' => [
			'name'    => __('Configuration'),
			'heading' => true,
		],

		'custom-fields' => [
			'name'   => __('Custom fields'),
			'url'    => $admin_path . '?module=product/fields',
			'module' => 'product/fields',
			'action' => 'index',
			'icon'   => 'la la-stream',
		],

		'options' => [
			'name'   => __('Options'),
			'url'    => $admin_path . '?module=product/options',
			'module' => 'product/options',
			'action' => 'index',
			'icon'   => 'la la-filter',
		],

		'digital' => [
			'name'   => __('Digital content'),
			'url'    => $admin_path . '?module=product/options',
			'module' => 'product/options',
			'action' => 'index',
			'icon'   => 'la la-cloud-download-alt',
		],
*/
		'content-heading' => [
			'name'    => __('Content'),
			'heading' => true,
		],

		'digital' => [
			'name'   => __('Digital assets'),
			'url'    => $admin_path . '?module=product/digital-assets',
			'module' => 'product/digital-assets',
			'action' => 'index',
			'icon'   => 'la la-cloud-download-alt',
		],

		'options' => [
			'name'   => __('Options'),
			'url'    => $admin_path . '?module=product/options',
			'module' => 'product/options',
			'action' => 'index',
			'icon'   => 'la la-stream',
			//'badge' => '5',
			//'badge-class' => 'badge bg-warning float-end',
		],

		'attributes' => [
			'name'   => __('Attributes'),
			'url'    => $admin_path . '?module=product/attribute-groups',
			'module' => 'product/attributes-groups',
			'action' => 'index',
			'icon'   => 'la la-list',
		],

		'feedback-heading' => [
			'name'    => __('User feedback'),
			'heading' => true,
		],

		'reviews' => [
			'name'   => __('Reviews'),
			'url'    => $admin_path . '?module=product/product-reviews&status=0',
			'module' => 'product/product-reviews',
			'action' => 'index',
			'icon'   => 'la la-comment',
			//'badge' => '5',
			//'badge-class' => 'badge bg-warning float-end',
		],

		'questions' => [
			'name'   => __('Questions'),
			'url'    => $admin_path . '?module=product/product-questions&status=0',
			'module' => 'product/product-questions',
			'action' => 'index',
			'icon'   => 'la la-question-circle',
			//'badge' => '7',
			//'badge-class' => 'badge bg-danger float-end',
		],

		/*					
		'filters' => [
			'name' => __('Filters'),
			'url' => $admin_path . '?module=categories',
		]*/
	],
];
