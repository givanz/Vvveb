//keep post values
input|value = <?php if (isset($_POST['@@__name__@@'])) echo htmlspecialchars($_POST['@@__name__@@']);?>

import(common.tpl)