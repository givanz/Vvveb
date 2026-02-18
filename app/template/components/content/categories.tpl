@categories = [data-v-component-content-categories] [data-v-cats]
@category   = [data-v-component-content-categories] [data-v-cat]

@categories|deleteAllButFirstChild
@category|deleteAllButFirstChild

[data-v-component-content-categories]|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
//make sure that the instance is unique even if the component is added into a loop inside a compomonent like data-v-posts
$line = __LINE__;
if (isset($_content_categories_idx)){
	if (!isset($_content_categories[$line])) {
		$_content_categories_idx++;
		$_content_categories[$line] = $_content_categories_idx;
	}
} else {
	$_content_categories_idx = 0;
	$_content_categories[$line] = $_content_categories_idx;
}

$_categories = [];

if (isset($this->_component['content_categories'][$_content_categories_idx])) {
	$_pagination_count = $count = $this->_component['content_categories'][$_content_categories_idx]['count'] ?? 0;
	//$_pagination_limit = $this->content_categories[$_content_categories_idx]['limit'];
	$_categories = $this->_component['content_categories'][$_content_categories_idx]['categories'] ?? [];
}

$previous_component = isset($current_component)?$current_component:null;
$categories = $current_component = $this->_component['content_categories'][$_content_categories_idx] ?? [];
$_categories = $categories['categories'] ?? [];

$_pagination_count = $categories['count'] ?? 0;
$_pagination_limit = isset($categories['limit']) ? $categories['limit'] : 5;
$parent            = 0;	
$generate_menu     = false;
?>
	
@categories|before = <?php

if ($_categories) {
$generate_menu = function ($parent) use (&$_categories, &$generate_menu, $vvveb_is_page_edit) {
?>
	@category|before = <?php 

	foreach($_categories as $id => $category) {
		if ($category['parent_id'] == $parent)  { 
	?>

		//catch all data attributes
		@category [data-v-cat-*]|innerText = $category['@@__data-v-cat-(*)__@@']
		@category img[data-v-cat-*]|src = $category['@@__data-v-cat-(*)__@@']
		
		@category [data-v-cat-url]|href = $category['url']
		@category [data-v-cat-img]|src  = $category['images'][0]
		
		@category input|id = <?php echo 'm' . $category['taxonomy_item_id'];?>
		@category input|addNewAttribute = <?php if (isset($category['active']) && $category['active']) echo 'checked';?>
		@category label|for = <?php echo 'm' . $category['taxonomy_item_id'];?>

		@category|addClass = <?php if (isset($category['active']) && $category['active']) echo 'active';?>
		
		@category|append = <?php 
		 if ($generate_menu) $generate_menu($category['taxonomy_item_id'], $_categories);
		?>
		
		@category|after = <?php 
		 // $generate_menu($category['taxonomy_item_id'], $_categories);
		} 
	}
	?>

	@categories|after = <?php 
}; 

if ($_categories) {
	reset($_categories);
	$parent = $_categories[key($_categories)]['parent_id'] ?? 0;
	$generate_menu($parent, $_categories); }
}
?>
