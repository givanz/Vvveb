@vendors =  [data-v-component-product-vendors]
@vendor  = [data-v-component-product-vendors] [data-v-vendor]

@vendor|deleteAllButFirstChild

@vendors|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_vendors_idx)) $_vendors_idx++; else $_vendors_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$vendors = $current_component = $this->_component['product_vendors'][$_vendors_idx] ?? [];

$_pagination_count = $vendors['count'] ?? 0;
$_pagination_limit = isset($vendors['limit']) ? $vendors['limit'] : 5;	
?>


@vendor|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$vendors['vendor'] = empty($vendors['vendor']) ? $_default : $vendors['vendor'];

if($vendors && is_array($vendors['vendor'])) {
	foreach ($vendors['vendor'] as $index => $vendor) {?>
		
		@vendor|data-vendor_id = $vendor['vendor_id']
		
		@vendor|id = <?php echo 'vendor-' . $vendor['vendor_id'];?>
		
		@vendor [data-v-vendor-content] = <?php echo($vendor['content']);?>
		
		@vendor img[data-v-vendor-*]|src = $vendor['@@__data-v-vendor-(*)__@@']
		
		@vendor [data-v-vendor-*]|innerText = $vendor['@@__data-v-vendor-(*)__@@']
		
		@vendor a[data-v-vendor-*]|href = $vendor['@@__data-v-vendor-(*)__@@']

		@vendor input[data-v-vendor-vendor_id]|addNewAttribute = <?php 
			if (isset($vendor['active']) && $vendor['active']) {
				echo 'checked';
			}
		?>
	
	@vendor|after = <?php 
	} 
}
?>
