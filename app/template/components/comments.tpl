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
$vvveb_is_page_edit = Vvveb\isEditor();
$_comments = $comments['comment'] ?? [];
$_default = (isset($vvveb_is_page_edit) && $vvveb_is_page_edit ) ? [0 => ['comment_id' => 1, 'content' => '']] : false;
$_comments = empty($_comments) ? $_default : $_comments;

if($_comments && is_array($_comments)) {
	foreach ($_comments as $index => $comment) {?>
		
		@comment|data-comment_id = $comment['comment_id']
		
		@comment|addClass = <?php if (!$vvveb_is_page_edit) echo 'level-' . ($comment['level'] ?? 0);?>
		
		@comment|id = <?php  if (!$vvveb_is_page_edit) echo 'comment-' . $comment['comment_id'];?>
		
		@comment [data-v-comment-content] = $comment['content']
		
		@comment img[data-v-comment-*]|src = $comment['@@__data-v-comment-(*)__@@']
		@comment img[data-v-comment-*]|width = <?php echo (int)($review['size'] ?? 60);?>
		
		@comment [data-v-comment-*]|innerText = $comment['@@__data-v-comment-(*)__@@']
		
		@comment a[data-v-comment-*]|href = $comment['@@__data-v-comment-(*)__@@']
	
	@comment|after = <?php 
	} 
}
?>