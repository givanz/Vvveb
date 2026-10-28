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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use function Vvveb\getSetting;
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use function Vvveb\setSetting;
use function Vvveb\slugify;
use Vvveb\Sql\CategorySQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Validator;

trait Edit {
	/**
	 * Resolve list/index routes for an object type.
	 *
	 * @param string $object Object name (post, product, ...).
	 * @param string $type   Post type, may equal $object.
	 *
	 * @return array{0: string, 1: string, 2: string} Route, alternate route and controller group.
	 */
	protected function editContentRoutes(string $object, string $type): array {
		if ($object == 'product') {
			$route      = "product/{$type}/index";
			$altRoute   = "product/{$object}/index";
			$controller = 'product';
		} else {
			$route      = "content/{$type}/index";
			$altRoute   = "content/{$object}/index";
			$controller = 'content';
		}

		return [$route, $altRoute, $controller];
	}

	/**
	 * Load a post record with image URLs plus its content URLs and revisions.
	 *
	 * @param string              $object        Object name (post, product, ...).
	 * @param string              $type          Post type, may equal $object.
	 * @param int|string|false    $postId        Record id, or false for a new record.
	 * @param string|false        $slug          Slug lookup when no id is given.
	 * @param string|null         $typeParam     Type override, null to keep $type.
	 * @param array<string,mixed> $global        Global context (site_id, host, path, admin_id, ...).
	 * @param array<int,mixed>    $languagesList Languages list keyed by code.
	 * @param bool                $canViewOthers Whether records of other admins may be loaded.
	 *
	 * @return array{0: array<string,mixed>|null, 1: mixed, 2: string, 3: string, 4: string} Post (null when not found), id, resolved type, default-language name and revisions url.
	 */
	protected function getEditPostData(string $object, string $type, int|string|false $postId, string|false $slug, ?string $typeParam, array $global, array $languagesList, bool $canViewOthers): array {
		$post        = [];
		$postOptions = [];

		if ($typeParam) {
			$type = $typeParam;
		}

		if ($postId) {
			$postOptions[$object . '_id'] = (int)$postId;
		} else {
			if ($slug) {
				$postOptions['slug'] = $slug;
			}
		}

		if ($postOptions) {
			$posts = model($object);

			$postOptions['type'] = $type;
			$options             = $postOptions + $global;

			if ($canViewOthers) {
				unset($options['admin_id']);
			} else {
				$options['admin_id'] = $global['admin_id'];
			}

			//get all languages
			//unset($options['language_id']);
			$post = $posts->get($options);

			if (!$post) {
				return ['post' => null, 'post_id' => $postId, 'type' => $type];
			}

			//featured image
			if (isset($post['image'])) {
				$post['image_url'] = Images::image($post['image'], $object);
			}

			//gallery
			if (isset($post[$object . '_image'])) {
				$post['images'] = Images::images($post[$object . '_image'], $object);
			}

			//$productImages = $posts->getImages($postOptions);
		} else {
			$post['image_url']  = Images::image('', $object);
			$post['updated_at'] = date('Y-m-d H:i:s', time());
		}

		$type = $post['type'] ?? $type;

		[$route, $altRoute, $controller] = $this->editContentRoutes($object, $type);

		if ($object != 'product') {
			$stickyPosts = getSetting('posts', 'sticky') ?? [];
			//$post['sticky'] = in_array($post['post_id'], $stickyPosts);
			$post['sticky'] = isset($post['post_id'], $stickyPosts[$post['post_id']]);
		}

		if ($this->editRevisionsEnabled($object)) {
			$revisions = model($object . '_content_revision');
		} else {
			$revisions = false;
		}

		//get site host for current selected site to use for absolute url
		$url = ['host' => $global['host'], 'path' => $global['path'] ? '/' . $global['path'] : null];

		if ($type != $object) {
			$url['type'] = $type;
		}

		$revisionsUrl = \Vvveb\url(['module' => "$controller/revisions", 'object' => $object, 'type' => $type, $object . '_id' => $postId]);
		$name         = '';

		if (isset($post[$object . '_content'])) {
			foreach ($post[$object . '_content'] as &$content) {
				if (!isset($post['url'])) {
					$post['url']          = \Vvveb\url($route, ['slug' => $content['slug'], $object . '_id' => $postId] + $url);
					$post['relative-url'] = \Vvveb\url($route, ['slug' => $content['slug'], $object . '_id' => $postId]);

					if (!$post['url'] || $post['url'] == '//' . $global['site_url']) {
						$post['url']          = \Vvveb\url($altRoute, ['slug' => $content['slug'], $object . '_id' => $postId] + $url);
						$post['relative-url'] = \Vvveb\url($altRoute, ['slug' => $content['slug'], $object . '_id' => $postId]);
					}
				}
				$language = [];

				if ($content['language_id'] != $global['default_language_id']) {
					$code = 'en_US';

					foreach ($languagesList as $code => $lang) {
						if ($lang['language_id'] == $content['language_id']) {
							break;
						}
					}
					$language = ['language' => $code];
				} else {
					$name = $content['name'];
				}

				$content['url']             = \Vvveb\url($route, $content + $language + $url);
				$content['revision_count']  = 0;

				if (!$content['url'] || $content['url'] == '//' . $global['site_url']) {
					$content['url']         = \Vvveb\url($altRoute, $content + $language + $url);
				}

				if ($revisions) {
					$revision = $revisions->getAll([$object . '_id' => $postId, 'language_id' => $content['language_id']]);

					if ($revision) {
						foreach ($revision[$object . '_content_revision'] as &$rev) {
							$rev['preview-url'] = $content['url'] . '?revision=preview&created_at=' . $rev['created_at'] . '&language_id=' . $content['language_id'];
							$rev['compare-url'] = $revisionsUrl . '&created_at=' . $rev['created_at'] . '&language_id=' . $content['language_id'];
						}

						$content['revision_count'] = $revision['count'];
						$content['revision']       = $revision[$object . '_content_revision'];
					}
				}
			}
		}

		[$post, $postId, $type] = Event :: trigger(__CLASS__, 'index', $post, $postId, $type);

		return [$post, $postId, $type, $name, $revisionsUrl];
	}

