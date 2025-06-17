@currencies = [data-v-component-currency]
@currency = [data-v-component-currency] [data-v-currency]

@currency|deleteAllButFirstChild

@currencies|prepend = <?php
$vvveb_is_page_edit = Vvveb\isEditor();
if (isset($_currency_idx)) $_currency_idx++; else $_currency_idx = 0;

$previous_component = isset($current_component)?$current_component:null;
$current_component = $this->_component['currency'][$_currency_idx] ?? [];
$currencies = $current_component['currency'] ?? []; 
$active     = $current_component['active'] ?? [];
$current    = $current_component['current'] ?? [];

$_pagination_count = $currencies['count'] ?? 0;
$_pagination_limit = isset($currencies['limit']) ? $currencies['limit'] : 5;
?>

[data-v-component-currency] [data-v-currency-info-*] = $current_component['active']['@@__data-v-currency-info-(*)__@@']

@currency|before = <?php
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => []] : false;
$currencies = empty($currencies) ? $_default : $currencies;

if($currencies)  {
	foreach ( $currencies as $index => $currency) { 
		$code = $currency['code'] ?? '';
?>
	
	@currency [data-v-currency-*]|innerText = $currency['@@__data-v-currency-(*)__@@']
	@currency button[data-v-currency-code]|value = $code
	@currency .dropdown-item|addClass = <?php if (($code == $current) && !$vvveb_is_page_edit) echo 'active'?>
	
    @currency [data-v-currency-url] = <?php 
        echo Vvveb\url(['module' => 'currency/currency', 'currency_id' => $currency['currency_id']]);
    ?>
	
	@currency|after = <?php 
	} 
}
?>
