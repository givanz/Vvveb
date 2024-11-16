import(common.tpl) 

[data-v-user_address-*] = $this->user_address['@@__data-v-user_address-(*)__@@']

[data-v-countries]|data-v-region-id = $this->user_address['region_id']

@country = [data-v-countries] option
@country|deleteAllButFirstChild

@country|before = <?php
$count = 0;
$country_index = 0;
if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>
	
	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']	

	@country|innerText = $country['name']	
	@country|value = $country['country_id']	
	@country|addNewAttribute = <?php if (isset($region['country_id']) && ($country['country_id'] == $region['country_id'])) echo 'selected';?>
	
	@country|after = <?php 
		$count++;
	} 
}?>