	/**
	 * Whether content revisions apply. Controllers override the $revisions
	 * property; extracted so the trait stays decoupled from controller state.
	 *
	 * @param string $object Object name (unused, kept for overrides).
	 *
	 * @return bool True when revisions should be loaded and saved.
	 */
	protected function editRevisionsEnabled(string $object): bool {
		return (bool)($this->revisions ?? true);
	}

	/**
	 * Adds a new category taxonomy item.
	 *
	 * @param int                 $taxonomy_id The taxonomy ID.
	 * @param string              $name        The category name.
	 * @param array<string,mixed> $global      Global context.
	 *
	 * @return int The new taxonomy_item ID.
	 */
	protected function addEditCategory(int $taxonomy_id, string $name, array $global): int {
		$categories = new CategorySQL();
		$cat        = $categories->addCategory([
			'taxonomy_item' => $global + [
				'taxonomy_id' => $taxonomy_id,
			],
			'taxonomy_item_content' => $global + ['slug' => slugify($name), 'name' => $name, 'content' => ''],
		] + $global);

		return $category_id = $cat['taxonomy_item'];
	}

	/**
	 * Resolve new tag names to taxonomy_item ids, appending them to the post.
	 *
	 * @param array<string,mixed> $post   Post input with optional tag lists.
	 * @param array<string,mixed> $global Global context.
	 *
	 * @return array<string,mixed> Post input with taxonomy_item_id list filled.
	 */
	protected function processEditTags(array $post, array $global): array {
		//process tags
		if (isset($post['tag'])) {
			foreach ($post['tag'] as $listId => $tags) {
				foreach ($tags as $tagId => $tag) {
					//existing tag add to post taxonomy_item list
					if (is_numeric($tagId)) {
						//$post['taxonomy_item_id'][] = $tagId;
					} else {
						//add new taxonomy_item
						$tagId = $this->addEditCategory($listId, $tag, $global);
					}
					$post['taxonomy_item_id'][] = $tagId;
				}
			}
		}

		return $post;
	}

	/**
	 * Flatten custom field input to post_field_value rows.
	 *
	 * @param mixed $fields Field input keyed by language then field id.
	 *
	 * @return list<array<string,mixed>> Field value rows.
	 */
	protected function flattenEditFieldValues(mixed $fields): array {
		$post_field_value = [];

		if (isset($fields) && is_array($fields)) {
			foreach ($fields as $language_id => $fieldList) {
				foreach ($fieldList as $field_id => $value) {
					if (is_array($value)) {
						$value = json_encode($value);
					}

					$post_field_value[] = ['field_id' => $field_id, 'language_id' => $language_id, 'value' => $value];
				}
			}
		}

		return $post_field_value;
	}

