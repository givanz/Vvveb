@archives = [data-v-component-content-archives]
@archive = [data-v-component-content-archives] [data-v-archives] [data-v-archive]

@archive|deleteAllButFirstChild

@archives|before = <?php
//make sure that the instance is unique even if the component is added into a loop inside a compomonent like data-v-posts
$line = __LINE__;
if (isset($_content_archives_idx)){
	if (!isset($_content_archives[$line])) {
		$_content_archives_idx++;
		$_content_archives[$line] = $_content_archives_idx;
	}
} else {
	$_content_archives_idx = 0;
	$_content_archives[$line] = $_content_archives_idx;
}

//if (isset($_content_archives_idx)) $_content_archives_idx++; else $_content_archives_idx = 0;
$_archives = [];


$previous_component = isset($current_component)?$current_component:null;
$content_archives = $current_component = $this->_component['content_archives'][$_content_archives_idx] ?? [];

$_pagination_count = $content_archives['count'] ?? 0;
$_pagination_limit = isset($content_archives['limit']) ? $content_archives['limit'] : 5;
$_archives = $content_archives['archives'] ?? [];
?>

@archive|before = <?php 
foreach($_archives as $id => $archive) {?>

	@archive [data-v-archive-*]|innerText 	= $archive['@@__data-v-archive-(*)__@@']
	@archive a[data-v-archive-*]|href   	= $archive['@@__data-v-archive-(*)__@@']
	@archive option[data-v-archive-*]|value = $archive['@@__data-v-archive-(*)__@@']
	@archive option[data-v-archive]|value   = $archive['url']
	
	@archives option[data-v-archive]|value   = $archive['url']
	@archives option[data-v-archive-*]|value   = $archive['@@__data-v-archive-(*)__@@']

@archive|after = <?php  
	}
?>
