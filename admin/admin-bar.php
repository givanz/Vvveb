<?php

/**
 * Vvveb.
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
if (!defined('V_VERSION')) {
	exit('Invalid request!');
}
//if (function_exists('printAdminBarMenu')) return;

use function Vvveb\__;

$menu       = \Vvveb\config('admin-menu', []);
[$menu]     = Vvveb\System\Event::trigger('Vvveb\Controller\Base', 'init-menu', $menu);
[$menu]     = Vvveb\System\Event::trigger('admin-bar', 'menu', $menu);
[$top]      = Vvveb\System\Event::trigger('admin-bar', 'top', []);

$template    = Vvveb\getCurrentTemplate();
$url         = Vvveb\getCurrentUrl();
$admin_path  = Vvveb\adminPath() . 'index.php';
$params      = ['module' => 'editor/page-builder', 'template' => $template, 'url' => $url];
$request     = Vvveb\System\Core\Request::getInstance();
$name        = $request->get['name'] ?? $request->get['slug'] ?? '';
$urlData     = Vvveb\System\Routes::getUrlData($url);
$edit_url    = isset($urlData['edit']) ? $admin_path . $urlData['edit'] : '';

if ($name) {
	$params['name'] = urlencode($name);
}

if (isset($request->get['post_id'])) {
	$params['post_id'] = $request->get['post_id'];

	if ($edit_url) {
		$edit_url .= '&post_id=' . $params['post_id'];
	}
}

if (isset($request->get['product_id'])) {
	$params['product_id'] = $request->get['product_id'];

	if ($edit_url) {
		$edit_url .= '&product_id=' . $params['product_id'];
	}
}

$design_url  = $admin_path . Vvveb\url($params, false, false);
$admin       = \Vvveb\System\User\Admin :: current();
$profile_url = $admin_path . Vvveb\url(['module' => 'admin/user', 'admin_id' => $admin['admin_id']], false, false);

if (!function_exists('printAdminBarIcon')) {
	// Inline Tabler icon from the admin sprite (no font dependency, works on
	// any frontend theme). Unknown ids fall back to the legacy <i> tag.
	function printAdminBarIcon(mixed $id): void {
		static $symbols = null;

		if (!is_string($id) || !preg_match('/^[a-z0-9-]+$/', $id)) {
			return;
		}

		if ($symbols === null) {
			$sprite  = defined('DIR_PUBLIC') ? DIR_PUBLIC . 'admin/default/img/icons.svg' : null;
			$symbols = ($sprite && is_file($sprite)) ? file_get_contents($sprite) : '';
		}

		if ($symbols && preg_match('/<symbol id="' . $id . '"[^>]*>(.*?)<\/symbol>/s', $symbols, $m)) {
			echo '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $m[1] . '</svg>';
		} else {
			echo '<i class="' . $id . '"></i>';
		}
	}
}

if (!function_exists('printAdminBarMenu')) {
	/**
	 * @param array<array-key, mixed> $menu
	 */
	function printAdminBarMenu(array $menu): void {
		/** @var array<int, array<string, mixed>> $menu */
		foreach ($menu as $menuEntry) {
			echo '<li>';

			if (isset($menuEntry['url'])) {
				echo '<a href="' . $menuEntry['url'] . '" ' . (isset($menuEntry['items']) ? 'class="has-submenu"' : '') . '>';

				if (isset($menuEntry['icon']) && $menuEntry['icon']) {
					printAdminBarIcon($menuEntry['icon']);
				} elseif (isset($menuEntry['icon-img']) && $menuEntry['icon-img']) {
					echo '<img src="' . $menuEntry['icon-img'] . '">';
				} else {
					echo '<i></i>';
				}

				echo $menuEntry['name'] . '</a>';
			} else {
				echo '<span>' . $menuEntry['name'] . '</span><hr/>';
			}

			if (isset($menuEntry['items'])) {
				echo '<ul class="submenu">';
				printAdminBarMenu($menuEntry['items']);
				echo '</ul>';
			}

			echo '</li>';
		}
	}
}
?>

