//set selector prefix to have shorter and easier to read selectors for rules
@breadcrumb = [data-v-component-breadcrumb]
@item  = [data-v-component-breadcrumb] [data-v-breadcrumb-item]

@item|deleteAllButFirstChild

@breadcrumb|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_breadcrumb_idx)) $_breadcrumb_idx++; else $_breadcrumb_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$breadcrumb = $current_component = $this->_component['breadcrumb'][$_breadcrumb_idx] ?? [];

$_pagination_count = $breadcrumb['count'] ?? 0;
$_pagination_limit = isset($breadcrumb['limit']) ? $breadcrumb['limit'] : 5;	
$index = 0;
?>


@item|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$breadcrumb['breadcrumb'] = empty($breadcrumb['breadcrumb']) ? $_default : $breadcrumb['breadcrumb'];

if($breadcrumb && is_array($breadcrumb['breadcrumb'])) {
	foreach ($breadcrumb['breadcrumb'] as $index => $breadcrumb) {$index++;?>
		
		@item [data-v-breadcrumb-item-*]|innerText = $breadcrumb['@@__data-v-breadcrumb-item-(*)__@@']
		
		@item a[data-v-breadcrumb-item-*]|href = $breadcrumb['@@__data-v-breadcrumb-item-(*)__@@']
	
	@item|after = <?php 
	} 
}
?>