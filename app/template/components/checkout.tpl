[data-v-component-checkout]|prepend = <?php 
	if (isset($_user_idx)) $_user_idx++; else $_user_idx = 0;
	$previous_component = isset($component)?$component:null;
	$user = $component = $this->_component['checkout'][$_user_idx];
	//$user = \Vvveb\session('user');
?>


[data-v-component-checkout]|append = <?php 
	$component = $previous_component;
?>