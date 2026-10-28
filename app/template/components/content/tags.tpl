@tags = [data-v-component-content-tags]
@archive = [data-v-component-content-tags] [data-v-tags] [data-v-archive]

@archive|deleteAllButFirstChild

@tags|before = <?php
//make sure that the instance is unique even if the component is added into a loop inside a compomonent like data-v-posts
$line = __LINE__;
if (isset($_content_tags_idx)){
	if (!isset($_content_tags[$line])) {
		$_content_tags_idx++;
		$_content_tags[$line] = $_content_tags_idx;
	}
} else {
	$_content_tags_idx = 0;
	$_content_tags[$line] = $_content_tags_idx;
}

//if (isset($_content_tags_idx)) $_content_tags_idx++; else $_content_tags_idx = 0;
$_tags = [];


$previous_component = isset($current_component)?$current_component:null;
$content_tags = $current_component = $this->_component['content_tags'][$_content_tags_idx] ?? [];

$_pagination_count = $content_tags['count'] ?? 0;
$_pagination_limit = isset($content_tags['limit']) ? $content_tags['limit'] : 5;
$_tags = $content_tags['tags'] ?? [];
?>

@archive|before = <?php 
foreach($_tags as $id => $archive) {?>

	@archive [data-v-archive-*]|innerText 	= $archive['@@__data-v-archive-(*)__@@']
	@archive a[data-v-archive-*]|href   	= $archive['@@__data-v-archive-(*)__@@']
	@archive option[data-v-archive-*]|value = $archive['@@__data-v-archive-(*)__@@']
	@archive option[data-v-archive]|value   = $archive['url']
	
	@tags option[data-v-archive]|value   = $archive['url']
	@tags option[data-v-archive-*]|value   = $archive['@@__data-v-archive-(*)__@@']

@archive|after = <?php  
	}
?>
