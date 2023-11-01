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

namespace Vvveb\Controller\Tools;

use function Vvveb\__;
use function Vvveb\camelToUnderscore;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\System\Update as UpdateSys;
use function Vvveb\url;

class Update extends Base {
	private $steps = ['download', 'unzip', 'backup', 'copySystem', 'copyApp', 'copyInstall', 'copyAdmin', 'copyCore',  'copyConfig', 'copyPublic', 'copyPublicAdmin', 'copyPublicMedia', 'setPermissions', 'cleanUp', 'clearCache'];

	function __construct() {
		$this->update = new UpdateSys();
	}

	private function checkUpdates($type = 'core', $force = false) {
		$updateInfo = [];
		$errorMsg   = __('Error checking updates!');

		try {
			$updateInfo = $this->update->checkUpdates($type, $force);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $updateInfo) {
			$this->view->errors[] = $errorMsg;
		}

		return $updateInfo;
	}

	function check() {
		$type             = $this->request->get['type'] ?? 'core';
		$updateInfo       = $this->update->checkUpdates($type, true);

		if ($updateInfo) {
			//$this->view->success[] = __('Check succesful!');
			if ($updateInfo['hasUpdate']) {
				$this->view->info[] = __('Updates available!') . '&ensp;<span class="badge bg-success">' . $updateInfo['version'] . '</span>';
			} else {
				$this->view->info[] = __('No updates available!');
			}
		}

		return $this->index();
	}

	function updateNext() {
		ignore_user_abort(true);
		$step = $this->request->get['step'] ?? false;
		$next = false;

		if ($step && (($position = array_search($step, $this->steps)) !== false)) {
			if (method_exists($this, $step) && $this->$step()) {
				$position++;
				$next = $this->steps[$position] ?? false;
			} else {
				$errorMsg             = __('Failed at ') . $step;
				$this->view->errors[] = $errorMsg;
				$this->cleanUp();
			}
		}

		$url = [];

		if (isset($this->view->errors)) {
			$url['error'] = $this->view->errors[0];
		}

		if (isset($this->view->success)) {
			$url['success'] = $this->view->success[0];
		}

		if (isset($this->view->info)) {
			$url['success'] = $this->view->info[0];
		}

		if ($next) {
			$count              = count($this->steps);
			$url['info']        = sprintf(__('Step (%d/%d) %s ...'), $position, $count, humanReadable(camelToUnderscore($next)));
			$this->view->info[] = $url['info'];
			$url += ['module'=>'tools/update', 'action' => 'updateNext', 'step' => $next, 'position' => $position, 'count' => $count];

			if ($this->request->isAjax()) {
				die(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);
			}
		} else {
			if ($position = count($this->steps)) {
				$message = __('Update finished');

				if ($this->request->isAjax()) {
					die(json_encode($url + ['url' => url($url), 'success' => $message]));
				} else {
					$this->view->success[] = $message;

					return $this->index();
				}
			}
		}
	}

	private function download() {
		$updateFile       = false;
		$errorDownloadMsg = __('Error downloading update!');
		$updateInfo       = $this->update->checkUpdates();

		try {
			$updateFile = $this->update->download($updateInfo['download']);
			$this->session->set('updateFile', $updateFile);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $updateInfo || ! $updateFile) {
			$this->view->errors[] = $errorDownloadMsg;

			return false;
		} else {
			$this->view->info[] = $updateInfo['download'] . ' ' . __('downloaded successfully');
		}

		return true;
	}

