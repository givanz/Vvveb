import(common.tpl)
import(pagination.tpl)

@tag = [data-v-tags] [data-v-tag]

@tag|deleteAllButFirstChild

@tag|before = <?php
if(isset($this->categories) && is_array($this->categories)) {
	foreach ($this->categories as $index => $tag) {?>
	
	@tag [data-v-*]|title = $tag['@@__data-v-(*)__@@']
	@tag [data-v-*]|innerText = $tag['@@__data-v-(*)__@@']
	@tag input[data-v-*]|value = $tag['@@__data-v-(*)__@@']	
	
	@tag a[data-v-*]|href = $tag['@@__data-v-(*)__@@']	
	@tag [data-v-img]|src = $tag['image']	
	
	@tag [data-v-url]|href =<?php echo Vvveb\url(['module' => 'tag/tag', 'taxonomy_item_id' => $tag['taxonomy_item_id']]);?>
	@tag [data-v-url]|title = $tag['title']	
	
	@tag [data-v-category] = $tag['category'];
	@tag [data-v-tag] = $tag['tag'];
	
	
	@tag|after = <?php 
	} 
}?>
