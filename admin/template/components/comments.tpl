@comment = [data-v-component-comments] [data-v-comment]
@comment|deleteAllButFirstChild

[data-v-component-comments]|prepend = <?php
if (isset($_comments_idx)) $_comments_idx++; else $_comments_idx = 0;

$comments = [];
$count = 0;
if(isset($this->_component['comments']) && is_array($this->_component['comments'][$_comments_idx])) {
	$comments = $this->_component['comments'][$_comments_idx];
	$count = $comments['count'] ?? 0;
}
?>

[data-v-component-comments] [data-v-comments-*]|innerText = $comments['@@__data-v-comments-(*)__@@']

@comment|before = <?php
if(isset($comments['comment'])) {
	//$pagination = $this->comments[$_comments_idx]['pagination'];
	$index = 0;
	foreach ($comments['comment'] as $index => $comment)  {?>
	
	@comment [data-v-comment-*]|innerText = $comment['@@__data-v-comment-(*)__@@']
	@comment [data-v-comment-*]|title = $comment['@@__data-v-comment-(*)__@@']
    
    @comment [data-v-comment-url]|href = <?php echo Vvveb\url(['module' => 'content/comments', 'status' => '0','comment_id' => $comment['comment_id']]);?>
	
	@comment|after = <?php 
		$index++;
	} 
}
?>
