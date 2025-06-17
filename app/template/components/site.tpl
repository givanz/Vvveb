@site = [data-v-component-site]

@site|before = <?php 
	if (isset($_site_idx)) $_site_idx++; else $_site_idx = 0;
	$previous_component = isset($component)?$component:null;
	$site = $component = $this->_component['site'][$_site_idx] ?? [];
?>

//editor info
@site|addNewAttribute = <?php 
if (\Vvveb\isEditor()) {
	$site_id = $site['site_id'] ?? ''; 
	echo "data-v-id = '$site_id' data-v-type = 'site'";
}
?>


@site [data-v-site-description-*]|innerText = $site['description']['@@__data-v-site-description-(*)__@@']
@site [data-v-site-*]|innerText = $site['@@__data-v-site-(*)__@@']
@site img[data-v-site-*]|src = $site['@@__data-v-site-(*)__@@']
@site a[data-v-site-*]|href = $site['@@__data-v-site-(*)__@@']

@site a[data-v-site-*]|href = <?php
$name = '@@__data-v-site-(*)__@@';
if (strpos($name, 'phone-number') !== false) echo 'tel:';
if (strpos($name, 'email') !== false) echo 'mailto:';
echo htmlspecialchars($site[$name] ?? '');
?>

@site a[data-v-site-description-*]|href = <?php
$name = '@@__data-v-site-description-(*)__@@';
if (strpos($name, 'phone-number') !== false) echo 'tel:';
if (strpos($name, 'email') !== false) echo 'mailto:';
echo htmlspecialchars($site['description'][$name] ?? '');
?>


@site|append = <?php 
	$component = $previous_component;
?>
