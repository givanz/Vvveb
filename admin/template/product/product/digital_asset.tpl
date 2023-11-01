//product digital_asset
@product-digital_asset = [data-v-product] [data-v-product-digital_asset] [data-v-digital_asset]
@product-digital_asset|deleteAllButFirst

@product-digital_asset|before = <?php
if(isset($this->product['product_to_digital_asset']) && is_array($this->product['product_to_digital_asset']))
foreach ($this->product['product_to_digital_asset'] as $product_digital_asset_id => $digital_asset)  {
?>
	@product-digital_asset input[data-v-digital_asset-*]|value = $digital_asset['@@__data-v-digital_asset-(*)__@@']
	@product-digital_asset [data-v-digital_asset-*] = $digital_asset['@@__data-v-digital_asset-(*)__@@']
	
@product-digital_asset|after = <?php
	}	
?>
