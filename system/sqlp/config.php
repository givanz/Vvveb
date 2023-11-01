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

return
[
	//Parsing regexes

	'functionRegex' => '/(CREATE\s+)?PROCEDURE\s+(?<name>\w+)\((?<params>.*?)\)\s+BEGIN\s+(?<statement>.*?)END(?!\s*@)/ms',

	'paramRegex'    => '/(IN|OUT|INOUT|LOCAL)\s+([\.@\w]+)\s*(\w+)?(\(\d+\))?[,$\n\s]?/ms',

	'valuesRegex'   => '/\s*@VALUES\s*\(\s*:(?<list>[\w\[\]]+)\s*\)\s*/ms',

	'eachRegex'     => '/\s*@EACH\s*\(\s*(\w+)\s*\,\s*([\w\.]+)\s*\)\s*/ms',

	'eachVarRegex'  => '/\s*@EACH\s*\(\s*:(.+?)\s*\)\s*/ms',

	'filterRegex'   => '/\s*:?(?<return>[\w_\.]+)?\s*=?\s*@FILTER\s*\(\s*:(?<data>[\w\._]+)\s*\,\s*(?<columns>[\w_]+),?\s*(?<addmissing>true|false)?,?\s*(?<array>true|false)?\s*\)\s*/ms',

	'varRegex'      => '/:(\w+)/ms',

	'importRegex'   => '/import\(([\w\-\_\.\/]+?)\);?/',

	//Generated model templates

	'placeholder' => '?',

	//Lexer
	'tokenMap' => [
		'@IF\s+.+?\s*THEN'  => 'T_IF_START',
		'@ELSE'             => 'T_ELSE',
		'END @IF'           => 'T_IF_END',
		'@KEYS\(.+?\)'      => 'T_KEYS',
		'@LIST\(.+?\)'      => 'T_LIST',
		//'@EACH\(.+?\)'      => 'T_EACH_START',
		//'END @EACH'         => 'T_EACH_END',
		'@SQL_COUNT\(.+?\)' => 'T_SQL_COUNT',
		'@SQL_LIMIT\(.+?\)' => 'T_SQL_LIMIT',
		'.+?'               => 'T_SQL',
	],

	'macroMap' => [
		//if
		'T_IF_START' => [
			'/\s*@IF\s*(?<cond>.+?)\s*THEN\s*/', //regex
			<<<'PHP'
		';
		if ($%cond) {
			$sql .= '
PHP
			,
		],

		'T_ELSE' => <<<'PHP'
				';
			} else {
				$sql .= ' 		
PHP
		,
		'T_IF_END' => '\';
			} //end if
			
			$sql .= \'',
		//end if
		'T_KEYS'      => [
			'/\s*@KEYS\s*\(\s*:(?<keys>[\w_\.]+)\s*\)\s*/ms',
			<<<'PHP'
		';
		$q = $this->db->quote;
		$sql .= $q . implode("$q,$q", array_keys($params['%keys'])); 
		$sql .= $q . ' 
PHP
		],

		'T_LIST'      => [
			'/\s*@LIST\s*\(\s*:(?<list>[\w_\.]+)\s*\)\s*/ms',
			<<<'PHP'
		';
		
			list($_sql, $_params) = $this->db->expandList($params['%list'], '%list');

			$sql .= ' ' . $_sql;

			if (is_array($_params)) $paramTypes = array_merge($paramTypes, $_params);

			$sql .= ' ' . '
PHP
		],

		'T_SQL_COUNT'      => [
			'/\s*@SQL_COUNT\s*\(\s*(?<column>.+?),\s*(?<table>.+?)\s*\)\s*/ms',
			<<<'PHP'
		'; 
		$sql .= $this->db->sqlCount($prevSql, '%column', $this->db->prefix . '%table'); 
		$sql .= '
PHP
		],
		
		'T_SQL_LIMIT'      => [
			'/\s*@SQL_LIMIT\s*\(\s*(?<start>.+?),\s*(?<limit>.+?)\s*\)\s*/ms',
			<<<'PHP'
		'; 
		$sql .= $this->db->sqlLimit('%start', '%limit'); 
		$sql .= '
PHP
		],

		//'T_EACH_START'      => 'EACH()',
		//'T_EACH_END'        => '}',
		'@SQL_COUNT\(.+?\)' => 'T_SQL_COUNT',
		'@SQL_LIMIT\(.+?\)' => 'T_SQL_LIMIT',
		'T_SQL'             => '',
	],
];
