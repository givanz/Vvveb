@payments = [data-v-component-checkout-payment]
@payment  = [data-v-component-checkout-payment] [data-v-payment]

@payment|deleteAllButFirstChild

@payments|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_payments_idx)) $_payments_idx++; else $_payments_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$payments = $current_component = $this->_component['checkout_payment'][$_payments_idx] ?? [];

$count = $_pagination_count = $payments['count'] ?? 0;
$_pagination_limit = isset($payments['limit']) ? $payments['limit'] : 5;	
?>


@payment|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['payment_id' => 1, 'name' => 'payment', 'title' => 'Payment name',]] : false;
$payments['payment'] = empty($payments['payment']) ? $_default : $payments['payment'];

if($payments && is_array($payments['payment'])) {
	foreach ($payments['payment'] as $key => $payment) {?>
		
		@payment|data-key = $key
		
		@payment input[data-v-payment-*] = $payment['@@__data-v-payment-(*)__@@']
		
		@payment input[data-v-payment-key][type=radio]|addNewAttribute = <?php if ($payment_method == $key) echo 'checked';?>

		@payment .collapse|addClass = <?php if (($payment_method == $key) && !$vvveb_is_page_edit) echo 'show';?>
		
		@payment img[data-v-payment-*]|src = $payment['@@__data-v-payment-(*)__@@']
		
		@payment [data-v-payment-render] = <?php echo $payment['render'] ?? '';?>
		
		@payment [data-v-payment-*]|innerText = $payment['@@__data-v-payment-(*)__@@']
		
		@payment input[data-v-payment-key] = $key
		
		@payment a[data-v-payment-*]|href = $payment['@@__data-v-payment-(*)__@@']
	
	@payment|after = <?php 
	} 
}
?>
