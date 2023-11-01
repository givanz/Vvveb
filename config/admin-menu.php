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
$homeUrl = Vvveb\Url('index/index');

return
 [
 	'dashboard' => [
 		'name'   => __('Dashboard'),
 		'url'    => $admin_path,
 		'module' => 'index',
 		'action' => 'index',
 		'icon'   => 'icon-pulse-outline',
 	],
 	'edit' => [
 		'name'   => __('Edit website'),
 		'url'    => $admin_path . "?module=editor/editor&url=$homeUrl&template=index.html",
 		'module' => 'editor/editor',
 		'action' => 'index',
 		'icon'   => 'icon-color-wand-outline',
 	],
 	/*
	'posts' => 
	[
		'name' => __('Posts'),
		'url' => $admin_path . '?module=content/posts',
'module' => 'content/posts',
 'action' => 'index',
		'icon' => 'ion-ios-photos-outline',
		'show_on_modules' => ['posts', 'post', 'pages', 'categories'],
		'items' => [
			'posts' => [
				'name' => __('Posts'),
				'url' => $admin_path . '?module=content/posts',
'module' => 'content/posts',
 'action' => 'index',
				'icon' => 'la la-file-alt',
			],
			'addpost' => [
				'name' => __('Add new post'),
				'url' => $admin_path . '?module=content/post',
'module' => 'content/post',
 'action' => 'index',
				'icon' => 'la la-plus-circle',
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
				'icon' => 'la la-boxes',
			],
			
			'tags' => [
				'name' => __('Tags'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'la la-tags',
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
				'icon' => 'la la-comments',
			],
			
			'custom-fields' => [
				'name' => __('Custom fields'),
				'url' => $admin_path . '?module=content/fields',
'module' => 'content/fields',
 'action' => 'index',
				'icon' => 'la la-stream',
			],		
		
			'taxonomies' => [
				'name' => __('Taxonomies'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'la la-boxes',
				'class' => 'align-top',
				
				'items' => [
					'categories' => [
						'name' => __('Categories'),
						'subtitle' => __('(Hierarchical)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'la la-boxes',
					],
					
					'tags' => [
						'name' => __('Tags'),
						'subtitle' => __('(Flat)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'la la-tags',
					],
				],
			],		
			
		]
	], 
	*/
 	'pages' => [
 		'name'     => __('Pages'),
 		'url'      => $admin_path . '?module=content/posts&type=page',
 		'module'   => 'content/posts',
 		'action'   => 'index',
 		'icon' 		  => 'icon-document-outline',
 		//'icon-img' => $admin_path . 'themes/default/img/svg/ionicons/ios-document-outline.svg',
 		//'icon-img' => $admin_path . 'themes/default/img/svg/ionicons/ios-photos-outline.svg',
 		'show_on_modules' => ['posts', 'post', 'pages', 'categories'],
 		'items'           => [
 			'pages' => [
 				'name'   => __('Pages'),
 				'url'    => $admin_path . '?module=content/posts&type=page',
 				'module' => 'content/posts',
 				'action' => 'index',
 				'icon'   => 'la la-file-invoice',
 			],
 			'addpage' => [
 				'name'   => __('Add new page'),
 				'url'    => $admin_path . '?module=content/post&type=page',
 				'module' => 'content/post',
 				'action' => 'save',
 				'icon'   => 'la la-plus-circle',
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
 				'icon'   => 'la la-boxes',
 			],
 			/*
			'categories' => 
			[
				'name' => __('Categories'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'la la-boxes',
			],
			
			'tags' => [
				'name' => __('Tags'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'la la-tags',
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
				'icon' => 'la la-stream',
			],		
			
			'taxonomies' => [
				'name' => __('Taxonomies'),
				'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
				'icon' => 'la la-boxes',
				'class' => 'align-top',
				
				'items' => [
					'categories' => [
						'name' => __('Categories'),
						'subtitle' => __('(Hierarchical)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'la la-boxes',
					],
					
					'tags' => [
						'name' => __('Tags'),
						'subtitle' => __('(Flat)'),
						'url' => $admin_path . '?module=content/categories',
'module' => 'content/categories',
 'action' => 'index',
						'icon' => 'la la-tags',
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
 		'icon'   => 'icon-images-outline',
 	],
 	'users' => [
 		'name'   => __('Users'),
 		'url'    => $admin_path . '?module=user/users',
 		'module' => 'user/users',
 		'action' => 'index',
 		'icon'   => 'icon-people-outline',
 		'items'  => [
 			'users' => [
 				'name'   => __('Users'),
 				'url'    => $admin_path . '?module=user/users',
 				'module' => 'user/users',
 				'action' => 'index',
 				'icon'   => 'la la-user',
 			],
 			'add-user' => [
 				'name'   => __('Add new user'),
 				'url'    => $admin_path . '?module=user/user',
 				'module' => 'user/user',
 				'action' => 'save',
 				'icon'   => 'la la-plus-circle',
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
 				'icon'   => 'la la-user-friends',
 			],
 			'add-group' => [
 				'name'   => __('Add new group'),
 				'url'    => $admin_path . '?module=user/user-group',
 				'module' => 'user/user-group',
 				'action' => 'save',
 				'icon'   => 'la la-plus-circle',
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
 		'icon'   => 'icon-bag-handle-outline',
 		//'badge' => '10',
 		//'badge-class' => 'badge bg-success float-end',
 		'items' => [
 			'orders' => [
 				'name'   => __('Orders'),
 				'url'    => $admin_path . '?module=order/orders',
 				'module' => 'order/orders',
 				'action' => 'index',
 				'icon'   => 'la la-file-invoice-dollar',
 				//'badge' => '7',
 				//'badge-class' => 'badge bg-secondary float-end',
 			],
			'subscriptions' => [
				'name'   => __('Subscriptions'),
				'url'    => $admin_path . '?module=order/subscriptions',
				'module' => 'order/subscriptions',
				'action' => 'index',
				'icon'   => 'la la-retweet',
			],
			'returns' => [
				'name'   => __('Returns'),
				'url'    => $admin_path . '?module=order/returns',
				'module' => 'order/returns',
				'action' => 'index',
				'icon'   => 'la la-undo',
				//'badge' => '3',
				//'badge-class' => 'badge bg-danger float-end',
			],
			'marketing-heading' => [
				'name'    => __('Marketing'),
				'heading' => true,
			],
			'coupons' => [
				'name'   => __('Discount coupons'),
				'icon'   => 'la la-percentage',
				'url'    => $admin_path . '?module=order/coupons',
				'module' => 'order/coupons',
				'action' => 'index',
			],
			'vouchers' => [
				'name'   => __('Gift vouchers'),
 				'icon'   => 'la la-gift',
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
		 'icon'   		  => 'icon-cube-outline',
		'show_on_modules' => ['Product/products', 'Product/product', 'Product/categories'],
		'items' => [
			'pages' => [
				'name' => __('Products'),
				'url' => $admin_path . '?module=product/products',
'module' => 'product/products',
 'action' => 'index',
				'icon' => 'la la-box',
			],
			'addpage' => [
				'name' => __('Add new product'),
				'url' => $admin_path . '?module=product/product',
'module' => 'product/product',
 'action' => 'index',
				'icon' => 'la la-plus-circle',
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
				'icon' => 'la la-boxes',
			],
			'manufacturers' => [
				'name' => __('Manufacturers'),
				'url' => $admin_path . '?module=product/manufacturers',
'module' => 'product/manufacturers',
 'action' => 'index',
				'icon' => 'la la-industry',
			],
			'vendors' => [
				'name' => __('Vendors'),
				'url' => $admin_path . '?module=product/vendors',
'module' => 'product/vendors',
 'action' => 'index',
				'icon' => 'la la-store',
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
				'icon' => 'la la-stream',
			],
			'options' => [
				'name' => __('Options'),
				'url' => $admin_path . '?module=product/options',
'module' => 'product/options',
 'action' => 'index',
				'icon' => 'la la-filter',
			],
			'digital' => [
				'name' => __('Digital content'),
				'url' => $admin_path . '?module=product/options',
'module' => 'product/options',
 'action' => 'index',
				'icon' => 'la la-cloud-download-alt',
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
				'icon' => 'la la-comment',
				//'badge' => '5',
				//'badge-class' => 'badge bg-warning float-end',
			],
			'questions' => [
				'name' => __('Questions'),
				'url' => $admin_path . '?module=product/questions',
'module' => 'product/questions',
 'action' => 'index',
				'icon' => 'la la-question-circle',
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
 		'icon'   => 'icon-extension-puzzle-outline',
 		'class'  => 'align-top',
 		'items'  => [
 			'installed' => [
 				'name'   => __('Installed Plugins'),
 				'url'    => $admin_path . '?module=plugin/plugins',
 				'module' => 'plugin/plugins',
 				'action' => 'index',
 				'icon'   => 'la la-plug',
 			],
 			'marketplace' => [
 				'name'   => __('Add new plugin'),
 				'url'    => $admin_path . '?module=plugin/market',
 				'module' => 'plugin/market',
 				'action' => 'index',
 				'icon'   => 'la la-plus-circle',
 			],
 			'editor' => [
 				'name'   => __('Code editor'),
 				'url'    => $admin_path . '?module=editor/code&type=plugins',
 				'module' => 'editor/code',
 				'action' => 'index',
 				'icon'   => 'la la-code',
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
 		'icon'   => 'icon-copy-outline',
 		'items'  => [
 			'installed' => [
 				'name'   => __('Installed Themes'),
 				'url'    => $admin_path . '?module=theme/themes',
 				'module' => 'theme/themes',
 				'action' => 'index',
 				'icon'   => 'la la-brush',
 			],
 			'marketplace' => [
 				'name'   => __('Add new'),
 				'url'    => $admin_path . '?module=theme/market',
 				'module' => 'theme/market',
 				'action' => 'index',
 				'icon'   => 'la la-plus-circle',
 			],
 			'editor' => [
 				'name'   => __('Code editor'),
 				'url'    => $admin_path . '?module=editor/code&type=themes',
 				'module' => 'editor/code',
 				'action' => 'index',
 				'icon'   => 'la la-code',
 			],
 		],
 	],
/*
 	'field' => [
 		'name'   => __('Fields'),
 		'url'    => $admin_path . '?module=field/field-groups',
 		'module' => 'field/fields',
 		'action' => 'index',
 		'icon'   => 'icon-reader-outline',
 		'items'  => [
 			'groups' => [
 				'name'   => __('Field groups'),
 				'url'    => $admin_path . '?module=field/field-groups',
 				'module' => 'field/field-groups',
 				'action' => 'index',
 				'icon'   => 'la la-brush',
 			],
 			'new' => [
 				'name'   => __('Add new'),
 				'url'    => $admin_path . '?module=field/field-group',
 				'module' => 'field/field-group',
 				'action' => 'index',
 				'icon'   => 'la la-plus-circle',
 			],
 		],
 	],
*/
 	'settings' => [
 		'name'   => __('Settings'),
 		'url'    => $admin_path . '?module=settings/sites',
 		'module' => 'settings/sites',
 		'action' => 'index',
 		'icon'   => 'icon-settings-outline',
 		'class'  => 'align-top mega-menu',
 		'items'  => [
 			/*
			'general' => [
				'name'   => __('General Settings'),
				'url'    => $admin_path . '?module=settings/settings',
				'module' => 'settings/settings',
				'action' => 'index',
				'icon'   => 'la la-cog',
			],
			*/
 			'sites' => [
 				'name'   => __('Sites'),
 				'url'    => $admin_path . '?module=settings/sites',
 				'module' => 'settings/sites',
 				'action' => 'index',
 				'icon'   => 'la la-stop la-90',
 				'items'  => [
 					'sites' => [
 						'name'   => __('Sites'),
 						'url'    => $admin_path . '?module=settings/sites',
 						'module' => 'settings/sites',
 						'action' => 'index',
 						'icon'   => 'la la-stop',
 					],
 					'add-site' => [
 						'name'   => __('Add new'),
 						'url'    => $admin_path . '?module=settings/site',
 						'module' => 'settings/site',
 						'action' => 'index',
 						'icon'   => 'la la-plus-circle',
 					],
 				],
 			],
 			'admins' => [
 				'name'   => __('Admin users'),
 				'url'    => $admin_path . '?module=admin/users',
 				'module' => 'admin/users',
 				'action' => 'index',
 				'icon'   => 'la la-user',
 				'items'  => [
 					'users' => [
 						'name'   => __('Users'),
 						'url'    => $admin_path . '?module=admin/users',
 						'module' => 'admin/users',
 						'action' => 'index',
 						'icon'   => 'la la-user',
 					],
 					'add' => [
 						'name'   => __('Add new user'),
 						'url'    => $admin_path . '?module=admin/user',
 						'module' => 'admin/user',
 						'action' => 'index',
 						'icon'   => 'la la-user-plus',
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
 						'icon'   => 'la la-user-cog',
 					],
 					'add-role' => [
 						'name'   => __('Add Role'),
 						'url'    => $admin_path . '?module=admin/role',
 						'module' => 'admin/role',
 						'action' => 'index',
 						'icon'   => 'la la-user-tag',
 					],
 				],
 			],
 			'content' => [
 				'name'   => __('Content'),
 				'url'    => $admin_path . '?module=content/menus',
 				'module' => 'settings/taxonomies',
 				'action' => 'index',
 				'icon'   => 'la la-file-alt',
 				'items'  => [
 					/*
					'menus' => [
						'name'   => __('Menus'),
						'url'    => $admin_path . '?module=content/menus',
						'module' => 'content/menus',
						'action' => 'index',
						'icon'   => 'la la-bars',
					],
					*/
 					'taxonomies' => [
 						'name'   => __('Taxonomies'),
 						'url'    => $admin_path . '?module=settings/taxonomies',
 						'module' => 'content/taxonomies',
 						'action' => 'index',
 						'icon'   => 'la la-boxes',
 					],
 					'custom-posts' => [
 						'name'   => __('Custom posts'),
 						'url'    => $admin_path . '?module=settings/posts',
 						'module' => 'content/taxonomies',
 						'action' => 'index',
 						'icon'   => 'la la-file-alt',
 					],
 					'custom-products' => [
 						'name'   => __('Custom products'),
 						'url'    => $admin_path . '?module=settings/products',
 						'module' => 'content/taxonomies',
 						'action' => 'index',
 						'icon'   => 'la la-box',
 					],
 					'fields' => [
 						'name'   => __('Fields'),
 						'url'    => $admin_path . '?module=field/field-group',
 						'module' => 'field/field-group',
 						'action' => 'index',
 						'icon'   => 'la la-stream',
 					],
 				],
 			],
 			'ecommerce' => [
 				'name'   => __('Ecommerce'),
 				'url'    => $admin_path . '?module=user/users',
 				'module' => 'user/users',
 				'action' => 'index',
 				'icon'   => 'la la-shopping-cart',
 				'class'  => 'align-top',
 				'items'  => [
 					/*
 					'checkout' => [
 						'name'   => __('Checkout & payments'),
 						'icon'   => 'la la-credit-card',
 						'url'    => $admin_path . '?module=settings/checkout',
 						'module' => 'settings/checkout',
 						'action' => 'index',
 					],
 					'email' => [
 						'name'   => __('Email notifications'),
 						'icon'   => 'la la-envelope',
 						'url'    => $admin_path . '?module=settings/notifications',
 						'module' => 'settings/notifications',
 						'action' => 'index',
					],*/
 					'subscriptions' => [
 						'name'   => __('Subscriptions plans'),
 						'icon'   => 'la la-receipt',
 						'url'    => $admin_path . '?module=settings/subscription-plans',
 						'module' => 'settings/subscriptions-plans',
 						'action' => 'index',
 					],

 					/*
					'shipping' => [
						'name'  => __('Shipping'),
						'icon'  => 'la la-shipping-fast',
						'url' => $admin_path . '?module=settings/shipping',
'module' => 'settings/shipping',
 'action' => 'index',
						'items' => [
							'tax-types' => [
								'name' => __('Shipping Regions'),
								'url' => $admin_path . '?module=admin/users',
'module' => 'admin/users',
 'action' => 'index',
								'icon' => 'la la-user',
							],
							'tax-rates' => [
								'name' => __('Tax rates'),
								'url' => $admin_path . '?module=admin/user',
'module' => 'admin/user',
 'action' => 'index',
								'icon' => 'la la-user-plus',
							],
							'role' => [
								'name' => __('Manage Roles'),
								'url' => $admin_path . '?module=admin/roles',
'module' => 'admin/roles',
 'action' => 'index',
								'icon' => 'la la-user-cog',
							],
							'add-role' => [
								'name' => __('Add Roles'),
								'url' => $admin_path . '?module=admin/roles',
'module' => 'admin/roles',
 'action' => 'index',
								'icon' => 'la la-users-cog',
							],
						],
					],*/
 					'taxes' => [
 						'name'   => __('Taxes'),
 						'icon'   => 'la la-file-invoice-dollar',
 						'url'    => $admin_path . '?module=settings/tax-types',
 						'module' => 'settings/tax-types',
 						'action' => 'index',
 						'items'  => [
 							'tax-types' => [
 								'name'   => __('Tax types'),
 								'url'    => $admin_path . '?module=settings/tax-types',
 								'module' => 'settings/tax-types',
 								'action' => 'index',
 								'icon'   => 'la la-file-invoice',
 							],
 							'tax-rates' => [
 								'name'   => __('Tax rates'),
 								'url'    => $admin_path . '?module=settings/tax-rates',
 								'module' => 'settings/tax-rates',
 								'action' => 'index',
 								'icon'   => 'la la-file-invoice-dollar',
 							],
 						],
 					],
 					'statuses' => [
 						'name'   => __('Statuses'),
 						'icon'   => 'la la-tags',
 						'url'    => $admin_path . '?module=settings/order-statuses',
 						'module' => 'settings/order-statuses',
 						'action' => 'index',
 						'items'  => [
 							'order-status' => [
 								'name'   => __('Order'),
 								'url'    => $admin_path . '?module=settings/order-statuses',
 								'module' => 'settings/order-statuses',
 								'action' => 'index',
 								'icon'   => 'la la-file-invoice',
 							],
 							'stock-status' => [
 								'name'   => __('Stock'),
 								'url'    => $admin_path . '?module=settings/stock-statuses',
 								'module' => 'settings/stock-statuses',
 								'action' => 'index',
 								'icon'   => 'la la-box',
 							],
 							'subscription-status' => [
 								'name'   => __('Subscription'),
 								'url'    => $admin_path . '?module=settings/subscription-statuses',
 								'module' => 'settings/subscription-statuses',
 								'action' => 'index',
 								'icon'   => 'la la-retweet',
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
 								'icon'   => 'la la-undo',
 							],
 							'return-resolutions' => [
 								'name'   => __('Return resolutions'),
 								'url'    => $admin_path . '?module=settings/return-resolutions',
 								'module' => 'settings/return-resolutions',
 								'action' => 'index',
 								'icon'   => 'la la-exchange-alt',
 							],
 							'return-reasons' => [
 								'name'   => __('Return reasons'),
 								'url'    => $admin_path . '?module=settings/return-reasons',
 								'module' => 'settings/return-reasons',
 								'action' => 'index',
 								'icon'   => 'la la-undo-alt',
 							],
 						],
 					],
 					'classes' => [
 						'name'   => __('Measurements'),
 						'icon'   => 'la la-ruler',
 						'url'    => $admin_path . '?module=settings/weight-types',
 						'module' => 'settings/weight-types',
 						'action' => 'index',
 						'items'  => [
 							'order-length' => [
 								'name'   => __('Length'),
 								'url'    => $admin_path . '?module=settings/length-types',
 								'module' => 'settings/length-types',
 								'action' => 'index',
 								'icon'   => 'la la-ruler-horizontal',
 							],
 							'stock-weight' => [
 								'name'   => __('Weight'),
 								'url'    => $admin_path . '?module=settings/weight-types',
 								'module' => 'settings/weight-types',
 								'action' => 'index',
 								'icon'   => 'la la-box',
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
 				'icon'   => 'la la-flag',
 				'items'  => [
 					'languages' => [
 						'name'   => __('Languages'),
 						'icon'   => 'la la-language',
 						'url'    => $admin_path . '?module=localization/languages',
 						'module' => 'localization/languages',
 						'action' => 'index',
 					],
 					'currencies' => [
 						'name'   => __('Currencies'),
 						'icon'   => 'la la-coins',
 						'url'    => $admin_path . '?module=localization/currencies',
 						'module' => 'localization/currencies',
 						'action' => 'index',
 					],
 					'geo-location' => [
 						'name'   => __('Geo location'),
 						'icon'   => 'la la-globe',
 						'url'    => $admin_path . '?module=localization/region-groups',
 						'module' => 'localization/region-groups',
 						'action' => 'index',
 						'class'  => 'align-top',
 						'items'  => [
 							'countries' => [
 								'name'   => __('Countries'),
 								'icon'   => 'la la-flag',
 								'url'    => $admin_path . '?module=localization/countries',
 								'module' => 'localization/countries',
 								'action' => 'index',
 							],
 							'regions' => [
 								'name'   => __('Regions'),
 								'icon'   => 'la la-city',
 								'url'    => $admin_path . '?module=localization/regions',
 								'module' => 'localization/regions',
 								'action' => 'index',
 							],
 							'geoRegions' => [
 								'name'   => __('Region Groups'),
 								'icon'   => 'la la-atlas',
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
 				'icon'   => 'la la-tools',
 				'items'  => [
 					'email' => [
 						'name'   => __('Email settings'),
 						'icon'   => 'la la-envelope',
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
 		'icon'   => 'icon-globe-outline',
 		'class'  => 'align-top',
 		'items'  => [
 			'cache' => [
 				'name'   => __('Cache'),
 				'url'    => $admin_path . '?module=tools/cache',
 				'module' => 'tools/cache',
 				'action' => 'index',
 				'icon'   => 'la la-circle-notch',
 			],
 			'backup' => [
 				'name'   => __('Backup'),
 				'url'    => $admin_path . '?module=tools/backup',
 				'module' => 'tools/backup',
 				'action' => 'index',
 				'icon'   => 'la la-server',
 			],
 			/*
			'cron' => [
				'name'   => __('Cron job'),
				'url'    => $admin_path . '?module=tools/cron',
				'module' => 'tools/cron',
				'action' => 'index',
				'icon'   => 'la la-history la-90',
			],
*/
 			'import-export' => [
 				'name'   => __('Import/Export'),
 				'url'    => $admin_path . '?module=tools/import',
 				'module' => 'tools/import',
 				'action' => 'index',
 				'icon'   => 'la la-upload',
 				'items'  => [
 					'content-heading' => [
 						'name'    => __('Content'),
 						'heading' => true,
 					],
 					'import' => [
 						'name'   => __('Import content'),
 						'icon'   => 'la la-file-import',
 						'url'    => $admin_path . '?module=tools/import',
 						'module' => 'tools/import',
 						'action' => 'index',
 					],
 					'export' => [
 						'name'   => __('Export content'),
 						'icon'   => 'la la-file-export',
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
						'icon'   => 'la la-caret-square-left',
						'url'    => $admin_path . '?module=tools/import',
						'module' => 'tools/import',
						'action' => 'index',
					],
					'export-media' => [
						'name'   => __('Export media'),
						'icon'   => 'la la-caret-square-right',
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
				'icon'   => 'la la-shield-alt',
			],
			*/
 			'system' => [
 				'name'   => __('System info'),
 				'url'    => $admin_path . '?module=tools/systeminfo',
 				'module' => 'tools/systeminfo',
 				'action' => 'index',
 				'icon'   => 'la la-info-circle',
 				'items'  => [
 					'info' => [
 						'name'   => __('System Info'),
 						'icon'   => 'la la-info',
 						'url'    => $admin_path . '?module=tools/systeminfo',
 						'module' => 'tools/systeminfo',
 						'action' => 'index',
 					],
 					'error-log' => [
 						'name'   => __('Error log'),
 						'url'    => $admin_path . '?module=tools/errorlog',
 						'module' => 'tools/errorlog',
 						'action' => 'index',
 						'icon'   => 'la la-bug',
 					],
 				],
 			],
 			'update' => [
 				'name'   => __('Update'),
 				'url'    => $admin_path . '?module=tools/update',
 				'module' => 'tools/update',
 				'action' => 'index',
 				'icon'   => 'la la-sync',
 			],
 		],
 	],
 ];
