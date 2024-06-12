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
class VtplDebug {
	private $enabled = false;

	private $debugLog = [];

	private $debugHtml;

	function enabled() {
		return $this->enabled;
	}

	function enable($switch) {
		$this->enabled = $switch;
	}

	function log($type,$message) {
		if ($this->enabled) {
			$this->debugLog[][$type]= $message;
		}
	}

	function addDebugHtmlLine($command, $parameters, $break = '<br/>') {
		$this->debugHtml .= "<span>&nbsp;<b>$command</b> $parameters</span>$break";
	}

	function debugLogToHtml() {
		foreach ($this->debugLog as $line) {
			$type    = key($line);
			$message = $line[$type];

			switch ($type) {
				case 'LOAD':
					$this->addDebugHtmlLine('LOAD',$message);

				break;

				case 'SAVE':
					$this->addDebugHtmlLine('SAVE',$message);

				break;

				case 'SELECTOR':
					$this->addDebugHtmlLine('SELECTOR',
							   /*$this->cssToXpath($message) . */"<a href='#' 
							onclick=\"return vtpl_selector_click('$message')\" 
							onmouseover=\"return vtpl_selector_over('$message')\"
							onmouseout=\"return vtpl_selector_out('$message')\">
							$message</a>", '');

				break;

				case 'SELECTOR_STRING':
					$this->addDebugHtmlLine('INJECT STRING',$message);

				break;

				case 'SELECTOR_PHP':
					$this->addDebugHtmlLine('INJECT PHP',htmlentities($message));

				break;

				case 'SELECTOR_VARIABLE':
					$this->addDebugHtmlLine('INJECT VARIABLE',$message);

				break;

				case 'SELECTOR_FROM':
					$this->addDebugHtmlLine('EXTERNAL HTML',$message);

				break;

				case 'CSS_XPATH_TRANSFORM':
					if (VTPL_DEBUG_SHOW_XPATH) {
						$this->addDebugHtmlLine('RESULTED XPATH',
								   "<a href='#' 
							onclick=\"return vtpl_selector_click('$message')\" 
							onmouseover=\"return vtpl_selector_over('$message')\"
							onmouseout=\"return vtpl_selector_out('$message')\">
							$message</a>");
					}

				break;

				case 'CSS_SELECTOR':
					$this->addDebugHtmlLine('INVALID CSS SELECTOR',htmlentities($message));

				break;

				default:
					$this->addDebugHtmlLine('',$message);

				break;
				}
		}
	}

	function printLog() {
		$this->debugLogToHtml();
		echo
<<<HTML
<script>
function vtplSelectorOver(selector)
{
	jQuery(selector).addClass('vtpl_selected');
	return false;
}
function vtplSelectorOut(selector)
{
	jQuery(selector).removeClass('vtpl_selected');
	return false;
}

//this needs firebug or equivalent
function vtplSelectorClick(selector)
{
	console.log(jQuery(selector));
	return false;
}

function vtplHide(selector)
{
	if (jQuery(".vtpl_console_log_content").css('display') == 'none')
	{
		jQuery(".vtpl_console_log").css({height:"350px"});
	} else
	{
		jQuery(".vtpl_console_log").css({height:"30px"});
	}
	jQuery(".vtpl_console_log_content").toggle("slow");
	return false;
}

function vtplClose()
{
	jQuery(".vtpl_console_log").remove();
	return false;
}
</script>   	
<style>
.vtpl_selected {
	border:5px solid red !important;        
}
html {
	padding-bottom:350px;
}
.vtpl_console_log  {
	background:#fff;
	z-index:10000;
	position:fixed;
	line-height: 1.6;
	left:0;
	bottom:0;
	width:100%;
	height:300px;
	padding:1rem;
	overflow:auto;
	border:1px dashed #ccc;
}
.vtpl_console_log_content {
	margin-top:1rem;
}
</style>
<div class="vtpl_console_log">
	<a href="#" onclick="return vtplHide()">Toggle</a>
	<a href="#" onclick="return vtplClose()">Close</a>
	<div class="vtpl_console_log_content">
		$this->debugHtml;
	</div>
</div>
HTML;
	}
}
