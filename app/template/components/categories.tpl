@categories = [data-v-component-categories] [data-v-cats]
@category = [data-v-component-categories] [data-v-cats] [data-v-cat]

@categories|deleteAllButFirstChild
@category|deleteAllButFirstChild

@categories|before = <?php
if (isset($_categories_idx)) $_categories_idx++; else $_categories_idx = 0;

$_categories = [];

$previous_component = isset($current_component)?$current_component:null;
$categories = $current_component = $this->_component['categories'][$_categories_idx] ?? [];
$_categories = $categories['categories'] ?? [];

$_pagination_count = $categories['count'] ?? 0;
$_pagination_limit = isset($categories['limit']) ? $categories['limit'] : 5;	
	

if ($_categories) {
$generate_menu = function ($parent) use (&$_categories, &$generate_menu) {
?>
	@category|before = <?php 

	foreach($_categories as $id => $category) {
		if ($category['parent_id'] == $parent)  { ?>

		//catch all data attributes
		@category [data-v-cat-*]|innerText = $category['@@__data-v-cat-(*)__@@']
		
		@category [data-v-cat-url]|href = <?php echo htmlentities(Vvveb\url('content/category/index', $category));?>
		@category [data-v-cat-img]|src = $category['images'][0]
		
		@category|append = <?php 
		 $generate_menu($category['taxonomy_item_id'], $_categories);
		} 
	}
	?>

	@categories|after = <?php 
}; 
reset($_categories);
$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); }
?>