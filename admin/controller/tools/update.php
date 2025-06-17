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
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Update extends Base {
	private $steps = ['checkPermissions', 'download', 'unzip', 'copyInstall', /*'backup',*/ 'copySystem', /* 'copyAdmin',*/  'copyApp', 'copyCore', 'copyConfig', 'addNewColumns', 'createNewTables', 'copyPublic', 'copyPublicAdmin', 'copyPublicMedia', 'setFilePermissions', 'cleanUp', 'clearCache', 'complete'];

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

		if (isset($this->view->errors[0])) {
			$url['error'] = $this->view->errors[0];
		}

		if (isset($this->view->success[0])) {
			$url['success'] = $this->view->success[0];
		}

		if (isset($this->view->info[0])) {
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
		//don't load plugins during update to avoid possible conflicts
		Admin::session(['safemode' => true]);

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

	function checkPermissions() {
		$result = false;

		try {
			$result = $this->update->checkPermissions();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if ($result === true) {
			$this->view->info[] = __('Permissions ok!');
		} else {
			$this->view->errors[] = sprintf(__('%s not writable!'), $result);

			return false;
		}

		return $result;
	}

	function backup() {
		$result = false;

		try {
			$result = $this->update->backup();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if ($result) {
			$this->view->info[] = __('Backup successful!');
		}

		return $result;
	}

	function addNewColumns() {
		$result = false;

		try {
			$result = $this->update->addNewColumns();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if ($result) {
			$this->view->info[] = __('Add new columns successful!');
		}

		return $result;
	}

	function createNewTables() {
		$result = false;

		try {
			$result = $this->update->createNewTables();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if ($result) {
			$this->view->info[] = __('Create new tables successful!');
		}

		return $result;
	}

	function unzip() {
		$errorInstallMsg  = __('Error unzipping update!');
		$result           = true;

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

	private function copy($method) {
		$name            = humanReadable(camelToUnderscore($method));
		$errorInstallMsg = sprintf(__('Error updating %s'), $name);
		$result          = false;

		$method = "copy$method";

		try {
			$result = $this->update->$method();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (! $result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = sprintf(__('Update %s successful!'), $name);
		}

		return $result;
	}

	function copyCore() {
		return $this->copy('Core');
	}

	function copyConfig() {
		return $this->copy('Config');
	}

	function copySystem() {
		//merge system and admin in one step so that the updater does not break if depends on older system code.
		$return = $this->copy('System');

		if ($return) {
			$return = $this->copy('Admin');
		}

		return $return;
	}

	/*
	function copyAdmin() {
		return $this->copy('Admin');
	}
	*/
	function copyApp() {
		return $this->copy('App');
	}

	function copyInstall() {
		return $this->copy('Install');
	}

	function copyPublic() {
		return $this->copy('Public');
	}

	function copyPublicAdmin() {
		return $this->copy('PublicAdmin');
	}

	function copyPublicMedia() {
		return $this->copy('PublicMedia');
	}

	function setFilePermissions() {
		$errorInstallMsg = __('Error setting permissions');
		$result          = false;

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
		$result          = false;

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
		$result          = false;

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

	function complete() {
		$this->view->success[] = __('Update complete!');
		Admin::session(['safemode' => false]);

		return true;
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

		return $this->index();
	}

	function index() {
		$updateInfo = $this->checkUpdates();

		if (isset($updateInfo['error']) && $updateInfo['error']) {
			$this->view->errors[] = $updateInfo['error'];
		}

		$info = [
			'core' => [
				'current'   => V_VERSION,
				'latest'    => $updateInfo['version'] ?? '',
				'hasUpdate' => $updateInfo['hasUpdate'] ?? false,
			],
		];

		$this->view->update = $info;
	}
}
