// example <div data-v-copy-from="index.html,#element">
[data-v-copy-from]|outerHTML = from(@@__data-v-copy-from:([^\,]+)__@@|@@__data-v-copy-from:[^\,]+\,([^\,]+)__@@)
[data-v-save-global]|outerHTML = from(@@__data-v-save-global:([^\,]+)__@@|@@__data-v-save-global:[^\,]+\,([^\,]+)__@@)

a[data-v-url]|href = <?php echo htmlspecialchars(Vvveb\url('@@__data-v-url__@@'));?>
form[data-v-url]|action = <?php echo htmlspecialchars(Vvveb\url('@@__data-v-url__@@'));?>

a[data-v-url-params]|href = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@, false);?>
form[data-v-url-params]|action = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@);?>

/* [data-v-img]|href = <?php echo htmlspecialchars(Vvveb\url('@@__data-v-img__@@'));?> */

head base|href = <?php if($vvveb_is_page_edit) echo Vvveb\themeUrlPath()?>

//csrf
input[data-v-csrf]|value = <?php echo \Vvveb\session('csrf');?>

import(components.tpl)
import(ifmacros.tpl)
import(notifications.tpl)
import(editor.tpl)
import(pagination.tpl)

//make sure theme has fav icon <link rel="icon" type="image/x-icon" href="/media/favicon.ico" data-v-global-site.favicon>
link[rel="icon"]|delete
head|append    = from(/public/themes/default/index.html|link[rel="icon"])
link[rel="shortcut icon"]|delete
head|append    = from(/public/themes/default/index.html|link[rel="shortcut icon"])

html|addNewAttribute = <?php 
$vvveb_is_page_edit = Vvveb\isEditor();

if (isset($_COOKIE['theme']) && !$vvveb_is_page_edit && !defined('PAGE_CACHE_GENERATING')) { 
	echo 'data-bs-theme="';
	if ($_COOKIE['theme'] == 'dark') echo 'dark'; else if ($_COOKIE['theme'] == 'light') echo 'light';else echo 'auto';  
	echo '"';
} 

if (isset($this->global['rtl']) && $this->global['rtl'] && !$vvveb_is_page_edit) { 
	echo 'dir="rtl"';
}
?>

html|lang = $this->global['locale']

[data-v-global-*]|innerText = <?php 
$name = '@@__data-v-global-(*)__@@';
if (isset($this->global) && $name 
	&& ($value = \Vvveb\arrayPath($this->global, $name))) {
	echo htmlspecialchars($value);
}
?>

img[data-v-global-*]|src = <?php 
$name = '@@__data-v-global-(*)__@@';
if (isset($this->global) && $name 
	&& ($value = \Vvveb\arrayPath($this->global, $name))) {
	echo htmlspecialchars($value);
}
?>

a[data-v-global-*]|href = <?php 
$name = '@@__data-v-global-(*)__@@';
if (isset($this->global) && $name 
	&& ($value = \Vvveb\arrayPath($this->global, $name))) {
	echo htmlspecialchars($value);
}
?>

link[data-v-global-*]|href = <?php 
$name = '@@__data-v-global-(*)__@@';
if (isset($this->global) && $name 
	&& ($value = \Vvveb\arrayPath($this->global, $name))) {
	echo htmlspecialchars($value);
}
?>

[data-v-year]= $this->global['year']

head > link[hreflang]|deleteAllButFirst

head > link[hreflang]|before = <?php
	if (isset($this->hreflang)) {
		foreach ($this->hreflang as $lang => $url) { ?>

	head > link[hreflang]|hreflang = $lang
	head > link[hreflang]|href = $url

head > link[hreflang]|after = <?php
	}
}
?>

head > title                            = <?php echo htmlspecialchars($this->global['site']['description']['title'] ?? '@@__innerText__@@');?>
head > meta[name="description"]|content = <?php echo htmlspecialchars($this->global['site']['description']['meta-description'] ?? '@@__content__@@');?>
head > meta[name="keywords"]|content    = <?php echo htmlspecialchars($this->global['site']['description']['meta-keywords'] ?? '@@__content__@@');?>
