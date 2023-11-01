@comments = [data-v-component-comments]
@comment  = [data-v-component-comments] [data-v-comment]

@comment|deleteAllButFirstChild

@comments|prepend = <?php
if (isset($_comments_idx)) $_comments_idx++; else $_comments_idx = 0;
$previous_component = isset($current_component)?$current_component:null;
$comments = $current_component = $this->_component['comments'][$_comments_idx] ?? [];

$count = $comments['count'] ?? 0;
$limit = isset($comments['limit']) ? $comments['limit'] : 5;	
?>


@comment|before = <?php
if($comments && is_array($comments['comment'])) {
	foreach ($this->_component['comments'][$_comments_idx]['comment'] as $index => $comment) {?>
		
		@comment|data-comment_id = $comment['comment_id']
		
		@comment|addClass = <?php echo 'level-' . ($comment['level'] ?? 0);?>
		
		@comment|id = <?php echo 'comment-' . $comment['comment_id'];?>
		
		@comment [data-v-comment-content] = <?php echo $comment['content'];?>
		
		@comment img[data-v-comment-*]|src = $comment['@@__data-v-comment-(*)__@@']
		
		@comment [data-v-comment-*]|innerText = $comment['@@__data-v-comment-(*)__@@']
		
		@comment a[data-v-comment-*]|href = $comment['@@__data-v-comment-(*)__@@']
	
	@comment|after = <?php 
	} 
}
?>
