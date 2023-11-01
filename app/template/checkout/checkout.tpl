//keep post values
//input|value = <?php if (isset($_POST['@@__name__@@'])) echo $_POST['@@__name__@@'];?>

/* input elements */
input[type="text"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) 
		echo $post; 
	 else echo $value;
?>

input[type="email"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) 
		echo $post; 
	 else echo $value;
?>

input[type="password"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) 
		echo $post; 
	 else echo $value;
?>


/* textarea elements */
textarea = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) 
		echo $post; 
	 else echo $value;
?>

input[name="different_shipping_address"]|addNewAttribute = 
<?php
	/* if no shipping is unchecked or user didn't submit the form then set no shipping to checked */
	$checked = ($_POST && !empty($_POST['different_shipping_address']));
	if ($checked) echo ' checked';
?>

#billing_address_new|addNewAttribute = 
<?php
	/* if no address is selected or user set new address to checked */
	$checked = (isset($_POST['billing_address_id']) && (empty($_POST['billing_address_id'])));
	if ($checked) echo ' checked';
?>

[data-v-address] input[name="billing_address_id"]|addNewAttribute = <?php 
	if (isset($_POST['billing_address_id']) && ($_POST['billing_address_id'] == $address['user_address_id'])) echo ' checked';
?>


[data-v-payment] input[name="payment_method"]|addNewAttribute = <?php 
	if (isset($_POST['payment_method']) && ($_POST['payment_method'] == $payment['name'])) echo ' checked';
?>

[data-v-shipping] input[name="shipping_method"]|addNewAttribute = <?php 
	if (isset($_POST['shipping_method']) && ($_POST['shipping_method'] == $shipping['name'])) echo ' checked';
?>

@country = [data-v-countries] option
@country|deleteAllButFirstChild

@country|before = <?php
$count = 0;
$country_index = 0;
if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>
	
	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']	

	@country|innerText = $country['name']	
	@country|value = $country['country_id']	
	@country|addNewAttribute = <?php if (isset($region['country_id']) && ($country['country_id'] == $region['country_id'])) echo 'selected';?>
	
	@country|after = <?php 
		$count++;
	} 
}?>


import(common.tpl)
