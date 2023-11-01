/**
 * Vvveb
 *
 * Copyright (C) 2021  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
 
 
import {ServerComponent} from '../server-component.js';

let template = 
`
<div id="comments" class="post-comments mt-4" data-v-component-comments>
	<h4 class="comments-title">Comments</h4>

	<ol class="comment-list list-unstyled mb-3">
		<li class="comment mb-4" data-v-comment>

				<div class="comment-wrap d-flex ">

					<figure class="comment-author-avatar me-2">
						<img src="img/sections/team/1.jpg" alt="user" width="60" height="60" data-v-comment-avatar  data-v-if="comment.avatar">
					</figure>

					<div class="comment-author">

						<div class="comment-author">
							<a rel="external nofollow ugc" href="#" data-v-if="comment.url"><span data-v-comment-author>Maria Williams</span></a>
							<span data-v-comment-author data-v-if-not="comment.url">Maria Williams</span>
						</div>

						<div class="comment-meta text-small text-muted">
							<span data-v-comment-created_at data-filter-friendly_date>Jan 29, 2018</span>
						</div>

					</div>

				</div>


				<div data-v-comment-content>
					<p>Consectetur adipiscing elit. Praesent vel tortor facilisis, volutpat nulla placerat, tincidunt mi. Nullam vel orci dui. Su spendisse sit amet laoreet neque. Fusce sagittis suscipit sem a consequat. Proin nec interdum sem. Quisque in porttitor magna, a imperdiet est. Donec accumsan justo nulla, sit amet varius urna laoreet vitae. Maecenas feugiat fringilla metus. </p>
				</div>

				<div class="alert alert-light my-2 small" data-v-if="comment.status = 0">
					<div>Your comment is awaiting moderation.</div>
					<div>This is a preview, your comment will be visible after it has been approved.</div>
				</div>

				<div class="reply">
					<a href="#comment-form" class="reply-btn" data-comment_id="$comment.comment_id" data-comment_author="$comment.author" data-v-vvveb-action="replyTo">Reply <i class="la la-reply"></i></a>
				</div>

		</li>
		<li class="comment mb-4" data-v-comment>

				<div class="comment-wrap d-flex ">

					<figure class="comment-author-avatar me-2">
						<img src="img/sections/team/1.jpg" alt="user" width="60" height="60" data-v-comment-avatar  data-v-if="comment.avatar">
					</figure>

					<div class="comment-author">

						<div class="comment-author">
							<a rel="external nofollow ugc" href="#" data-v-if="comment.url"><span data-v-comment-author>Maria Williams</span></a>
							<span data-v-comment-author data-v-if-not="comment.url">Maria Williams</span>
						</div>

						<div class="comment-meta text-small text-muted">
							<span data-v-comment-created_at data-filter-friendly_date>Jan 29, 2018</span>
						</div>

					</div>

				</div>

				<div data-v-comment-content>
					<p>Consectetur adipiscing elit. Praesent vel tortor facilisis, volutpat nulla placerat, tincidunt mi. Nullam vel orci dui. Su spendisse sit amet laoreet neque. Fusce sagittis suscipit sem a consequat. Proin nec interdum sem. Quisque in porttitor magna, a imperdiet est. Donec accumsan justo nulla, sit amet varius urna laoreet vitae. Maecenas feugiat fringilla metus. </p>
				</div>

				<div class="alert alert-light my-2 small" data-v-if="comment.status = 0">
					<div>Your comment is awaiting moderation.</div>
					<div>This is a preview, your comment will be visible after it has been approved.</div>
				</div>

				<div class="reply">
					<a href="#comment-form" class="reply-btn" data-comment_id="$comment.comment_id" data-comment_author="$comment.author" data-v-vvveb-action="replyTo">Reply <i class="la la-reply"></i></a>
				</div>

		</li>
	</ol>
</div>
`;

class CommentsComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Comments";
		this.attributes = ["data-v-component-comments"],

		this.image ="icons/posts.svg";
		this.html = template;
		
		this.properties = [{
			name: false,
			key: "source",
			inputtype: RadioButtonInput,
			htmlAttr:"data-v-source",
			data: {
				inline: true,
				extraclass:"btn-group-fullwidth",
				options: [{
					value: "autocomplete",
					text: "Autocomplete",
					title: "Autocomplete",
					icon:"la la-search",
					extraclass:"btn-sm",
					checked:true,
				}, {
					value: "automatic",
					icon:"la la-cog",
					text: "Configuration",
					title: "Configuration",
					extraclass:"btn-sm",
				}],
			},
			
			setGroup: group => {
				$('.mb-3[data-group]').attr('style','display:none !important');
				$('.mb-3[data-group="'+ group + '"]').attr('style','');
				//return element;
			}, 		
			onChange : function(element, value, input)  {
				this.setGroup(input.value);
				return element;
			}, 
			init: function (node) {
				//this.setGroup(node.dataset.vSource);
				//return 'autocomplete';
				return node.dataset.vSource;
			},            
		},{
			name: "Comments",
			key: "comments",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:true,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=comments",
			},
		}];
	}

    init(node)
	{
		console.log(node);
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		if (node.dataset.vSource != undefined)
		{
			$('.mb-3[data-group="'+ node.dataset.vSource + '"]').attr('style','');
		} else
		{		
			$('.mb-3[data-group]:first').attr('style','');
		}
	}
}

let commentsComponent = new CommentsComponent;

export {
  commentsComponent
};
