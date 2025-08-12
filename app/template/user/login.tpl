//keep post values
input|value = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']);?>
#redirect|value = <?php 
	if (isset($this->redirect)) {
		echo htmlspecialchars($this->redirect);
	} else {
		if (isset($_GET['module'])) {
			echo Vvveb\escUrl($_SERVER['REQUEST_URI']);
		}
	}
?>

import(common.tpl)

