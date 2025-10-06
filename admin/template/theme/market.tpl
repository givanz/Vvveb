import(common.tpl)
import(pagination.tpl)

@theme = [data-v-themes] [data-v-theme]
@theme|deleteAllButFirstChild

[data-v-themes]  [data-v-theme]|before = <?php
if(isset($this->themes) && is_array($this->themes)) {
	foreach ($this->themes as $index => $theme) {?>
	
    @theme [data-v-theme-*]|innerText 				= $theme['@@__data-v-theme-(*)__@@']
    @theme button[data-v-theme-*]|value 			= $theme['@@__data-v-theme-(*)__@@']
    @theme a[data-v-theme-*]|href    				= $theme['@@__data-v-theme-(*)__@@']
    @theme img[data-v-theme-*]|src    				= <?php echo 'https://themes.vvveb.com' . $theme['@@__data-v-theme-(*)__@@']; ?>
	@theme a[data-v-theme-*]|href   				= <?php echo 'https://themes.vvveb.com' . $theme['@@__data-v-theme-(*)__@@'] ?? ''; ?>
	@theme [data-v-theme-install-url]|href   		= <?php echo Vvveb\url( ['module' => 'theme/market', 'action' => 'install', 'slug' => $theme['slug']]); ?>
	@theme [data-v-theme-install-url]|data-slug  	= $theme['slug']
    
	[data-v-themes]  [data-v-theme]|after = <?php 
	} 
}?>


@category = [data-v-theme-categories] [data-v-theme-category]
@category|deleteAllButFirstChild

@category|before = <?php
if(isset($this->categories) && is_array($this->categories))  {
foreach ($this->categories as $category) {?>	
	
	@category [data-v-category-link] = $category['name']
	@category [data-v-category-*]|innerText = $category[''@@__data-v-theme-(*)__@@'']
	@category img[data-v-category-*]|src = $category[''@@__data-v-theme-(*)__@@'']
	@category [data-v-category-link]|addClass = <?php if (isset($this->taxonomy_item_id) && $category['taxonomy_item_id'] == $this->taxonomy_item_id) echo 'active';?>
	@category [data-v-category-link]|href = <?php echo htmlspecialchars(Vvveb\url(['module' => 'theme/market', 'taxonomy_item_id' => $category['taxonomy_item_id']]));?>
	
@category|after = <?php } 
}?>

#safemode|if_exists = $this->safemode

[data-v-search] = $this->search
	
