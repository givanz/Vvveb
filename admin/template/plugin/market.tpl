import(common.tpl)
import(pagination.tpl)

@plugin = [data-v-plugins] [data-v-plugin]
@plugin|deleteAllButFirstChild


[data-v-plugins]  [data-v-plugin]|before = <?php
if(isset($this->plugins) && is_array($this->plugins)) 
{
	foreach ($this->plugins as $index => $plugin) {?>
	
    @plugin [data-v-plugin-*]|innerText  			= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin button[data-v-plugin-*]|value		        = $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin a[data-v-plugin-*]|href  				= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin img[data-v-plugin-*]|src  				= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin img[data-v-plugin-icon]|src  			= $plugin['icon']
	@plugin img[data-v-plugin-*]|src    			= <?php echo 'https://www.vvveb.com' . $plugin['@@__data-v-plugin-(*)__@@'] ?? ''; ?>
	@plugin a[data-v-plugin-*]|href   			 	= <?php echo 'https://plugins.vvveb.com' . $plugin['@@__data-v-plugin-(*)__@@'] ?? ''; ?>
	@plugin [data-v-plugin-install-url]|href   		= <?php echo Vvveb\url( ['module' => 'plugin/market', 'action' => 'install', 'slug' => $plugin['slug']]); ?>
	@plugin [data-v-plugin-install-url]|data-slug 	= $plugin['slug']

    //@plugin [data-v-plugin-author]  = $plugin['author'];
    
	[data-v-plugins]  [data-v-plugin]|after = <?php 
	} 
}?>


@category = [data-v-plugin-categories] [data-v-plugin-category]
@category|deleteAllButFirstChild

@category|before = <?php
if(isset($this->categories) && is_array($this->categories))  {
foreach ($this->categories as $category) {?>	
	
	@category [data-v-category-link] = $category['name']
	@category [data-v-category-*]|innerText = $category[''@@__data-v-theme-(*)__@@'']
	@category img[data-v-category-*]|src = $category[''@@__data-v-theme-(*)__@@'']
	@category [data-v-category-link]|addClass = <?php if (isset($this->taxonomy_item_id) && $category['taxonomy_item_id'] == $this->taxonomy_item_id) echo 'active';?>
	@category [data-v-category-link]|href = <?php echo htmlspecialchars(Vvveb\url(['module' => 'plugin/market', 'taxonomy_item_id' => $category['taxonomy_item_id']]));?>
	
@category|after = <?php } 
}?>

#safemode|if_exists = $this->safemode

[data-v-search] = $this->search

