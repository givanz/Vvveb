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
	/**
	 * @var mixed Database driver object
	 */
	private mixed $db;

	/**
	 * @var \DOMDocument
	 */
	private \DOMDocument $dom;

	/**
	 * @var \DOMXPath
	 */
	private \DOMXPath $xpath;

	/**
	 * @var array<array-key, mixed>
	 */
	private array $idsMap;

	/**
	 * @var int
	 */
	private int $importXMLOptions = LIBXML_NOBLANKS |
		LIBXML_COMPACT |
		LIBXML_NOCDATA |
		LIBXML_PARSEHUGE |
		LIBXML_NOWARNING |
		LIBXML_BIGLINES;

	/**
	 * @var array<array-key, mixed>
	 */
	private array $tableOrder = [];

	/**
	 * @var array<array-key, mixed>
	 */
	private array $cDataColumns = ['content', 'excerpt', 'name', 'title', 'description', 'meta_title', 'meta_description', 'slug'];

	/**
	 * @param string $driver
	 *
	 * @return void
	 */
	public function __construct(string $driver = DB_ENGINE) {
		$engine        = '\Vvveb\System\Db\\' . ucfirst($driver);

		$this->db = new $engine();

		$this->tableOrder = include 'table-order.php';
	}

	/**
	 * Get all table names for the database.
	 *
	 * @param string $db
	 *
	 * @return array<array-key, mixed>
	 */
	public function getTableNames(string $db = DB_NAME): array {
		return $this->db->getTableNames();
	}

	/**
	 * @param array<array-key, mixed> $tables
	 *
	 * @return string
	 */
	public function export(array $tables = []): string {
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
			$q     = $this->db->quote;

			$stmt   = $this->db->query("SELECT * FROM $q$tableName$q");

			while ($row = $this->db->fetchArray($stmt)) {
				$item = $this->dom->createElement('item');

				foreach ($row as $key => $value) {
					$isString = is_string($value);

					if ($value === null) {
						$value = 'NULL';
					}

					if ($value === 0) {
						$value = '0';
					}

					if (!$value && $value !== '0') {
						$value = '';
					}

					if (in_array($key, $this->cDataColumns) || $isString) {
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

	/**
	 * @param \LibXMLError            $error
	 * @param array<array-key, mixed> $xml
	 *
	 * @return string
	 */
	public function displayXmlError(\LibXMLError $error, array $xml): string {
		$return  = ($xml[$error->line - 1] ?? '') . "\n";
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

	/**
	 * @param string                  $xml
	 * @param array<array-key, mixed> $tables
	 *
	 * @return bool
	 */
	public function import(string $xml, array $tables = []): bool {
		if ($tables) {
			//sort tables by table order, unknown tables go last
			$tableOrder = array_flip($this->tableOrder);
			usort($tables, fn ($left, $right) => ($tableOrder[$left] ?? PHP_INT_MAX) <=> ($tableOrder[$right] ?? PHP_INT_MAX));
		} else {
			$tables = $this->tableOrder;
		}

		$this->dom                      = new \DOMDocument('1.0', 'utf-8');
		$this->dom->formatOutput        = false;
		$this->dom->preserveWhiteSpace  = false;
		$this->dom->strictErrorChecking = false;
		libxml_use_internal_errors(true);
		$result = $this->dom->loadXML($xml, $this->importXMLOptions);

		if (!$result) {
			$error = libxml_get_last_error();

			if ($error === false) {
				throw new \Exception('Unable to load XML content, check if valid XML!', 1);
			}

			$message = $this->displayXmlError($error, explode("\n", $xml));

			throw new \Exception(sprintf("Unable to load XML content, check if valid XML!\n\t%s", $message), 1);
		}

		$this->dom->normalize();

		$this->xpath  = new \DOMXPath($this->dom);

		$domTables       = $this->xpath->query('//root/database/*');

		//map ids
		foreach ($domTables as $table) {
			$tableName = $table->nodeName;
			$rows      = $table->childNodes;

			if ($rows->length) {
				foreach ($rows as $row) {
					$columns = $row->childNodes;

					if ($columns->length) {
						foreach ($columns as $column) {
							//if ($column->nodeName == '#text') continue;
							$columnName  = $column->nodeName;
							$columnValue = $column->nodeValue;

							$data[$columnName] = $columnValue;
							$idColumn          = $tableName . '_id';

							if ($columnName == $idColumn) {
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

		$q = $this->db->quote;

		foreach ($tables as $tableName) {
			$rows       = $this->xpath->query("//root/database/$tableName/item");

			if ($rows) {
				foreach ($rows as $row) {
					$columns = $row->childNodes;

					$update            = '';
					$data              = [];
					$idColumn          = $tableName . '_id';
					$idValue           = null;

					foreach ($columns as $column) {
						$columnName  = $column->nodeName;
						$columnValue = $column->nodeValue;

						if ($columnName == 'parent_id' && $columnValue > 0) {
							//self refrence table id
							$oldId       = $columnValue;
							$columnValue = $this->idsMap[$idColumn][$columnValue] ?? $columnValue;
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
							$update .= "$q$columnName$q = :$columnName";
						}
					}

					$cols   = implode("$q, $q", array_keys($data));
					$values = implode(', :', array_keys($data));

					$duplicate = "ON DUPLICATE KEY UPDATE $update\n\n";

					if (DB_ENGINE == 'sqlite' || DB_ENGINE == 'pgsql') {
						//$duplicate = "ON CONFLICT($q$cols$q) DO UPDATE SET $update\n\n";
						$duplicate = '';
					}

					$sql = "INSERT INTO $q$tableName$q ($q$cols$q) VALUES (:$values)" . $duplicate;

					$stmt   = $this->db->execute($sql, $data);
					$lastId = $this->db->insert_id;

					if ($idValue !== null && $idColumn != 'site_id' && $idColumn != 'language_id') {
						$this->idsMap[$idColumn][$idValue] = $lastId;
					}

					//$result = $stmt->get_result();
				}
			}
		}

		return true;
	}
}
