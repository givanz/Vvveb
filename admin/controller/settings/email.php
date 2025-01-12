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
use function Vvveb\config;
use Vvveb\Controller\Base;
use function Vvveb\email;
use function Vvveb\setConfig;
use Vvveb\System\Config;
use Vvveb\System\Event;
use Vvveb\System\Validator;

class Email extends Base {
	function test() {
		$to  = $this->request->post['to'] ?? false;

		if ($to) {
			$message = __('This is a test email to confirm that email is properly configured!');

			try {
				$error =  __('Error sending mail!');

				if (email($to, __('Test email'), ['html'=> $message, 'txt' => $message])) {
					$this->view->success[] = sprintf(__('Email sent to %s!'), $to);
				} else {
					//$this->session->set('errors', $error);
					$this->view->errors[] = $error;
				}
			} catch (\Exception $e) {
				$error .= "\n" . $e->getMessage();
				//$this->session->set('errors', $error);
				$this->view->errors[] = $error;
			}
		}

		return $this->index();
	}

	function save() {
		$validator = new Validator(['settings']);
		$settings  = $this->request->post['settings'] ?? false;
		$errors    = [];

		if ($settings /*&&
			($errors = $validator->validate($settings)) === true*/) {
			//$settings              = $validator->filter($settings);
			//$results               = \Vvveb\setMultiSetting('mail',$settings);
			$results               = setConfig('mail',$settings);

			if ($results) {
				$this->view->success[] = __('Settings saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		} else {
			$this->view->errors = $errors;
		}

		$this->index();
	}

	function index() {
		$settings          = config('mail', []);

		$drivers          = ['mail' => 'Mail', 'smtp' => 'Smtp'];
		list($drivers) 	  = Event::trigger(__CLASS__, __FUNCTION__, $drivers);

		$this->view->email = $settings;
		$this->view->drivers = $drivers;
	}
}
