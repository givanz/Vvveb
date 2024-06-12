<?php

%T_IF_START_start%
		';
		if ($%cond) {
			$sql .= '
%T_IF_START_end%

%T_ELSE_start%
		';
		} else {
			$sql .= ' 	
%T_ELSE_end%

%T_IF_END_start%
		';
		} //end if
		
		$sql .= '
%T_IF_END_end%

%T_KEYS_start%
	';
		$sql .= '`' . implode('`,`', array_keys($params['%keys'])); 
		$sql .= '` 
%T_KEYS_end%


%T_SQL_COUNT_start%
	'; 
		$sql .= $this->db->sqlCount($prevSql, '%column', $this->db->prefix . '%table'); 
		$sql .= '
%T_SQL_COUNT_end%

%T_SQL_LIMIT_start%
	'; 
		$sql .= $this->db->sqlLimit('%start', '%limit'); 
		$sql .= '
%T_SQL_LIMIT_end%

%T_LIST_start%
';
		
	list($_sql, $_params) = $this->db->expandList($params['%list'], '%list');

	$sql .= ' ' . $_sql;

	if (is_array($_params)) $paramTypes = array_merge($paramTypes, $_params);

	$sql .= ' ' .
%T_LIST_end%
