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

namespace Vvveb\System\Import;

#[\AllowDynamicProperties]
class Xml {
	private $db;

	private  $dom;

	private  $xpath;

	private $importXMLOptions = LIBXML_NOBLANKS |
								LIBXML_COMPACT |
								LIBXML_NOCDATA |
								LIBXML_NOENT |
								LIBXML_NONET |
								LIBXML_PARSEHUGE |
								LIBXML_NOWARNING |
								LIBXML_BIGLINES;

	private $tableOrder = [];

	private $cDataColumns = ['content', 'excerpt', 'name', 'title', 'description', 'meta_title', 'meta_description', 'slug'];

	function __construct($driver = DB_ENGINE, $host = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS, $prefix = DB_PREFIX) {
		$this->sqlPath = DIR_ROOT . "install/sql/$driver/";
		$engine        = '\Vvveb\System\Db\\' . ucfirst($driver);

		$this->prefix = $prefix;

		try {
			$this->db = new $engine($host, $dbname, $user, $pass, $prefix);
		} catch (\Exception $e) {
			//unknown database, try to create
			if ($e->getCode() == 1049) {
				$this->db = new $engine($host, '', $user, $pass, $prefix);
				$this->createDb($dbname);
			} else {
				throw($e);
			}
		}

		$this->tableOrder = include 'table-order.php';
	}

	/*
	 * Get all table names for the database
	 */
	function getTableNames($db = DB_NAME) {
		$sql = "SELECT table_name 
			FROM information_schema.tables 
			WHERE table_schema = '$db' ORDER BY table_name";

		$stmt   = $this->db->execute($sql, [], []);
		$result = $stmt->get_result();

		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$tables[] = $row['table_name'];
		}

