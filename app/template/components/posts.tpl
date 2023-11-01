//set selector prefix to have shorter and easier to read selectors for rules
@posts = [data-v-component-posts]
@post  = [data-v-component-posts] [data-v-post]

//editor info
@post|data-v-id = $post['post_id']
@post|data-v-type = 'post'

//search
@posts [data-v-search] = $posts['search']

@posts|prepend = <?php
	if (isset($_posts_idx)) $_posts_idx++; else $_posts_idx = 0;
	$previous_component = isset($current_component)?$current_component:null;
	$posts = $current_component = $this->_component['posts'][$_posts_idx] ?? [];

	$count = $posts['count'] ?? 0;
	$limit = isset($posts['limit']) ? $posts['limit'] : 5;
?>

@post|deleteAllButFirstChild

@posts [data-v-posts-category] = <?php $_category = current($posts['posts']);echo $_category['category'];?>
@posts [data-v-posts-count] = <?php echo $posts['count'] ?? ''?>


@post|before = <?php 
//if no posts available and page is loaded in editor then set an empty post to show post content for the editor
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : [];
$_default = [0 => []];
$_posts = empty($posts['posts']) ? $_default : $posts['posts'];
//$pagination = $this->posts[$_posts_idx]['pagination'];
$count = 0;
foreach ($_posts as $index => $post) {?>

	//editor attributes

	//@post [data-v-post-excerpt] = $post['excerpt']

    //catch all data attributes
    @post [data-v-post-*]|innerText = $post['@@__data-v-post-(*)__@@']
    @post img[data-v-post-*]|src = <?php
		$image = $post['@@__data-v-post-(*)__@@'] ?? '';
		$size = '@@__data-v-size__@@';
		if ($size) {
			//$image = Vvveb\System\Images::size($image, $size);
			echo $image;
		} else {
			echo $image;
		}
	?>
	

	//@post [data-v-post-img]|src = <?php echo $post['images'][0] ?? '';?>
	
	@post [data-v-post-url-text]	= $post['url']	
	@post a[data-v-post-*]|href   	= $post['@@__data-v-post-(*)__@@']	
	@post [data-v-post-url]|title	= $post['name']	
	
	@post [data-v-post-content] = <?php if (isset($post['content'])) echo($post['content']);?>
	@post [data-v-post-excerpt] = <?php if (isset($post['excerpt'])) echo($post['excerpt']);?>
	
	@post|after = <?php 
	$count++;
}

$current_component = $previous_component;
?>

//taxonomies 

@post [data-v-categories]|before = <?php 
$categories_count = 0;

$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : [];
//$_default = [0 => []];
$_categories = $post['categories'] ?? $_default;
$categories = count($_categories);
?>

	@post [data-v-categories] [data-v-categories-cat]|deleteAllButFirstChild
	
	@post [data-v-categories-cat]|before = <?php 
		foreach ($_categories as $cat){ $categories_count++;?>

		@post [data-v-categories] [data-v-categories-cat] a[data-v-categories-cat-*]|href = $cat['@@__data-v-categories-cat-(*)__@@']	
		@post [data-v-categories] [data-v-categories-cat] [data-v-categories-cat-*]|innerText = $cat['@@__data-v-categories-cat-(*)__@@']	

	@post [data-v-categories] [data-v-categories-cat]|after = <?php } ?>
	

@post [data-v-tags]|before = <?php 
$tags_count = 0;

$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : [];
//$_default = [0 => []];
$_tags = $post['tags'] ?? $_default;
$tags = count($_tags);
?>

	@post [data-v-tags] [data-v-tags-tag]|deleteAllButFirstChild
	
	@post [data-v-tags] [data-v-tags-tag]|before = <?php 
		foreach ($_tags as $tag){ $tags_count++;?>

		@post [data-v-tags] [data-v-tags-tag] a[data-v-tags-tag-*]|href = $tag['@@__data-v-tags-tag-(*)__@@']	
		@post [data-v-tags] [data-v-tags-tag] [data-v-tags-tag-*]|innerText = $tag['@@__data-v-tags-tag-(*)__@@']	

	@post [data-v-tags] [data-v-tags-tag]|after = <?php } ?>



@post [data-v-taxonomy] [data-v-tags-tax]|before = <?php 
	$taxonomy_count = 0;
	if (isset($post['taxonomy'])) { $taxonomy = count($post['taxonomy']);
	foreach ($post['taxonomy'] as $tax){ $taxonomy_count++;?>

	@post [data-v-taxonomy] [data-v-taxonomy-tax] a[data-v-taxonomy-tax-*]|href = $tax['@@__data-v-taxonomy-tax-(*)__@@']	
	@post [data-v-taxonomy] [data-v-taxonomy-tax] [data-v-taxonomy-tax-*]|innerText = $tax['@@__data-v-taxonomy-tax-(*)__@@']	

@post [data-v-taxonomy] [data-v-taxonomy-tax]|after = <?php } } ?>