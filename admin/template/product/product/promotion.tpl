//product promotion
@product-promotion = [data-v-product] [data-v-product-promotion] [data-v-promotion]
@product-promotion|deleteAllButFirst

@product-promotion|before = <?php
if(isset($this->product['product_promotion']) && is_array($this->product['product_promotion']))
foreach ($this->product['product_promotion'] as $product_promotion_id => $promotion)  {
?>

	@product-promotion input[data-v-promotion-*]|name  = <?php echo "product_promotion[$product_promotion_id][@@__data-v-promotion-(*)__@@]";?>

	@product-promotion input[data-v-promotion-*]|value = $promotion['@@__data-v-promotion-(*)__@@']
	@product-promotion [data-v-promotion-*] = $promotion['@@__data-v-promotion-(*)__@@']
	
	@product-promotion [data-v-promotion_plan_id]|name = <?php echo "product_promotion[$product_promotion_id][promotion_plan_id]";?>
	

	@product-promotion [data-v-user_group_id]|name = <?php echo "product_promotion[$product_promotion_id][user_group_id]";?>
	
	[data-v-product] [data-v-product-promotion] [data-v-user_group_id]|before = <?php 
		$name = 'user_group';
		$selected = $promotion['user_group_id'] ?? 1;
	?>		
	
@product-promotion|after = <?php
	}	
?>
