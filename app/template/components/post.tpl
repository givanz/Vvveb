@post  = [data-v-component-post]
@image = [data-v-component-post] [data-v-post-images] [data-v-post-image]

//editor info
@post|data-v-id = $post['post_id']
@post|data-v-type = 'post'

@post|before = <?php

if (isset($post_idx)) $post_idx++; else $post_idx = 0;
$post = $this->_component['post'][$post_idx] ?? [];
?>

//catch all data attributes
@post [data-v-post-*]|innerText = $post['@@__data-v-post-(*)__@@']
@post input[data-v-post-*]|value = $post['@@__data-v-post-(*)__@@']


//manual echo to avoid html escape
@post [data-v-post-content] = <?php if (isset($post['content'])) echo $post['content'];?>
//@post [data-v-post-content] = $post['content']



//featured image
//catch all data attributes
@post [data-v-post-*]|innerText = $post['@@__data-v-post-(*)__@@']
@post img[data-v-post-*]|src = $post['@@__data-v-post-(*)__@@']
@post a[data-v-post-*]|href   = $post['@@__data-v-post-(*)__@@']	
@post [data-v-post-url]|title = $post['name']	


//images
@image|deleteAllButFirstChild
@image|before = <?php
if(isset($post['images']) && is_array($post['images']))
foreach ($post['images'] as $image) { ?>

	@image [data-v-image-src]|src = <?php 
		echo '/image/' . $image['image'];
	?>

	@image|after = <?php 
}
?>