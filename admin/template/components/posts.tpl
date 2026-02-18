@post = [data-v-component-posts] [data-v-post]
@post|deleteAllButFirstChild

[data-v-component-posts]|prepend = <?php
if (isset($_posts_idx)) $_posts_idx++; else $_posts_idx = 0;

$_pagination_count = $this->_component['posts'][$_posts_idx]['count'];
$_pagination_limit = isset($this->_component['posts'][$_posts_idx]['limit'])? $this->_component['posts'][$_posts_idx]['limit'] : 5;
?>

[data-v-component-posts] [data-v-category] = <?php $_category = current($this->_component['posts'][$_posts_idx]['posts']);echo htmlspecialchars($_category['category']);?>
[data-v-component-posts] [data-v-manufacturer] = <?php $_manufacturer = current($this->_component['posts'][$_posts_idx]['posts']);echo htmlspecialchars($_manufacturer['manufacturer']);?>


//$post = [data-v-component-posts]  [data-v-post]

[data-v-component-posts]  [data-v-post]|before = <?php 
if(isset($this->_component['posts']) && is_array($this->_component['posts'][$_posts_idx]['post'])) 
{
	//$pagination = $this->posts[$_posts_idx]['pagination'];
	foreach ($this->_component['posts'][$_posts_idx]['post'] as $index => $post) {?>
	
	@post [data-v-post-url-text]	= $post['url']	
	@post a[data-v-post-*]|href   	= $post['@@__data-v-post-(*)__@@']	
	@post a[data-v-post-url]|href	= $post['url']	
	@post [data-v-post-url]|title	= $post['name']	

	@post [data-v-img]|src = $post['images'][0]
	
	@post [data-v-url]|href  = <?php echo htmlspecialchars(Vvveb\url('content/post/index', $post));?>
	@post [data-v-url]|title = $post['title']	
	
	@post|after = <?php 
	} 
}?>