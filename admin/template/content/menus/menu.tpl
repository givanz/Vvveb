import(common.tpl)

@categories = [data-v-categories] [data-v-cats]
@menu       = [data-v-categories] [data-v-cats] [data-v-taxonomy_item]
@language   = [data-v-languages] [data-v-language]

@categories|deleteAllButFirstChild
@menu|deleteAllButFirstChild


[data-v-taxonomy_id] = <?php echo (int)($this->menu_data['menu_id'] ?? false);?>
[data-v-name]        = <?php echo htmlspecialchars($this->menu_data['name'] ?? '');?>
[data-v-slug]        = <?php echo htmlspecialchars($this->menu_data['slug'] ?? '');?>


@categories|before = <?php
$_categories = $this->categories ?? [];
if ($_categories) {
	$generate_menu = function ($parent) use (&$_categories, &$generate_menu) {
		
	$hasChildren = false;	
	foreach($_categories as $id => $menu) {
		if ($menu['parent_id'] == $parent) {
			$hasChildren = true;
			break;
		}
	}
	if (!$hasChildren) return;	
?>

	@menu|data-v-id = $menu['menu_item_id']
	@menu input[data-v-menu-*] = $menu['@@__data-v-menu-(*)__@@']
	@menu input[data-v-menu-item_id]|data-text = <?php 
		$type = '@@__data-type__@@';
		if ($menu['type'] == $type) {
			$langtext = reset($menu['languages']); 
			echo htmlspecialchars($langtext['name'] ?? '');
		} else echo ' ';
	?>
	
	@menu select[data-v-menu-*]|before = <?php $name = '@@__data-v-menu-(*)__@@';?>
	
	@menu select[data-v-menu-*] option|addNewAttribute = <?php 
		$value = '@@__value__@@';
		if (isset($menu[$name]) && ($value == $menu[$name])) {
			echo 'selected';
		}
	?>

	@menu|before = <?php 
	
	foreach($_categories as $id => $menu) {
		$uniq = Vvveb\System\Functions\Str::random(5);
		if ($menu['parent_id'] == $parent) {?>

		//catch all data attributes
		@menu [data-v-taxonomy_item-*] = $menu['@@__data-v-taxonomy_item-(*)__@@']
		@menu [data-v-taxonomy_item_id] = $menu['menu_item_id']
		
		
		@menu [data-v-taxonomy_item-url]|href = <?php echo htmlspecialchars(Vvveb\url('post/menu/index', $menu));?>
		@menu [data-v-taxonomy_item-img]|src = $menu['images'][0]
				
		@menu|append = <?php 
		 $generate_menu($menu['menu_item_id'], $_categories);
	}?>
	
	@menu|after = 
	<?php } ?>

	@categories|after = <?php 
}; 
reset($_categories);
$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); }
$uniq = Vvveb\System\Functions\Str::random(5);
?>


/* language tabs */
[data-v-languages]|before = <?php $_lang_instance = '@@__data-v-languages__@@';$_i = 0;?>
@language|deleteAllButFirstChild
//@language|addClass = <?php if ($_i == 0) echo 'active';?>

@language|before = <?php
//$menu['languages']
$languages = $this->languagesList;
foreach ($languages as $key => $language) {
	$language_id = $language['language_id'];
	$code = $language['code'];
?>

	[data-v-languages] [data-v-language-id]|id         = <?php echo 'lang-' . $language_id . '-' . $_lang_instance . '-' . $uniq;?>
	[data-v-languages] [data-v-language-id]|addClass   = <?php if ($_i == 0) echo 'show active';?>
	[data-v-languages] input[data-v-language_id]|value = <?php echo $language_id;?>
	[data-v-languages] input[data-v-language_id]|name  = <?php echo "menu_item_content[$language_id][language_id]";?>
	
	@language [data-v-language-lang-name]|innerText = <?php echo ucfirst(htmlspecialchars($language['name']));?>
	@language [data-v-language-*]|innerText         = $menu['languages'][$language_id]['@@__data-v-language-(*)__@@']
	@language [data-v-language-*]|name              = <?php echo "menu_item_content[$language_id][@@__data-v-language-(*)__@@]";?>
	@language input[data-v-language-*]|value        = $menu['languages'][$language_id]['@@__data-v-language-(*)__@@']

	@language [data-v-language-img]|title     = $language['name']
	@language [data-v-language-img]|src       = <?php echo 'language/' . $language_id . '/' . $language_id . '.png';?>
	@language [data-v-language-link]|href     = <?php echo '#lang-' . $language_id . '-' . $_lang_instance . '-' . $uniq;?>
	@language [data-v-language-link]|addClass = <?php if ($_i == 0) echo 'active';?>

@language|after = <?php 
	$_i++;
}
?>
