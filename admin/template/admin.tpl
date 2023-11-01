[data-v-url]|href = <?php echo htmlentities(Vvveb\url('@@__data-v-url__@@'));?>
form[data-v-url]|action = <?php echo htmlentities(Vvveb\url('@@__data-v-url__@@'));?>

[data-v-url-params]|href = <?php echo Vvveb\url(@@__data-v-url-params__@@);?>
form[data-v-url-params]|action = <?php echo Vvveb\url(@@__data-v-url-params__@@);?>

[data-v-url][data-v-url-params]|href = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@);?>
form[data-v-url][data-v-url-params]|action = <?php echo Vvveb\url('@@__data-v-url__@@' , @@__data-v-url-params__@@);?>

//csrf
[data-v-csrf]|value = <?php echo \Vvveb\session('csrf');?>
				  
/*body|prepend = <?php var_dump($this);?>*/

head base|href = <?php echo Vvveb\themeUrlPath()?>

