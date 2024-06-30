@manufacturers =  [data-v-component-product-manufacturers]
@manufacturer  = [data-v-component-product-manufacturers] [data-v-manufacturer]

@manufacturer|deleteAllButFirstChild

@manufacturers|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_manufacturers_idx)) $_manufacturers_idx++; else $_manufacturers_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$manufacturers = $current_component = $this->_component['product_manufacturers'][$_manufacturers_idx] ?? [];

$_pagination_count = $manufacturers['count'] ?? 0;
$_pagination_limit = isset($manufacturers['limit']) ? $manufacturers['limit'] : 5;	
?>


@manufacturer|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$manufacturers['manufacturer'] = empty($manufacturers['manufacturer']) ? $_default : $manufacturers['manufacturer'];

if($manufacturers && is_array($manufacturers['manufacturer'])) {
	foreach ($manufacturers['manufacturer'] as $index => $manufacturer) {?>
		
		@manufacturer|data-manufacturer_id = $manufacturer['manufacturer_id']
		
		@manufacturer|id = <?php echo 'manufacturer-' . $manufacturer['manufacturer_id'];?>
		
		@manufacturer [data-v-manufacturer-content] = <?php echo($manufacturer['content']);?>
		
		@manufacturer img[data-v-manufacturer-*]|src = $manufacturer['@@__data-v-manufacturer-(*)__@@']
		
		@manufacturer [data-v-manufacturer-*]|innerText = $manufacturer['@@__data-v-manufacturer-(*)__@@']
		
		@manufacturer a[data-v-manufacturer-*]|href = $manufacturer['@@__data-v-manufacturer-(*)__@@']
		@manufacturer input[data-v-manufacturer-manufacturer_id]|addNewAttribute = <?php 
			if (isset($manufacturer['active']) && $manufacturer['active']) {
				echo 'checked';
			}
		?>
	
	@manufacturer|after = <?php 
	} 
}
?>
