@reviews = [data-v-component-reviews]
@review  = [data-v-component-reviews] [data-v-review]

@review|deleteAllButFirstChild

@reviews|prepend = <?php
if (isset($_reviews_idx)) $_reviews_idx++; else $_reviews_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$reviews = $current_component = $this->_component['reviews'][$_reviews_idx] ?? [];

$_pagination_count = $reviews['count'] ?? 0;
$_pagination_limit = isset($reviews['limit']) ? $reviews['limit'] : 5;	
?>


@review|before = <?php
if($reviews && is_array($reviews['review'])) {
	foreach ($this->_component['reviews'][$_reviews_idx]['review'] as $index => $review) {?>
		
		@review|data-review_id = $review['review_id']
		
		@review|addClass = <?php echo 'level-' . ($review['level'] ?? 0);?>
		
		@review|id = <?php echo 'review-' . $review['review_id'];?>
		
		@review [data-v-review-content] = <?php echo $review['content'];?>
		
		@review img[data-v-review-*]|src = $review['@@__data-v-review-(*)__@@']
		
		@review [data-v-review-*]|innerText = $review['@@__data-v-review-(*)__@@']
		
		@review a[data-v-review-*]|href = $review['@@__data-v-review-(*)__@@']
	
	@review|after = <?php 
	} 
}
?>