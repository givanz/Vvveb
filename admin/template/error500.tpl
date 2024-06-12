import(admin.tpl)
import(ifmacros.tpl)

[data-v-exception-*]|innerText = $this->@@__data-v-exception-(*)__@@

[data-v-debug]|before = <?php if (defined('DEBUG') && DEBUG == true) { ?>
[data-v-debug]|after = <?php } ?>

[data-v-exception-lines] [data-v-exception-line]|deleteAllButFirstChild

[data-v-exception-lines] [data-v-exception-line]|before = <?php 
$lines = [];
if (isset($this->lines) && is_array($this->lines)) {
	$lines = $this->lines;
} else if (isset($this->code) && is_array($this->code)) {
	$lines = $this->code;
}

if (isset($lines) && is_array($lines)) {
	foreach ($lines as $index => $line) {?>
		
		[data-v-exception-lines] [data-v-exception-line] = $line
		
		[data-v-exception-lines] [data-v-exception-line]|addClass = <?php if ($index == 7) echo 'selected';?>
		
	[data-v-exception-lines] [data-v-exception-line]|after = <?php 
	} 
} ?>


/*minimal display for iframes such as plugin check iframe*/
body|addClass = <?php if (!isset($this->minimal) || !$this->minimal) echo 'minimal';?>

html|before   = <?php if (!isset($this->minimal) || !$this->minimal) { ?>
#content|before = <?php } ?>

#content|after = <?php if (!isset($this->minimal) || !$this->minimal) { ?>
html|after   = <?php } ?>


body|prepend = <?php $debug = constant('DEBUG');?>

