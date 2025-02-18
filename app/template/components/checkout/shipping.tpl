@shippings = [data-v-component-checkout-shipping]
@shipping  = [data-v-component-checkout-shipping] [data-v-shipping]

@shipping|deleteAllButFirstChild

@shippings|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_shippings_idx)) $_shippings_idx++; else $_shippings_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$shippings = $current_component = $this->_component['checkout_shipping'][$_shippings_idx] ?? [];

$count = $_pagination_count = $shippings['count'] ?? 0;
$_pagination_limit = isset($shippings['limit']) ? $shippings['limit'] : 5;	
?>


@shipping|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['shipping_id' => 1, 'name' => 'shipping', 'title' => 'Shipping name',]] : false;
$shippings['shipping'] = empty($shippings['shipping']) ? $_default : $shippings['shipping'];

if($shippings && is_array($shippings['shipping'])) {
	foreach ($shippings['shipping'] as $key => $shipping) {?>
		
		@shipping|data-key = $key

		@shipping input[data-v-shipping-*] = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping input[data-v-shipping-key]|addNewAttribute = <?php if ($shipping_method == $key) echo 'checked';?>
		
		@shipping .collapse|addClass = <?php if (($shipping_method == $key) && !$vvveb_is_page_edit) echo 'show';?>
		
		@shipping img[data-v-shipping-*]|src = $shipping['@@__data-v-shipping-(*)__@@']
		
		@shipping [data-v-shipping-render] = <?php echo $shipping['render'] ?? '';?>

		@shipping [data-v-shipping-*]|innerText = $shipping['@@__data-v-shipping-(*)__@@']

		@shipping input[data-v-shipping-key] = $key
		
		@shipping a[data-v-shipping-*]|href = $shipping['@@__data-v-shipping-(*)__@@']
	
	@shipping|after = <?php 
	} 
}
?>
