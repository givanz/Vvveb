@subscriptions =  [data-v-component-product-subscriptions]
@subscription  = [data-v-component-product-subscriptions] [data-v-subscription]

@subscription|deleteAllButFirstChild

@subscriptions|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_subscriptions_idx)) $_subscriptions_idx++; else $_subscriptions_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$subscriptions = $current_component = $this->_component['product_subscriptions'][$_subscriptions_idx] ?? [];

$_pagination_count = $count = $subscriptions['count'] ?? 0;
$_pagination_limit = isset($subscriptions['limit']) ? $subscriptions['limit'] : 5;	
?>


@subscription|before = <?php
//if page loaded in editor then set a fist empty product if there are no products 
//to render an empty product to avoid losing the html on edit
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$product_subscription = empty($subscriptions['product_subscription']) ? $_default : $subscriptions['product_subscription'];


if($product_subscription) {
	foreach ($product_subscription as $index => $subscription) {?>
		
		@subscription [data-v-subscription-*]|innerText = $subscription['@@__data-v-subscription-(*)__@@']
	
	@subscription|after = <?php 
	} 
}
?>
