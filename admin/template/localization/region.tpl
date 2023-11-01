import(crud.tpl, {"type":"region"})

@country = [data-v-countries] [data-v-country]
@country|deleteAllButFirstChild

@country|before = <?php
$count = 0;
$country_index = 0;
if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>
	
	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']	
	@country|addNewAttribute = <?php if (isset($this->region['country_id']) && ($country['country_id'] == $this->region['country_id'])) echo 'selected';?>
	
	@country|after = <?php 
		$count++;
	} 
}?>
