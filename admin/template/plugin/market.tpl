import(common.tpl)
import(pagination.tpl)

@plugin = [data-v-plugins] [data-v-plugin]
@plugin|deleteAllButFirstChild


[data-v-plugins]  [data-v-plugin]|before = <?php
if(isset($this->plugins) && is_array($this->plugins)) 
{
	foreach ($this->plugins as $index => $plugin) {?>
	
    @plugin [data-v-plugin-*]|innerText  			= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin a[data-v-plugin-*]|href  				= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin img[data-v-plugin-*]|src  				= $plugin['@@__data-v-plugin-(.+)__@@']
    @plugin img[data-v-plugin-icon]|src  			= $plugin['icon']
	@plugin img[data-v-plugin-*]|src    			= <?php echo 'https://www.vvveb.com' . $plugin['@@__data-v-plugin-([-_\w]+)__@@'] ?? ''; ?>
	@plugin a[data-v-plugin-*]|href   			 	= <?php echo 'https://plugins.vvveb.com' . $plugin['@@__data-v-plugin-([-_\w]+)__@@'] ?? ''; ?>
	@plugin [data-v-plugin-install-url]|href   		= <?php echo Vvveb\url( ['module' => 'plugin/market', 'action' => 'install', 'slug' => $plugin['slug']]); ?>
	@plugin [data-v-plugin-install-url]|data-slug 	= $plugin['slug']

    //@plugin [data-v-plugin-author]  = <?php echo $plugin['author'];?>
    
	[data-v-plugins]  [data-v-plugin]|after = <?php 
	} 
}?>

