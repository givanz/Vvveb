//keep post values
//input|value = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']);?>

/* input elements */
input[type="text"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) {
		$value = $post; 
	 }
	 echo htmlspecialchars($value);
?>

input[type="email"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) {
		$value = $post; 
	 }
	 echo htmlspecialchars($value);
?>

input[type="password"]|value = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) {
		$value = $post; 
	 }
	 echo htmlspecialchars($value);
?>


/* textarea elements */
textarea = 
<?php
	$post = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout@@macro postNameToArrayKey("@@__name__@@")@@ ?? '';
	$value = '@@__value__@@';
	 if ($post) {
		$value = $post; 
	 }
	 echo htmlspecialchars($value);
?>


input[name="register"]|addNewAttribute = <?php 
	if (isset($_POST['register']) && ($_POST['register'] == '@@__value__@@')) echo ' checked';
?>

input[name="terms"]|addNewAttribute = <?php 
	if (isset($_POST['terms']) && ($_POST['terms'] == '@@__value__@@')) echo ' checked';
?>

input[name="newsletter"]|addNewAttribute = <?php 
	if (isset($_POST['newsletter']) && ($_POST['newsletter'] == '@@__value__@@')) echo ' checked';
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
	$payment_method = $_POST['payment_method'] ?? $this->checkout['payment_method'] ?? '';
	if ($payment_method == $payment['name']) echo ' checked';
?>

[data-v-shipping] input[name="shipping_method"]|addNewAttribute = <?php 
	$shipping_method = $_POST['shipping_method'] ?? $this->checkout['shipping_method'] ?? '';
	if ($shipping_method == $shipping['name']) echo ' checked';
?>

@country = [data-v-countries] option
@country|deleteAllButFirstChild

[data-v-countries]|before = <?php
	$country_id = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout["@@__id__@@"] ?? false;
?> 

@country|before = <?php
$count = 0;
$country_index = 0;

if(isset($this->countries) && is_array($this->countries)) {
	foreach ($this->countries as $country_index => $country) {?>
	
	[data-v-country-*]|innerText  = $country['@@__data-v-country-(*)__@@']
	option[data-v-country-*]|value = $country['@@__data-v-country-(*)__@@']	

	@country|innerText = $country['name']	
	@country|value = $country['country_id']	
	@country|addNewAttribute = <?php if ($country_id && ($country['country_id'] == $country_id)) echo 'selected';?>
	
	@country|after = <?php 
		$count++;
	} 
}?>


@region = [data-v-regions] option
@region|deleteAllButFirstChild

[data-v-regions]|before = <?php
	$region_id = $_POST@@macro postNameToArrayKey("@@__name__@@")@@ ?? $this->checkout["@@__id__@@"] ?? false;
?> 

@region|before = <?php
$count = 0;
$region_index = 0;

if(isset($this->regions) && is_array($this->regions)) {
	foreach ($this->regions as $region_index => $region) {?>
	
	[data-v-region-*]|innerText  = $region['@@__data-v-region-(*)__@@']
	option[data-v-region-*]|value = $region['@@__data-v-region-(*)__@@']	

	@region|innerText = $region['name']	
	@region|value = $region['region_id']	
	@region|addNewAttribute = <?php if ($region_id && ($region['region_id'] == $region_id)) echo 'selected';?>
	
	@region|after = <?php 
		$count++;
	} 
}?>


import(common.tpl)
