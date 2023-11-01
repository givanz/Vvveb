@shippings = [data-v-component-checkout-shipping]
@shipping  = [data-v-component-checkout-shipping] [data-v-shipping]

@shipping|deleteAllButFirstChild

@shippings|prepend = <?php
if (isset($_shippings_idx)) $_shippings_idx++; else $_shippings_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$shippings = $current_component = $this->_component['checkout_shipping'][$_shippings_idx] ?? [];

$count = $_pagination_count = $shippings['count'] ?? 0;
$_pagination_limit = isset($shippings['limit']) ? $shippings['limit'] : 5;	
?>


@shipping|before = <?php
if($shippings && is_array($shippings['shipping'])) {
	foreach ($shippings['shipping'] as $index => $shipping) {?>
		
		@shipping|data-shipping_id = $shipping['shipping_id']
		
		@shipping input[data-v-shipping-*] = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping img[data-v-shipping-*]|src = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping [data-v-shipping-*]|innerText = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping a[data-v-shipping-*]|href = $shipping['@@__data-v-shipping-(*)__@@']
	
	@shipping|after = <?php 
	} 
}
?>
