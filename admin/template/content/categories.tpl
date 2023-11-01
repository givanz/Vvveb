import(common.tpl)

@categories = [data-v-categories] [data-v-cats]
@taxonomy_item   = [data-v-categories] [data-v-cats] [data-v-taxonomy_item]
@language   = [data-v-languages] [data-v-language]

@categories|deleteAllButFirstChild
@taxonomy_item|deleteAllButFirstChild

[data-v-taxonomy_id] = $this->taxonomy_id

@categories|before = <?php
$_categories = $this->categories ?? [];
if ($_categories) {
	$generate_menu = function ($parent) use (&$_categories, &$generate_menu) {

	$hasChildren = false;	
	foreach($_categories as $id => $taxonomy_item) {
		if ($taxonomy_item['parent_id'] == $parent) {
			$hasChildren = true;
			break;
		}
	}
	if (!$hasChildren) return;	
?>

	@taxonomy_item|data-v-id = $taxonomy_item['taxonomy_item_id']
	@taxonomy_item [data-v-url] = $taxonomy_item['url']
	@taxonomy_item [data-v-sort_order] = $taxonomy_item['sort_order']

	@taxonomy_item|before = <?php 

	foreach($_categories as $id => $taxonomy_item) {
		$uniq = Vvveb\System\Functions\Str::random(5);
		if ($taxonomy_item['parent_id'] == $parent) {?>

		//catch all data attributes
		@taxonomy_item [data-v-taxonomy_item-*] = $taxonomy_item['@@__data-v-taxonomy_item-(*)__@@']
		
		@taxonomy_item [data-v-taxonomy_item-url]|href = <?php echo htmlentities(Vvveb\url('post/taxonomy_item/index', $taxonomy_item));?>
		@taxonomy_item [data-v-taxonomy_item-img]|src = $taxonomy_item['images'][0]
				
		@taxonomy_item|append = <?php 
		 $generate_menu($taxonomy_item['taxonomy_item_id'], $_categories);
	}?>
	
	@taxonomy_item|after = 
	<?php } ?>

	@categories|after = <?php 
}; 
reset($_categories);
$generate_menu($_categories[key($_categories)]['parent_id'], $_categories); }
$uniq = Vvveb\System\Functions\Str::random(5);
?>


/* language tabs */
[data-v-languages]|before = <?php $_lang_instance = '@@__data-v-languages__@@';$_i = 0;?>
@language|deleteAllButFirstChild
//@language|addClass = <?php if ($_i == 0) echo 'active';?>

@language|before = <?php
$languages = $this->languagesList;
foreach ($languages as $key => $language) {
	$language_id = $language['language_id'];
	$code = $language['code'];
?>

	[data-v-languages] [data-v-language-id]|id = <?php echo 'lang-' . $language_id . '-' . $_lang_instance . '-' . $uniq;?>
	[data-v-languages] [data-v-language-id]|addClass = <?php if ($_i == 0) echo 'show active';?>
	[data-v-languages] [data-v-language_id]|name = <?php echo "taxonomy_item_content[$language_id][language_id]";?>
	[data-v-languages] [data-v-language_id]|value = <?php echo $language_id;?>

	@language [data-v-language-lang-name]|innerText = <?php echo ucfirst($language['name']);?>
	@language [data-v-language-*]|innerText = $taxonomy_item['languages'][$language_id]['@@__data-v-language-(*)__@@']
	@language [data-v-language-*]|name = <?php echo "taxonomy_item_content[$language_id][@@__data-v-language-(*)__@@]";?>
	@language input[data-v-language-*]|value = $taxonomy_item['languages'][$language_id]['@@__data-v-language-(*)__@@']

	@language [data-v-language-img]|title = $language['name']
	@language [data-v-language-img]|src = <?php echo 'language/' . $language_id . '/' . $language_id . '.png';?>
	@language [data-v-language-link]|href = <?php echo '#lang-' . $language_id . '-' . $_lang_instance . '-' . $uniq;?>
	@language [data-v-language-link]|addClass = <?php if ($_i == 0) echo 'active';?>

@language|after = <?php 
$_i++;
}
?>

