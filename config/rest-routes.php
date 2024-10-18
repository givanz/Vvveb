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

return [
	'/rest/'                                       => ['module' => 'index'],

	//posts
	'/rest/posts'                                  => ['module' => 'posts'],
	'/rest/posts/#post_id#'                        => ['module' => 'posts/post/index'],
	'/rest/posts/{slug}'                           => ['module' => 'posts/post/index'],
	//revisions
	'/rest/posts/#post_id#/revisions'              => ['module' => 'posts/revisions/index'],
	'/rest/posts/#post_id#/revisions/{created_at}' => ['module' => 'posts/revision/index'],
	//pages

	//comments
	'/rest/posts/#post_id#/comments'              => ['module' => 'posts/comments/index'],
	'/rest/posts/#post_id#/comments/{created_at}' => ['module' => 'posts/comment/index'],

	//media
	'/rest/media'        => ['module' => 'media'],
	'/rest/media/{file}' => ['module' => 'media/media/index'],

	//menus
	'/rest/menus'           => ['module' => 'menus'],
	'/rest/menus/{menu_id}' => ['module' => 'menus/v/index'],
	//menu-items
	'/rest/menu-items'           => ['module' => 'menu-items'],
	'/rest/menu-items/{menu_id}' => ['module' => 'menu-items/menu-items/index'],

	//taxonomies
	'/rest/taxonomies'           => ['module' => 'taxonomies'],
	'/rest/taxonomies/{menu_id}' => ['module' => 'taxonomies/taxonomies/index'],
	//tags
	//categories

	//users
	'/rest/users'           => ['module' => 'users'],
	'/rest/users/#user_id#' => ['module' => 'users/user/index'],

	//search
	'/rest/search' => ['module' => 'search'],

	//settings
	'/rest/settings' => ['module' => 'settings'],

	//themes
	'/rest/themes'                                  => ['module' => 'settings'],
	'/rest/themes/{slug}'                           => ['module' => 'themes/themes/index'],

	//plugins
	'/rest/plugins'                                  => ['module' => 'plugins'],
	'/rest/plugins/{slug}'                           => ['module' => 'plugins/plugins/index'],

	//languages

	//ecommerce

	//products
	'/rest/products'              => ['module' => 'products'],
	'/rest/products/#product_id#' => ['module' => 'products/product/index'],
	'/rest/products/{slug}'       => ['module' => 'products/products/index'],

	//product attributes
	//product reviews
	//product questions
	//taxonomies
	//tags
	//categories

	//orders
	//coupons
	//tax rates
	//tax classes
	//tax types
	//returns
	//zones
	//countries
	//currencies

	//payment methods
	//shipping methods

	//stats
];
