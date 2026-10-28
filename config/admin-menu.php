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

$admin_path .= !str_contains($admin_path, 'index.php') ? 'index.php' : '';

return
 [
 	'dashboard' => [
 		'name'   => __('Dashboard'),
 		'url'    => $admin_path,
 		'module' => 'index',
 		'action' => 'index',
 		'icon'   => 'chart-pie',
 	],
 	'edit' => [
 		'name'   => __('Page builder'),
 		'url'    => $admin_path . '?module=editor/page-builder',
 		'module' => 'editor/page-builder',
 		'action' => 'index',
 		'icon'   => 'wand',
 	],
 	/*
	'posts' =>
	[
		'name' => __('Posts'),
		'url' => $admin_path . '?module=content/posts',
'module' => 'content/posts',
 'action' => 'index',
		'icon' => 'photo',
		'show_on_modules' => ['posts', 'post', 'pages', 'categories'],
		'items' => [
			'posts' => [
				'name' => __('Posts'),
				'url' => $admin_path . '?module=content/posts',
'module' => 'content/posts',
 'action' => 'index',
				'icon' => 'file-text',
			],
			'addpost' => [
				'name' => __('Add new post'),
				'url' => $admin_path . '?module=content/post',
'module' => 'content/post',
 'action' => 'index',
				'icon' => 'circle-plus',
			],

			'taxonomy-heading' =>
			[
				'name' => __('Taxonomy'),
				'heading' => true
			],

			'categories' =>
			[
				'name' => __('Categories'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'package',
			],

			'tags' => [
				'name' => __('Tags'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'tags',
			],

			'categories-heading' =>
			[
				'name' => __('General'),
				'heading' => true
			],

			'comments' => [
				'name' => __('Comments'),
				'url' => $admin_path . '?module=content/comments',
'module' => 'content/comments',
 'action' => 'index',
				'icon' => 'messages',
			],

			'custom-fields' => [
				'name' => __('Custom fields'),
				'url' => $admin_path . '?module=content/fields',
'module' => 'content/fields',
 'action' => 'index',
				'icon' => 'activity',
			],

			'taxonomies' => [
				'name' => __('Taxonomies'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'package',
				'class' => 'align-top',

				'items' => [
					'categories' => [
						'name' => __('Categories'),
						'subtitle' => __('(Hierarchical)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'package',
					],

					'tags' => [
						'name' => __('Tags'),
						'subtitle' => __('(Flat)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'tags',
					],
				],
			],

		]
	],
	*/
 	'pages' => [
 		'name'   => __('Pages'),
 		'url'    => $admin_path . '?module=content/posts&type=page',
 		'module' => 'content/posts',
 		'action' => 'index',
 		'icon'   => 'file',
 		//'icon-img' => $admin_path . 'themes/default/img/svg/ionicons/ios-document-outline.svg',
 		//'icon-img' => $admin_path . 'themes/default/img/svg/ionicons/ios-photos-outline.svg',
 		'show_on_modules' => ['posts', 'post', 'pages', 'categories'],
 		'items'           => [
 			'pages' => [
 				'name'   => __('Pages'),
 				'url'    => $admin_path . '?module=content/posts&type=page',
 				'module' => 'content/posts',
 				'action' => 'index',
 				'icon'   => 'receipt',
 			],
 			'addpage' => [
 				'name'   => __('Add new page'),
 				'url'    => $admin_path . '?module=content/post&type=page',
 				'module' => 'content/post',
 				'action' => 'save',
 				'icon'   => 'circle-plus',
 			],
 			'taxonomy-heading' => [
 				'name'    => __('Taxonomy'),
 				'heading' => true,
 			],
 			'menus' => [
 				'name'   => __('Menus'),
 				'url'    => $admin_path . '?module=content/menus&type=page',
 				'module' => 'content/menus',
 				'action' => 'index',
 				'icon'   => 'package',
 			],
 			'categories' => [
 				'name'   => __('Categories'),
 				'url'    => $admin_path . '?module=content/categories&taxonomy_id=6',
 				'module' => 'content/categories',
 				'action' => 'index',
 				'icon'   => 'package',
 			],

 			'tags' => [
 				'name'   => __('Tags'),
 				'url'    => $admin_path . '?module=content/categories&taxonomy_id=7',
 				'module' => 'content/categories',
 				'action' => 'index',
 				'icon'   => 'tags',
 			],
 			/*
			'categories' =>
			[
				'name' => __('Categories'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'package',
			],

			'tags' => [
				'name' => __('Tags'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'tags',
			],

			'categories-heading' =>
			[
				'name' => __('General'),
				'heading' => true
			],

			'custom-fields' => [
				'name' => __('Custom fields'),
				'url' => $admin_path . '?module=content/fields',
'module' => 'content/fields',
 'action' => 'index',
				'icon' => 'activity',
			],

			'taxonomies' => [
				'name' => __('Taxonomies'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'package',
				'class' => 'align-top',

				'items' => [
					'categories' => [
						'name' => __('Categories'),
						'subtitle' => __('(Hierarchical)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'package',
					],

					'tags' => [
						'name' => __('Tags'),
						'subtitle' => __('(Flat)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'tags',
					],
				],
			],
			*/
 		],
 	],
 	'medialibrary' => [
 		'name'   => __('Media library'),
 		'url'    => $admin_path . '?module=media/media',
 		'module' => 'media/media',
 		'action' => 'index',
 		'icon'   => 'photo',
 	],
 	'users' => [
 		'name'   => __('Users'),
 		'url'    => $admin_path . '?module=user/users',
 		'module' => 'user/users',
 		'action' => 'index',
 		'icon'   => 'users',
 		'items'  => [
 			'users' => [
 				'name'   => __('Users'),
 				'url'    => $admin_path . '?module=user/users',
 				'module' => 'user/users',
 				'action' => 'index',
 				'icon'   => 'user',
 			],
 			'add-user' => [
 				'name'   => __('Add new user'),
 				'url'    => $admin_path . '?module=user/user',
 				'module' => 'user/user',
 				'action' => 'save',
 				'icon'   => 'circle-plus',
 			],
 			'groups' => [
 				'name'    => __('Groups'),
 				'heading' => true,
 			],
 			'user-groups' => [
 				'name'   => __('User groups'),
 				'url'    => $admin_path . '?module=user/user-groups',
 				'module' => 'user/user-groups',
 				'action' => 'index',
 				'icon'   => 'users',
 			],
 			'add-group' => [
 				'name'   => __('Add new group'),
 				'url'    => $admin_path . '?module=user/user-group',
 				'module' => 'user/user-group',
 				'action' => 'save',
 				'icon'   => 'circle-plus',
 			],
 		],
 	],
 	'ecommerce' => [
 		'name'    => __('Ecommerce'),
 		'url'     => $admin_path . '?module=product/products',
 		'heading' => true,
 		'module'  => 'product/products',
 	],
 	'sales' => [
 		'name'   => __('Sales'),
 		'url'    => $admin_path . '?module=order/orders',
 		'module' => 'order/orders',
 		'action' => 'index',
 		'icon'   => 'shopping-bag',
 		//'badge' => '10',
 		//'badge-class' => 'badge bg-success float-end',
 		'items' => [
 			'orders' => [
 				'name'   => __('Orders'),
 				'url'    => $admin_path . '?module=order/orders',
 				'module' => 'order/orders',
 				'action' => 'index',
 				'icon'   => 'receipt-dollar',
 				//'badge' => '7',
 				//'badge-class' => 'badge bg-secondary float-end',
 			],
 			'subscriptions' => [
 				'name'   => __('Subscriptions'),
 				'url'    => $admin_path . '?module=order/subscriptions',
 				'module' => 'order/subscriptions',
 				'action' => 'index',
 				'icon'   => 'repeat',
 			],
 			'returns' => [
 				'name'   => __('Returns'),
 				'url'    => $admin_path . '?module=order/returns',
 				'module' => 'order/returns',
 				'action' => 'index',
 				'icon'   => 'arrow-back-up',
 				//'badge' => '3',
 				//'badge-class' => 'badge bg-danger float-end',
 			],
 			'warehouses' => [
 				'name'   => __('Warehouses'),
 				'url'    => $admin_path . '?module=order/warehouses',
 				'module' => 'order/warehouses',
 				'action' => 'index',
 				'icon'   => 'building-warehouse',
 			],
 			'marketing-heading' => [
 				'name'    => __('Marketing'),
 				'heading' => true,
 			],
 			'coupons' => [
 				'name'   => __('Discount coupons'),
 				'icon'   => 'discount',
 				'url'    => $admin_path . '?module=order/coupons',
 				'module' => 'order/coupons',
 				'action' => 'index',
 			],
 			'vouchers' => [
 				'name'   => __('Gift vouchers'),
 				'icon'   => 'gift',
 				'url'    => $admin_path . '?module=order/vouchers',
 				'module' => 'order/vouchers',
 				'action' => 'index',
 			],
 		],
 	],
 	/*
	'products' => [
		'name'            => __('Products'),
		'url' => $admin_path . '?module=product/products',
'module' => 'product/products',
 'action' => 'index',
		 'icon'   		  => 'box',
		'show_on_modules' => ['Product/products', 'Product/product', 'Product/categories'],
		'items' => [
			'pages' => [
				'name' => __('Products'),
				'url' => $admin_path . '?module=product/products',
'module' => 'product/products',
 'action' => 'index',
				'icon' => 'box',
			],
			'addpage' => [
				'name' => __('Add new product'),
				'url' => $admin_path . '?module=product/product',
'module' => 'product/product',
 'action' => 'index',
				'icon' => 'circle-plus',
			],
			'categories-heading' => [
				'name'    => __('Taxonomy'),
				'heading' => true,
			],
			'categories' => [
				'name' => __('Categories'),
				'url' => $admin_path . '?module=product/categories',
'module' => 'product/categories',
 'action' => 'index',
				'icon' => 'package',
			],
			'manufacturers' => [
				'name' => __('Manufacturers'),
				'url' => $admin_path . '?module=product/manufacturers',
'module' => 'product/manufacturers',
 'action' => 'index',
				'icon' => 'building-factory',
			],
			'vendors' => [
				'name' => __('Vendors'),
				'url' => $admin_path . '?module=product/vendors',
'module' => 'product/vendors',
 'action' => 'index',
				'icon' => 'building-store',
			],
			'configuration-heading' => [
				'name'    => __('Configuration'),
				'heading' => true,
			],
			'custom-fields' => [
				'name' => __('Custom fields'),
				'url' => $admin_path . '?module=product/fields',
'module' => 'product/fields',
 'action' => 'index',
				'icon' => 'activity',
			],
			'options' => [
				'name' => __('Options'),
				'url' => $admin_path . '?module=product/options',
'module' => 'product/options',
 'action' => 'index',
				'icon' => 'filter',
			],
			'digital' => [
				'name' => __('Digital content'),
				'url' => $admin_path . '?module=product/options',
'module' => 'product/options',
 'action' => 'index',
				'icon' => 'cloud-download',
			],
			'configuration-heading' => [
				'name'    => __('Configuration'),
				'heading' => true,
			],
			'reviews' => [
				'name' => __('Reviews'),
				'url' => $admin_path . '?module=product/reviews',
'module' => 'product/reviews',
 'action' => 'index',
				'icon' => 'message',
				//'badge' => '5',
				//'badge-class' => 'badge bg-warning float-end',
			],
			'questions' => [
				'name' => __('Questions'),
				'url' => $admin_path . '?module=product/questions',
'module' => 'product/questions',
 'action' => 'index',
				'icon' => 'help-circle',
				//'badge' => '7',
				//'badge-class' => 'badge bg-danger float-end',
			],
			'filters' => [
				'name' => __('Filters'),
				'url' => $admin_path . '?module=categories',
'module' => 'categories',
 'action' => 'index',
		],
	],
	*/
 	'configuration' => [
 		'name'    => __('Configuration'),
 		'url'     => $admin_path . '?module=settings/sites',
 		'module'  => 'settings/sites',
 		'heading' => true,
 	],
 	'plugins' => [
 		'name'   => __('Plugins'),
 		'url'    => $admin_path . '?module=plugin/plugins',
 		'module' => 'plugin/plugins',
 		'action' => 'index',
 		'icon'   => 'puzzle',
 		'class'  => 'align-top',
 		'items'  => [
 			'installed' => [
 				'name'   => __('Installed Plugins'),
 				'url'    => $admin_path . '?module=plugin/plugins',
 				'module' => 'plugin/plugins',
 				'action' => 'index',
 				'icon'   => 'plug',
 			],
 			'marketplace' => [
 				'name'   => __('Add new plugin'),
 				'url'    => $admin_path . '?module=plugin/market',
 				'module' => 'plugin/market',
 				'action' => 'index',
 				'icon'   => 'circle-plus',
 			],
 			'editor' => [
 				'name'   => __('Code editor'),
 				'url'    => $admin_path . '?module=editor/code&type=plugins',
 				'module' => 'editor/code',
 				'action' => 'index',
 				'icon'   => 'code',
 			],
 			'plugins-heading' => [
 				'name'    => __('Plugins'),
 				'heading' => true,
 			],
 		],
 	],
 	'themes' => [
 		'name'   => __('Themes'),
 		'url'    => $admin_path . '?module=theme/themes',
 		'module' => 'theme/themes',
 		'action' => 'index',
 		'icon'   => 'copy',
 		'items'  => [
 			'installed' => [
 				'name'   => __('Installed Themes'),
 				'url'    => $admin_path . '?module=theme/themes',
 				'module' => 'theme/themes',
 				'action' => 'index',
 				'icon'   => 'brush',
 			],
 			'marketplace' => [
 				'name'   => __('Add new'),
 				'url'    => $admin_path . '?module=theme/market',
 				'module' => 'theme/market',
 				'action' => 'index',
 				'icon'   => 'circle-plus',
 			],
 			'fonts-heading' => [
 				'name'    => __('Customize'),
 				'heading' => true,
 				'url'     => 'javascript:void(0)',
 				'module'  => 'theme/themes',
 				'action'  => 'fonts',
 			],
 			'fonts' => [
 				'name'   => __('Fonts'),
 				'url'    => $admin_path . '?module=theme/fonts',
 				'module' => 'theme/themes',
 				'action' => 'fonts',
 				'icon'   => 'text-size',
 			],
 			'editor' => [
 				'name'   => __('Code editor'),
 				'url'    => $admin_path . '?module=editor/code&type=themes',
 				'module' => 'editor/code',
 				'action' => 'index',
 				'icon'   => 'code',
 			],
 		],
 	],
 	'field' => [
 		'name'   => __('Fields'),
 		'url'    => $admin_path . '?module=field/field-groups',
 		'module' => 'field/fields',
 		'action' => 'index',
 		'icon'   => 'notebook',
 		'items'  => [
 			'groups' => [
 				'name'   => __('Field groups'),
 				'url'    => $admin_path . '?module=field/field-groups',
 				'module' => 'field/field-groups',
 				'action' => 'index',
 				'icon'   => 'activity',
 			],
 			'new' => [
 				'name'   => __('Add new'),
 				'url'    => $admin_path . '?module=field/field-group',
 				'module' => 'field/field-group',
 				'action' => 'index',
 				'icon'   => 'circle-plus',
 			],
 		],
 	],
 	'settings' => [
 		'name'   => __('Settings'),
 		'url'    => $admin_path . '?module=settings/sites',
 		'module' => 'settings/sites',
 		'action' => 'index',
 		'icon'   => 'settings',
 		'class'  => 'align-top mega-menu',
 		'items'  => [
 			/*
			'general' => [
				'name'   => __('General Settings'),
				'url'    => $admin_path . '?module=settings/settings',
				'module' => 'settings/settings',
				'action' => 'index',
				'icon'   => 'settings',
			],
			*/
 			'sites' => [
 				'name'   => __('Sites'),
 				'url'    => $admin_path . '?module=settings/sites',
 				'module' => 'settings/sites',
 				'action' => 'index',
 				'icon'   => 'square',
 				'items'  => [
 					'sites' => [
 						'name'   => __('Sites'),
 						'url'    => $admin_path . '?module=settings/sites',
 						'module' => 'settings/sites',
 						'action' => 'index',
 						'icon'   => 'square',
 					],
 					'add-site' => [
 						'name'   => __('Add new'),
 						'url'    => $admin_path . '?module=settings/site',
 						'module' => 'settings/site',
 						'action' => 'add',
 						'icon'   => 'circle-plus',
 					],
 				],
 			],
 			'admins' => [
 				'name'   => __('Admin users'),
 				'url'    => $admin_path . '?module=admin/users',
 				'module' => 'admin/users',
 				'action' => 'index',
 				'icon'   => 'user',
 				'items'  => [
 					'users' => [
 						'name'   => __('Users'),
 						'url'    => $admin_path . '?module=admin/users',
 						'module' => 'admin/users',
 						'action' => 'index',
 						'icon'   => 'user',
 					],
 					'add' => [
 						'name'   => __('Add new user'),
 						'url'    => $admin_path . '?module=admin/user',
 						'module' => 'admin/user',
 						'action' => 'index',
 						'icon'   => 'user-plus',
 					],
 					'roles-heading' => [
 						'name'    => __('Roles'),
 						'heading' => true,
 					],
 					'role' => [
 						'name'   => __('Manage Roles'),
 						'url'    => $admin_path . '?module=admin/roles',
 						'module' => 'admin/roles',
 						'action' => 'index',
 						'icon'   => 'user-cog',
 					],
 					'add-role' => [
 						'name'   => __('Add Role'),
 						'url'    => $admin_path . '?module=admin/role',
 						'module' => 'admin/role',
 						'action' => 'index',
 						'icon'   => 'id',
 					],
 				],
 			],
 			'content' => [
 				'name'   => __('Content'),
 				'url'    => $admin_path . '?module=content/menus',
 				'module' => 'content/menus',
 				'action' => 'index',
 				'icon'   => 'file-text',
 				'items'  => [
 					/*
					'menus' => [
						'name'   => __('Menus'),
						'url'    => $admin_path . '?module=content/menus',
						'module' => 'content/menus',
						'action' => 'index',
						'icon'   => 'menu-2',
					],
					*/
 					'taxonomies' => [
 						'name'   => __('Taxonomies'),
 						'url'    => $admin_path . '?module=settings/taxonomies',
 						'module' => 'settings/taxonomies',
 						'action' => 'index',
 						'icon'   => 'package',
 					],
 					'custom-posts' => [
 						'name'   => __('Post types'),
 						'url'    => $admin_path . '?module=settings/post-types',
 						'module' => 'settings/post-types',
 						'action' => 'index',
 						'icon'   => 'file-text',
 					],
 					'custom-products' => [
 						'name'   => __('Product types'),
 						'url'    => $admin_path . '?module=settings/product-types',
 						'module' => 'settings/product-types',
 						'action' => 'index',
 						'icon'   => 'box',
 					],
 				],
 			],
 			'ecommerce' => [
 				'name'   => __('Ecommerce'),
 				'url'    => $admin_path . '?module=user/users',
 				'module' => 'user/users',
 				'action' => 'index',
 				'icon'   => 'shopping-cart',
 				'class'  => 'align-top',
 				'items'  => [
 					/*
					'checkout' => [
						'name'   => __('Checkout & payments'),
						'icon'   => 'credit-card',
						'url'    => $admin_path . '?module=settings/checkout',
						'module' => 'settings/checkout',
						'action' => 'index',
					],
					'email' => [
						'name'   => __('Notifications'),
						'icon'   => 'mail',
						'url'    => $admin_path . '?module=settings/notifications',
						'module' => 'settings/notifications',
						'action' => 'index',
					],*/
 					'subscriptions' => [
 						'name'   => __('Subscriptions plans'),
 						'icon'   => 'receipt',
 						'url'    => $admin_path . '?module=settings/subscription-plans',
 						'module' => 'settings/subscriptions-plans',
 						'action' => 'index',
 					],

 					/*
					'shipping' => [
						'name'  => __('Shipping'),
						'icon'  => 'truck-delivery',
						'url' => $admin_path . '?module=settings/shipping',
'module' => 'settings/shipping',
 'action' => 'index',
						'items' => [
							'tax-types' => [
								'name' => __('Shipping Regions'),
								'url' => $admin_path . '?module=admin/users',
'module' => 'admin/users',
 'action' => 'index',
								'icon' => 'user',
							],
							'tax-rates' => [
								'name' => __('Tax rates'),
								'url' => $admin_path . '?module=admin/user',
'module' => 'admin/user',
 'action' => 'index',
								'icon' => 'user-plus',
							],
							'role' => [
								'name' => __('Manage Roles'),
								'url' => $admin_path . '?module=admin/roles',
'module' => 'admin/roles',
 'action' => 'index',
								'icon' => 'user-cog',
							],
							'add-role' => [
								'name' => __('Add Roles'),
								'url' => $admin_path . '?module=admin/roles',
'module' => 'admin/roles',
 'action' => 'index',
								'icon' => 'users',
							],
						],
					],*/
 					'taxes' => [
 						'name'   => __('Taxes'),
 						'icon'   => 'receipt-dollar',
 						'url'    => $admin_path . '?module=settings/tax-types',
 						'module' => 'settings/tax-types',
 						'action' => 'index',
 						'items'  => [
 							'tax-types' => [
 								'name'   => __('Tax types'),
 								'url'    => $admin_path . '?module=settings/tax-types',
 								'module' => 'settings/tax-types',
 								'action' => 'index',
 								'icon'   => 'receipt',
 							],
 							'tax-rates' => [
 								'name'   => __('Tax rates'),
 								'url'    => $admin_path . '?module=settings/tax-rates',
 								'module' => 'settings/tax-rates',
 								'action' => 'index',
 								'icon'   => 'receipt-dollar',
 							],
 						],
 					],
 					'statuses' => [
 						'name'   => __('Statuses'),
 						'icon'   => 'tags',
 						'url'    => $admin_path . '?module=settings/order-statuses',
 						'module' => 'settings/order-statuses',
 						'action' => 'index',
 						'items'  => [
 							'stock-status' => [
 								'name'   => __('Stock'),
 								'url'    => $admin_path . '?module=settings/stock-statuses',
 								'module' => 'settings/stock-statuses',
 								'action' => 'index',
 								'icon'   => 'box',
 							],
 							'subscription-status' => [
 								'name'   => __('Subscription'),
 								'url'    => $admin_path . '?module=settings/subscription-statuses',
 								'module' => 'settings/subscription-statuses',
 								'action' => 'index',
 								'icon'   => 'repeat',
 							],
 							'order-heading' => [
 								'name'    => __('Order'),
 								'heading' => true,
 							],
 							'order-status' => [
 								'name'   => __('Order'),
 								'url'    => $admin_path . '?module=settings/order-statuses',
 								'module' => 'settings/order-statuses',
 								'action' => 'index',
 								'icon'   => 'receipt',
 							],
 							'payment-status' => [
 								'name'   => __('Payment'),
 								'url'    => $admin_path . '?module=settings/payment-statuses',
 								'module' => 'settings/payment-statuses',
 								'action' => 'index',
 								'icon'   => 'coin',
 							],
 							'shipping-status' => [
 								'name'   => __('Shipping'),
 								'url'    => $admin_path . '?module=settings/shipping-statuses',
 								'module' => 'settings/shipping-statuses',
 								'action' => 'index',
 								'icon'   => 'truck-delivery',
 							],
 							'status-heading' => [
 								'name'    => __('Returns'),
 								'heading' => true,
 							],
 							'return-status' => [
 								'name'   => __('Return status'),
 								'url'    => $admin_path . '?module=settings/return-statuses',
 								'module' => 'settings/return-statuses',
 								'action' => 'index',
 								'icon'   => 'arrow-back-up',
 							],
 							'return-resolutions' => [
 								'name'   => __('Return resolutions'),
 								'url'    => $admin_path . '?module=settings/return-resolutions',
 								'module' => 'settings/return-resolutions',
 								'action' => 'index',
 								'icon'   => 'arrows-exchange',
 							],
 							'return-reasons' => [
 								'name'   => __('Return reasons'),
 								'url'    => $admin_path . '?module=settings/return-reasons',
 								'module' => 'settings/return-reasons',
 								'action' => 'index',
 								'icon'   => 'arrow-back-up',
 							],
 						],
 					],
 					'classes' => [
 						'name'   => __('Measurements'),
 						'icon'   => 'ruler',
 						'url'    => $admin_path . '?module=settings/weight-types',
 						'module' => 'settings/weight-types',
 						'action' => 'index',
 						'items'  => [
 							'order-length' => [
 								'name'   => __('Length'),
 								'url'    => $admin_path . '?module=settings/length-types',
 								'module' => 'settings/length-types',
 								'action' => 'index',
 								'icon'   => 'ruler',
 							],
 							'stock-weight' => [
 								'name'   => __('Weight'),
 								'url'    => $admin_path . '?module=settings/weight-types',
 								'module' => 'settings/weight-types',
 								'action' => 'index',
 								'icon'   => 'box',
 							],
 						],
 					],
 				],
 			],
 			'localization' => [
 				'name'   => __('Localization'),
 				'url'    => $admin_path . '?module=localization/languages',
 				'module' => 'localization/languages',
 				'action' => 'index',
 				'icon'   => 'flag',
 				'items'  => [
 					'languages' => [
 						'name'   => __('Languages'),
 						'icon'   => 'language',
 						'url'    => $admin_path . '?module=localization/languages',
 						'module' => 'localization/languages',
 						'action' => 'index',
 					],
 					'currencies' => [
 						'name'   => __('Currencies'),
 						'icon'   => 'coin',
 						'url'    => $admin_path . '?module=localization/currencies',
 						'module' => 'localization/currencies',
 						'action' => 'index',
 					],
 					'geo-location' => [
 						'name'   => __('Geo location'),
 						'icon'   => 'globe',
 						'url'    => $admin_path . '?module=localization/region-groups',
 						'module' => 'localization/region-groups',
 						'action' => 'index',
 						'class'  => 'align-top',
 						'items'  => [
 							'countries' => [
 								'name'   => __('Countries'),
 								'icon'   => 'flag',
 								'url'    => $admin_path . '?module=localization/countries',
 								'module' => 'localization/countries',
 								'action' => 'index',
 							],
 							'regions' => [
 								'name'   => __('Regions'),
 								'icon'   => 'building',
 								'url'    => $admin_path . '?module=localization/regions',
 								'module' => 'localization/regions',
 								'action' => 'index',
 							],
 							'geoRegions' => [
 								'name'   => __('Region Groups'),
 								'icon'   => 'map',
 								'url'    => $admin_path . '?module=localization/region-groups',
 								'module' => 'localization/region-groups',
 								'action' => 'index',
 							],
 						],
 					],
 				],
 			],
 			'system' => [
 				'name'   => __('System'),
 				'url'    => $admin_path . '?module=settings/email',
 				'module' => 'settings/email',
 				'action' => 'index',
 				'icon'   => 'tools',
 				'items'  => [
 					'notifications' => [
 						'name'   => __('Notifications'),
 						'icon'   => 'bell',
 						'url'    => $admin_path . '?module=settings/notifications',
 						'module' => 'settings/notifications',
 						'action' => 'index',
 					],
 					'email' => [
 						'name'   => __('Email settings'),
 						'icon'   => 'mail',
 						'url'    => $admin_path . '?module=settings/email',
 						'module' => 'settings/email',
 						'action' => 'index',
 					],
 				],
 			],
 		],
 	],
 	'tools' => [
 		'name'   => __('Tools'),
 		'url'    => $admin_path . '?module=tools/cache',
 		'module' => 'tools/cache',
 		'action' => 'index',
 		'icon'   => 'hammer',
 		'class'  => 'align-top',
 		'items'  => [
 			'cache' => [
 				'name'   => __('Cache'),
 				'url'    => $admin_path . '?module=tools/cache',
 				'module' => 'tools/cache',
 				'action' => 'index',
 				'icon'   => 'loader-2',
 			],
 			'backup' => [
 				'name'   => __('Backup'),
 				'url'    => $admin_path . '?module=tools/backup',
 				'module' => 'tools/backup',
 				'action' => 'index',
 				'icon'   => 'server',
 				'items'  => [
 					'backup' => [
 						'name'   => __('Database Backup'),
 						'url'    => $admin_path . '?module=tools/backup',
 						'module' => 'tools/backup',
 						'action' => 'index',
 						'icon'   => 'server',
 					],
 					'media-backup' => [
 						'name'   => __('Media Backup'),
 						'url'    => $admin_path . '?module=tools/media-backup',
 						'module' => 'tools/media-backup',
 						'action' => 'index',
 						'icon'   => 'photo',
 					],
 					'plugins-backup' => [
 						'name'   => __('Plugins Backup'),
 						'url'    => $admin_path . '?module=tools/plugins-backup',
 						'module' => 'tools/plugins-backup',
 						'action' => 'index',
 						'icon'   => 'plug',
 					],
 					'themes-backup' => [
 						'name'   => __('Themes Backup'),
 						'url'    => $admin_path . '?module=tools/themes-backup',
 						'module' => 'tools/themes-backup',
 						'action' => 'index',
 						'icon'   => 'brush',
 					]
 				]
 			],
 			'cron' => [
 				'name'   => __('Cron job'),
 				'url'    => $admin_path . '?module=tools/cron',
 				'module' => 'tools/cron',
 				'action' => 'index',
 				'icon'   => 'history',
 			],
 			'queue' => [
 				'name'   => __('Queue'),
 				'url'    => $admin_path . '?module=tools/queue',
 				'module' => 'tools/queue',
 				'action' => 'index',
 				'icon'   => 'checklist',
 			],
 			'import-export' => [
 				'name'   => __('Import/Export'),
 				'url'    => $admin_path . '?module=tools/import',
 				'module' => 'tools/import',
 				'action' => 'index',
 				'icon'   => 'upload',
 				'items'  => [
 					'content-heading' => [
 						'name'    => __('Content'),
 						'heading' => true,
 					],
 					'import' => [
 						'name'   => __('Import content'),
 						'icon'   => 'file-import',
 						'url'    => $admin_path . '?module=tools/import',
 						'module' => 'tools/import',
 						'action' => 'index',
 					],
 					'export' => [
 						'name'   => __('Export content'),
 						'icon'   => 'file-export',
 						'url'    => $admin_path . '?module=tools/export',
 						'module' => 'tools/export',
 						'action' => 'index',
 					],
 					/*
					'media-heading' => [
						'name'    => __('Media'),
						'heading' => true,
					],
					'import-media' => [
						'name'   => __('Import media'),
						'icon'   => 'square-chevron-left',
						'url'    => $admin_path . '?module=tools/import',
						'module' => 'tools/import',
						'action' => 'index',
					],
					'export-media' => [
						'name'   => __('Export media'),
						'icon'   => 'square-chevron-right',
						'url'    => $admin_path . '?module=tools/export',
						'module' => 'tools/export',
						'action' => 'index',
					],
					 */
 				],
 			],
 			/*
			'security' => [
				'name'   => __('Security'),
				'url'    => $admin_path . '?module=tools/security',
				'module' => 'tools/security',
				'action' => 'index',
				'icon'   => 'shield',
			],
			*/
 			'system' => [
 				'name'   => __('System info'),
 				'url'    => $admin_path . '?module=tools/systeminfo',
 				'module' => 'tools/systeminfo',
 				'action' => 'index',
 				'icon'   => 'info-circle',
 				'items'  => [
 					'info' => [
 						'name'   => __('System Info'),
 						'icon'   => 'info-circle',
 						'url'    => $admin_path . '?module=tools/systeminfo',
 						'module' => 'tools/systeminfo',
 						'action' => 'index',
 					],
 					'error-log' => [
 						'name'   => __('Error log'),
 						'url'    => $admin_path . '?module=tools/errorlog',
 						'module' => 'tools/errorlog',
 						'action' => 'index',
 						'icon'   => 'bug',
 					],
 				],
 			],
 			'update' => [
 				'name'   => __('Update'),
 				'url'    => $admin_path . '?module=tools/update',
 				'module' => 'tools/update',
 				'action' => 'index',
 				'icon'   => 'refresh',
 			],
 		],
 	]
 ];
