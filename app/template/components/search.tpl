[data-v-component-search] [data-v-product]|deleteAllButFirstChild

[data-v-component-search]|prepend = <?php
if (isset($_search_idx)) $_search_idx++; else $_search_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$search = $current_component = $this->_component['search'][$_search_idx] ?? [];
$searchTabBtnNo = 0;
$searchTabNo = 0;
//$_pagination_count = $search['count'];
//$_pagination_limit = isset($search['limit'])? $search['limit'] : 5;
?>

#nav-search|prepend = <?php
$searchTabBtnNo = 0;
$searchTabNo = 0;
?>

#nav-search .nav-link|addClass = <?php

	if ($searchTabBtnNo++ == 0) echo 'active';
?>

#nav-searchContent .tab-pane|addClass = <?php
	if ($searchTabNo++ == 0) echo 'active';
?>