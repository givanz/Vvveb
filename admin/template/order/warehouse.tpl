import(crud.tpl, {"type":"warehouse"})

@country = [data-v-countries] [data-v-country]
@country|deleteAllButFirstChild

@country|before = <?php
$count = 0;
$country_index = 0;
if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>

	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']
	@country|addNewAttribute = <?php if (isset($this->warehouse['country_id']) && ($country['country_id'] == $this->warehouse['country_id'])) echo 'selected';?>

	@country|after = <?php
		$count++;
	}
}?>

@region = [data-v-regions] [data-v-region]
@region|deleteAllButFirstChild

@region|before = <?php
$count = 0;
$region_index = 0;
if(isset($this->regions) && is_array($this->regions)) {
	foreach ($this->regions as $region_index => $region) {?>

	[data-v-region-*]|innerText  = $region['@@__data-v-region-(*)__@@']
	option[data-v-region-*]|value = $region['@@__data-v-region-(*)__@@']
	@region|addNewAttribute = <?php if (isset($this->warehouse['region_id']) && ($region['region_id'] == $this->warehouse['region_id'])) echo 'selected';?>

	@region|after = <?php
		$count++;
	}
}?>

//warehouse products
@warehouse-product = [data-v-warehouse] [data-v-warehouse-product] [data-v-product]
@warehouse-product|deleteAllButFirst

@warehouse-product|before = <?php
if(isset($this->warehouse['warehouse_product']) && is_array($this->warehouse['warehouse_product']))
foreach ($this->warehouse['warehouse_product'] as $warehouse_product_id => $product)  {
?>
	@warehouse-product input[data-v-product-*]|value = $product['@@__data-v-product-(*)__@@']
	@warehouse-product [data-v-product-*] = $product['@@__data-v-product-(*)__@@']

@warehouse-product|after = <?php
	}
?>
