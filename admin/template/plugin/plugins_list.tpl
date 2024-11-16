@plugin = [data-v-plugins] [data-v-plugin]

@plugin|deleteAllButFirstChild

[data-v-check-plugin-url]|src= $this->checkPluginUrl

@plugin|before = <?php
if(isset($this->plugins) && is_array($this->plugins)) {
	$category = $this->category ?? '';
	foreach ($this->plugins as $index => $plugin) { ?>
	
	@plugin|id = <?php echo 'plugin-' . $plugin['slug'];?>
	@plugin img[data-v-plugin-screenshot]|src = $plugin['screenshot']
	@plugin input[data-v-vvveb-action]|value = $plugin['slug']
	
	@plugin [data-v-plugin-*]|innerText  = $plugin['@@__data-v-plugin-([-_\w]+)__@@']
	@plugin a[data-v-plugin-*]|href  = $plugin['@@__data-v-plugin-([-_\w]+)__@@']

	@plugin [data-v-plugin-author-url]|href  = $plugin['author-url']
	
	@plugin [data-v-plugin-activate-url]|href  = <?php echo Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'checkPluginAndActivate', 'plugin' => $plugin['slug'], 'category' => $category]);?>
	@plugin [data-v-plugin-deactivate-url]|href = <?php echo Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'deactivate', 'plugin' => $plugin['slug'], 'category' => $category]);?>
		
	@plugin [data-v-plugin-global-activate-url]|href  = <?php echo Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'checkPluginAndActivate', 'global'=> 'true', 'plugin' => $plugin['slug'], 'category' => $category]);?>
	@plugin [data-v-plugin-global-deactivate-url]|href = <?php echo Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'deactivate', 'global'=> 'true', 'plugin' => $plugin['slug'], 'category' => $category]);?>
	
	@plugin [data-v-plugin-delete-url]|href = <?php echo Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'delete', 'plugin' => $plugin['slug'], 'category' => $category]);?>

	@plugin [data-v-plugin-thumb_url]|src = $plugin['thumb_url']	

	@plugin|after = <?php 
	} 
}?>



