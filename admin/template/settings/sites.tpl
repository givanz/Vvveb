import(common.tpl)
import(pagination.tpl)

[data-v-sites]|before = <?php
	$count = $this->count;
?>

@site = [data-v-sites] [data-v-site]
@site|deleteAllButFirstChild

@site|before = <?php

if(isset($this->sitesList) && is_array($this->sitesList)) {
	//$pagination = $this->sites[$_sites_idx]['pagination'];
	foreach ($this->sitesList as $index => $site) {?>
	
	@site [data-v-*]|innerText = $site['@@__data-v-(*)__@@']
	@site [data-v-*]|title = $site['@@__data-v-(*)__@@']

	@site a[data-v-url]|href = <?php echo "//{$site['url']}";?>	
	@site [data-v-host]|title = $site['name']	
	@site [data-v-url]|title = $site['name']	
	@site a[data-v-edit-url]|href = <?php echo \Vvveb\url(['module' => 'settings/site', 'site_id' => $site['site_id']]);?>
	@site a[data-v-delete-url]|href = $site['delete-url']
	
	
	@site|after = <?php
	} 
}?>
