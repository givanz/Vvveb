import(crud.tpl, {"type":"region_group"})

@region = [data-v-regions] [data-v-region]
@region|deleteAllButFirstChild

@region|before = <?php
$count = 0;
$region_index = 0;
$region = [];
if(isset($this->regions) && is_array($this->regions)) {
	foreach ($this->regions as $region_index => $region) { ?>
	
	@region [data-v-region-*]|name  = <?php echo "region[$region_index][@@__data-v-region-(*)__@@]";?>
	@region [data-v-region-*]|data-v-region-id  = <?php echo $region['region_id'];?>

	@region [data-v-region-*]|innerText  = $region['@@__data-v-region-(*)__@@']
	@region input[data-v-region-*]|value = $region['@@__data-v-region-(*)__@@']	
	@region a[data-v-region-*]|href 	 = $region['@@__data-v-region-(*)__@@']	
	
	@region|after = <?php 
		$count++;
	} 
}?>


@country = [data-v-countries] [data-v-country]
@country|deleteAllButFirstChild

@country|before = <?php
$count = 0;
$country_index = 0;
if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>
	
	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']	
	@country|addNewAttribute = <?php if (isset($region['country_id']) && ($country['country_id'] == $region['country_id'])) echo 'selected';?>
	
	@country|after = <?php 
		$count++;
	} 
}?>
