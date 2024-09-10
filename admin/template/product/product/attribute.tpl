//attributes

//product attribute
@product-attribute = [data-v-product] [data-v-product-attribute] [data-v-attribute]
@product-attribute|deleteAllButFirst

@product-attribute|before = <?php
if(isset($this->product['product_attribute']) && is_array($this->product['product_attribute']))
foreach ($this->product['product_attribute'] as $product_attribute_id => $attribute)  {
?>
	@product-attribute input[data-v-attribute-*]|value = $attribute['@@__data-v-attribute-(*)__@@']
	@product-attribute [data-v-attribute-*] = $attribute['@@__data-v-attribute-(*)__@@']
	
	@product-attribute [data-v-attribute-*]|name = <?php echo "product_attribute[$product_attribute_id][@@__data-v-attribute-(*)__@@]";?>
	@product-attribute [data-v-attribute-attribute_id]|data-text = $attribute['name']
	
@product-attribute|after = <?php
	}	
?>

[data-v-product] [data-v-attributes] [data-v-group]|deleteAllButFirst
[data-v-product] [data-v-attributes] [data-v-attribute]|deleteAllButFirst

