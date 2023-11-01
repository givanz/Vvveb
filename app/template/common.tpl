// example <div data-v-copy-from="index.html,#element">
[data-v-copy-from]|outerHTML = from(@@__data-v-copy-from:([^\,]+)__@@|@@__data-v-copy-from:[^\,]+\,([^\,]+)__@@)

a[data-v-url]|href = <?php echo htmlentities(Vvveb\url('@@__data-v-url__@@'));?>
form[data-v-url]|action = <?php echo htmlentities(Vvveb\url('@@__data-v-url__@@'));?>

a[data-v-url-params]|href = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@, false);?>
form[data-v-url-params]|action = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@);?>

/* [data-v-img]|href = <?php echo htmlentities(Vvveb\url('@@__data-v-img__@@'));?> */

head base|href = <?php echo Vvveb\themeUrlPath()?>

//csrf
input[data-v-csrf]|value = <?php echo \Vvveb\session('csrf');?>

import(components.tpl)
import(ifmacros.tpl)
import(notifications.tpl)
import(editor.tpl)
import(pagination.tpl)

html|addNewAttribute = <?php if (isset($_COOKIE['theme'])) { 
	echo 'data-bs-theme="';
	if ($_COOKIE['theme'] == 'dark') echo 'dark'; else if ($_COOKIE['theme'] == 'light') echo 'light';else echo 'auto';  
	echo '"';
} ?>

[data-v-global-*] = <?php 
$name = '@@__data-v-global-(*)__@@';
$path = str_replace('-', '.', $name);
if (isset($this->global) && $path) {
	echo \Vvveb\arrayPath($this->global, $path);
}
?>

img[data-v-global-*]|src = <?php 
$name = '@@__data-v-global-(*)__@@';
$path = str_replace('-', '.', $name);
if (isset($this->global) && $path) {
	echo \Vvveb\arrayPath($this->global, $path);
}
?>


head > link[hreflang]|deleteAllButFirst

head > link[hreflang]|before = <?php
	if (isset($this->hreflang)) {
		foreach ($this->hreflang as $lang => $url) {
?>

	head > link[hreflang]|hreflang = $lang
	head > link[hreflang]|href = $url

head > link[hreflang]|after = <?php
	}
}
?>
