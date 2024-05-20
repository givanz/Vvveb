@digital_assetcomp = [data-v-component-digital_assets]
@digital_asset  = [data-v-component-digital_assets] [data-v-digital_asset]

@digital_asset|deleteAllButFirstChild

@digital_assetcomp|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_digital_assetcomp_idx)) $_digital_assetcomp_idx++; else $_digital_assetcomp_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$digital_assetcomp = $current_component = $this->_component['digital_assets'][$_digital_assetcomp_idx] ?? [];

$count = $_pagination_count = $digital_assetcomp['count'] ?? 0;
$_pagination_limit = isset($digital_assetcomp['limit']) ? $digital_assetcomp['limit'] : 5;	
?>


@digital_asset|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$digital_assetcomp['digital_asset'] = empty($digital_assetcomp['digital_asset']) ? $_default : $digital_assetcomp['digital_asset'];

if($digital_assetcomp && is_array($digital_assetcomp['digital_asset'])) {
	foreach ($digital_assetcomp['digital_asset'] as $index => $digital_asset) {?>
		
		@digital_asset|data-digital_asset_id = $digital_asset['digital_asset_id']
		
		@digital_asset|id = <?php echo 'digital_asset-' . $digital_asset['digital_asset_id'];?>
		
		@digital_asset img[data-v-digital_asset-*]|src = $digital_asset['@@__data-v-digital_asset-(*)__@@']
		
		@digital_asset [data-v-digital_asset-*]|innerText = $digital_asset['@@__data-v-digital_asset-(*)__@@']
		
		@digital_asset a[data-v-digital_asset-*]|href = $digital_asset['@@__data-v-digital_asset-(*)__@@']
	
	@digital_asset|after = <?php 
	} 
}
?>
