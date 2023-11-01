import(common.tpl)
import(pagination.tpl)

[data-v-taxonomies] [data-v-taxonomy_item]|deleteAllButFirstChild

[data-v-taxonomies]  [data-v-taxonomy_item]|before = <?php
if(isset($this->taxonomies) && is_array($this->taxonomies)) 
{
	//$pagination = $this->taxonomies[$_taxonomies_idx]['pagination'];
	foreach ($this->taxonomies as $index => $taxonomy_item) { ?>
	
	[data-v-taxonomies] [data-v-taxonomy_item] [data-v-*]|innerText = $taxonomy_item['@@__data-v-(*)__@@']
	[data-v-taxonomies] [data-v-taxonomy_item] [data-v-*]|title = $taxonomy_item['@@__data-v-(*)__@@']

	[data-v-taxonomies] [data-v-taxonomy_item] [data-v-url]|title = $taxonomy_item['name']	
	[data-v-taxonomies] [data-v-taxonomy_item] a[data-v-edit-url]|href = <?php echo \Vvveb\url(['module' => 'content/taxonomy_item', 'category_type_id' => $taxonomy_item['category_type_id']]);?>
	
	
	[data-v-taxonomies]  [data-v-taxonomy_item]|after = <?php 
	} 
}?>




/* language tabs */
[data-v-languages]|before = <?php $_lang_instance = '@@__data-v-languages__@@';$_i = 0;?>
[data-v-languages] [data-v-language]|deleteAllButFirstChild
//[data-v-languages] [data-v-language]|addClass = <?php if ($_i == 0) echo 'active';?>

[data-v-languages] [data-v-language]|before = <?php

foreach ($this->languagesList as $language) {
?>
	[data-v-languages] [data-v-language-id]|id = <?php echo 'lang-' . $language['code'] . '-' . $_lang_instance;?>
	[data-v-languages]  [data-v-language-id]|addClass = <?php if ($_i == 0) echo 'show active';?>

	[data-v-languages] [data-v-language] [data-v-language-name] = $language['name']
	[data-v-languages] [data-v-language] [data-v-language-img]|title = $language['name']
	[data-v-languages] [data-v-language] [data-v-language-img]|src = <?php echo 'language/' . $language['code'] . '/' . $language['code'] . '.png';?>
	[data-v-languages] [data-v-language] [data-v-language-link]|href = <?php echo '#lang-' . $language['code'] . '-' . $_lang_instance?>
	[data-v-languages] [data-v-language] [data-v-language-link]|addClass = <?php if ($_i == 0) echo 'active';?>

[data-v-languages] [data-v-language]|after = <?php 
$_i++;
}
?>

