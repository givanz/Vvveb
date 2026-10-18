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

namespace Vvveb\Controller\Product;

use Vvveb\Controller\Crud;
use Vvveb\System\Images;
use Vvveb\System\Traits\Media as MediaTrait;
use Vvveb\System\User\Admin;
use function Vvveb\__;
use function Vvveb\model;
use function Vvveb\sanitizeFileName;

class DigitalAsset extends Crud {
	use MediaTrait {
		upload as traitUpload;
		delete as traitDelete;
		rename as traitRename;
		newFolder as traitNewFolder;
	}

	protected string $dirMedia = DIR_STORAGE . 'digital_assets';

	protected string $type = 'digital_asset';

	protected string $controller = 'digital-asset';

	protected string $module = 'product';

	/**
	 * Save the digital asset, checking edit capabilities first.
	 *
	 * @return void
	 */
	public function save(): void {
		$editCapability = 'edit_other_products';

		if (Admin::hasCapability($editCapability)) {
			$this->adminFilter = false;
		}

		parent::save();
	}

	/**
	 * Display the digital asset edit form.
	 *
	 * @return void
	 */
	public function index(): void {
		$editCapability = 'view_other_products';

		if (Admin::hasCapability($editCapability)) {
			$this->adminFilter = false;
		}

		parent::index();

		$adminPath = \Vvveb\adminPath();

		$controllerPath        = $adminPath . 'index.php?module=product/digital-asset';
		$this->setMediaEndpoints($controllerPath);

		if ($this->view->digital_asset) {
			$this->view->digital_asset['image']     = $this->view->digital_asset['file'];
			$this->view->digital_asset['image_url'] = Images::image($this->view->digital_asset['image'], 'digital_asset', 'thumb');
		}
	}

	/**
	 * Get the file names that belong to the current user.
	 *
	 * Digital assets are stored in a single shared directory, so file actions
	 * arrive with an attacker controlled file name that could match any
	 * vendor's asset. The digital_asset rows (each with the author's admin_id)
	 * are used to decide which files the current user owns.
	 *
	 * @return array<string, true> Owned file names indexed by name.
	 */
	private function ownedFiles(): array {
		static $owned = null;

		if ($owned !== null) {
			return $owned;
		}

		$owned = [];

		if (! empty($this->global['admin_id'])) {
			$results = model($this->type)->getAll([
				'admin_id'    => $this->global['admin_id'],
				'language_id' => $this->global['language_id'] ?? 0,
				'start'       => 0,
				'limit'       => 99999999,
			]);

			foreach ((array) ($results[$this->type] ?? []) as $asset) {
				if (! empty($asset['file'])) {
					$owned[basename((string) $asset['file'])] = true;
				}
			}
		}

		return $owned;
	}

	/**
	 * Whether the current user can act on files inside the digital assets store.
	 *
	 * Vendors without `edit_other_products` can only act on files that belong
	 * to them. Digital assets live at the root of the store, so any name with
	 * a directory separator (vendor created subdirectories) is rejected.
	 *
	 * @param array<string> $files File names to check.
	 * @param bool          $mustExist Whether the names must already belong to the user (rename/delete) or just must not conflict with other users' files (upload).
	 *
	 * @return bool True when all the files are allowed.
	 */
	private function canManageFiles(array $files, bool $mustExist = true): bool {
		//users with cross vendor capability manage all the digital assets
		if (Admin::hasCapability('edit_other_products')) {
			return true;
		}

		$owned = $this->ownedFiles();

		foreach ($files as $file) {
			$file = sanitizeFileName((string) $file);

			if (! $file || strpbrk($file, '/\\') !== false) {
				return false;
			}

			$file   = basename($file);
			$isMine = isset($owned[$file]);

			if ($mustExist && ! $isMine) {
				return false;
			}

			//don't allow overwriting files that are not owned by the current user
			if (! $mustExist && ! $isMine && file_exists($this->dirMedia . DS . $file)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Refuse the request.
	 *
	 * @return void
	 */
	private function denyAccess(): void {
		$this->response->setType('json');
		$this->response->output(['success' => false, 'message' => __('Access denied!')]);
	}

	/**
	 * Upload digital assets, checking ownership first.
	 *
	 * @return void
	 */
	public function upload(): void {
		//vendors can only upload assets at the root of the asset store
		if (((string) ($this->request->post['mediaPath'] ?? '') !== '') && ! Admin::hasCapability('edit_other_products')) {
			$this->denyAccess();

			return;
		}

		$files = $this->request->files['files'] ?? [];
		$names = [];

		if (is_array($files)) {
			$fileNames = $files['name'] ?? [];

			if (! is_array($fileNames)) {
				$fileNames = [$fileNames];
			}

			foreach ($fileNames as $name) {
				$name = sanitizeFileName((string) $name);

				if ($name) {
					$names[] = $name;
				}
			}
		}

		if (! $this->canManageFiles($names, false)) {
			$this->denyAccess();

			return;
		}

		$this->traitUpload();
	}

	/**
	 * Delete digital assets, checking ownership first.
	 *
	 * @return void
	 */
	public function delete(): void {
		/** @var string|array<int, string> $file */
		$file  = $this->request->post['file'] ?? [];
		$files = is_array($file) ? array_values($file) : [$file];

		if (! $this->canManageFiles($files)) {
			$this->denyAccess();

			return;
		}

		$this->traitDelete();
	}

	/**
	 * Rename digital assets, checking ownership of both source and target first.
	 *
	 * @return void
	 */
	public function rename(): void {
		$file    = (string) ($this->request->post['file'] ?? '');
		$newfile = (string) ($this->request->post['newfile'] ?? '');
		$newname = (string) ($this->request->post['newname'] ?? '');

		//the source file must belong to the current user
		if (! $this->canManageFiles([$file])) {
			$this->denyAccess();

			return;
		}

		//the target must not overwrite a file that belongs to another user
		$target = $newname ?: $newfile;

		if ($target && ! Admin::hasCapability('edit_other_products')) {
			$target       = basename(sanitizeFileName($target));
			$isMine       = isset($this->ownedFiles()[$target]);
			$targetExists = file_exists($this->dirMedia . DS . $target);
			$duplicate    = (bool) ($this->request->post['duplicate'] ?? false);

			if ($targetExists && ! $isMine) {
				if (! $duplicate) {
					$this->denyAccess();

					return;
				}

				//copies get a random fresh name instead of overwriting other users' files
				$target = random_int(0, 100000) . '-' . $target;
			}

			if ($newname) {
				$this->request->post['newname'] = $target;
			}

			if ($newfile) {
				$this->request->post['newfile'] = $target;
			}
		}

		$this->traitRename();
	}

	/**
	 * Only users with cross vendor capability can create folders
	 * since the asset store is shared between vendors.
	 *
	 * @return void
	 */
	public function newFolder(): void {
		if (! Admin::hasCapability('edit_other_products')) {
			$this->denyAccess();

			return;
		}

		$this->traitNewFolder();
	}
}
