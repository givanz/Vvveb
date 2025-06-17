@categories = [data-v-component-categories] [data-v-cats]
@category   = [data-v-component-categories] [data-v-cats] [data-v-cat]

@categories|deleteAllButFirstChild
@category|deleteAllButFirstChild

[data-v-component-categories]|prepend = <?php
//make sure that the instance is unique even if the component is added into a loop inside a compomonent like data-v-posts
$line = __LINE__;
if (isset($_categories_idx)){
	if (!isset($_product_categories[$line])) {
		$_categories_idx++;
		$_product_categories[$line] = $_categories_idx;
	}
} else {
	$_categories_idx = 0;
	$_product_categories[$line] = $_categories_idx;
}

$_categories = [];

if (isset($this->_component['categories'][$_categories_idx])) {
	$_pagination_count = $count = $this->_component['categories'][$_categories_idx]['count'] ?? 0;
	//$_pagination_limit = $this->product_categories[$_categories_idx]['limit'];
	$_categories = $this->_component['categories'][$_categories_idx]['categories'] ?? [];
}

$previous_component = isset($current_component)?$current_component:null;
$categories = $current_component = $this->_component['categories'][$_categories_idx] ?? [];
$_categories = $categories['categories'] ?? [];

$_pagination_count = $categories['count'] ?? 0;
$_pagination_limit = isset($categories['limit']) ? $categories['limit'] : 5;	
?>
	
@categories|before = <?php

if ($_categories) {
$generate_menu = function ($parent) use (&$_categories, &$generate_menu) {
?>
	@category|before = <?php 

	foreach($_categories as $id => $category) {
		if ($category['parent_id'] == $parent)  { 
	?>

		//catch all data attributes
		@category [data-v-cat-*]|innerText = $category['@@__data-v-cat-(*)__@@']
		
		@category [data-v-cat-url]|href = $category['url']
		@category [data-v-cat-img]|src  = $category['images'][0]
		
		@category input|id = <?php echo 'm' . $category['taxonomy_item_id'];?>
		@category input|addNewAttribute = <?php if (isset($category['active']) && $category['active']) echo 'checked';?>
		@category label|for = <?php echo 'm' . $category['taxonomy_item_id'];?>

		@category|addClass = <?php if (isset($category['active']) && $category['active']) echo 'active';?>
		
		@category|append = <?php 
		 $generate_menu($category['taxonomy_item_id'], $_categories);
		} 
	}
	?>

	@categories|after = <?php 
}; 

if ($_categories) {
reset($_categories);
$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); }
}
?>
