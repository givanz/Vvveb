@site = [data-v-sites] [data-v-site]
@site|deleteAllButFirstChild

@site|before = <?php
if(isset($this->sitesList) && is_array($this->sitesList)) {
	foreach ($this->sitesList as $index => $site) {?>
	
	@site [data-v-site-*]|innerText = $site['@@__data-v-site-(*)__@@']
	@site [data-v-site-*]|title = $site['@@__data-v-site-(*)__@@']
	@site input[data-v-site-*]|addNewAttribute = <?php if (isset($site['selected']) && $site['selected']) echo 'checked';?>

	
	@site|after = <?php
	} 
}?>

