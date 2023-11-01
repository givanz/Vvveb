@post  = [data-v-component-posts] [data-v-post]

@post [data-v-post-categories]|before = <?php 
$categories_count = 0;

$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : [];
$_default = [0 => []];
$_categories = $posts['categories'] ?? $_default : $posts['categories'];
$categories = count($post['categories']);
?>

	@post [data-v-post-categories] [data-v-post-categories-cat]|deleteAllButFirst
	
	@post [data-v-post-categories-cat]|before = <?php 
		foreach ($_categories as $cat){ $categories_count++;?>

		@post [data-v-post-categories] [data-v-post-categories-cat] a[data-v-post-categories-cat-*]|href = $cat['@@__data-v-post-categories-cat-(*)__@@']	
		@post [data-v-post-categories] [data-v-post-categories-cat] [data-v-post-categories-cat-*]|innerText = $cat['@@__data-v-post-categories-cat-(*)__@@']	

	@post [data-v-post-categories] [data-v-post-categories-cat]|after = <?php } ?>
	
@post [data-v-post-categories]|after = <?php }  ?>

@post [data-v-post-tags]|before = <?php 
$categories_count = 0;

$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : [];
$_default = [0 => []];
$_tags = $posts['tags'] ?? $_default : $posts['tags'];
$tags = count($post['tags']);
?>
	@post [data-v-post-tags] [data-v-post-tags-tag]|before = <?php 
		$tags_count = 0;
		if (isset($post['tags'])) {  $tags = count($post['tags']);
		foreach ($post['tags'] as $tag){ $tags_count++;?>

		@post [data-v-post-tags] [data-v-post-tags-tag] a[data-v-post-tags-tag-*]|href = $tag['@@__data-v-post-tags-cat-(*)__@@']	
		@post [data-v-post-tags] [data-v-post-tags-tag] [data-v-post-tags-tag-*]|innerText = $tag['@@__data-v-post-tags-cat-(*)__@@']	

	@post [data-v-post-tags] [data-v-post-tags-tag]|after = <?php } }?>
@post [data-v-post-tags]|after = <?php }  ?>



@post [data-v-post-taxonomy] [data-v-post-tags-tax]|before = <?php 
	$taxonomy_count = 0;
	if (isset($post['taxonomy'])) { $taxonomy = count($post['taxonomy']);
	foreach ($post['taxonomy'] as $tax){ $taxonomy_count++;?>

	@post [data-v-post-taxonomy] [data-v-post-taxonomy-tax] a[data-v-post-taxonomy-tax-*]|href = $tax['@@__data-v-post-taxonomy-cat-(*)__@@']	
	@post [data-v-post-taxonomy] [data-v-post-taxonomy-tax] [data-v-post-taxonomy-tax-*]|innerText = $tax['@@__data-v-post-taxonomy-cat-(*)__@@']	

@post [data-v-post-taxonomy] [data-v-post-taxonomy-tax]|after = <?php } } ?>