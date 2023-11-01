@site = [data-v-component-sites] [data-v-site]
@site|deleteAllButFirstChild

[data-v-component-sites]|prepend = <?php
if (isset($_sites_idx)) $_sites_idx++; else $_sites_idx = 0;
?>


[data-v-component-sites] [data-v-site-info-*] = $sites['active']['@@__data-v-site-info-(*)__@@']

@site|before = <?php
if(isset($this->_component['sites']) && $this->_component['sites'][$_sites_idx]) {
	$sites = $this->_component['sites'][$_sites_idx];
	
	if (is_array($sites['sites'])) {
		foreach ($sites['sites'] as $index => $site) {
			$state = $site['state'] ?? 'live';
		?>
		
		@site .dropdown-item|addClass = <?php if (isset($site['id']) && ($site['id'] == $sites['site_id'])) echo 'active'?>
		
		@site [data-v-site-name] = $site['name']
		@site [data-v-site-icon]|addClass = <?php echo $sites['states'][$state]['icon'];?>
		@site a[data-v-site-href]|href = <?php echo '//' . $site['href'];?>
		
		@site button[data-v-site-site_id]|value = $site['id']
		
		@site|after = <?php 
		} 
	}
}	
?>