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
 * Get multiple setting content values.
 *
 * @param int                                 $site_id     Site ID
 * @param string                              $namespace   Setting namespace
 * @param array<array-key, mixed>|string|null $key         Setting key or array of keys
 * @param array<array-key, mixed>             $default     Default value if keys are not found
 * @param int|false                           $language_id Language ID or false for default
 *
 * @return mixed Setting content values
 */
function getMultiSettingContent(int $site_id, string $namespace, array|string|null $key = null, array $default = [], int|false $language_id = false): mixed {
	return System\Meta\SettingContent::getInstance()->getMulti($site_id, $namespace, $key, $default, $language_id);
}

/**
 * Set multiple setting content values.
 *
 * @param int   $site_id Site ID
 * @param mixed $meta    Setting content data to set
 *
 * @return mixed Result of the set operation
 */
function setMultiSettingContent(int $site_id, mixed $meta): mixed {
	return System\Meta\SettingContent::getInstance()->setMulti($site_id, $meta);
}

/**
 * Get post metadata.
 *
 * @param int         $post_id   Post ID
 * @param string      $namespace Metadata namespace
 * @param string|null $key       Metadata key
 * @param mixed       $default   Default value if key is not found
 *
 * @return mixed Metadata value
 */
function getPostMeta(int $post_id, string $namespace, ?string $key = null, mixed $default = null): mixed {
	return System\Meta\PostMeta::getInstance()->get($post_id, $namespace, $key, $default);
}

/**
 * Set post metadata.
 *
 * @param int         $post_id   Post ID
 * @param string      $namespace Metadata namespace
 * @param string|null $key       Metadata key
 * @param mixed       $value     Value to set
 *
 * @return mixed Result of the set operation
 */
function setPostMeta(int $post_id, string $namespace, ?string $key = null, mixed $value = null): mixed {
	return System\Meta\PostMeta::getInstance()->set($post_id, $namespace, $key, $value);
}

/**
 * Delete post metadata.
 *
 * @param int         $post_id   Post ID
 * @param string      $namespace Metadata namespace
 * @param string|null $key       Metadata key
 *
 * @return mixed Result of the delete operation
 */
function deletePostMeta(int $post_id, string $namespace, ?string $key = null): mixed {
	return System\Meta\PostMeta::getInstance()->delete($post_id, $namespace, $key);
}

/**
 * Set multiple post metadata values at once.
 *
 * @param int       $post_id     Post ID
 * @param string    $namespace   Metadata namespace
 * @param mixed     $meta        List of ['key' => ..., 'value' => ...] rows
 * @param int|false $language_id Language ID or false for the default language
 *
 * @return mixed Result of the set operation
 */
function setMultiPostMeta(int $post_id, string $namespace, mixed $meta, int|false $language_id = false): mixed {
	return System\Meta\PostMeta::getInstance()->setMulti($post_id, $meta, $namespace, $language_id);
}

/**
 * Get post content metadata.
 *
 * @param int         $post_id     Post ID
 * @param string      $namespace   Metadata namespace
 * @param string|null $key         Metadata key
 * @param mixed       $default     Default value if key is not found
 * @param int|false   $language_id Language ID or false for default
 *
 * @return mixed Metadata value
 */
function getPostContentMeta(int $post_id, string $namespace, ?string $key = null, mixed $default = null, int|false $language_id = false): mixed {
	return System\Meta\PostContentMeta::getInstance()->get($post_id, $namespace, $key, $default, $language_id);
}

/**
 * Get multiple post content metadata values.
 *
 * @param int                                 $post_id     Post ID
 * @param string                              $namespace   Metadata namespace
 * @param array<array-key, mixed>|string|null $key         Metadata key or array of keys
 * @param array<array-key, mixed>             $default     Default value if keys are not found
 * @param int|false                           $language_id Language ID or false for default
 *
 * @return mixed Metadata values
 */
function getMultiPostContentMeta(int $post_id, string $namespace, array|string|null $key = null, array $default = [], int|false $language_id = false): mixed {
	return System\Meta\PostContentMeta::getInstance()->getMulti($post_id, $namespace, $key, $default, $language_id);
}

/**
 * Get multiple product content metadata values.
 *
 * @param int                                 $product_id  Product ID
 * @param string                              $namespace   Metadata namespace
 * @param array<array-key, mixed>|string|null $key         Metadata key or array of keys
 * @param array<array-key, mixed>             $default     Default value if keys are not found
 * @param int|false                           $language_id Language ID or false for default
 *
 * @return mixed Metadata values
 */
function getMultiProductContentMeta(int $product_id, string $namespace, array|string|null $key = null, array $default = [], int|false $language_id = false): mixed {
	return System\Meta\ProductContentMeta::getInstance()->getMulti($product_id, $namespace, $key, $default, $language_id);
}

/**
 * Set post content metadata.
 *
 * @param int         $post_id     Post ID
 * @param string      $namespace   Metadata namespace
 * @param string|null $key         Metadata key
 * @param mixed       $value       Value to set
 * @param int|false   $language_id Language ID or false for default
 *
 * @return mixed Result of the set operation
 */
function setPostContentMeta(int $post_id, string $namespace, ?string $key = null, mixed $value = null, int|false $language_id = false): mixed {
	return System\Meta\PostContentMeta::getInstance()->set($post_id, $namespace, $key, $value, $language_id);
}

/**
 * Set product content metadata.
 *
 * @param int         $product_id  Product ID
 * @param string      $namespace   Metadata namespace
 * @param string|null $key         Metadata key
 * @param mixed       $value       Value to set
 * @param int|false   $language_id Language ID or false for default
 *
 * @return mixed Result of the set operation
 */
function setProductContentMeta(int $product_id, string $namespace, ?string $key = null, mixed $value = null, int|false $language_id = false): mixed {
	return System\Meta\ProductContentMeta::getInstance()->set($product_id, $namespace, $key, $value, $language_id);
}

/**
 * Delete post content metadata.
 *
 * @param int         $post_id     Post ID
 * @param string      $namespace   Metadata namespace
 * @param string|null $key         Metadata key
 * @param int|false   $language_id Language ID or false for default
 *
 * @return mixed Result of the delete operation
 */
function deletePostContentMeta(int $post_id, string $namespace, ?string $key = null, int|false $language_id = false): mixed {
	return System\Meta\PostContentMeta::getInstance()->delete($post_id, $namespace, $key, $language_id);
}

/**
 * Delete product content metadata.
 *
 * @param int         $product_id  Product ID
 * @param string      $namespace   Metadata namespace
 * @param string|null $key         Metadata key
 * @param int|false   $language_id Language ID or false for default
 *
 * @return mixed Result of the delete operation
 */
function deleteProductContentMeta(int $product_id, string $namespace, ?string $key = null, int|false $language_id = false): mixed {
	return System\Meta\ProductContentMeta::getInstance()->delete($product_id, $namespace, $key, $language_id);
}

/**
 * Set multiple post content metadata values at once.
 *
 * @param int   $post_id Post ID
 * @param mixed $meta    Metadata data to set
 *
 * @return mixed Result of the set operation
 */
function setMultiPostContentMeta(int $post_id, mixed $meta): mixed {
	return System\Meta\PostContentMeta::getInstance()->setMulti($post_id, $meta);
}

/**
 * Set multiple product content metadata values at once.
 *
 * @param int   $product_id Product ID
 * @param mixed $meta       Metadata data to set
 *
 * @return mixed Result of the set operation
 */
function setMultiProductContentMeta(int $product_id, mixed $meta): mixed {
	return System\Meta\ProductContentMeta::getInstance()->setMulti($product_id, $meta);
}
