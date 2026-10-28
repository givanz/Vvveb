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
 * Return list of theme folders.
 *
 * @param string|false $theme The theme name, or false to use the current theme.
 *
 * @return array<array-key, mixed> The list of theme folders.
 */
function getThemeFolderList(string|false $theme = false): array {
	$skipFolders    = ['src', 'source', 'backup', 'sections', 'blocks', 'inputs', 'css', 'scss', 'fonts', 'img', 'import', 'node_modules', 'screenshots', 'video', 'js'];

	if (!$theme) {
		$theme = \Vvveb\System\Sites::getTheme(SITE_ID);
	}

	$themeFolder = DIR_THEMES . $theme;
	$files       = glob("$themeFolder/*", GLOB_ONLYDIR) ?: [];
	$pages['/']  = ['name' => '/', 'title' => '/', 'filename' => '/', 'file' => '/', 'path' => '/', 'folder' => $theme];

	foreach ($files as $file) {
		$folder     = preg_replace('@^.*/themes/[^/]+/@', '', $file);
		$filename   = basename($file);

		if (in_array($folder, $skipFolders)) {
			continue;
		}

		$pages[$file]  = ['name' => $filename, 'title' => "/$filename", 'filename' => $filename, 'file' => $filename, 'folder' => $theme];
	}

	return $pages;
}

/**
 * Return list of default template filenames.
 *
 * @return array<array-key, mixed> List of default template filenames.
 */
function getDefaultTemplateList(): array {
	return [
		'index.html',
		'index.coming-soon.html',
		'index.maintenance.html',
		'blank.html',
		'error404.html',
		'error500.html',
		'contact.html',
		'search/index.html',
		'content/post.html',
		'content/page.html',
		'content/index.html',
		'content/category.html',
		'content/tag.html',
		'product/index.html',
		'product/product.html',
		'product/category.html',
	];
}

/**
 * Return list of available templates for a theme.
 *
 * @param string|null             $theme The theme name, or null to use the current theme.
 * @param array<array-key, mixed> $skip  List of folder names to skip.
 *
 * @return array<array-key, mixed> Associative array of templates.
 */
function getTemplateList(?string $theme = null, array $skip = []): array {
	$friendlyNames =  [
		'index'             => ['name' => __('Home page'), 'description' => __('Website homepage'), 'global' => true],
		'index.coming-soon' => ['name' => __('Coming soon'), 'description' => __('Coming soon message page'), 'global' => true],
		'index.maintenance' => ['name' => __('Under maintenance'), 'description' => __('Website under maintenance message page'), 'global' => true],
		'contact'           => ['name' => __('Contact us page'), 'description' => __('Contact us page')],
		'blank'             => ['name' => __('Blank page'), 'description' => __('Template page used for new pages')],
		'product'           => ['name' => __('Product page'), 'description' => __('Used to display a product'), 'editor' => ['template' => 'product'], 'global' => true],
		'error404'          => ['name' => __('Page not found'), 'description' => __('Shows when a page is not available'), 'global' => true],
		'error500'          => ['name' => __('Server error'), 'description' => __('Site error display page'), 'global' => true],
		'content-index'     => ['name' => __('Blog homepage'), 'description' => __('Blog page with latest posts'), 'global' => true],
		'content-post'      => ['name' => __('Blog post'), 'description' => __('Blog post'), 'editor' => ['template' => 'post'], 'global' => true],
		'content-page'      => ['name' => __('Page'), 'description' => __('Page'), 'editor' => ['template' => 'page'], 'global' => true],
		'product-index'     => ['name' => __('Shop page'), 'description' => __('Shop homepage'), 'global' => true],
		'search-index'      => ['name' => __('Search page'), 'description' => __('Search page'), 'global' => true],
		'user-index'        => ['name' => __('Dashboard'), 'description' => __('User dashboard'), 'global' => true],
	];

	$skipFolders    = array_merge(['src', 'source', 'backup', 'sections', 'blocks', 'inputs', 'css', 'scss', 'screenshots', 'locale', 'node_modules'], $skip);

	if (!$theme) {
		$theme = \Vvveb\System\Sites::getTheme(SITE_ID);
	}
	$pages       = [];
	$themeFolder = DIR_THEMES . $theme;
	//$files       = glob("$themeFolder/{,*/*/,*/}*.html", GLOB_BRACE);
	$glob        = ['', '*/*/', '*/'];
	$files       = globBrace($themeFolder . DS, $glob, '*.html');

	foreach ($files as $file) {
		$file     = preg_replace('@^.*[\\\/]themes[\\\/][^\\\/]+[\\\/]@', '', $file);
		$filename = basename($file);

		$folder   = \Vvveb\System\Functions\Str::match('@(\w+)/.*?$@', $file);
		$path     = $folder;
		$folder ??= DS;

		if (in_array($folder, $skipFolders)) {
			continue;
		}
		$name        = $title       = str_replace('.html', '', $filename);
		$description = '';
		$name        = (!empty($folder) && $folder != DS) ? "$folder-$name" : $name;

		if (isset($friendlyNames[$name])) {
			$description = $friendlyNames[$name]['description'];

			$title = $friendlyNames[$name]['name'];
		}

		$url   = PUBLIC_PATH . "themes/$theme/$file";
		$title = str_replace('index', 'home', $title);

		$pages[$name]  = ['name' => $name, 'filename' => $filename, 'file' => $file, 'url' => $url, 'title' => humanReadable($title), 'folder' => $path, 'description' => $description, 'global' => $friendlyNames[$name]['global'] ?? false];

		if (isset($friendlyNames[$name]['editor'])) {
			$pages[$name]['editor'] = $friendlyNames[$name]['editor'];
		}
	}

	//ksort($pages);

	return $pages;
}
