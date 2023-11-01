@categories = [data-v-component-categories-pages] [data-v-cats]
@category   = [data-v-component-categories-pages] [data-v-cats] [data-v-cat]
@posts      = [data-v-component-categories-pages] [data-v-cats] [data-v-cat] [data-v-posts]
@post       = [data-v-component-categories-pages] [data-v-cats] [data-v-cat] [data-v-posts] [data-v-post]

@categories|deleteAllButFirstChild
@category|deleteAllButFirstChild
@post|deleteAllButFirstChild

@categories|before = <?php
if (isset($_categories_pages_idx)) $_categories_pages_idx++; else $_categories_pages_idx = 0;

$_categories = [];

$previous_component = isset($current_component)?$current_component:null;
$categories_pages = $current_component = $this->_component['categories_pages'][$_categories_pages_idx] ?? [];

$_pagination_count = $posts['count'] ?? 0;
$_pagination_limit = isset($posts['limit']) ? $posts['limit'] : 5;
$_categories = $categories_pages['categories'] ?? [];

	
$_pagination_count = $categories_pages['count'] ?? 0;
$_pagination_limit = isset($categories_pages['limit']) ? $categories_pages['limit'] : 5;
$_categories = $categories_pages['categories'] ?? [];

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
		
		
			@post|before = <?php 
			if (isset($category['posts']) && $category['posts'])	
			foreach($category['posts'] as $key => $post) {?>
				//catch all data attributes
				@post [data-v-post-*]|innerText = $post['@@__data-v-post-(*)__@@']
				
				@post a[data-v-post-url]|href = <?php echo htmlentities(Vvveb\url('content/post/index', $post));?>

			@post|after = <?php 
				}
		    ?>
		
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
