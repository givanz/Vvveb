input[name="module"]|value = <?php echo htmlspecialchars(Vvveb\get('module'));?>
input[name="action"]|value = <?php echo htmlspecialchars(Vvveb\get('action'));?>
input[name="type"]|value   = <?php echo htmlspecialchars(Vvveb\get('type'));?>

#filters|addClass = <?php if ($this->filter) echo 'show';?>
#filters input[type="text"],#filters input[type="search"]|value = <?php 
	$name = str_replace(['filter[',']'], '', '@@__name__@@');
	if (isset($this->filter[$name])) echo htmlspecialchars($this->filter[$name]);
?>

#filters input.autocomplete|data-text = <?php
$text = $name. '_text';
if (isset($this->filter[$text])) echo htmlspecialchars($this->filter[$text]);
?>


@option = #filters select [data-v-option]
@option|deleteAllButFirstChild

#filters select|before = 
<?php
	 //set select name
	 $selected = '';	
	 $name = str_replace(['filter[',']'], '', '@@__name__@@');
	 if (isset($_GET[$name])) {
		 $selected = $_GET[$name];
	 } else
	 if (isset($this->filter[$name])) {
		$selected = $this->filter[$name];
	 }
?>

@option|before = <?php
	if (isset($this->$name)) {
	$options = 	$this->$name;
	foreach($options as $key => $option){?>
	
		@option|value = $key
		@option = <?php echo htmlspecialchars(Vvveb\humanReadable($option));?>

@option|after = <?php
}}?>

@option|addNewAttribute = <?php if ($key == $selected) echo 'selected';?>
