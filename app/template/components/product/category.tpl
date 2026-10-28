//set component variables
[data-v-component-product-category]|prepend = <?php
	$vvveb_is_page_edit = Vvveb\isEditor();

	//use a counter to know which component instance we need to use if there are more than one component on page
	if (isset($product_category_idx)) $product_category_idx++; else $product_category_idx = 0;
	$previous_component = isset($current_component)?$current_component:null;
	$component = $current_component = $this->_component['product_category'][$product_category_idx] ?? [];

	$index = 0;
	$count = $component['count'] ?? 0;
	$limit = isset($component['limit'])? $component['limit'] : 5;

	//if page loaded in editor then set a fist empty category if there are no categories 
	//to render an empty category to avoid losing the html on edit
	$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
	//$_default = [0 => []];
	$_category = empty($component) ? $_default : $component;
?>

//catch all data attributes
[data-v-component-product-category] [data-v-category-*] = $_category['@@__data-v-category-(*)__@@']
