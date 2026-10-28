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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Sites as SitesList;
use Vvveb\System\User\Admin;

class Sites extends Base {
	/** @var array<int, string> Additional permission URLs required for access */
	protected ?array $additionalPermissionCheck = ['settings/site/add'];

	/**
	 * Add a new site (empty action, redirects to site form).
	 *
	 * @return void
	 */
	public function add(): void {}

	/**
	 * Delete one or more sites.
	 *
	 * @return void
	 */
	public function delete(): void {
		$site_id    = $this->request->post['site_id'] ?? false;

		if ($site_id) {
			$edit_other_sites = Admin::hasCapability('edit_other_sites');

			if (!$edit_other_sites) {
				$access = Admin::siteAccess();
				if (!in_array($site['site_id'], $access)) {
					$this->view->errors[] = sprintf(__('Error deleting %s!'), __(' site'));

					return;
				}
			}

			$active_deleted = false;
			$active_site_id = $this->session->get('site_id');

			if (is_numeric($site_id)) {
				$site_id = [$site_id];
			}

			foreach ($site_id as $id) {
				$site = SitesList::getSiteById($id);

				if ($site) {
					SitesList::deleteSite($site);
				}

				if ($id == $active_site_id) {
					$active_deleted = true;
				}
			}

			$sites         = new SiteSQL();
			$options       = [
				'site_id' => $site_id,
			] + $this->global;

			$result  = $sites->delete($options);

			if ($result && isset($result['site'])) {
				$this->view->success[] = ucfirst(__('site')) . __(' deleted!');
				//if active site was deleted set the next valid site as active
				if ($active_deleted) {
					$this->setSite();
				}
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), __(' site'));
			}
		}

		$this->index();
	}

	/**
	 * Display the list of all sites with pagination.
	 *
	 * @return void
	 */
	public function index(): void {
		$view  = $this->view;
		$sites = new SiteSQL();

		$options = [];

		if (Admin::hasCapability('view_other_sites')) {
			unset($options['site_id']);
		} else {
			$options['site_id'] = Admin :: siteAccess();
		}

		$page    = $this->request->get['page'] ?? 1;
		$limit   = $this->request->get['limit'] ?? 10;

		$results = $sites->getAll(
			$options + [
				'start' => ($page - 1) * $limit,
				'limit' => $limit,
			]
		);

		$sites = SitesList::getSites();

		if (isset($results['site']) && $sites) {
			foreach ($sites as &$site) {
				$site['template-friendly'] = humanReadable(str_replace(['index', '.html'], '', $site['template'][0] ?? ''));
				$site['delete-url']        = \Vvveb\url(['module' => 'settings/sites', 'action' => 'delete', 'site_id[]' => $site['site_id']]);

				if (isset($results['site'][$site['site_id']])) {
					$results['site'][$site['site_id']] += $site;
				}
			}
		}

		$view->sitesList = $results['site'] ?? [];
		$view->count     = $results['count'] ?? 0;
	}
}