		return $tables;
	}

	function export($tables = []) {
		$this->dom                     = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput       = true;
		$this->dom->preserveWhiteSpace = false;

		if (empty($tables)) {
			$tables = $this->getTableNames();
		}

		$root         = $this->dom->createElement('root');
		$database     = $this->dom->createElement('database');

		foreach ($tables as $tableName) {
			$table = $this->dom->createElement($tableName);

			$stmt   = $this->db->execute("SELECT * FROM `$tableName`", [], []);
			$result = $stmt->get_result();

			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
				$item = $this->dom->createElement('item');

				foreach ($row as $key => $value) {
					$isString = is_string($value);

					if ($value === NULL) {
						$value = 'NULL';
					}

					if ($value === 0) {
						$value = '0';
					}

					if (! $value) {
						$value = '';
					}

					if (in_array($key,$this->cDataColumns) || $isString) {
						//$element = $this->dom->createElement($key, $value);

						$cdata   = $this->dom->createCDataSection($value);
						$element = $this->dom->createElement($key);
						$element->appendChild($cdata);
					} else {
						$element = $this->dom->createElement($key, $value);
					}

					$item->appendChild($element);
				}
				$table->appendChild($item);
			}

			if ($table->hasChildNodes()) {
				$database->appendChild($table);
			}
		}

		$root->appendChild($database);
		$this->dom->appendChild($root);

		return $this->dom->saveXML();
	}

	function display_xml_error($error, $xml) {
		$return  = $xml[$error->line - 1] . "\n";
		$return .= str_repeat('-', $error->column) . "^\n";

		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";

				break;

			 case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";

				break;

			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";

				break;
		}

		$return .= trim($error->message) .
				   "\n  Line: $error->line" .
				   "\n  Column: $error->column";

		if ($error->file) {
			$return .= "\n  File: $error->file";
		}

		return "$return\n\n--------------------------------------------\n\n";
	}

	function import($xml, $tables = []) {
		if ($tables) {
			//sort tables by table order
			$tableOrder = array_flip($this->tableOrder);
			usort($tables, function ($left, $right) use ($tableOrder) {
				return  $tableOrder[$left] <=> $tableOrder[$right];
			});
		} else {
			$tables = $this->tableOrder;
		}

		$this->dom                      = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput        = false;
		$this->dom->preserveWhiteSpace  = false;
		$this->dom->strictErrorChecking = false;
		libxml_use_internal_errors(true);
		$result = $this->dom->loadXML($xml, $this->importXMLOptions);

		if (! $result) {
			$error   = libxml_get_last_error();
			$message = $this->display_xml_error($error, explode("\n", $xml));

			throw new \Exception(sprintf("Unable to load XML content, check if valid XML!\n\t%s", $message), 1);

			return;
		}

		$this->dom->normalize();

		$this->xpath  = new \DOMXpath($this->dom);

		$domTables       = $this->xpath->query('//root/database/*');

		//map ids
		foreach ($domTables as $table) {
			$tableName = $table->nodeName;
			//continue;
			$rows = $table->childNodes;

			if (! is_null($rows)) {
				foreach ($rows as $row) {
					$columns = $row->childNodes;

					if ($columns) {
						foreach ($columns as $column) {
							//if ($column->nodeName == '#text') continue;
							$columnName  = $column->nodeName;
							$columnValue = $column->nodeValue;

							$data[$columnName] = $columnValue;
							$idColumn          = $tableName . '_id';

							if ($columnName == $idColumn) {
								//$this->idsMap[$idColumn][$columnValue] = 0;
								$this->idsMap[$idColumn][$columnValue] = $columnValue;
							}
						}
					}
				}
			}
		}

		if (DB_ENGINE == 'mysqli') {
			$stmt   = $this->db->execute('SET sql_mode = "";');
		}

		foreach ($tables as $tableName) {
			$rows       = $this->xpath->query("//root/database/$tableName/item");

			if (! is_null($rows)) {
				foreach ($rows as $row) {
					$columns = $row->childNodes;

					$update            = '';
					$data              = [];
					$idColumn          = $tableName . '_id';

					foreach ($columns as $column) {
						//if ($column->nodeName == '#text') continue;
						$columnName  = $column->nodeName;
						$columnValue = $column->nodeValue;

						if ($columnName == 'parent_id' && $columnValue > 0) {
							//self refrence table id
							$oldId       = $columnValue;
							$columnValue = $this->idsMap[$idColumn][$columnValue];
						}

						if ($columnName == $idColumn) {
							$idValue                           = $columnValue;
							$this->idsMap[$idColumn][$idValue] = $idValue;
						} else {
							if (isset($this->idsMap[$columnName]) && $columnValue > 0) {
								$oldId       = $columnValue;

								if (isset($this->idsMap[$columnName][$columnValue])) {
									$columnValue = $this->idsMap[$columnName][$columnValue];
								} else {
									error_log("$tableName - $columnName with value `$columnValue` does not map, probably missing data");
								}
							}

							$data[$columnName] = $columnValue;

							if ($update) {
								$update .= ',';
							}
							$update .= "`$columnName` = :$columnName";
						}
					}

					$cols   = implode('`,`',array_keys($data));
					$values = implode(',:',array_keys($data));

					$sql = "INSERT INTO `$tableName` (`$cols`) VALUES (:$values)" .
							"ON DUPLICATE KEY UPDATE $update\n\n";

					$stmt   = $this->db->execute($sql, $data);
					$lastId = $this->db->insert_id;

					if ($idColumn != 'site_id' && $idColumn != 'language_id') {
						$this->idsMap[$idColumn][$idValue] = $lastId;
					}

					$result = $stmt->get_result();
				}
			}
		}

		return true;
	}

	/*
	function importLegacy($tables = []) {
		return;
		$stmt   = $this->db->execute('SET sql_mode = "";');

		foreach ($tables as $table) {
			$rows       = $this->xpath->query("//root/database/$tableName/item");

			foreach ($rows as $row) {
				$columns = $row->childNodes;

				$update = '';
				$data   = [];

				foreach ($columns as $column) {
					//if ($column->nodeName == '#text') continue;
					$columnName  = $column->nodeName;
					$columnValue = $column->nodeValue;

					$data[$columnName] = $columnValue;

					if ($update) {
						$update .= ',';
					}
					$update .= "`$columnName` = :$columnName";

					$idColumn          = $tableName . '_id';

					if ($columnName == $idColumn) {
						$this->idsMap[$idColumn][$columnValue] = 0;
					}
				}

				$cols   = implode('`,`',array_keys($data));
				$values = implode(',:',array_keys($data));

				$sql = "INSERT INTO `$tableName` (`$cols`) VALUES (:$values)" .
						"ON DUPLICATE KEY UPDATE $update\n\n";
				//echo $sql;

				$stmt   = $this->db->execute($sql, $data);
				$lastId = $this->db->insert_id;
				$result = $stmt->get_result();

				//break;
			}
		}
		
		return true;
	}
	*/
}
