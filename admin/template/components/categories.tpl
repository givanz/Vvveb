@category = [data-v-component-categories] [data-v-category]
@category|deleteAllButFirstChild

[data-v-component-categories]|prepend = <?php 
if (isset($_categories_idx)) $_categories_idx++; else $_categories_idx = 0;

$_pagination_count = $this->categories[$_categories_idx]['count'];
$_pagination_limit = $this->categories[$_categories_idx]['limit'];
?>


[data-v-component-categories]  [data-v-category]|before = <?php 
if(isset($this->_component['categories']) && is_array($this->_component['categories'][$_categories_idx]['categories'])) 
{
	//$pagination = $this->categories[$_categories_idx]['pagination'];
	foreach ($this->categories[$_categories_idx]['categories'] as $index => $category) 
	{
	?>
	
	@category [data-v-category-name] = $category['name']
	@category [data-v-category-url]|href = <?php echo '/admin?module=category&category_id=' . $category['category_id'];?>


	@category [data-v-img]|src = 
	<?php 
		echo '/image/' .$category['image'];
		//echo htmlentities(str_replace('large', '@@__class:image_([a-zA-Z_]+)__@@', $category['images'][$category['main_image']]['url']));
	?>
	
	
	[data-v-component-categories]  [data-v-category]|after = <?php 
	} 
}
?>
