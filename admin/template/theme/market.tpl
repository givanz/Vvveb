import(common.tpl)
import(pagination.tpl)

@theme = [data-v-themes] [data-v-theme]
@theme|deleteAllButFirstChild

[data-v-themes]  [data-v-theme]|before = <?php
if(isset($this->themes) && is_array($this->themes)) {
	foreach ($this->themes as $index => $theme) {?>
	
    @theme [data-v-theme-*]|innerText 				= $theme['@@__data-v-theme-([-_\w]+)__@@']
    @theme a[data-v-theme-*]|href    				= $theme['@@__data-v-theme-([-_\w]+)__@@']
    @theme img[data-v-theme-*]|src    				= <?php echo 'https://themes.vvveb.com' . $theme['@@__data-v-theme-([-_\w]+)__@@']; ?>
	@theme a[data-v-theme-*]|href   				= <?php echo 'https://themes.vvveb.com' . $theme['@@__data-v-theme-([-_\w]+)__@@'] ?? ''; ?>
	@theme [data-v-theme-install-url]|href   		= <?php echo Vvveb\url( ['module' => 'theme/market', 'action' => 'install', 'slug' => $theme['slug']]); ?>
	@theme [data-v-theme-install-url]|data-slug  	= $theme['slug']
    
	[data-v-themes]  [data-v-theme]|after = <?php 
	} 
}?>
