/* taxonomies */
@taxonomy_item = [data-v-taxonomies] [data-v-taxonomy_item]
@taxonomy_item|deleteAllButFirstChild

@taxonomy_item|before = <?php
if(isset($this->taxonomies) && is_array($this->taxonomies)) {
	//$pagination = $this->taxonomies[$_taxonomies_idx]['pagination'];
	foreach ($this->taxonomies as $index => $taxonomy_item) {?>
	
	@taxonomy_item [data-v-taxonomy_item-*]|innerText = $taxonomy_item['@@__data-v-taxonomy_item-(*)__@@']
	@taxonomy_item [data-v-taxonomy_item-*]|title = $taxonomy_item['@@__data-v-taxonomy_item-(*)__@@']
	
	@taxonomy_item [data-taxonomy_id]|data-taxonomy = $taxonomy_item['taxonomy']
	@taxonomy_item [data-taxonomy_id]|data-taxonomy_id = $taxonomy_item['taxonomy_id']
	
	@taxonomy_item input[data-v-post-taxonomy_item-*]|value = $taxonomyItem['@@__data-v-post-taxonomy_item-(*)__@@']
	@taxonomy_item input[data-v-post-taxonomy_item-taxonomy_item_id]|addNewAttribute = <?php if (isset($taxonomyItem['checked']) && $taxonomyItem['checked']) echo 'checked';?>

	@taxonomy_item [data-v-taxonomy_item-url]|title = $taxonomy_item['name']	
	@taxonomy_item a[data-v-edit-url]|href = <?php echo \Vvveb\url(['module' => 'content/taxonomy_item', 'taxonomy_id' => $taxonomyItem['taxonomy_id']]);?>
	
	
	@taxonomy_item|after = <?php 
	} 
}?>


/* categories */
@categories = [data-v-categories] [data-v-cats]
@category = [data-v-categories] [data-v-cats] [data-v-cat]

@categories|deleteAllButFirstChild
@category|deleteAllButFirstChild

@categories|before = <?php
	if ($taxonomy_item['type'] == 'categories') {
	$_categories = $taxonomy_item['taxonomy_item']['categories'] ?? [];
	$generate_menu = function ($parent) use (&$_categories, &$generate_menu, $taxonomy_item) {
		
	$hasChildren = false;	
	foreach($_categories as $id => $taxonomyItem) {
		if ($taxonomyItem['parent_id'] == $parent) {
			$hasChildren = true;
			break;
		}
	}
	if (!$hasChildren) return; 
?>

	@category|before = <?php 
	if ($hasChildren)
	foreach($_categories as $id => $taxonomyItem) {
		if ($taxonomyItem['parent_id'] == $parent) {?>

		//catch all data attributes
		@category [data-v-taxonomy_item-*] = $taxonomyItem['@@__data-v-taxonomy_item-(*)__@@']
		
		@category [data-v-taxonomy_item-url]|href = <?php echo htmlentities(Vvveb\url('product/taxonomy_item/index', $taxonomyItem));?>
		@category [data-v-taxonomy_item-img]|src = $taxonomyItem['images'][0]
				
		@category|append = <?php 
		
		 $generate_menu($taxonomyItem['taxonomy_item_id'], $_categories);
	}?>
	
	@category|after = 
	<?php } ?>

	@categories|after = <?php }; 
	if ($_categories) {
		reset($_categories);
		$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); 
	}
} ?>


/* tags */
@tags = [data-v-categories] [data-v-tags]
@tag = [data-v-categories] [data-v-tags] [data-v-tag]

@tags|deleteAllButFirstChild
@tag|deleteAllButFirstChild

@tags|before = <?php
	if ($taxonomy_item['type'] == 'tags') {
	$_categories = $taxonomy_item['taxonomy_item']['categories'] ?? [];
	$parent = 0;
?>

	@tag|before = <?php 
	foreach($_categories as $id => $taxonomyItem) {
		if (isset($parent) && ($taxonomyItem['parent_id'] == $parent)) {?>

		//catch all data attributes
		@tag [data-v-taxonomy_item-*] = $taxonomyItem['@@__data-v-taxonomy_item-(*)__@@']
		
		@tag [data-v-taxonomy_item-url]|href = <?php echo htmlentities(Vvveb\url('product/taxonomy_item/index', $taxonomyItem));?>
		@tag [data-v-taxonomy_item-img]|src = $taxonomyItem['images'][0]
		@tag [data-v-taxonomy_item-list-name]|name = <?php echo "tag[$taxonomy_item[taxonomy_id]][$taxonomyItem[taxonomy_item_id]]";?>
		@tag [data-v-taxonomy_item-list-name]|value = <?php echo $taxonomyItem['name'];?>
				
		@tag|append = <?php 
	}?>
	
	@tag|after = 
	<?php } ?>

	@tags|after = <?php
} ?>


