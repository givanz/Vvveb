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
<div class="container" data-v-component-posts="popular" data-v-limit="4">      
<div class="row">

	<div class="col-12 col-lg-4 mb-2" data-v-post="" data-v-id="6" data-v-type="post">

	  <article class="card">
		<div class="card-img-top">
		  <img src="../../media/posts/6.jpg" alt="" data-v-post-image="">
		</div>
		
		<div class="card-body">
		  <div class="post-title card-title">
			<a href="/hello-world-6" data-v-post-url="" title="">
			  <h3 data-v-post-name="">Mauris viverra cursus ante laoreet eleifend</h3>
			</a>
		  </div>
		  
		  <p class="card-text text-muted" data-v-post-excerpt="">Et et saepe suscipit debitis a accusamus nulla in amet molestiae voluptates dolor autem vitae optio ipsa mollitia voluptatem vitae.</p>
		  <a href="/hello-world-6" data-v-post-url="" title="">
			<span>Read more</span>
			<i class="la la-angle-right"></i>
		  </a>
		</div>
	  </article>


	</div><div class="col-12 col-lg-4 mb-2" data-v-post="" data-v-id="5" data-v-type="post">

	  <article class="card">
		<div class="card-img-top">
		  <img src="../../media/posts/5.jpg" alt="" data-v-post-image="">
		</div>
		
		<div class="card-body">
		  <div class="post-title card-title">
			<a href="/hello-world-5" data-v-post-url="" title="">
			  <h3 data-v-post-name="">Sed viverra pellentesque dictum. Aenean ligula justo, viverra in lacus porttitor</h3>
			</a>
		  </div>
		  
		  <p class="card-text text-muted" data-v-post-excerpt="">Et ut aliquid blanditiis id sit et. Est ea ut tenetur veritatis recusandae est voluptatem.</p>
		  <a href="/hello-world-5" data-v-post-url="" title="">
			<span>Read more</span>
			<i class="la la-angle-right"></i>
		  </a>
		</div>
	  </article>


	</div><div class="col-12 col-lg-4 mb-2" data-v-post="" data-v-id="4" data-v-type="post">

	  <article class="card">
		<div class="card-img-top">
		  <img src="../../media/posts/4.jpg" alt="" data-v-post-image="">
		</div>
		
		<div class="card-body">
		  <div class="post-title card-title">
			<a href="/hello-world-4" data-v-post-url="" title="">
			  <h3 data-v-post-name="">Etiam leo nibh, consectetur nec orci et, tempus tempus ex</h3>
			</a>
		  </div>
		  
		  <p class="card-text text-muted" data-v-post-excerpt="">
			Lorem ipsum dolor sit amet, consectetur adipisicing elit. Corporis perferendis rem accusantium ducimus animi nesciunt expedita omnis aut quas molestias!
			Mauris viverra</p>
		  <a href="/hello-world-4" data-v-post-url="" title="">
			<span>Read more</span>
			<i class="la la-angle-right"></i>
		  </a>
		</div>
	  </article>


	</div><div class="col-12 col-lg-4 mb-2" data-v-post="" data-v-id="3" data-v-type="post">

	  <article class="card">
		<div class="card-img-top">
		  <img src="../../media/posts/3.jpg" alt="" data-v-post-image="">
		</div>
		
		<div class="card-body">
		  <div class="post-title card-title">
			<a href="/hello-world-3" data-v-post-url="" title="">
			  <h3 data-v-post-name="">The work is accomplished, and there is no resting in it</h3>
			</a>
		  </div>
		  
		  <p class="card-text text-muted" data-v-post-excerpt="">
All in the world know the beauty of the beautiful, and in doing this they have (the idea of) what ugliness is; they all know the skill of the skilful, and in doing this they have (the idea of) what t</p>
		  <a href="/hello-world-3" data-v-post-url="" title="">
			<span>Read more</span>
			<i class="la la-angle-right"></i>
		  </a>
		</div>
	  </article>

	</div>
  </div>
</div>
`;

class PostsComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Posts";
		this.attributes = ["data-v-component-posts"],

		this.image ="icons/posts.svg";
		this.html = template;
		
		this.properties = [{
			name: false,
			key: "source",
			inputtype: RadioButtonInput,
			inline:false,
			col:12,
			htmlAttr:"data-v-source",
			data: {
				inline: true,
				extraclass:"btn-group-fullwidth",
				options: [{
					value: "automatic",
					icon:"la la-cog",
					text: "Configuration",
					title: "Configuration",
					extraclass:"btn-sm",
					checked:true,
				}, {
					value: "autocomplete",
					text: "Autocomplete",
					title: "Autocomplete",
					icon:"la la-search",
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
				//this.setGroup(node.dataset.vType);
				//return 'autocomplete';
				return node.dataset.vSource;
			},
		},{
			name: "Start from page",
			group:"automatic",
			col:6,
			inline:false,
			key: "page",
			htmlAttr:"data-v-page",
			data: {
				value: "1",//default
				min: "1",
				max: "1024",
				step: "1"
			},        
			inputtype: NumberInput,
		},{
			name: "Nr. of posts",
			group:"automatic",
			col:6,
			inline:false,
			key: "limit",
			htmlAttr:"data-v-limit",
			inputtype: NumberInput,
			data: {
				value: "8",//default
				min: "1",
				max: "1024",
				step: "1"
			},
		},{
			name: "Order by",
			group:"automatic",
			key: "order",
			col:6,
			inline:false,
			htmlAttr:"data-v-order_by",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "NULL",
					text: "Default"
				}, {
					value: "created_at",
					text: "Date added"
				}, {
					value: "updated_at",
					text: "Date modified"
				}/*, {
					value: "sales",
					text: "Sales"
				}*/]
			}
		},{	
			name: "Order direction",
			group:"automatic",
			key: "order",
			col:6,
			inline:false,
			htmlAttr:"data-v-direction",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "asc",
					text: "Ascending"
				}, {
					value: "desc",
					text: "Descending"
				}]
			}
		},{
			name: "Limit to categories",
			group:"automatic",
			key: "category",
			htmlAttr:"data-v-category",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor/autocomplete&action=categories",
			},

		},{
			name: "Limit to manufacturers",
			group:"automatic",
			key: "manufacturer",
			htmlAttr:"data-v-manufacturer",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor/autocomplete&action=manufacturers",
			}
		},{
			name: "Posts",
			key: "posts",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:false,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=posts",
			},

		}];
	}

    init(node) {
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 
		
		$('.mb-3[data-group="'+ source + '"]').attr('style','');
	}
}

let postsComponent = new PostsComponent;

export {
  postsComponent
};