	function backup() {
		try {
			//$result = $this->update->backup();
			//$install = $this->update->unzip($updateFile);
			$result = true;
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if ($result) {
			$this->view->info[] = __('Backup successful!');
		}

		return $result;
	}

	function unzip() {
		$errorInstallMsg  = __('Error unzipping update!');

		try {
			$updateFile = $this->session->get('updateFile');
			$result     = $this->update->unzip($updateFile);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Unzip successful!');
		}

		return $result;
	}

	function copyCore() {
		$errorInstallMsg = __('Error updating core');

		try {
			$result = $this->update->copyCore();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update core successful!');
		}

		return $result;
	}

	function copyConfig() {
		$errorInstallMsg = __('Error updating config');

		try {
			$result = $this->update->copyConfig();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update config successful!');
		}

		return $result;
	}

	function copySystem() {
		$errorInstallMsg = __('Error updating system');

		try {
			$result = $this->update->copySystem();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update system successful!');
		}

		return $result;
	}

	function copyApp() {
		$errorInstallMsg = __('Error updating app');

		try {
			$result = $this->update->copyApp();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update app successful!');
		}

		return $result;
	}

	function copyAdmin() {
		$errorInstallMsg = __('Error updating system');

		try {
			$result = $this->update->copyAdmin();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update system successful!');
		}

		return $result;
	}

	function copyInstall() {
		$errorInstallMsg = __('Error updating install');

		try {
			$result = $this->update->copyInstall();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update install successful!');
		}

		return $result;
	}

	function copyPublic() {
		$errorInstallMsg = __('Error updating public');

		try {
			$result = $this->update->copyPublic();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update public successful!');
		}

		return $result;
	}

	function copyPublicAdmin() {
		$errorInstallMsg = __('Error updating public admin');

		try {
			$result = $this->update->copyPublicAdmin();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update public admin successful!');
		}

		return $result;
	}

	function copyPublicMedia() {
		$errorInstallMsg = __('Error updating public media');

		try {
			$result = $this->update->copyPublicMedia();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Update public successful!');
		}

		return $result;
	}

	function setPermissions() {
		$errorInstallMsg = __('Error setting permissions');

		try {
			$result = $this->update->setPermissions();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Set permissions successful!');
		}

		return $result;
	}

	function cleanUp() {
		$errorInstallMsg = __('Error cleaning up');

		try {
			$result = $this->update->cleanup();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Cleanup successful!');
		}

		return $result;
	}

	function clearCache() {
		$errorInstallMsg = __('Error clearing cache');

		try {
			$result = $this->update->clearCache();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Clear cache successful!');
		}

		return $result;
	}

	function update() {
		$this->request->get['step'] = $this->steps[0];
		$info                       = __('Update in progress, do not close this page ...');
		$this->view->info[]         = $info;

		if ($this->request->isAjax()) {
			$url = ['module'=>'tools/update', 'action' => 'updateNext', 'step' => $this->steps[0], 'position' => 0, 'count' => count($this->steps), 'info' => $info];

			die(json_encode($url + ['url' => url($url)]));
		} else {
			$this->updateNext();
		}

		return;
		$updateInfo       = $this->update->checkUpdates();
		$errorDownloadMsg = __('Error downloading update!');

		try {
			$updateFile = $this->update->download($updateInfo['download']);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $updateInfo) {
			$this->view->errors[] = $errorDownloadMsg;

			return;
		} else {
			//$this->view->info[] = $updateInfo['download'] . __(' downloaded successfully');
		}

		if ($updateFile) {
			$this->update->setPermissions();

			try {
				$this->update->backup();
				$install = $this->update->install($updateFile);
			} catch (\Exception $e) {
				$this->view->errors[] = $e->getMessage();
			}

			try {
				$this->update->setPermissions();
			} catch (\Exception $e) {
				$this->view->errors[] = $e->getMessage();
			}

			//set maintenance
			if (! $install) {
				$this->view->errors[] = $errorInstallMsg;
			} else {
				$this->view->success[] = __('Update successful!');
			}

			//unset maintenance
		}

		return $this->index();
	}

	function index() {
		$updateInfo = $this->checkUpdates();

		$info = [
			'core' => [
				'current'   => V_VERSION,
				'latest'    => $updateInfo['version'] ?? '',
				'hasUpdate' => $updateInfo['hasUpdate'],
			],
			'themes' => [
				[
					'mytheme' => [
						'current'               => V_VERSION,
						'latest'                => V_VERSION,
					],
					'mytheme2' => [
						'current'               => V_VERSION,
						'latest'                => V_VERSION,
					],
				],
			],
			'plugins' => [
				[
					'myplugin' => [
						'current'               => V_VERSION,
						'latest'                => V_VERSION,
					],
					'myplugin2' => [
						'current'               => V_VERSION,
						'latest'                => V_VERSION,
					],
				],
			],
		];

		$this->view->update = $info;
	}
}
