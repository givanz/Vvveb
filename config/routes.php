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
	//PWA manifest
	'/manifest.webmanifest' => ['module' => 'feed/manifest/index'],

	//homepage
	'/'           => ['module' => 'index/index'],
	//pagination for blog posts
	'/page/#page#'  => ['module' => 'index/index'],

	//user
	'/user/login'    			             => ['module' => 'user/login/index'],
	'/user/signup'   			             => ['module' => 'user/signup/index'],
	'/user/edit'     			             => ['module' => 'user/edit/index'],
	'/user/reset'    			             => ['module' => 'user/reset/index'],
	'/user/reset/{token}/{username}' => ['module' => 'user/reset/reset'],

	//user dashboard
	'/user/orders'                           => ['module' => 'user/orders/index'],
	'/user/order/#order_id#'                 => ['module' => 'user/orders/order'],
	'/user/downloads'                        => ['module' => 'user/downloads/index'],
	'/user/downloads/#page#'                 => ['module' => 'user/downloads/index'],
	'/user/downloads/#download_id#'          => ['module' => 'user/downloads/download'],
	'/user/address'                          => ['module' => 'user/address/index'],
	'/user/address/edit'                     => ['module' => 'user/address/edit'],
	'/user/address/edit/#user_address_id#'   => ['module' => 'user/address/edit'],
	'/user/address/delete/#user_address_id#' => ['module' => 'user/address/delete'],
	'/user/comments'                         => ['module' => 'user/comments/index'],
	'/user/profile'                          => ['module' => 'user/profile/index'],

	//user wishlist
	'/user/wishlist'                     => ['module' => 'user/wishlist/index'],
	'/user/wishlist/add/#product_id#'    => ['module' => 'user/wishlist/add'],
	'/user/wishlist/remove/#product_id#' => ['module' => 'user/wishlist/remove'],

	'/user'  => ['module' => 'user/index'],

	//search
	'/search'                        => ['module' => 'search'],
	'/search/{search}'               => ['module' => 'search'],
	'/search/{search}/#page#'        => ['module' => 'search'],
	'/search/{search}/{type}/#page#' => ['module' => 'search'],

	//rest api
	'/rest/'              => ['module' => 'rest'],
	'/rest/{method}'      => ['module' => 'rest'],
	'/rest/{method}/{id}' => ['module' => 'rest'],

	//ecommerce

	//catalog - multi language - language code must be at least 2 characters
	'/{language{2,5}}/shop'                 => ['module' => 'product/index'],
	'/{language{2,5}}/shop/{slug}'          => ['module' => 'product/index'],
	'/{language{2,5}}/shop/{slug}/#page#'   => ['module' => 'product/index'],
	'/{language{2,5}}/manufacturer/{slug}'  => ['module' => 'product/manufacturer'],
	'/{language{2,5}}/product/{slug}'       => ['module' => 'product/product/index', 'edit'=>'?module=product/product&slug={slug}'],

	//catalog
	'/shop'                                 => ['module' => 'product/index'],
	'/shop/#page#'                          => ['module' => 'product/index'],
	'/shop/{slug}'                          => ['module' => 'product/category/index'],
	'/shop/{slug}/#page#'                   => ['module' => 'product/category/index'],
	'/shop/{slug}/#page#/filters-{filters}' => ['module' => 'product/category/index'],
	'/manufacturer/{slug}'                  => ['module' => 'product/manufacturer/index'],
	'/vendor/{slug}'                        => ['module' => 'product/vendor/index'],
	'/product/{slug}'                       => ['module' => 'product/product/index', 'edit'=>'?module=product/product&slug={slug}'],

	//compare
	'/product/compare'				          => ['module' => 'product/compare/index'],
	'/product/compare/#product_id#'	=> ['module' => 'product/compare/index'],

	//multi language catalog - language code must be at least 2 characters
	'/{language{2,5}}/shop'                                 => ['module' => 'product/index'],
	'/{language{2,5}}/shop/#page#'                          => ['module' => 'product/index'],
	'/{language{2,5}}/shop/{slug}'                          => ['module' => 'product/category/index'],
	'/{language{2,5}}/shop/{slug}/#page#'                   => ['module' => 'product/category/index'],
	'/{language{2,5}}/shop/{slug}/#page#/filters-{filters}' => ['module' => 'product/category/index'],
	'/{language{2,5}}/manufacturer/{slug}'                  => ['module' => 'product/manufacturer/index'],
	'/{language{2,5}}/vendor/{slug}'                        => ['module' => 'product/vendor/index'],
	'/{language{2,5}}/product/{slug}'                       => ['module' => 'product/product/index', 'edit'=>'?module=product/product&slug={slug}'],

	//checkout
	'/cart'                         => ['module' => 'cart/cart/index'],
	'/cart/add/#product_id#'        => ['module' => 'cart/cart/add'],
	'/cart/remove/#product_id#'     => ['module' => 'cart/cart/remove'],
	'/cart/voucher'                 => ['module' => 'checkout/cart/voucher'],

	'/checkout/#product_id#'  => ['module' => 'checkout/checkout/index'],
	'/checkout'               => ['module' => 'checkout/checkout/index'],
	'/checkout/pay'           => ['module' => 'checkout/pay'],
	'/checkout/confirm'       => ['module' => 'checkout/confirm/index'],
	'/checkout/confirm/#id#'  => ['module' => 'checkout/order/index'],

	//feeds
	'/feed/{rss}'     => ['module' => 'feed/index'],
	/*
	'/feed/posts'     => ['module' => 'feed/posts'],
	'/feed/products'  => ['module' => 'feed/products'],
	'/feed/comments'  => ['module' => 'feed/comments'],
	*/

	//content
	'/blog'                  => ['module' => 'content'],
	'/blog/#page#'           => ['module' => 'content'],
	'/cat/{slug}'            => ['module' => 'content/category/index'],
	'/cat/{slug}/#page#'     => ['module' => 'content/category/index'],
	'/tag/{slug}'            => ['module' => 'content/tag/index'],
	'/tag/{slug}/#page#'     => ['module' => 'content/tag/index'],
	'/author/{username}'     => ['module' => 'content/user/index'],
	//archive year
	'/#year{4,4}#'=> ['module' => 'content/archive/index'],
	//archive month and year
	'/#year{4,4}#/#month{2,2}#'=> ['module' => 'content/archive/index'],
	//archive day
	//'/#year#/#month#/#day#'=> ['module' => 'content/archive/index'],

	//post
	//'/#year{4,4}#-#month{1,2}#-#day#/{slug}'        => ['module' => 'content/post/index', 'edit'=>'?module=content/post&slug={slug}'],
	'/{slug}'        => ['module' => 'content/post/index', 'edit'=>'?module=content/post&slug={slug}&type=post'],
	//page
	//'/{slug}'   	 => ['module' => 'content/page/index', 'edit'=>'?module=content/post&slug={slug}'],
	'/page/{slug}'   => ['module' => 'content/page/index', 'edit'=>'?module=content/post&slug={slug}&type=page'],

	//multi language content - language code must be at least 2 characters
	'/{language{2,5}}/'           => ['module' => 'index/index'],
	//pagination for blog posts
	'/{language{2,5}}/page/#page#'  => ['module' => 'index/index'],
	//content
	'/{language{2,5}}/blog'         => ['module' => 'content'],
	'/{language{2,5}}/cat/{slug}'   => ['module' => 'content/category/language'],
	'/{language{2,5}}/tag/{slug}'   => ['module' => 'content/tag/index'],
	'/{language{2,5}}/{slug}'       => ['module' => 'content/post/index', 'edit'=>'?module=content/post&slug={slug}'],
	'/{language{2,5}}/page/{slug}'  => ['module' => 'content/page/index', 'edit'=>'?module=content/post&slug={slug}'],

	'/{language{2,5}}/tag/{slug}'            => ['module' => 'content/tag/index'],
	'/{language{2,5}}/tag/{slug}/#page#'     => ['module' => 'content/tag/index'],
	'/{language{2,5}}/author/{username}'     => ['module' => 'content/user/index'],
	//archive year
	'/{language{2,5}}/#year{4,4}#'=> ['module' => 'content/archive/index'],
	//archive month and year
	'/{language{2,5}}/#year{4,4}#/#month{2,2}#'=> ['module' => 'content/archive/index'],
	//archive day
	//'/#year#/#month#/#day#'=> ['module' => 'content/archive/index'],

	//feed
	'/feed'           => ['module' => 'feed'],
	'/feed/comments'  => ['module' => 'feed/comments/index'],

	//Cron
	'/run-cron/{key}' => ['module' => 'cron/index'],
];
