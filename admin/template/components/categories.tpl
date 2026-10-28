@category = [data-v-component-categories] [data-v-category]
@category|deleteAllButFirstChild

[data-v-component-categories]|prepend = <?php 
if (isset($_categories_idx)) $_categories_idx++; else $_categories_idx = 0;
$categories = $this->_component['categories'][$_categories_idx] ?? [];
$_pagination_count = $categories['count'] ?? 0;
$_pagination_limit = $categories['limit'] ?? 5;
?>


[data-v-component-categories]  [data-v-category]|before = <?php 
if(is_array($categories['categories'])) {
	//$pagination = $categories['pagination'];
	foreach ($categories['categories'] as $index => $category) {?>
	
	@category [data-v-category-name] = $category['name']
	@category [data-v-category-url]|href = <?php echo '/admin?module=category&category_id=' . $category['category_id'];?>


	@category [data-v-img]|src = 
	<?php 
		echo '/image/' .$category['image'];
	?>
	
	
	[data-v-component-categories]  [data-v-category]|after = <?php 
	} 
}
?>
