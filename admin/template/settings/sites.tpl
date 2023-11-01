import(common.tpl)
import(pagination.tpl)

[data-v-sites] [data-v-site]|deleteAllButFirstChild

[data-v-sites]  [data-v-site]|before = <?php
if(isset($this->sitesList) && is_array($this->sitesList)) {
	//$pagination = $this->sites[$_sites_idx]['pagination'];
	foreach ($this->sitesList as $index => $site) {?>
	
	[data-v-sites] [data-v-site] [data-v-*]|innerText = $site['@@__data-v-(*)__@@']
	[data-v-sites] [data-v-site] [data-v-*]|title = $site['@@__data-v-(*)__@@']

	[data-v-sites] [data-v-site] a[data-v-url]|href = <?php echo "//{$site['url']}";?>	
	[data-v-sites] [data-v-site] [data-v-host]|title = $site['name']	
	[data-v-sites] [data-v-site] [data-v-url]|title = $site['name']	
	[data-v-sites] [data-v-site] a[data-v-edit-url]|href = <?php echo \Vvveb\url(['module' => 'settings/site', 'site_id' => $site['site_id']]);?>
	
	
	[data-v-sites]  [data-v-site]|after = <?php
	} 
}?>