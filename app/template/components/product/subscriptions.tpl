@subscriptions =  [data-v-component-product-subscriptions]
@subscription  = [data-v-component-product-subscriptions] [data-v-subscription]

@subscription|deleteAllButFirstChild

@subscriptions|prepend = <?php
if (isset($_subscriptions_idx)) $_subscriptions_idx++; else $_subscriptions_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$subscriptions = $current_component = $this->_component['product_subscriptions'][$_subscriptions_idx] ?? [];

$_pagination_count = $count = $subscriptions['count'] ?? 0;
$_pagination_limit = isset($subscriptions['limit']) ? $subscriptions['limit'] : 5;	
?>


@subscription|before = <?php
if($subscriptions && is_array($subscriptions['product_subscription'])) {
	foreach ($subscriptions['product_subscription'] as $index => $subscription) {?>
		
		@subscription [data-v-subscription-*]|innerText = $subscription['@@__data-v-subscription-(*)__@@']
	
	@subscription|after = <?php 
	} 
}
?>
