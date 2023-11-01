//image gallery
@image-gallery = [data-v-product] [data-v-images] [data-v-image]
@image-gallery|deleteAllButFirst

@image-gallery|before = <?php
if(isset($this->product['images']) && is_array($this->product['images']))
foreach ($this->product['images'] as $product_image_id => $image)  {
	$gallery_id    = "gallery-image-$product_image_id";
	$gallery_input = "gallery-image-$product_image_id-input";

	$gallery_hash    = "#$gallery_id";
	$gallery_input_hash = "#$gallery_input";
?>
	@image-gallery [data-v-image-src]|src = $image['image']
	@image-gallery [data-v-image-src]|id = $gallery_id
	
	@image-gallery [data-v-image-src]|data-target-input = $gallery_input_hash
	@image-gallery [data-v-image-src]|data-target-thumb = $gallery_hash
	
	@image-gallery [data-v-image-btn]|data-target-input = $gallery_input_hash
	@image-gallery [data-v-image-btn]|data-target-thumb = $gallery_hash
	
	
	@image-gallery .product_image_input = $image['image']
	@image-gallery .product_image_input|data-target-input = $gallery_input_hash
	@image-gallery .product_image_input|id = $gallery_input
	
	/*
	@image-gallery [name="product_image[]"] = $image['image']
	@image-gallery [name="product_image[]"]|data-target-input = $gallery_input_hash
	@image-gallery [name="product_image[]"]|id = $gallery_input
	*/
@image-gallery|after = <?php 
}
?>
