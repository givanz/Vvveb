import(crud.tpl, {"type":"product"})
import(content/edit.tpl, {"type":"product"})

[data-v-product] .autocomplete|data-text = <?php 
	$text = '@@__data-v-product-(*)__@@_text';
	$value = $this->$text ?? '';
	echo htmlspecialchars($value);
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


[data-v-product] [data-v-url]|href = $this->product['url']
[data-v-product] [data-v-url] = $this->product['url']

[data-v-product] [data-v-design_url]|href = $this->product['design_url']

[data-v-product] input[type="checkbox"][data-v-product-*]|addNewAttribute = <?php
	if ($value) echo 'checked';
?>

[data-v-template_missing]       = $this->template_missing
[data-v-type_name_plural]       = $this->type_name_plural
[data-v-type-name]              = $this->type_name
[data-v-type]                   = $this->type
[data-v-products-list-url]|href = $this->posts_list_url
