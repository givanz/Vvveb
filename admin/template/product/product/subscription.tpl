//product subscription
@product-subscription = [data-v-product] [data-v-product-subscription] [data-v-subscription]
@product-subscription|deleteAllButFirst

@product-subscription|before = <?php
if(isset($this->product['product_subscription']) && is_array($this->product['product_subscription']))
foreach ($this->product['product_subscription'] as $product_subscription_id => $subscription)  {
?>

	@product-subscription input[data-v-subscription-*]|name  = <?php echo "product_subscription[$product_subscription_id][@@__data-v-subscription-(*)__@@]";?>

	@product-subscription input[data-v-subscription-*]|value = $subscription['@@__data-v-subscription-(*)__@@']
	@product-subscription [data-v-subscription-*] = $subscription['@@__data-v-subscription-(*)__@@']
	
	@product-subscription [data-v-subscription_plan_id]|name = <?php echo "product_subscription[$product_subscription_id][subscription_plan_id]";?>
	
	[data-v-product] [data-v-product-subscription] [data-v-subscription_plan_id]|before = <?php 
		$name = 'subscription_plan';
		$selected = $subscription['subscription_plan_id'] ?? false;
	?>		

	@product-subscription [data-v-user_group_id]|name = <?php echo "product_subscription[$product_subscription_id][user_group_id]";?>
	
	[data-v-product] [data-v-product-subscription] [data-v-user_group_id]|before = <?php 
		$name = 'user_group';
		$selected = $subscription['user_group_id'] ?? 1;
	?>		
	
@product-subscription|after = <?php
	}	
?>
