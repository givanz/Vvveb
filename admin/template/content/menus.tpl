import(common.tpl)
import(pagination.tpl)


[data-v-menus] [data-v-menu]|deleteAllButFirstChild

[data-v-menus]  [data-v-menu]|before = <?php
if(isset($this->menus) && is_array($this->menus)) {
	//$pagination = $this->menus[$_menus_idx]['pagination'];
	foreach ($this->menus as $index => $menu) { ?>
	
	[data-v-menus] [data-v-menu] [data-v-*]|innerText = $menu['@@__data-v-(*)__@@']
	[data-v-menus] [data-v-menu] [data-v-*]|title = $menu['@@__data-v-(*)__@@']

	[data-v-menus] [data-v-menu] [data-v-img]|src =  <?php echo $menu['image'] ? $menu['image']: 'img/placeholder.svg';?>
	[data-v-menus] [data-v-menu] [data-v-url]|title = $menu['name']	
	
	[data-v-menus] [data-v-menu] a[data-v-*]|href = $menu['@@__data-v-(*)__@@']	
	
	
	[data-v-menus]  [data-v-menu]|after = <?php 
	} 
}?>