@component  = [data-v-component-content-menu]
@categories = [data-v-component-menu] [data-v-menu-items]
@category = [data-v-component-menu] [data-v-menu-item]
@category-recursive = [data-v-component-menu] [data-v-menu-item-recursive] 

@categories|deleteAllButFirstChild
@category-recursive|deleteAllButFirstChild
@category|deleteAllButFirstChild

@categories|before = <?php
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_menu_idx)) $_menu_idx++; else $_menu_idx = 0;

$_categories = [];
if (isset($this->_component['menu']) && isset($this->_component['menu'][$_menu_idx])) {
	//$_pagination_count = $this->menu[$_menu_idx]['count'];
	//$_pagination_limit = $this->categories[$_menu_idx]['limit'];
	$_categories = $this->_component['menu'][$_menu_idx]['menu_item'] ?? [];
	if (isset($vvveb_is_page_edit) && $vvveb_is_page_edit) {
		$_categories = [
			['menu_item_id' => 1, 'parent_id' => 0, 'children' => 1, 'class' => 'vvveb-hidden'],
			['menu_item_id' => 2, 'parent_id' => 1, 'children' => 0, 'class' => 'vvveb-hidden'], 
			['menu_item_id' => 3, 'parent_id' => 0, 'children' => 0, 'class' => 'vvveb-hidden']
		] + $_categories;
	}
	$parent_id = 0;
	$parents = 0;
}
?>

//editor info
@category|data-v-id = $category['menu_item_id']
@category|data-v-component = 'menu'
@category|data-v-type = 'menu-item'
	
@category|before = <?php 

	foreach($_categories as $id => $category) {
		if (isset($category['parent_id']) && ($category['parent_id'] == $parent_id)) {
?>

		@category|addNewAttribute = <?php if (isset($category['options']) && !$vvveb_is_page_edit)
		echo 'style="';
		 if (isset($category['options']['width'])) echo 'position:static;';
		echo '"';
		?>		
		
		@category > a|addNewAttribute = <?php if (isset($category['options']) && !$vvveb_is_page_edit)
		echo 'style="';
		 if (isset($category['options']['padding-x'])) echo 'padding-left:' . $category['options']['padding-x'] . 'px;padding-right:' . $category['options']['padding-x'] .'px;';
		 if (isset($category['options']['padding-y'])) echo 'padding-top:' . $category['options']['padding-y'] . 'px;padding-bottom:' . $category['options']['padding-y'] .'px;';
		echo '"';
		?>
			

		//catch all data attributes
		@category [data-v-menu-item-*]|innerText = $category['@@__data-v-menu-item-(*)__@@']
		@category [data-v-menu-item-content] = <?php echo($category['content'] ?? '');?>
		
		@category [data-v-menu-item-url]|href = $category['url']
		@category [data-v-menu-item-img]|src  = $category['img']
		@category [data-v-menu-item-img]|width  = $category['options']['img_width']
		@category [data-v-menu-item-img]|height  = $category['options']['img_height']
		@category [data-v-menu-item-recursive]|addNewAttribute = <?php if (isset($_categories[$parent_id]['options']) && !$vvveb_is_page_edit)
		echo 'style="';
		 if (isset($_categories[$parent_id]['options']['columns'])) echo 'column-count:' . $_categories[$parent_id]['options']['columns'] .';';
		 if (isset($_categories[$parent_id]['options']['width'])) echo 'width:' . $_categories[$parent_id]['options']['width'] .'%;';
		 if (isset($_categories[$parent_id]['options']['sub-padding-x'])) echo 'padding-left:' . $_categories[$parent_id]['options']['sub-padding-x'] . 'px;padding-right:' . $_categories[$parent_id]['options']['sub-padding-x'] .'px;';
		 if (isset($_categories[$parent_id]['options']['sub-padding-y'])) echo 'padding-top:' . $_categories[$parent_id]['options']['sub-padding-x'] . 'px;padding-bottom:' . $_categories[$parent_id]['options']['sub-padding-x'] .'px;';
		echo '"';
		?>
		
		@category|append = <?php 
		  if ($category['children'] > 0 && isset($generate_menu)) {
			    $parents++; 
				$generate_menu($category['menu_item_id'], $_categories);
		 }
		?>

@category|after = <?php 
	}
}
?>


@category|addClass = <?php 
if (isset($category['class'])) {
	if ($vvveb_is_page_edit && strpos($category['class'], 'vvveb-hidden') !== false) {
		echo 'vvveb-hidden';
	} else {
		echo htmlspecialchars($category['class']);
	}
}	
?>


@category-recursive|before = <?php
$generate_menu = function ($parent_id) use (&$_categories, &$generate_menu, &$parents, $vvveb_is_page_edit) {
?>

@category-recursive|after = <?php 

}
?>