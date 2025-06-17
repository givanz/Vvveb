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
`<div data-v-component-post="post" data-v-post_id="1">
   <h2 data-v-post-name>Post name</h2>
	<img class="img-fluid" src="media/posts/6.jpg" alt="">   
   <div data-v-post-content>Post content</div>
</div>
`;			

class PostComponent extends ServerComponent{
	constructor () {
		super();

		this.name = "Post";
		this.attributes = ["data-v-component-post"],

		this.image ="icons/post.svg";
		this.html = template;
				
		this.properties = [{
			name: "Post name <span class='text-muted'>(autocomplete)</span>",
			key: "post",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:false,
			col:12,
			inputtype: AutocompleteInput,
			data: {
				url: window.location.pathname + "?module=editor/autocomplete&action=posts&type=",
			},

		}];
	}

    init(node) {
	}
}

let postComponent = new PostComponent;

export {
  postComponent
};
