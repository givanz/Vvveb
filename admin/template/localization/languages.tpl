import(listing.tpl, {"type":"language", "list": "languages"})

/*

import(common.tpl)

[data-v-language-list] option|deleteAllButFirst

[data-v-language-list] option|before = <?php
	foreach ($this->languagesList as $code => $language) {
		$code = $language['code'] ?? '';
		$shortcode = Vvveb\filter('/\w+/',$code);
		
?>

	[data-v-language-list] option img|src = <?php 
			echo '/img/flags/' . $shortcode . '.png';
	?>	
	
	[data-v-language-list] option|style = <?php 
			echo 'background-image:url(/img/flags/' . $shortcode . '.png)';
	?>
	
	[data-v-language-list] option|value = $code
	[data-v-language-list] option = <?php 
		if (isset($language['emoji'])) {
			echo $language['emoji'] . ' ';
		}
		echo $language['name'];
	?>

[data-v-language-list] option|after = <?php 
} ?>


@language = [data-v-languages] [data-v-language]
@language|deleteAllButFirst

@language|before = <?php
	foreach ($this->installedLanguages as $code => $language) {
		$locale = $language['locale'] ?? '';
?>

	@language img|src = $language['img']
	
	@language [data-v-language-*]|innerText = $language['@@__data-v-language-(*)__@@']
	@language a[data-v-language-*]|href = $language['@@__data-v-language-(*)__@@']
	
	@language [data-v-language-default]|if_exists = $language['default']

@language|after = <?php 
} ?>
*/

import(filters.tpl)