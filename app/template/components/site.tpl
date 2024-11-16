[data-v-component-site]|prepend = <?php 
	if (isset($_site_idx)) $_site_idx++; else $_site_idx = 0;
	$previous_component = isset($component)?$component:null;
	$site = $component = $this->_component['site'][$_site_idx] ?? [];
?>

[data-v-component-site] [data-v-site-description-*]|innerText = $site['description']['@@__data-v-site-description-(*)__@@']
[data-v-component-site] [data-v-site-*]|innerText = $site['@@__data-v-site-(*)__@@']
[data-v-component-site] img[data-v-site-*]|src = $site['@@__data-v-site-(*)__@@']
[data-v-component-site] a[data-v-site-*]|href = $site['@@__data-v-site-(*)__@@']

[data-v-component-site] a[data-v-site-*]|href = <?php
$name = '@@__data-v-site-(*)__@@';
if (strpos($name, 'phone-number') !== false) echo 'tel:';
if (strpos($name, 'email') !== false) echo 'mailto:';
echo htmlspecialchars($site[$name] ?? '');
?>

[data-v-component-site] a[data-v-site-description-*]|href = <?php
$name = '@@__data-v-site-description-(*)__@@';
if (strpos($name, 'phone-number') !== false) echo 'tel:';
if (strpos($name, 'email') !== false) echo 'mailto:';
echo htmlspecialchars($site['description'][$name] ?? '');
?>


[data-v-component-site]|append = <?php 
	$component = $previous_component;
?>
