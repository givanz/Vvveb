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
	/**
	 * Ordered list of update step method names.
	 *
	 * @var list<string>
	 */
	private array $steps = ['checkPermissions', 'download', 'unzip', 'copyInstall', 'addNewColumns', 'createNewTables', /*'backup', 'copySystem', 'copyAdmin', 'copyApp', 'copyCore', */ 'copyConfig', 'copyPublic', 'copyPublicAdmin', 'copyPublicMedia', 'moveFolders', 'setFilePermissions', 'cleanUp', 'clearCache', 'complete'];

	/**
	 * Initialize the update system instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->update = new UpdateSys();
	}

	/**
	 * Check for available updates of the specified type.
	 *
	 * @param string $type  The update type to check (e.g. 'core').
	 * @param bool   $force Force check ignoring cache.
	 *
	 * @return array<string, mixed> Update information array.
	 */
	private function checkUpdates(string $type = 'core', bool $force = false): array {
		$updateInfo = [];
		$errorMsg   = __('Error checking updates!');

		try {
			$updateInfo = $this->update->checkUpdates($type, $force);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$updateInfo) {
			$this->view->errors[] = $errorMsg;
		}

		return $updateInfo;
	}

	/**
	 * Check for updates and display the update page.
	 *
	 * @return void
	 */
	public function check(): void {
		$type             = $this->request->get['type'] ?? 'core';
		$updateInfo       = $this->update->checkUpdates($type, true);

		if ($updateInfo) {
			//$this->view->success[] = __('Check succesful!');
			if ($updateInfo['hasUpdate']) {
				$this->view->info[] = __('Updates available!') . '&ensp;<span class="badge bg-success">' . htmlspecialchars((string)$updateInfo['version'], ENT_QUOTES, 'UTF-8') . '</span>';
			} else {
				$this->view->info[] = __('No updates available!');
			}
		}

		$this->index();
	}

	/**
	 * Execute the next update step in the update sequence.
	 *
	 * @return void
	 */
	public function updateNext(): void {
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
			$url += ['module' => 'tools/update', 'action' => 'updateNext', 'step' => $next, 'position' => $position, 'count' => $count];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);
			}
		} else {
			if ($position = count($this->steps)) {
				$message = __('Update finished');

				if ($this->request->isAjax()) {
					exit(json_encode($url + ['url' => url($url), 'success' => $message]));
				} else {
					$this->view->success[] = $message;

					$this->index();
				}
			}
		}
	}

	/**
	 * Download the update package.
	 *
	 * @return bool True if download succeeded, false otherwise.
	 */
	private function download(): bool {
		$updateFile       = false;
		$errorDownloadMsg = __('Error downloading update!');
		$updateInfo       = $this->update->checkUpdates();
		//don't load plugins during update to avoid possible conflicts
		Admin::session(['safemode' => true]);

		if (!$updateInfo || !isset($updateInfo['download'])) {
			$this->view->errors[] = $errorDownloadMsg . ' ' . $updateInfo['error'];

			return false;
		}

		try {
			$updateFile = $this->update->download($updateInfo['download']);
			$this->session->set('updateFile', $updateFile);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$updateFile) {
			$this->view->errors[] = $errorDownloadMsg;

			return false;
		} else {
			$this->view->info[] = $updateInfo['download'] . ' ' . __('downloaded successfully');
		}

		return true;
	}

	/**
	 * Check file and directory permissions for the update.
	 *
	 * @return bool True if permissions are OK, false otherwise.
	 */
	public function checkPermissions(): bool {
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

	/**
	 * Create a backup before applying the update.
	 *
	 * @return bool True if backup succeeded, false otherwise.
	 */
	public function backup(): bool {
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

	/**
	 * Add new database columns introduced by the update.
	 *
	 * @return bool True if columns were added successfully, false otherwise.
	 */
	public function addNewColumns(): bool {
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

	/**
	 * Create new database tables introduced by the update.
	 *
	 * @return bool True if tables were created successfully, false otherwise.
	 */
	public function createNewTables(): bool {
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

	/**
	 * Unzip the downloaded update package.
	 *
	 * @return bool True if unzip succeeded, false otherwise.
	 */
	public function unzip(): bool {
		$errorInstallMsg  = __('Error unzipping update!');
		$result           = true;

		try {
			$updateFile = $this->session->get('updateFile');
			$result     = $this->update->unzip($updateFile);
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Unzip successful!');
		}

		return $result;
	}

	/**
	 * Copy files for the specified update section.
	 *
	 * @param string $method The copy method name (e.g. 'Core', 'Config').
	 *
	 * @return bool True if copy succeeded, false otherwise.
	 */
	private function copy(string $method): bool {
		$name            = humanReadable(camelToUnderscore($method));
		$errorInstallMsg = sprintf(__('Error updating %s'), $name);
		$result          = false;

		$method = "copy$method";

		try {
			$result = $this->update->$method();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = sprintf(__('Update %s successful!'), $name);
		}

		return $result;
	}

	/**
	 * Copy core framework files.
	 *
	 * @return bool
	 */
	public function copyCore(): bool {
		return $this->copy('Core');
	}

	/**
	 * Copy configuration files.
	 *
	 * @return bool
	 */
	public function copyConfig(): bool {
		return $this->copy('Config');
	}

	/**
	 * Copy system and admin files in one step.
	 *
	 * @return bool
	 */
	public function copySystem(): bool {
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
	/**
	 * Copy application files.
	 *
	 * @return bool
	 */
	public function copyApp(): bool {
		return $this->copy('App');
	}

	/**
	 * Copy installation files.
	 *
	 * @return bool
	 */
	public function copyInstall(): bool {
		return $this->copy('Install');
	}

	/**
	 * Copy public-facing files.
	 *
	 * @return bool
	 */
	public function copyPublic(): bool {
		return $this->copy('Public');
	}

	/**
	 * Copy public admin assets.
	 *
	 * @return bool
	 */
	public function copyPublicAdmin(): bool {
		return $this->copy('PublicAdmin');
	}

	/**
	 * Copy public media files.
	 *
	 * @return bool
	 */
	public function copyPublicMedia(): bool {
		return $this->copy('PublicMedia');
	}

	/**
	 * Move new folders and backup existing ones.
	 *
	 * @return bool
	 */
	public function moveFolders(): bool {
		return $this->update->moveFolders();
	}

	/**
	 * Set file permissions after the update.
	 *
	 * @return bool True if permissions were set successfully, false otherwise.
	 */
	public function setFilePermissions(): bool {
		$errorInstallMsg = __('Error setting permissions');
		$result          = false;

		try {
			$result = $this->update->setPermissions();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Set permissions successful!');
		}

		return $result;
	}

	/**
	 * Clean up temporary update files.
	 *
	 * @return bool True if cleanup succeeded, false otherwise.
	 */
	public function cleanUp(): bool {
		$errorInstallMsg = __('Error cleaning up');
		$result          = false;

		try {
			$result = $this->update->cleanup();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Cleanup successful!');
		}

		return $result;
	}

	/**
	 * Clear all caches after the update.
	 *
	 * @return bool True if cache was cleared successfully, false otherwise.
	 */
	public function clearCache(): bool {
		$errorInstallMsg = __('Error clearing cache');
		$result          = false;

		try {
			$result = $this->update->clearCache();
		} catch (\Exception $e) {
			$this->view->errors[] = $e->getMessage();
		}

		if (!$result) {
			$this->view->errors[] = $errorInstallMsg;
		} else {
			$this->view->success[] = __('Clear cache successful!');
		}

		return $result;
	}

	/**
	 * Mark the update as complete and disable safe mode.
	 *
	 * @return bool Always returns true.
	 */
	public function complete(): bool {
		$this->view->success[] = __('Update complete!');
		Admin::session(['safemode' => false]);

		return true;
	}

	/**
	 * Start the update process from the first step.
	 *
	 * @return void
	 */
	public function update(): void {
		$this->request->get['step'] = $this->steps[0];
		$info                       = __('Update in progress, do not close this page ...');
		$this->view->info[]         = $info;

		if ($this->request->isAjax()) {
			$url = ['module' => 'tools/update', 'action' => 'updateNext', 'step' => $this->steps[0], 'position' => 0, 'count' => count($this->steps), 'info' => $info];

			exit(json_encode($url + ['url' => url($url)]));
		} else {
			$this->updateNext();
		}

		$this->index();
	}

	/**
	 * Display the update status page with current version information.
	 *
	 * @return void
	 */
	public function index(): void {
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
