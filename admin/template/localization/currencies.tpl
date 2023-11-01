import(listing.tpl, {"type":"currency", "list": "currencies"})

import(filters.tpl)

/*

import(common.tpl)
import(pagination.tpl)

@currency = [data-v-currencies] [data-v-currency]
@currency|deleteAllButFirstChild

@currency|before = <?php
$count = 0;
if(isset($this->currencies) && is_array($this->currencies)) {
	foreach ($this->currencies as $index => $currency) { ?>
	
	@currency [data-v-*]|innerText  = $currency['@@__data-v-(*)__@@']
	@currency input[data-v-*]|value = $currency['@@__data-v-(*)__@@']	
	@currency a[data-v-*]|href 		= $currency['@@__data-v-(*)__@@']	
	@currency [data-v-img]|src 		= $currency['image']
	
	@currency|after = <?php 
		$count++;
	} 
}?>

*/