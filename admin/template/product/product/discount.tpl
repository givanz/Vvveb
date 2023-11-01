//product discount
@product-discount = [data-v-product] [data-v-product-discount] [data-v-discount]
@product-discount|deleteAllButFirst

@product-discount|before = <?php
if(isset($this->product['product_discount']) && is_array($this->product['product_discount']))
foreach ($this->product['product_discount'] as $product_discount_id => $discount)  {
?>

	@product-discount input[data-v-discount-*]|name  = <?php echo "product_discount[$product_discount_id][@@__data-v-discount-(*)__@@]";?>

	@product-discount input[data-v-discount-*]|value = $discount['@@__data-v-discount-(*)__@@']
	@product-discount [data-v-discount-*] = $discount['@@__data-v-discount-(*)__@@']
	
	@product-discount [data-v-user_group_id]|name = <?php echo "product_discount[$product_discount_id][user_group_id]";?>
	
	[data-v-product] [data-v-product-discount] [data-v-user_group_id]|before = <?php 
		$name = 'user_group';
		$selected = $discount['user_group_id'] ?? 1;
	?>		
	
@product-discount|after = <?php
	}	
?>