<div id="vvveb-admin">

	<ul>
		<li class="v-logo"><a href="https://www.vvveb.com" target="_blank"><div class="vvveb-logo"></div></a>
			<ul>
				<li><a href="https://www.vvveb.com" target="_blank">
						<?php printAdminBarIcon('home'); ?><?php echo __('Vvveb Homepage'); ?>
					</a>
				</li>
				<li>
					<a href="https://docs.vvveb.com" target="_blank">
						<?php printAdminBarIcon('file-text'); ?><?php echo __('Documentation'); ?>
					</a>
				</li>
				<li>
					<a href="https://github.com/givanz/Vvveb/discussions" target="_blank">
						<?php printAdminBarIcon('message'); ?><?php echo __('Forums'); ?>
					</a>
				</li>
			</ul>
		</li>
		<li><a href="<?php echo $admin_path; ?>"><?php printAdminBarIcon('settings'); ?><?php echo __('Admin'); ?></a>
			<ul>
				<?php printAdminBarMenu($menu); ?>
			</ul>
		</li>
		<li>
			<a href="<?php echo $design_url; ?>"> <?php printAdminBarIcon('brush'); ?><?php echo __('Design page'); ?></a>
		</li>
		<?php if ($edit_url) { ?>
		<li>
			<a href="<?php echo $edit_url; ?>"> <?php printAdminBarIcon('pencil'); ?><?php echo __('Edit page'); ?></a>
		</li>
		<?php } ?>
		<li>
			<a href="<?php echo $admin_path . '?module=content/comments&status=0&type=post'; ?>">
			<?php printAdminBarIcon('messages'); ?><?php echo __('Comments'); ?></a>
		</li>
		<li>
			<a href="<?php echo $admin_path; ?>?module=tools/cache&action=delete">
				<?php printAdminBarIcon('rotate-clockwise'); ?>
				<?php echo __('Clear cache'); ?></a>
			<ul>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=asset" target="_blank">
						<?php printAdminBarIcon('file-text'); ?>
						<?php echo __('Frontend assets cache'); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=template" target="_blank">
						<?php printAdminBarIcon('code-off'); ?><?php echo __('Compiled templates'); ?>
					</a>
				</li>
				<li><a href="<?php echo $admin_path; ?>?module=tools/cache&action=database" target="_blank">
					<?php printAdminBarIcon('server'); ?>
					<?php echo __('Database'); ?></a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=image" target="_blank">
						<?php printAdminBarIcon('photo'); ?>
						<?php echo __('Image resize cache'); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=page" target="_blank">
						<?php printAdminBarIcon('file'); ?>
						<?php echo __('Full page cache'); ?>
					</a>
				</li>
				<li class="dropdown-divider">
					<hr />
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=delete" target="_blank">
						<?php printAdminBarIcon('refresh'); ?>
						<?php echo __('All cache'); ?>
					</a>
				</li>
			</ul>

			<?php if ($top) { ?>
				<li><div class="vr align-middle mx-1">&nbsp;</div></li>
			<?php
				printAdminBarMenu($top);
			} ?>
		</li>
	</ul>


	<ul class="float-end">
		<li>
			<a href="<?php echo $admin_path; ?>"><?php printAdminBarIcon('user'); ?><?php echo $admin['display_name'] ?? ''; ?></a>
			<ul style="right:0;">
				 <li><a href="<?php echo $profile_url; ?>"><?php printAdminBarIcon('edit'); ?><?php echo __('Edit profile'); ?></a></li>
				<li>
				<form action="<?php echo $admin_path; ?>?module=user/login"
					method="post" enctype="multipart/form-data" target="_blank">
					<input type="hidden" name="csrf" data-v-csrf>
					<input type="hidden" name="logout">

					<button type="submit" value="logout" class="btn btn-light text-dark w-100">

						<span class="loading d-none">
						  <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
						  </span>
						  <span>Loading ...</span>...
						</span>

						<span class="button-text text-dark">
						  <?php printAdminBarIcon('logout'); ?><?php echo __('Log out'); ?>
						</span>

					  </button>
					</form>
				</li>
			</ul>
		</li>
	</ul>

<link href="<?php echo Vvveb\publicUrlPath(); ?>admin/default/css/admin-bar.css" rel="stylesheet">
<script>
window.addEventListener("load", (event) => {
	if (VvvebTheme.ajax.siteContainer) {
		VvvebTheme.ajax.siteContainer.push("#vvveb-admin");
	}
});
</script>
</div>
