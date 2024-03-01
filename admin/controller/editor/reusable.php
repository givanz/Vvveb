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

namespace Vvveb\Controller\Editor;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use function Vvveb\sanitizeFileName;
use function Vvveb\slugify;
use Vvveb\System\Sites;

class Reusable extends Base {
	private $sectionTemplate = <<<TPL

Vvveb.Sections.add("reusable/{name}", {
    name: "{title}",
    //image: Vvveb.themeBaseUrl + "/../../media/img/logo-small.png",
	image: "img/logo-small.png",
    html: `{content}`
});
Vvveb.SectionsGroup['Reusable'].push("reusable/{name}");

TPL;

	private $blockTemplate = <<<TPL

Vvveb.Blocks.add("reusable/{name}", {
    name: "{title}",
    //image: Vvveb.themeBaseUrl + "/../../media/img/logo-small.png",
	image: "img/logo-small.png",
    html: `{content}`
});
Vvveb.BlocksGroup['Reusable'].push("reusable/{name}");

TPL;

	private function getThemeFolder() {
		return DIR_THEMES . ($this->request->get['theme'] ?? Sites::getTheme() ?? 'default');
	}

	private function regenerate($type) {
		$themeFolder = $this->getThemeFolder();

		$folder      = $themeFolder . DS . $type . 's' . DS;
		$htmlFolder  = $folder . 'reusable' . DS;
		$template    = ($type == 'section') ? $this->sectionTemplate : $this->blockTemplate;

		$js = '';

		foreach (scandir($htmlFolder) as $file) {
			if (strpos($file, '.html') === false) {
				continue;
			}
			$name    = str_replace('.html','', $file);
			$title   = humanReadable($name);
			$content = file_get_contents($htmlFolder . DS . $file);

			$data     = compact('name', 'title', 'content');
			$reusable = $template;

			foreach ($data as $key => $value) {
				$reusable = str_replace('{' . $key . '}', $value, $reusable);
			}

			$js .= $reusable;
		}

		$jsFile    = $folder . $type . 's.js';
		$typeName  = ucfirst($type);
		$jsContent = '';

		if (file_exists($jsFile)) {
			$jsContent = file_get_contents($jsFile);

			//remove old reusable
			$regex =  '\s*Vvveb\.\w+?\.add\([\'"]reusable.+?`\s*\}\);?|' .
					 '\s*Vvveb\.\w+?Group\[["\']Reusable["\']\]\s*\.push\([^\)]+\);?\s*|' .
					 '\s*Vvveb\.\w+?Group\[["\']Reusable["\']\]\s*=\s*\[[^\]]*?\];?\s*';

			$jsContent = preg_replace("/$regex/ms", '', $jsContent);
		}

		//append new reusable
		$jsContent = $jsContent . "\n\nVvveb." . $typeName . "sGroup[\"Reusable\"] = [];\n" . $js;

		return file_put_contents($jsFile, $jsContent);
	}

	function save() {
		$name    = slugify(sanitizeFileName($this->request->post['name']));
		$type    = $this->request->post['type'];
		$html    = $this->request->post['html'];
		$message = ['success' => false, 'message' => __('Error saving!')];

		if (($type == 'section' || $type == 'block') && $html) {
			$themeFolder = $this->getThemeFolder();
			$folder      = $themeFolder . DS . $type . 's' . DS . 'reusable' . DS;
			$file        = "$name.html";

			@mkdir($folder, 0755 & ~umask(), true);

			if (file_put_contents($folder . $file, $html)) {
				if ($this->regenerate($type)) {
					$message = ['success' => true, 'message' => __('Element saved!')];
				}
			} else {
			}
		}

		$this->response->setType('json');
		$this->response->output($message);
	}
}
