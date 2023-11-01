@language = [data-v-component-language] [data-v-language]
@language|deleteAllButFirstChild

[data-v-component-language]|prepend = <?php
if (isset($_language_idx)) $_language_idx++; else $_language_idx = 0;
if(isset($this->_component['language']) && $this->_component['language'][$_language_idx]) {
	$language = $this->_component['language'][$_language_idx];
?>


[data-v-component-language] [data-v-language-info-*] = $language['active']['@@__data-v-language-info-(*)__@@']
[data-v-component-language] img[data-v-language-info-*]|src = $language['active']['@@__data-v-language-info-(*)__@@']

@language|before = <?php
	if (is_array($language['language'])) {
		foreach ($language['language'] as $index => $lang) {?>
		
		@language .dropdown-item|addClass = <?php if ($lang['code'] == $language['active']['code']) echo 'active'?>
		
		@language [data-v-language-name] = $lang['name']
		@language [data-v-language-img]|src = $lang['img']
		@language [data-v-language-url]|href = <?php echo $lang['url'];?>
		
		@language button|formaction = <?php echo $lang['url'];?>
		
		@language [data-v-language-code]|value = $lang['code']
		@language a[data-v-language-code]|href = $lang['code']
		@language [data-v-language-url]|href = $lang['url']
		@language [data-v-language-language_id]|value = $lang['language_id']
		
		@language|after = <?php 
		} 
	}
?>

[data-v-component-language]|append = <?php
	}
?>
