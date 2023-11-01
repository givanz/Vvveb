import(crud.tpl, {"type":"product"})
import(content/edit.tpl, {"type":"product"})

[data-v-product] .autocomplete|data-text = <?php 
	$text = '@@__data-v-product-(*)__@@_text';
	$value = $this->$text ?? '';
	echo $value;
?>

import(product/product/variant.tpl)
import(product/product/related.tpl)
import(product/product/attribute.tpl)
import(product/product/option.tpl)
import(product/product/subscription.tpl)
import(product/product/discount.tpl)
import(product/product/promotion.tpl)
import(product/product/gallery.tpl)
import(product/product/points.tpl)
import(product/product/digital_asset.tpl)


// featured media
[data-v-product] [data-v-image]|data-v-image = $this->product['image_url']
[data-v-product] input[data-v-image]|value = $this->product['image']
[data-v-product] [data-v-image]|src = <?php echo $this->product['image_url'] ? $this->product['image_url'] : 'img/placeholder.svg';?>


[data-v-product] [data-v-url]|href = $this->product['url']
[data-v-product] [data-v-url] = $this->product['url']

[data-v-product] [data-v-design_url]|href = $this->product['design_url']

[data-v-template_missing] = <?php echo $this->template_missing;?>

[data-v-product] input[type="checkbox"][data-v-product-*]|addNewAttribute = <?php
	if ($value) echo 'checked';
?>