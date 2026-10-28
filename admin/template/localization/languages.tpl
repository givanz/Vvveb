import(listing.tpl, {"type":"language", "list": "languages"})

[data-v-language-translations-url]|href = <?php echo Vvveb\url(['module' => 'localization/translations', 'action' => 'domains', 'lang' =>$language['code']]);?>


import(filters.tpl)
