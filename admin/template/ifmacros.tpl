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


//class if
[data-v-class-if-*]|addClass = <?php 
$condition = @@macro IfCondition("@@__data-v-class-if-*__@@")@@;
if  (($condition) || (isset($vvveb_is_page_edit) && $vvveb_is_page_edit)) { 
	echo '@@__data-v-class-if-(.+)__@@';
}?> 


//class if not
[data-v-class-if-not-*]|addClass = <?php 
$condition = @@macro IfCondition("@@__data-v-class-if-not-*__@@")@@;
if  (!($condition) || (isset($vvveb_is_page_edit) && $vvveb_is_page_edit)) {
	echo '@@__data-v-class-if-not-(.+)__@@';
}?> 
