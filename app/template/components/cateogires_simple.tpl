[data-v-component-categories] [data-v-cat]|deleteAllButFirstChild

[data-v-component-categories]|prepend = <?php
if (isset($_categories_idx)) $_categories_idx++; else $_categories_idx = 0;

$_pagination_count = $this->_component['categories'][$_categories_idx]['count'];
//$_pagination_limit = $this->categories[$_categories_idx]['limit'];
?>


[data-v-component-categories]  [data-v-cat]|before = <?php 
if(isset($this->categories) && is_array($this->categories[$_categories_idx]['categories'])) 
{
	//$pagination = $this->categories[$_categories_idx]['pagination'];
	foreach ($this->categories[$_categories_idx]['categories'] as $index => $category) 
	{
	?>

	//catch all data attributes
	[data-v-component-categories] [data-v-cat] [data-v-cat-*] = $category['@@__data-v-cat-(*)__@@']
	
    [data-v-component-categories] [data-v-cat] [data-v-cat-url]|href = <?php echo htmlentities(Vvveb\url('product/category/index', $category));?>
	[data-v-component-categories] [data-v-cat] [data-v-cat-img]|src = $category['images'][0]
	
	
	[data-v-component-categories] [data-v-cat]|after = <?php 
	} 
}
?>
