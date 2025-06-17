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
	'/rest/posts'                                  => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'post'],
	'/rest/posts/#post_id#'                        => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post'],
	'/rest/posts/{slug}'                           => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post'],
	//revisions
	'/rest/posts/#post_id#/revision'              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'post_content_revision'],
	'/rest/posts/#post_id#/revision/{created_at}' => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post_content_revision'],
	//pages

	//comments
	'/rest/posts/#post_id#/comment'              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'comment'],
	'/rest/posts/#post_id#/comment/{created_at}' => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'comment'],

	//media
	'/rest/media'        => ['module' => 'media'],
	'/rest/media/{file}' => ['module' => 'media/media/index'],

	//menus
	'/rest/menus'           => ['module' => 'menu'],
	'/rest/menus/{menu_id}' => ['module' => 'menu/menu/index'],
	//menu-items
	'/rest/menu-items'           => ['module' => 'menu-item'],
	'/rest/menu-items/{menu_id}' => ['module' => 'menu-item/menu-item/index'],

	//taxonomy
	'/rest/taxonomies/'           => ['module' => 'taxonomy'],
	'/rest/taxonomies//{menu_id}' => ['module' => 'taxonomy/taxonomy/index'],
	//tags
	//categories

	//users
	'/rest/users'           => ['module' => 'user'],
	'/rest/users/#user_id#' => ['module' => 'user/user/index'],

	//search
	'/rest/search' => ['module' => 'search'],

	//settings
	'/rest/settings' => ['module' => 'settings'],

	//themes
	'/rest/themes'                                  => ['module' => 'theme'],
	'/rest/themes/{slug}'                           => ['module' => 'theme/theme/index'],

	//plugins
	'/rest/plugins'                                  => ['module' => 'plugin'],
	'/rest/plugins/{slug}'                           => ['module' => 'plugin/plugin/index'],

	//languages

	//ecommerce

	//cart
	'/rest/cart'              => ['module' => 'cart/cart/', 'methods' => ['get', 'post']],
	'/rest/cart/{key}'        => ['module' => 'cart/cart', 'methods' => ['get', 'post', 'put', 'patch', 'delete']],
	'/rest/cart/#product_id#' => ['module' => 'cart/cart', 'methods' => ['get', 'post', 'put', 'patch', 'delete']],

	//products
	'/rest/products'              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product'],
	'/rest/products/#product_id#' => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product'],
	'/rest/products/{slug}'       => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product'],

	'/rest/admin'                                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'admin'],
	'/rest/admin/#admin_id#'                                             => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'admin'],
	'/rest/attributes'                                                   => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'attribute'],
	'/rest/attributes/#attribute_id#'                                    => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'attribute'],
	'/rest/attribute_groups'                                             => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'attribute_group'],
	'/rest/attribute_groups/#attribute_group_id#'                        => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'attribute_group'],
	'/rest/countries'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'country'],
	'/rest/countries/#country_id#'                                       => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'country'],
	'/rest/coupons'                                                      => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'coupon'],
	'/rest/coupons/#coupon_id#'                                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'coupon'],
	'/rest/currencies'                                                   => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'currency'],
	'/rest/currencies/#currency_id#'                                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'currency'],
	'/rest/digital_assets'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'digital_asset'],
	'/rest/digital_assets/#digital_asset_id#'                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'digital_asset'],
	'/rest/digital_asset_logs'                                           => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'digital_asset_log'],
	'/rest/digital_asset_logs/#digital_asset_log_id#'                    => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'digital_asset_log'],
	'/rest/fields'                                                       => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'field'],
	'/rest/fields/#field_id#'                                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'field'],
	'/rest/field_groups'                                                 => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'field_group'],
	'/rest/field_groups/#field_group_id#'                                => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'field_group'],
	'/rest/languages'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'language'],
	'/rest/languages/#language_id#'                                      => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'language'],
	'/rest/length_types'                                                 => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'length_type'],
	'/rest/length_types/#length_type_id#'                                => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'length_type'],
	'/rest/manufacturers'                                                => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'manufacturer'],
	'/rest/manufacturers/#manufacturer_id#'                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'manufacturer'],
	'/rest/media_content'                                                => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'media_content'],
	'/rest/media_content/#media_content_id#'                             => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'media_content'],
	'/rest/menus'                                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'menu'],
	'/rest/menus/#menu_id#'                                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'menu'],
	'/rest/options'                                                      => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'option'],
	'/rest/options/#option_id#'                                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'option'],
	'/rest/option_value'                                                 => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'option_value'],
	'/rest/option_value/#option_value_id#'                               => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'option_value'],
	'/rest/orders'                                                       => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'order'],
	'/rest/orders/#order_id#'                                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'order'],
	'/rest/order_log'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'order_log'],
	'/rest/order_log/#order_log_id#'                                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'order_log'],
	'/rest/order_statuses'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'order_status'],
	'/rest/order_statuses/#order_status_id#'                             => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'order_status'],
	'/rest/payment_statuses'                                             => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'payment_status'],
	'/rest/payment_statuses/#payment_status_id#'                         => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'payment_status'],
	'/rest/post_content_meta'                                            => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'post_content_meta'],
	'/rest/post_content_meta/#post_content_meta_id#'                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post_content_meta'],
	'/rest/post_content_revisions'                                       => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'post_content_revision'],
	'/rest/post_content_revisions/#post_content_revision_id#'            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post_content_revision'],
	'/rest/post_meta'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'post_meta'],
	'/rest/post_meta/#post_meta_id#'                                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'post_meta'],
	'/rest/product_attributes'                                           => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_attribute'],
	'/rest/product_attributes/#product_attribute_id#'                    => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_attribute'],
	'/rest/product_content_meta'                                         => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_content_meta'],
	'/rest/product_content_meta/#product_content_meta_id#'               => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_content_meta'],
	'/rest/product_content_revision'                                     => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_content_revision'],
	'/rest/product_content_revision/#product_content_revision_id#'       => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_content_revision'],
	'/rest/product_meta'                                                 => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_meta'],
	'/rest/product_meta/#product_meta_id#'                               => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_meta'],
	'/rest/product_options'                                              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_option'],
	'/rest/product_options/#product_option_id#'                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_option'],
	'/rest/product_option_values'                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_option_value'],
	'/rest/product_option_values/#product_option_value_id#'              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_option_value'],
	'/rest/product_questions'                                            => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_question'],
	'/rest/product_questions/#product_question_id#'                      => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_question'],
	'/rest/product_reviews'                                              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_review'],
	'/rest/product_reviews/#product_review_id#'                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_review'],
	'/rest/product_review_media'                                         => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_review_media'],
	'/rest/product_review_media/#product_review_media_id#'               => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_review_media'],
	'/rest/product_subscriptions'                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'product_subscription'],
	'/rest/product_subscriptions/#product_subscription_id#'              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'product_subscription'],
	'/rest/regions'                                                      => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'region'],
	'/rest/regions/#region_id#'                                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'region'],
	'/rest/region_groups'                                                => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'region_group'],
	'/rest/region_groups/#region_group_id#'                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'region_group'],
	'/rest/returns'                                                      => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'return'],
	'/rest/return/#return_id#'                                           => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'return'],
	'/rest/return_reasons'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'return_reason'],
	'/rest/return_reasons/#return_reason_id#'                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'return_reason'],
	'/rest/return_resolutions'                                           => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'return_resolution'],
	'/rest/return_resolution/#return_resolution_id#'                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'return_resolution'],
	'/rest/return_statuses'                                              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'return_status'],
	'/rest/return_statuses/#return_status_id#'                           => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'return_status'],
	'/rest/roles'                                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'role'],
	'/rest/roles/#role_id#'                                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'role'],
	'/rest/settings'                                                     => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'setting'],
	'/rest/settings/#setting_id#'                                        => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'setting'],
	'/rest/setting_content'                                              => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'setting_content'],
	'/rest/setting_content/#setting_content_id#'                         => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'setting_content'],
	'/rest/shipping_statuses'                                            => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'shipping_status'],
	'/rest/shipping_statuses/#shipping_status_id#'                       => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'shipping_status'],
	'/rest/stock_statuses'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'stock_status'],
	'/rest/stock_statuses/#stock_status_id#'                             => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'stock_status'],
	'/rest/subscriptions'                                                => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'subscription'],
	'/rest/subscriptions/#subscription_id#'                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'subscription'],
	'/rest/subscription_plans'                                           => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'subscription_plan'],
	'/rest/subscription_plans/#subscription_plan_id#'                    => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'subscription_plan'],
	'/rest/subscription_statuses'                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'subscription_status'],
	'/rest/subscription_statuses/#subscription_status_id#'               => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'subscription_status'],
	'/rest/taxonomies'                                                   => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'taxonomy'],
	'/rest/taxonomies/#taxonomy_id#'                                     => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'taxonomy'],
	'/rest/taxonomy_items'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'taxonomy_item'],
	'/rest/taxonomy_items/#taxonomy_item_id#'                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'taxonomy_item'],
	'/rest/tax_rates'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'tax_rate'],
	'/rest/tax_rates/#tax_rate_id#'                                      => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'tax_rate'],
	'/rest/tax_rules'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'tax_rule'],
	'/rest/tax_rules/#tax_rule_id#'                                      => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'tax_rule'],
	'/rest/tax_types'                                                    => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'tax_type'],
	'/rest/tax_types/#tax_type_id#'                                      => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'tax_type'],
	'/rest/users'                                                        => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'user'],
	'/rest/users/#user_id#'                                              => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'user'],
	'/rest/user_addresses'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'user_address'],
	'/rest/user_addresses/#user_address_id#'                             => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'user_address'],
	'/rest/user_groups'                                                  => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'user_group'],
	'/rest/user_groups/#user_group_id#'                                  => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'user_group'],
	'/rest/user_wishlists'                                               => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'user_wishlist'],
	'/rest/user_wishlists/#user_wishlist_id#'                            => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'user_wishlist'],
	'/rest/vendors'                                                      => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'vendor'],
	'/rest/vendors/#vendor_id#'                                          => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'vendor'],
	'/rest/vouchers'                                                     => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'voucher'],
	'/rest/vouchers/#voucher_id#'                                        => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'voucher'],
	'/rest/weight_types'                                                 => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'weight_type'],
	'/rest/weight_types/#weight_type_id#'                                => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'weight_type'],

	'/rest/sites'           => ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => 'site'],
	'/rest/sites/#site_id#' => ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => 'site'],
];
