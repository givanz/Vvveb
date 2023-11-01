@currencies = [data-v-component-currency]
@currency = [data-v-component-currency] [data-v-currency]

@currency|deleteAllButFirstChild

@currencies|prepend = <?php
if (isset($_currency_idx)) $_currency_idx++; else $_currency_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$current_component = $this->_component['currency'][$_currency_idx] ?? [];
$currencies = $current_component['currency'] ?? []; 

$_pagination_count = $currencies['count'] ?? 0;
$_pagination_limit = isset($currencies['limit']) ? $currencies['limit'] : 5;
?>

[data-v-component-currency] [data-v-currency-info-*] = $current_component['active']['@@__data-v-currency-info-(*)__@@']

@currency|before = <?php
if($currencies)  {
	foreach ( $currencies as $index => $currency) { ?>
	
	@currency [data-v-currency-*]|innerText = $currency['@@__data-v-currency-(*)__@@']
	@currency button[data-v-currency-code]|value = $currency['code']
	@currency .dropdown-item|addClass = <?php if ($currency['code'] == $current_component['active']['code']) echo 'active'?>
	
    @currency [data-v-currency-url] = <?php 
        echo Vvveb\url(['module' => 'currency/currency', 'currency_id' => $currency['currency_id']]);
    ?>
	
	@currency|after = <?php 
	} 
}
?>
