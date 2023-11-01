import(common.tpl)
import(pagination.tpl)


[data-v-posts] [data-v-post]|deleteAllButFirstChild


[data-v-posts]  [data-v-post]|before = <?php
if(isset($this->posts) && is_array($this->posts)) 
{
	//$pagination = $this->posts[$_posts_idx]['pagination'];
	foreach ($this->posts as $index => $post) 
	{
	?>
	
	[data-v-posts] [data-v-post] [data-v-name] = $post['name']
	[data-v-posts] [data-v-post] [data-v-content] = $post['content']
	[data-v-posts] [data-v-post] [data-v-warranty] = $post['warranty']
	[data-v-posts] [data-v-post] [data-v-stock] = $post['stock']
	[data-v-posts] [data-v-post] [data-v-sku] = $post['sku']
	[data-v-posts] [data-v-post] [data-v-weight] = $post['weight']
	[data-v-posts] [data-v-post] [data-v-sales] = $post['sales']
	[data-v-posts] [data-v-post] [data-v-id] = $post['post_id']

	[data-v-posts] [data-v-post] [data-v-price] = $post['price']
	[data-v-posts] [data-v-post] [data-v-promotional_price] = $post['promotional_price']
	[data-v-posts] [data-v-post] [data-v-selling_price] = $post['selling_price']


	[data-v-posts] [data-v-post] [data-v-img]|src = 
	<?php 
		echo 'http://anne2.givan.ro/image/' .$post['image'];
		//echo htmlentities(str_replace('large', '@@__class:image_([a-zA-Z_]+)__@@', $post['images'][$post['main_image']]['url']));
	?>

	
	[data-v-posts] [data-v-post] [data-v-post-cart-url]|href = <?php echo htmlentities(Vvveb\url(['module' => 'content/post', 'post_id' => $post['post_id']]));?>
	
	[data-v-posts] [data-v-post] [data-v-url]|href =<?php echo htmlentities(Vvveb\url(['module' => 'content/post', 'post_id' => $post['post_id']]));?>
	[data-v-posts] [data-v-post] [data-v-url]|title = $post['title']	
	
	[data-v-posts] [data-v-post] [data-v-category] = $post['category'];
	[data-v-posts] [data-v-post] [data-v-manufacturer] = $post['manufacturer'];
	
	
	[data-v-posts]  [data-v-post]|after = <?php 
	} 
}?>



