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
if (! defined('DIR_ROOT')) {
	exit();
}
//if (function_exists('printAdminBarMenu')) return;

use function Vvveb\__;

$menu       = \Vvveb\config('admin-menu', []);
list($menu) = Vvveb\System\Event::trigger('Vvveb\Controller\Base','init-menu', $menu);
list($menu) = Vvveb\System\Event::trigger('admin-bar', 'menu', $menu);
list($top)  = Vvveb\System\Event::trigger('admin-bar', 'top', []);

$template = Vvveb\getCurrentTemplate();
$url      =  Vvveb\getCurrentUrl();
$admin_path  =  Vvveb\adminPath();
$design_url  = $admin_path . Vvveb\url(['module' => 'editor/editor', 'template' => $template, 'url' => $url], false, false);
$urlData     = Vvveb\System\Routes::getUrlData($url);
$edit_url    = isset($urlData['edit']) ? $admin_path . $urlData['edit'] : '';
$admin       = \Vvveb\System\User\Admin :: current();
$profile_url = $admin_path . Vvveb\url(['module' => 'admin/user', 'admin_id' => $admin['admin_id']], false, false);

if (! function_exists('printAdminBarMenu')) {
	function printAdminBarMenu($menu) {
		foreach ($menu as $menuEntry) {
			echo '<li>';

			if (isset($menuEntry['url'])) {
				echo '<a href="' . $menuEntry['url'] . '" ' . (isset($menuEntry['items']) ? 'class="has-submenu"' : '') . '>';

				if (isset($menuEntry['icon']) && $menuEntry['icon']) {
					echo '<i class="' . $menuEntry['icon'] . '"></i>';
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
						<i class="la la-home"></i><?php echo __('Vvveb Homepage'); ?>
					</a>
				</li>
				<li>
					<a href="https://docs.vvveb.com" target="_blank">
						<i class="la la-file-alt"></i><?php echo __('Documentation'); ?>
					</a>
				</li>
				<li>
					<a href="https://github.com/givanz/Vvveb/discussions" target="_blank">
						<i class="la la-sms"></i><?php echo __('Forums'); ?>
					</a>
				</li>
			</ul>
		</li>
		<li><a href="<?php echo $admin_path; ?>"><i class="icon-pulse-outline"></i><?php echo __('Admin'); ?></a>
			<ul>
				<?php printAdminBarMenu($menu); ?>
			</ul>
		</li>
		<li>
			<a href="<?php echo $design_url; ?>"> <i class="la la-paint-brush"></i><?php echo __('Design page'); ?></a>
		</li>
		<?php if ($edit_url) { ?>	
		<li>
			<a href="<?php echo $edit_url; ?>"> <i class="la la-pencil-alt"></i><?php echo __('Edit page'); ?></a>
		</li>
		<?php } ?>
		<li>
			<a href="<?php echo $admin_path . '?module=content/comments&status=0&type=post'; ?>">
			<i class="la la-comments"></i><?php echo __('Comments'); ?></a>
		</li>
		<li>
			<a href="<?php echo $admin_path; ?>?module=tools/cache&action=delete">
				<i class="icon-reload-circle-outline"></i>
				<?php echo __('Clear cache'); ?></a>
			<ul>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=asset" target="_blank">
						<i class="la la-circle-notch"></i>
						<?php echo __('Frontend assets cache'); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=template" target="_blank">
						<i class="la la-circle-notch"></i><?php echo __('Compiled templates'); ?>
					</a>
				</li>
				<li><a href="<?php echo $admin_path; ?>?module=tools/cache&action=database" target="_blank">
					<i class="la la-circle-notch"></i>
					<?php echo __('Database'); ?></a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=image" target="_blank">
						<i class="la la-circle-notch"></i>
						<?php echo __('Image resize cache'); ?>
					</a>
				</li>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=page" target="_blank">
						<i class="la la-circle-notch"></i>
						<?php echo __('Full page cache'); ?>
					</a>
				</li>
				<div class="dropdown-divider">
					<hr />
				</div>
				<li>
					<a href="<?php echo $admin_path; ?>?module=tools/cache&action=delete" target="_blank">
						<i class="la la-circle-notch"></i>
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
			<a href="<?php echo $admin_path; ?>"><i class="la la-user"></i><?php echo $admin['display_name'] ?? ''; ?></a>
			<ul style="right:0;">
				 <li><a href="<?php echo $profile_url; ?>"><i class="la la-edit"></i><?php echo __('Edit profile'); ?></a></li>
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
						  <i class="la la-sign-out-alt"></i><?php echo __('Log out'); ?></a>
						</span>

					  </button>
					</form>
				</li>
			</ul>
		</li>
	</ul>
</div>
