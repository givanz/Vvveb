<?php
%model_start%
/*
Generated from %filename%
*/
namespace Vvveb\Sql%namespace%;

use \Vvveb\System\Db;

class %name%SQL {

	private $db;
	
	public function __construct(){
		$this->db = Db::getInstance();
	}

	%methods_start%
	
	%methodMultipleTemplate_start%
	
	function %name%($params = array()) {
		$paramTypes = %param_types%;

		$results = [];
		$stmt = [];

		%statement%

		if ($results)
		return $results;
	}		
	
	%methodMultipleTemplate_end%
		
	%methods_end%
}
%model_end%

%query_start%
		
		$prevSql = $sql ?? '';
		$sql = '%statement%';
		$sql = trim($sql);
			
		if ($sql) {
			$stmt['%query_id%'] = $this->db->execute($sql, $params, $paramTypes);
			
			$result = $stmt['%query_id%'];
			/*
			if ($stmt['%query_id%']) {
				if (method_exists($stmt['%query_id%'], 'get_result')) {
					$result = $stmt['%query_id%']->get_result();
				} else 	{
					$result = $this->db->get_result($stmt['%query_id%']);
				}
			}
			*/

			if (!empty('%array_key%')) {
				if ($result)
				//while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				while ($row = $this->db->fetchArray($result)) {
					$values = $row;
					if (!empty('%array_value%')) {
						$values = $row['array_value'];
					} 
				
					if ('%query_id%' == '_') {
						$results[$row['array_key']] = $values;
					} else {
						$results['%query_id%'][$row['array_key']] = $values;
						
					}
				}
			} else {
				if ('%query_id%' == '_') {
					$value = %fetch%;
					if (is_array($value)) {
						$results = $results + $value;
					} else {
						$results = $value;
					}
				} else  {
					$results['%query_id%'] = %fetch%;
				}
			}
		}
		
%query_end%
		
%varsTemplate_start%

			if (isset($params['%name%'])) {
				$stmt->bindValue(':%name%', (%type%)$params['%name%'], PDO::PARAM_%type%);
			}
			
%varsTemplate_end%


%insert_id_start%
	$this->db->insert_id
%insert_id_end%

%affected_rows_start%
	$this->db->affected_rows
%affected_rows_end%

//$result->fetchArray(SQLITE3_ASSOC)
%fetch_row_start%
	$this->db->fetchArray($result)
%fetch_row_end%

//$result->fetchArray(SQLITE3_NUM)[0] ?? null
%fetch_one_start%
		$this->db->fetchOne($result)
%fetch_one_end%

%fetch_result_start%
	isset($results['%key%']) ? $results['%key%'] : 'NULL'
%fetch_result_end%

%fetch_all_start%
	$this->db->fetchAll($result)
%fetch_all_end%