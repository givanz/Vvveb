import(crud.tpl, {"type":"tax_type"})

@rule = [data-v-tax-rules] [data-v-tax-rule]
@rule|deleteAllButFirstChild

@rule|before = <?php
$count = 0;
$rule_index = 0;
$rule = [];
if(isset($this->tax_rules) && is_array($this->tax_rules)) {
	foreach ($this->tax_rules as $rule_index => $rule) { ?>
	
	@rule [data-v-tax-rule-*]|name  = <?php echo "tax_rule[$rule_index][@@__data-v-tax-rule-(*)__@@]";?>

	@rule [data-v-tax-rule-*]|innerText  = $rule['@@__data-v-tax-rule-(*)__@@']
	@rule input[data-v-tax-rule-*]|value = $rule['@@__data-v-tax-rule-(*)__@@']	
	@rule a[data-v-tax-rule-*]|href 	 = $rule['@@__data-v-tax-rule-(*)__@@']	
	
	@rule|after = <?php 
		$count++;
	} 
}?>


@rate = [data-v-tax-rates] [data-v-tax-rate]
@rate|deleteAllButFirstChild

@rate|before = <?php
$count = 0;
$rate_index = 0;
if(isset($this->tax_rates) && is_array($this->tax_rates)) {
	foreach ($this->tax_rates as $rate_index => $rate) {?>
	
	[data-v-tax-rate-*]|innerText  = $rate['@@__data-v-tax-rate-(*)__@@']
	option[data-v-tax-rate-*]|value = $rate['@@__data-v-tax-rate-(*)__@@']	
	@rate|addNewAttribute = <?php if (isset($rule['tax_rate_id']) && ($rate['tax_rate_id'] == $rule['tax_rate_id'])) echo 'selected';?>
	
	@rate|after = <?php 
		$count++;
	} 
}?>

@based = [data-v-tax-rules] [data-v-tax-rule-based] [data-v-option]
@based|deleteAllButFirstChild

@based|before = <?php
$count = 0;
$based_index = 0;
if(isset($this->based) && is_array($this->based)) {
	foreach ($this->based as $based_index => $based) {?>
	
	@based|innerText  = $based
	@based|value = <?php echo $based_index;?>
	@based|addNewAttribute = <?php if (isset($rule['based']) && ($based_index == $rule['based'])) echo 'selected';?>
	
	@based|after = <?php 
		$count++;
	} 
}?>
