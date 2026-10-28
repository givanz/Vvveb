@post = [data-v-component-posts] [data-v-post]
@post|deleteAllButFirstChild

[data-v-component-posts]|prepend = <?php
if (isset($_posts_idx)) $_posts_idx++; else $_posts_idx = 0;

$_component_data    = $this->_component['posts'][$_posts_idx] ?? [];
$_pagination_count  = $_component_data['count'] ?? 0;
$_pagination_limit  = $_component_data['limit'] ?? 5;
?>

//$post = [data-v-component-posts]  [data-v-post]

[data-v-component-posts]  [data-v-post]|before = <?php 
if(isset($this->_component['posts']) && is_array($this->_component['posts'][$_posts_idx] ?? null) && is_array($this->_component['posts'][$_posts_idx]['post'] ?? null)) 
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