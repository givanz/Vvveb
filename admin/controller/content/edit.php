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

namespace Vvveb\Controller\Content;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\Sql\Field_groupSQL;
use Vvveb\Sql\FieldSQL;
use Vvveb\System\Cache;
use Vvveb\System\Sites;
use Vvveb\System\Traits\Edit as EditTrait;
use Vvveb\System\Traits\FieldGroup;
use Vvveb\System\User\Admin;

class Edit extends Base {
	use AutocompleteTrait;
	use EditTrait;
	use FieldGroup;
	use SitesTrait;
	use TaxonomiesTrait;
	protected string $type = 'post';

	protected string $object = 'post';

	protected bool $revisions = true;

	/**
	 * Returns the theme folder path for the current site.
	 *
	 * @return string Theme folder path.
	 */
	protected function getThemeFolder(): string {
		return DIR_THEMES . (Sites::getTheme($this->global['site_id']));
	}

	/**
	 * Loads custom field groups and renders them for the editor view.
	 *
	 * @return void
	 */
	protected function fields(): void {
		$options        = ['type' => $this->object, 'subtype' => $this->type, 'limit' => 1000];
		$fieldGroup     = new Field_groupSQL();
		$fieldGroups    = $fieldGroup->getAll($options + $this->global)['field_group'] ?? [];

		$langsFields    = [];
		$field_group_id = [];

		foreach ($fieldGroups as $group) {
			$field_group_id[$group['field_group_id']] = $group['field_group_id'];
		}

		if ($field_group_id) {
			$post_id = $this->request->get[$this->object . '_id'] ?? false;

			$field       = new FieldSQL();
			$fields	     = $field->getAll(['field_group_id' => $field_group_id, $this->object . '_id' => $post_id, 'limit' => 1000] + $this->global);
			$langsFields = [];

			if ($fields['count'] > 0) {
				foreach ($fields['field'] as &$field) {
					$input['settings']     = json_decode($field['settings'], true);
					$input['validation']   = json_decode($field['validation'], true);
					$input['presentation'] = json_decode($field['presentation'], true);

					$field['class']        = 'row mb-3';
					$field['label-class']  = 'form-label';
					$field['label']        = $input['settings']['label'] ?? '';
					$field['instructions'] = $input['presentation']['instructions'] ?? '';
					$field['placeholder']  = $input['settings']['placeholder'] ?? '';

					foreach (['value', 'default'] as $key) {
						if (isset($field[$key]) && $field[$key]) {
							if ($field[$key][0] == '{') {
								$field[$key] = json_decode($field[$key], true);
							}

							$field['value'] = $field[$key];

							break;
						}
					}

					foreach ($this->view->languagesList as $code => $lang) {
						$field['name']   = 'field[' . $lang['language_id'] . '][' . $field['field_id'] . ']';
						$field['field']  = $this->renderFields2([$field]);

						$groupName                                       = $fieldGroups[$field['field_group_id']]['name'];
						$langsFields[$lang['language_id']][$groupName][] = $field;
					}
				}
			}
		}
		$this->view->fields = $langsFields;
		$this->view->count  = $fields['count'] ?? 0;
	}

