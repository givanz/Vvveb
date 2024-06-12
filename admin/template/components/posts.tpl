@post = [data-v-component-posts] [data-v-post]
@post|deleteAllButFirstChild

[data-v-component-posts]|prepend = <?php
if (isset($_posts_idx)) $_posts_idx++; else $_posts_idx = 0;

$_pagination_count = $this->_component['posts'][$_posts_idx]['count'];
$_pagination_limit = isset($this->_component['posts'][$_posts_idx]['limit'])? $this->_component['posts'][$_posts_idx]['limit'] : 5;
?>

[data-v-component-posts] [data-v-category] = <?php $_category = current($this->_component['posts'][$_posts_idx]['posts']);echo $_category['category'];?>
[data-v-component-posts] [data-v-manufacturer] = <?php $_manufacturer = current($this->_component['posts'][$_posts_idx]['posts']);echo $_manufacturer['manufacturer'];?>


//$post = [data-v-component-posts]  [data-v-post]

[data-v-component-posts]  [data-v-post]|before = <?php 
if(isset($this->_component['posts']) && is_array($this->_component['posts'][$_posts_idx]['posts'])) 
{
	//$pagination = $this->posts[$_posts_idx]['pagination'];
	foreach ($this->_component['posts'][$_posts_idx]['posts'] as $index => $post) 
	{
	?>
	
	@post [data-v-name] = <?php echo $post['name'];?>
	@post [data-v-content] = $post['content']
	@post [data-v-excerpt] = <?php echo substr(strip_tags($post['content']), 0, 50);?>
	@post [data-v-warranty] = $post['warranty']
	@post [data-v-stock] = $post['stock']
	@post [data-v-sku] = $post['sku']
	@post [data-v-weight] = $post['weight']
	@post [data-v-sales] = $post['sales']
	@post [data-v-id] = $post['id']

	@post [data-v-price] = $post['price']
	@post [data-v-promotional_price] = $post['promotional_price']
	@post [data-v-selling_price] = $post['selling_price']


	@post [data-v-img]|src = <?php echo $post['images'][0];?>
	
	
	@post [data-v-url]|href =<?php echo htmlentities(Vvveb\url('content/post/index', $post));?>
	@post [data-v-url]|title = $post['title']	
	
	@post [data-v-category] = $post['category'];
	@post [data-v-manufacturer] = $post['manufacturer'];
	
	
	@post|after = <?php 
	} 
}?>