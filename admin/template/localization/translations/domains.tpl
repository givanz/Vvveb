import(common.tpl)


@domain = [data-v-domains] [data-v-domain]
@domain|deleteAllButFirst

@domain|before = <?php
	foreach ($this->domains as $domain => $url) {
?>

	@domain img|src = $domain['img']
	
	@domain [data-v-domain-name]|innerText = $domain
	@domain a[data-v-domain-url]|href = $url
	
	@domain [data-v-domain-default]|if_exists = $domain['default']

@domain|after = <?php 
} ?>