	/**
	 * Main index action - loads post data, templates, revisions, taxonomies, and sites for the editor.
	 *
	 * @return void
	 */
	public function index(): void {
		$view = $this->view;

		$admin_path = \Vvveb\adminPath();

		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";
		$view->linkUrl   = $admin_path . 'index.php?module=content/post&action=urlAutocomplete';
		$theme           = Sites::getTheme($this->global['site_id']);
		$view->themeCss  = '';

		if (file_exists(DIR_THEMES . "$theme/css/admin-post-editor.css")) {
			$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/admin-post-editor.css";
		} else {
			if (file_exists(DIR_THEMES . "$theme/css/style.css")) {
				$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/style.css";
			}
		}

		foreach (['custom.css', 'fonts.css'] as $css) {
			if (file_exists(DIR_THEMES . "$theme/css/$css")) {
				$view->themeCss .= ',' . PUBLIC_PATH . "themes/$theme/css/$css";
			}
		}
		//$view->themeCss        = PUBLIC_PATH . "themes/$theme/css/style.css";

		$viewCapability = 'view_other_posts';

		if ($this->object == 'product') {
			$viewCapability = 'view_other_products';
		}

		$postId = $this->request->get[$this->object . '_id'] ?? $this->request->post[$this->object . '_id'] ?? false;

		[$post, $postId, $type, $name, $revisionsUrl] = $this->getEditPostData(
			$this->object,
			$this->type,
			$postId,
			$this->request->get['slug'] ?? false,
			$this->request->get['type'] ?? null,
			$this->global,
			$this->view->languagesList ?? [],
			Admin::hasCapability($viewCapability)
		);

		$this->type = $type;

		if (!$post) {
			$message = sprintf(__('%s not found!'), humanReadable(__($this->type)));
			$this->notFound(['message' => $message, 'title' => $message]);
		}

		$type_name  = humanReadable(__($this->type));

		$defaultTemplate = \Vvveb\getCurrentTemplate();
		$template        = isset($post['template']) && $post['template'] ? $post['template'] : $defaultTemplate;
		$themeFolder     = $this->getThemeFolder();

		$design_url = '';

		if (isset($post['url'])) {
			$design_url         = \Vvveb\url(['module' => 'editor/page-builder', 'name' => urlencode($name),  'url' => $post['relative-url'], 'template' => $template], false);
			$post['design_url'] = $design_url;
		}

		$view->template_missing = '';

		if (!file_exists($themeFolder . DS . $template)) {
			if ($template == $defaultTemplate) {
				$view->template_missing = sprintf(__('Template missing, choose existing template or %screate global template%s for %s.'), '<a href="' . $design_url . '" target="_blank">', '</a>', $type_name);
			} else {
				$view->template_missing = sprintf(__('Template %s missing, %screate template%s for this %s.'), '<b>' . $template . '</b>', '<a href="' . $design_url . '" target="_blank">', '</a>', $type_name);
			}
		}

		$view->taxonomies = $this->taxonomies($post[$this->object . '_id'] ?? false);

		if ($this->type == 'page') {
			//remove menu type
			unset($view->taxonomies[3]);
		}

		$sites = $post[$this->object . '_to_site'] ?? [];

		if ($sites) {
			$sites = array_keys($sites);
		} else {
			if (!$postId) {
				$sites[] = $this->global['site_id'];
			}
		}

		$view->sitesList = $this->sites($sites);

		$object          = $this->object;
		$view->$object   = $post;
		$view->status    = ['publish' => 'Publish', 'draft' => 'Draft', 'pending' => 'Pending', 'private' => 'Private', 'scheduled' => 'Scheduled'];

		$view->templates = Cache::getInstance()->cache(APP, 'template-list-' . $theme, fn () => \Vvveb\getTemplateList($theme, ['email']), 604800);

		$view->themeFonts = Cache::getInstance()->cache(APP, 'fonts-list-' . $theme, function() use ($theme) {
			$fonts = \Vvveb\System\Media\Font::themeFonts($theme);
			$names = [];

			if ($fonts) {
				foreach ($fonts as $font) {
					if (isset($font['font-family'])) {
						$names[$font['font-family']] = null;
					}
				}
			}

			return $names;
		}, 604800);

		$this->fields();

		//$validator                 = new Validator([$this->object]);
		//$view->validatorJson       = $validator->getJSON();
		$view->type           = __($this->type);
		$view->type_name      = $type_name;
		$view->posts_list_url =  \Vvveb\url(['module' => $this->list, 'type' => $this->type]);
		$view->revisions_url  =  $revisionsUrl;
		$view->oEmbedProxyUrl = $admin_path . 'index.php?module=editor/page-builder&action=oEmbedProxy';
	}

	/**
	 * Alias for save().
	 *
	 * @return void
	 */
	public function add(): void {
		$this->save();
	}

	/**
	 * Saves post/page content from the request via the Edit trait.
	 *
	 * Maps request input to saveEditData() and its result to view output.
	 *
	 * @return void
	 */
	public function save(): void {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		$post_id                       = $this->request->get[$this->object . '_id'] ?? $this->request->post[$this->object . '_id'] ?? false;
		$this->{$this->object . '_id'} = $post_id;

		$editCapability = 'edit_other_posts';

		if ($this->object == 'product') {
			$editCapability = 'edit_other_products';
		}

		$result = $this->saveEditData($this->object, $this->type, $this->request->post, $this->global, Admin::hasCapability($editCapability), $this->revisions);

		if (!$result['ok']) {
			$this->view->errors = $result['errors'];

			return;
		}

		$this->view->success['get'] = $result['message'];

		if ($result['new']) {
			$this->request->get[$this->object . '_id'] = $result['post_id'];

			$this->redirect(['module' => $this->module, $this->object . '_id' => $result['post_id'], 'type' => $this->type, 'success' => $result['message']], [], false);
		}
	}
}
