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

namespace Vvveb;

/**
 * Converts a CSS selector to an XPath query.
 *
 * @param string $selector The CSS selector to convert.
 *
 * @return string The resulting XPath query.
 */
function cssToXpath(string $selector): string {
	//if already xpath don't transform
	//if (substr_compare($selector,'vtpl_xpath', 0, 11) == 0) return substr($selector, 12, -1);

	$selector = (string)$selector;

	//convert , to | union operator to allow multiple queries
	$selector = str_replace(',', '|', $selector);

	$cssSelector = [
		// E > F: Matches any F element that is a child of an element E
		'/\s*>\s*/',

		// E + F: Matches any F element immediately preceded by an element
		'/\s*\+\s*/',

		// E F: Matches any F element that is a descendant of an E element
		'/([a-zA-Z\*="\[\]#._-])\s+([a-zA-Z\*="\[\]#._-])/', //'/([a-zA-Z\*="\[\]#._-])\s+([a-zA-Z\*#._-])/',

		// E:first-child: Matches element E when E is the first child of its parent
		'/([a-z#\.]\w*):first-child/',

		// E:nth-child() Matches the nth child element
		'/([a-z#\.]\w*):nth-child\((\d+)\)/',

		// E:first: Matches the first element from the set
		'/([a-z#\.]\w*):first/',

		// E:nth(2): Matches the nth element from the set
		'/([a-z#\.]\w*):nth\((\d+)\)/',

		// E[foo="warning"]: Matches any E element whose "foo" attribute value is exactly equal to "warning"
		'/([a-z]\w*)\[([a-z][\w\-_]*)\="([^"]*)"]/',

		// E[foo]: Matches any E element with the "foo" attribute set (whatever the value)
		'/([a-z]\w*)\[([a-z][\w_\-]*)\]/',

		// E[!foo]: Matches any E element without the "foo" attribute set
		'/([a-z]\w*)\[!([a-z][\w\-_]*)\]/',

		// [foo="warning"]: Matches any element whose "foo" attribute value is exactly equal to "warning"
		'/\[([a-z][\w\-_]*)\=\"(.*)\"\]/',

		// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning" and has other attributes
		'/(?<=\])\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',

		// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning"
		'/\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',

		// [foo^="warning"]: Matches any element whose "foo" attribute value begins with the string "warning"
		'/\[([a-z][\w_\-]*)\^\=\"([^"]+)\"\]/',

		// [foo$="warning"]: Matches any element whose "foo" attribute value ends  with the string "warning"
		'/\[([a-z][\w_\-]*)\$\=\"([^"]+)\"\]/',

		// [foo][baz]: Matches any element with the "foo" attribute set (whatever the value)
		'/(?<=\])(?<! )\[([a-z][\w_\-]*)\]/',

		// [foo]: Matches any element with the "foo" attribute set (whatever the value)
		'/\[([a-z][\w_\-]*)\]/',

		// element[foo*]: Matches any element that starts with "foo" attribute (whatever the value)
		'/(\w+)\[([a-z][\w\-]*)\*\]/',

		// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and has other attributes
		'/(?<=\])\[([a-z][\w\-]*)\*\]/',

		// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and is a single attribute
		'/(?<!\])\[([a-z][\w\-]*)\*\]/',

		// div.warn*: HTML only. The same as DIV[class*="warning"]
		'/([a-z]\w*|\*)\.([a-z][\w\-_]*)\*/',

		// div.warning: HTML only. The same as DIV[class~="warning"]
		'/([a-z]\w*|\*)\.([a-z][\w\-_]*)+/',

		// .warn*: HTML only. The same as [class*="warning"]
		'/\.([a-z][\w\-\_]*)\*/',

		// .warning: HTML only. The same as [class~="warning"]
		'/\.([a-z][\w\-\_]*)+/',

		// E#myid: Matches any E element with id-attribute equal to "myid"
		'/([a-z]\w*)\#([a-z][\w\-_]*)/',

		// #myid: Matches any E element with id-attribute equal to "myid"
		'/\#([a-z][\w\-_]*)/',
	];

	$xpathQuery = [
		'/', //element > child
		'/following-sibling::*[1]/self::', // element + precedent
		'\1//\2', //element descendent
		'*[1]/self::\1', //element:first-child
		'*[\2]/self::\1', //element:nth-child(2)
		'\1[1]', //element:first
		'\1[\2]', //element:nth(2)
		'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]', //element[attribute="string"]
		'\1 [ @\2 ]', //element[attribute]
		'\1 [ not(@\2) ]', //element[!attribute]
		'[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo="warning"]
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo="warning"]
		'*[ contains( concat( " ", @\1, " " ), "\2" ) ]', //[foo*="warning"]
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo^="warning"] not implemented
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo$="warning"] not implemented
		'[ @\1 ]', //[attribute][attribute]
		'*[ @\1 ]', //[attribute]
		'\1 [ @*[starts-with(name(), "\2")] ]', //element[attr*]
		'[ @*[starts-with(name(), "\1")] ]', //[attr*] - attribute with other attributes
		'*[ @*[starts-with(name(), "\1")] ]', //[attr*] - single attribute
		'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2") ) ]', //element[class*="string"]
		'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]', //element[class~="string"]
		'*[ contains( concat( " ", @class, " " ), concat( " ", "\1") ) ]', //[class*="string"]
		'*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]', //element[class~="string"]
		'\1[ @id = "\2" ]', //element#id
		'*[ @id = "\1" ]', //#id
	];

	return (string)'//' . preg_replace($cssSelector, $xpathQuery, $selector);
}

/**
 * Imports an HTML string into a DOM document and returns the node.
 *
 * @param string      $html     The HTML string to import.
 * @param string|null $selector An optional CSS selector to find a specific node.
 *
 * @return \DOMNode|null The imported DOM node or null.
 */
function importHtml(string $html, ?string $selector = null): ?\DOMNode {
	$dom                      = new \DOMDocument();
	$dom->preserveWhiteSpace  = true;
	$dom->recover             = true;
	$dom->strictErrorChecking = false;
	$dom->substituteEntities  = false;
	$dom->formatOutput        = false;
	$dom->resolveExternals    = false;
	$dom->validateOnParse     = false;
	$dom->xmlStandalone       = true;
	$dom->loadHTML($html);

	if ($selector) {
		$xpath      = new \DOMXPath($dom);
		$xpathQuery = cssToXpath($selector);
		$result     = $xpath->query($xpathQuery);

		$nodeToImport = $result ? $result->item(0)?->firstChild : null;
	} else {
		$body         = $dom->getElementsByTagName('body');
		$nodeToImport = $body->item(0)?->firstChild;
	}

	return $nodeToImport;
}

/**
 * Converts an HTML string to plain text.
 *
 * @param string $html The HTML string to convert.
 *
 * @return string The plain text result.
 */
function htmlToText(string $html): string {
	$html = preg_replace('/\s+/', ' ', $html) ?? $html;
	$html = str_replace('<br>', "\n", $html);
	//add new line for block elements
	$html = preg_replace('/<(div|p|li|hr|pre|blockquote|section|h\d+)/', "\n<$1", $html) ?? $html;

	return trim(strip_tags($html));
}

/**
 * Wraps double-newline-separated text in paragraph tags.
 *
 * @param string $string The text to wrap.
 *
 * @return string The text wrapped in <p> tags.
 */
function nl2p(string $string): string {
	$paragraphs = '';

	foreach (explode("\n\n", $string) as $line) {
		if (trim($line)) {
			$paragraphs .= "<p>$line</p>";
		}
	}

	return $paragraphs;
}
