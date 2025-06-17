@product = [data-v-component-product]
@images = [data-v-component-product] [data-v-product-images]


@product|data-v-id = $product['product_id']
@product|data-v-type = 'product'

@product|before = <?php
$vvveb_is_page_edit = Vvveb\isEditor();

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

//manual echo to avoid html escape
@product [data-v-product-content] = <?php echo($product['content']);?>

//catch all data attributes
@product [data-v-product-*]|innerText = $product['@@__data-v-product-(*)__@@']
@product input[data-v-product-*]|value = $product['@@__data-v-product-(*)__@@']
@product a[data-v-product-*]|href = $product['@@__data-v-product-(*)__@@']


@product button[data-v-product-*]|formaction = $product['@@__data-v-product-(*)__@@']
@product a[data-v-product-*]|href = $product['@@__data-v-product-(*)__@@']
@product [name="product_variant_id"] = $product['product_variant_id']


@product img[data-v-product-main-image]|src = $product['image']
@product [data-v-product-main-image-background-image]|style = <?php echo 'background-image: url(\'' . $product['image'] . '\');';?>
@product a[data-v-product-main-image]|href = <?php echo reset($product['images'])['image'];?>

@images [data-v-product-image]|deleteAllButFirstChild

@images [data-v-product-image]|before = <?php
$_images = $product['images'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_image_id' => 1, 'image' => '']] : false;
$_images = empty($_images) ? $_default : $_images;

if($_images) {
	$i = 0;
	foreach ($_images as $index => $_image) { ?>

		@images [data-bs-slide-to]|data-bs-slide-to = <?php echo $i ?? 1;?>
		@images img[data-v-product-image-src]|src = $_image['image']
		@images [data-v-product-image-src] = $_image['image']
		[data-v-product-image-src]@images = $_image['image']
		@images [data-v-product-image-background-image]|style = <?php echo 'background-image: url(\'' . $_image['image'] . '\');';?>
		@images a[data-v-product-image-src]|href = $_image['image']
		@images img[data-v-product-image-src]|data-v-id = $_image['product_image_id']
		@images img[data-v-product-image-src]|data-v-type = 'product_image'
		
		@images [data-v-product-image]|after = <?php 
			$i++; 
	}
}

$component = $previous_component;
?>


 
