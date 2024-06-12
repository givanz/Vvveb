@cats  = [data-v-component-content-categories] [data-v-cats]
@cats|deleteAllButFirstChild
@cats [data-v-cat]|deleteAllButFirstChild

@cats|prepend = <?php
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

if (isset($this->content_categories[$_content_categories_idx])) {
	$_pagination_count = $count = $this->content_categories[$_content_categories_idx]['count'] ?? 0;
	//$_pagination_limit = $this->content_categories[$_categories_idx]['limit'];
	$_categories = $this->content_categories[$_content_categories_idx]['categories'] ?? [];
}

$previous_component = isset($current_component)?$current_component:null;
$content_categories = $current_component = $this->_component['content_categories'][$_content_categories_idx] ?? [];

$_pagination_count = $content_categories['count'] ?? 0;
$_pagination_limit = isset($content_categories['limit']) ? $content_categories['limit'] : 5;
$_categories = $content_categories['categories'] ?? [];


if ($_categories) {
$generate_menu = function ($parent) use (&$_categories, &$generate_menu) {
?>
	@cats [data-v-cat]|before = <?php 

	foreach($_categories as $id => $category) {
		if ($category['parent_id'] == $parent)  { 
	?>

		//catch all data attributes
		@cats [data-v-cat] [data-v-cat-*]|innerText = $category['@@__data-v-cat-(*)__@@']
		
		@cats [data-v-cat] [data-v-cat-url]|href = <?php echo htmlentities(Vvveb\url('content/category/index', $category));?>
		@cats [data-v-cat] [data-v-cat-img]|src = $category['images'][0]
		
		@cats [data-v-cat]|after = <?php 
		 $generate_menu($category['taxonomy_item_id'], $_categories);
		} 
	}
	?>

	@cats|append = <?php 
}; 

if ($_categories) {
	reset($_categories);
	$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); }
}
?>
