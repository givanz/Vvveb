@reviews = [data-v-component-reviews]
@review  = [data-v-component-reviews] [data-v-review]

@reviews [data-v-reviews-*] = $product_review['@@__data-v-reviews-(*)__@@']

@review|deleteAllButFirstChild

@reviews|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_reviews_idx)) $_reviews_idx++; else $_reviews_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$product_review = $current_component = $this->_component['reviews'][$_reviews_idx] ?? [];
$reviews = $product_review['product_review'] ?? [];

$_pagination_count = $count = $current_component['count'] ?? 0;
$_pagination_limit = isset($reviews['limit']) ? $reviews['limit'] : 5;	
?>


@review|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_review_id' => 0, 'content' => '']] : false;
$reviews = empty($reviews) ? $_default : $reviews;

if($reviews && is_array($reviews)) {
	foreach ($reviews as $index => $review) {?>
		
		@review|data-review_id = $review['review_id']
		
		@review|addClass = <?php echo 'level-' . ($review['level'] ?? 0);?>
		
		@review|id = <?php echo 'review-' . $review['product_review_id'];?>
		
		@review [data-v-review-content] = <?php echo $review['content'];?>
		@review img[data-v-review-avatar]|width = <?php echo $review['size'] ?? '60';?>
		
		@review img[data-v-review-*]|src = $review['@@__data-v-review-(*)__@@']
		
		@review [data-v-review-*]|innerText = $review['@@__data-v-review-(*)__@@']
		
		@review a[data-v-review-*]|href = $review['@@__data-v-review-(*)__@@']
	
	@review|after = <?php 
	} 
}
?>


@reviews [data-v-summary-five]  = <?php echo $product_review['summary'][5]['count'];?>
@reviews [data-v-summary-four]  = <?php echo $product_review['summary'][4]['count'];?>
@reviews [data-v-summary-three] = <?php echo $product_review['summary'][3]['count'];?>
@reviews [data-v-summary-two]   = <?php echo $product_review['summary'][2]['count'];?>
@reviews [data-v-summary-one]   = <?php echo $product_review['summary'][1]['count'];?>


@reviews [data-v-summary-five-width]|style  = <?php echo 'width:' . $product_review['summary'][5]['percent'] .'%';?>
@reviews [data-v-summary-four-width]|style  = <?php echo 'width:' . $product_review['summary'][4]['percent'] .'%';?>
@reviews [data-v-summary-three-width]|style = <?php echo 'width:' . $product_review['summary'][3]['percent'] .'%';?>
@reviews [data-v-summary-two-width]|style   = <?php echo 'width:' . $product_review['summary'][2]['percent'] .'%';?>
@reviews [data-v-summary-one-width]|style   = <?php echo 'width:' . $product_review['summary'][1]['percent'] .'%';?>



@images = [data-v-component-reviews] [data-v-image]
@images|deleteAllButFirstChild

@images|before = <?php
$_images = $product_review['images'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_review_media_id' => 0, 'image' => '']] : false;
$_images = empty($_images) ? $_default : $_images;

if($_images) {
	$i = 0;
	foreach ($_images as $index => $_image) { ?>

		@images img[data-v-thumb-src]|src = $_image['thumb']
		@images img[data-v-image-src]|src = $_image['image']
		@images [data-v-image-background-image]|style = <?php echo 'background-image: url(\'' . $_image['image'] . '\');';?>
		
		@images a[data-v-thumb-src]|href = $_image['thumb']
		@images a[data-v-image-src]|href = $_image['image']
		@images img[data-v-image-src]|data-v-id = $_image['product_review_media_id']
		@images img[data-v-image-src]|data-v-type = 'product_review_media'
		
		@images|after = <?php 
			$i++; 
	}
}
?>


@review_images = [data-v-component-reviews] [data-v-review] [data-v-user-image]
@review_images|deleteAllButFirstChild

@review_images|before = <?php
$_images = $review['images'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['product_review_media_id' => 0, 'image' => '']] : false;
$_images = empty($_images) ? $_default : $_images;

if($_images) {
	$i = 0;
	foreach ($_images as $index => $_image) { ?>

		@review_images [data-bs-slide-to]|data-bs-slide-to = <?php echo $i;?>
		@review_images img[data-v-image-src]|src = $_image['image']
		@review_images img[data-v-thumb-src]|src = $_image['thumb']
		@review_images [data-v-image-background-image]|style = <?php echo 'background-image: url(\'' . $_image['image'] . '\');';?>
		
		@review_images [data-gallery]|data-gallery = <?php echo 'user-' . $review['product_review_id'];?>
		@review_images a[data-v-thumb-src]|href = $_image['thumb']
		@review_images a[data-v-image-src]|href = $_image['image']
		@review_images img[data-v-image-src]|data-v-id = $_image['product_review_media_id']
		@review_images img[data-v-image-src]|data-v-type = 'product_review_media'
		
		@review_images|after = <?php 
			$i++; 
	}
}
?>