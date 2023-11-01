import(common.tpl)

/* input elements */
[data-v-{{type}}] input[data-v-{{type}}-*]|value = 
<?php
	 $name = '@@__data-v-{{type}}-(*)__@@';
	 if (isset($_POST['{{type}}'][$name])) 
		$value = $_POST['{{type}}'][$name]; 
	 else if (isset($this->{{type}}[$name])) 
		$value = $this->{{type}}[$name];
	 else $value = '@@__value__@@';
	 
	 echo $value;
?>

/* textarea elements */
[data-v-{{type}}] textarea[data-v-{{type}}-*] = 
<?php
	 $name = '@@__data-v-{{type}}-(*)__@@';
	 if (isset($_POST['{{type}}'][$name])) 
		$value =  $_POST['{{type}}'][$name]; 
	 else if (isset($this->{{type}}[$name])) 
		$value =  $this->{{type}}[$name];
	 else $value = '@@__innerHTML__@@';
	 
	 echo $value;
?>

[data-v-{{type}}] select[data-v-{{type}}-*]|before = 
<?php
	 $name = '@@__data-v-{{type}}-(*)__@@';
	 $selected = '';	
	 if (isset($this->{{type}}[$name])) 
	 $selected = $this->{{type}}[$name];
?>

[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option]|deleteAllButFirstChild
[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option]|before = <?php 
    if (isset($this->$name))
	foreach ($this->$name as $value => $text) {
	?>

	[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option]|value = $value
	[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option]|addNewAttribute = <?php if ($value == $selected) echo 'selected';?>
	[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option] = <?php if (is_array($text)) { if (isset($text['name'])) echo $text['name'];} else echo $text;?>  

[data-v-{{type}}] [data-v-{{type}}-*] [data-v-option]|after = <?php 
} ?>


/* Featured media */
[data-v-{{type}}] [data-v-image]|data-v-image = $this->{{type}}['image_url']
[data-v-{{type}}] input[data-v-image]|value = $this->{{type}}['image']
[data-v-{{type}}] img[data-v-image]|src = <?php echo (isset($this->{{type}}['image_url']) && $this->{{type}}['image_url']) ? $this->{{type}}['image_url'] : 'img/placeholder.svg';?>
