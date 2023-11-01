//product points
@product-points = [data-v-product] [data-v-product-points] [data-v-points]
@product-points|deleteAllButFirst

@product-points|before = <?php
if(isset($this->product['product_points']) && is_array($this->product['product_points']))
foreach ($this->product['product_points'] as $product_points_id => $points)  {
?>

	@product-points input[data-v-points-*]|name  = <?php echo "product_points[$product_points_id][@@__data-v-points-(*)__@@]";?>

	@product-points input[data-v-points-*]|value = $points['@@__data-v-points-(*)__@@']
	@product-points [data-v-points-*] = $points['@@__data-v-points-(*)__@@']
	
	@product-points [data-v-user_group_id]|name = <?php echo "product_points[$product_points_id][user_group_id]";?>
	
	[data-v-product] [data-v-product-points] [data-v-user_group_id]|before = <?php 
		$name = 'user_group';
		$selected = $points['user_group_id'] ?? 1;
	?>		
	
@product-points|after = <?php
	}	
?>
