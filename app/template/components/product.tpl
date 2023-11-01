@product = [data-v-component-product]
@images = [data-v-component-product] [data-v-product-images]


@product|data-v-id = $product['product_id']
@product|data-v-type = 'product'

@product|before = <?php
if (isset($_product_idx)) $_product_idx++; else $_product_idx = 0;
$previous_component = isset($component)?$component:null;

$previous_component = isset($current_component)?$current_component:null;
$product = $current_component = $this->_component['product'][$_product_idx] ?? [];

$_pagination_count = $product['count'] ?? 0;
$_pagination_limit = isset($product['limit']) ? $product['limit'] : 5;
?>

//editor attributes
@product|data-v-id = $product['product_id']
@product|data-v-type = 'product'

//catch all data attributes
@product [data-v-product-*]|innerText = $product['@@__data-v-product-(*)__@@']
@product input[data-v-product-*]|value = $product['@@__data-v-product-(*)__@@']
@product a[data-v-product-*]|href = $product['@@__data-v-product-(*)__@@']

//manual echo to avoid html escape
@product [data-v-product-content] = <?php echo $product['content'];?>

@product img[data-v-product-main-image]|src = <?php echo $product['image'];?>
@product a[data-v-product-main-image]|href = <?php echo reset($product['images'])['image'];?>


@images [data-v-product-image]|deleteAllButFirstChild

@product a[data-v-product-cart-url]|href = $product['add-cart-url']
@product a[data-v-product-buy-url]|href = $product['buy-now-url']

@product button[data-v-product-cart-url]|formaction = $product['add-cart-url']
@product button[data-v-product-buy-url]|formaction = $product['buy-now-url']
@product button[data-v-product-wishlist-url]|formaction = $product['wishlist-url']
@product button[data-v-product-compare-url]|formaction = $product['compare-url']


@images [data-v-product-image]|before = <?php
if(isset($product['images']) && is_array($product['images'])) {
	$i = 0;
	foreach ($product['images'] as $index => $image) { ?>

		@images [data-bs-slide-to]|data-bs-slide-to = <?php echo $i;?>
		@images img[data-v-product-image-src]|src = $image['image']
		@images [data-v-product-image-background-image]|style = <?php echo 'background-image: url(\'' . $image['image'] . '\');';?>
		@images a[data-v-product-image-src]|href = $image['image']
		@images img[data-v-product-image-src]|data-v-id = $image['id']
		@images img[data-v-product-image-src]|data-v-type = 'product_image'
		
		@images [data-v-product-image]|after = <?php 
			$i++; 
	}
}

$component = $previous_component;
?>


 