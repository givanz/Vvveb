//keep post values
input|value = <?php if (isset($_POST['@@__name__@@'])) echo $_POST['@@__name__@@']; else echo '@@__value__@@';?>
#redir|value = <?php 
	if (isset($this->redir)) {
		echo $this->redir;
	} else {
		//echo '/admin';
		//echo Vvveb\escUrl($_SERVER['REQUEST_URI']);
	}
?>

form|action = $this->action

/*login modal for heart beat check*/
body|addClass = <?php if (!isset($this->modal) || !$this->modal) echo 'login-modal';?>

html|before   = <?php if (!isset($this->modal) || !$this->modal) { ?>
#login|before = <?php } ?>

#login|after = <?php if (!isset($this->modal) || !$this->modal) { ?>
html|after   = <?php } ?>

import(common.tpl)
