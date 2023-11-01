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
 	'name'            => __('Posts'),
 	'url'             => $admin_path . '?module=content/posts',
 	'icon'            => 'icon-document-text-outline',
 	'show_on_modules' => ['posts', 'post', 'pages', 'categories'],

 	'items' => [
 		'posts' => [
 			'name'   => __('List'),
 			'url'    => $admin_path . '?module=content/posts',
 			'module' => 'content/menus',
 			'action' => 'index',
 			'icon'   => 'la la-file-alt',
 		],

 		'addpost' => [
 			'name'   => __('Add new'),
 			'url'    => $admin_path . '?module=content/post',
 			'module' => 'content/menus',
 			'action' => 'save',
 			'icon'   => 'la la-plus-circle',
 		],

 		'taxonomy-heading' => [
 			'name'    => __('Taxonomy'),
 			'heading' => true,
 		],
 		/*
		'categories' => [
			'name' => __('Categories'),
			//'subtitle' => __('(Hierarchical)'),
			'url'    => $admin_path . '?module=content/categories',
			'module' => 'content/menus',
			'action' => 'index', 			
			'icon' => 'la la-boxes',
		],

		'tags' => [
			'name' => __('Tags'),
			//'subtitle' => __('(Flat)'),
			'url'    => $admin_path . '?module=content/tags',
			'module' => 'content/menus',
			'action' => 'index',
			'icon'   => 'la la-tags',
		],
		 */

 		'categories-heading' => [
 			'name'    => __('General'),
 			'heading' => true,
 		],

 		'comments' => [
 			'name'   => __('Comments'),
 			'url'    => $admin_path . '?module=content/comments&status=0',
 			'module' => 'content/menus',
 			'action' => 'index', 			'icon' => 'la la-comments',
 		],
 		/*		
		'custom-fields' => [
			'name' => __('Custom fields'),
			'url' => $admin_path . '?module=content/fields',
			'icon' => 'la la-stream',
		],		
	
		'taxonomies' => [
			'name' => __('Taxonomies'),
			'url' => $admin_path . '?module=content/categories',
			'icon' => 'la la-boxes',
			'class' => 'align-top',
			
			'items' => [
				'categories' => [
					'name' => __('Categories'),
					'subtitle' => __('(Hierarchical)'),
					'url' => $admin_path . '?module=content/categories',
					'icon' => 'la la-boxes',
				],
				
				'tags' => [
					'name' => __('Tags'),
					'subtitle' => __('(Flat)'),
					'url' => $admin_path . '?module=content/categories',
					'icon' => 'la la-tags',
				],
			],
		],	
*/
 	],
 ];
