//if 
[data-v-if]|before = <?php 
$condition = @@macro IfCondition("@@__data-v-if__@@")@@;
if  (@($condition) || (isset($vvveb_is_page_edit) && $vvveb_is_page_edit)) {
?> 

[data-v-if]|after = <?php } ?>


//if not
[data-v-if-not]|before = <?php 
$condition = @@macro IfCondition("@@__data-v-if-not__@@")@@;
if  (!@($condition) || (isset($vvveb_is_page_edit) && $vvveb_is_page_edit)) {
?> 

[data-v-if-not]|after = <?php } ?>

[data-v-class-if-*]|addClass = <?php @@macro IfClass("")@@?>
[data-v-class-if-not-*]|addClass = <?php @@macro IfClass("")@@?>

[data-v-attr-if-*]|addClass = <?php @@macro IfAttr("")@@?>
[data-v-attr-if-not-*]|addClass = <?php @@macro IfAttr("")@@?>