	/**
	 * Validate and save post/page content: tags, fields, images, revisions and cache.
	 *
	 * @param string              $object        Object name (post, product, ...).
	 * @param string              $type          Post type.
	 * @param array<string,mixed> $input         Raw post input (request post equivalent).
	 * @param array<string,mixed> $global        Global context.
	 * @param bool                $canEditOthers Whether records of other admins may be edited.
	 * @param bool                $withRevisions Whether to store content revisions.
	 *
	 * @return array{ok: bool, post: array<string,mixed>, post_id: mixed, new: bool, message: string|null, errors: array<array-key, mixed>|null} Save outcome.
	 */
	protected function saveEditData(string $object, string $type, array $input, array $global, bool $canEditOthers, bool $withRevisions): array {
		$post_id          = $input[$object . '_id'] ?? false;
		$validator        = new Validator([$object]);
		$validatorContent = new Validator([$object . '_content']);

		$postContent = $input[$object . '_content'] ?? [];

		if (!is_array($postContent)) {
			$postContent = [];
		}

		if (
			(($errors = $validator->validate($input)) === true) &&
			(($errors = $validatorContent->validate(current($postContent))) === true)
		) {
			$posts = model($object);

			$post = $input;

			foreach ($post[$object . '_content'] as &$content) {
				$content['name']    = strip_tags($content['name']);
				$content['content'] = sanitizeHTML($content['content']);

				if (isset($content['excerpt'])) {
					$content['excerpt'] = sanitizeHTML($content['excerpt']);
				}
			}

			$post = $this->processEditTags($post, $global);

			//process fields
			$post_field_value = $this->flattenEditFieldValues($post['field'] ?? null);

			$site_id = $input['site'] ?? []; //[$global['site_id']];

			$post += $global;
			$new  = false;

			if ($post_id) {
				if (!$canEditOthers) {
					return ['ok' => false, 'post' => $post, 'post_id' => $post_id, 'new' => false, 'message' => null, 'errors' => [__('Permission denied!')]];
				}

				$post[$object . '_id'] = (int)$post_id;
				$data                  = [
					$object              => $post,
					$object . '_id'      => $post_id,
					$object . '_content' => $post[$object . '_content'],
					'taxonomy_item_id'   => $post['taxonomy_item_id'] ?? [],
					'post_field_value'   => $post_field_value,
					'site_id'            => $site_id,
				] + $global;

				$result = $posts->edit($data);

				if ($result >= 0) {
					$message = ucfirst($type) . ' ' . __('saved') . '!';

					$stickyPosts = getSetting('posts', 'sticky') ?? [];

					if (isset($post['sticky']) && !isset($stickyPosts[$post_id])) {
						$stickyPosts[$post_id] = $post_id;
						setSetting('posts', 'sticky', $stickyPosts);
					}

					if (isset($stickyPosts[$post_id]) && !isset($post['sticky'])) {
						unset($stickyPosts[$post_id]);
						setSetting('posts', 'sticky', $stickyPosts);
					}

					if ($withRevisions) {
						$revisions = model($object . '_content_revision');

						foreach ($input[$object . '_content'] as &$content) {
							if (isset($content['has_changes']) && $content['has_changes'] == 1) {
								$revisions->add(['revision' => [
									$object . '_id' => $post_id,
									'language_id'   => $content['language_id'],
									'content'       => $content['content'],
									'created_at'    => date("Y-m-d\TH:i:s", time()),
								] + $global]);
							}
						}
					}
				} else {
					return ['ok' => false, 'post' => $post, 'post_id' => $post_id, 'new' => false, 'message' => null, 'errors' => [$posts->error]];
				}
			} else {
				$post['type'] = $type;

				if (isset($post['updated_at']) && !$post['updated_at']) {
					unset($post['updated_at'], $post[$object . '_id']);
				}

				$add = [
					$object              => $post,
					$object . '_content' => $post[$object . '_content'],
					'taxonomy_item_id'   => $post['taxonomy_item_id'] ?? [],
					'post_field_value'   => $post_field_value,
					'site_id'            => $site_id,
				] + $global;

				$return = $posts->add($add);
				$id     = $return[$object] ?? false;

				if (!$id) {
					return ['ok' => false, 'post' => $post, 'post_id' => $post_id, 'new' => false, 'message' => null, 'errors' => [$posts->error]];
				}

				$post_id = $id;
				$new     = true;

				$message = ucfirst($type) . ' ' . __('saved') . '!';

				if (isset($post['sticky'])) {
					$stickyPosts           = getSetting('posts', 'sticky') ?? [];
					$stickyPosts[$post_id] = $post_id;
					setSetting('posts', 'sticky', $stickyPosts);
				}
			}

			if (isset($post[$object . '_image'])) {
				$publicPath   = \Vvveb\publicUrlPath();
				$productImage = $object . 'Image';

				foreach ($post[$object . '_image'] as &$image) {
					$image = str_replace($publicPath . 'media/', '', $image);
				}

				$posts->$productImage([$object . '_id' => $post_id ? $post_id : 0, $object . '_image' => $post[$object . '_image']]);
			}

			[$post, $post_id, $type] = Event :: trigger(__CLASS__, 'save', $post, $post_id, $type);

			CacheManager::clearObjectCache('component', $object);
			CacheManager::clearObjectCache('component', $object . 's');
			CacheManager::clearPageCache();

			return ['ok' => true, 'post' => $post, 'post_id' => $post_id, 'new' => $new, 'message' => $message ?? null, 'errors' => null];
		}

		return ['ok' => false, 'post' => $input, 'post_id' => $post_id, 'new' => false, 'message' => null, 'errors' => $errors];
	}
}
