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

class RecentPostsComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Recent Posts";
		this.attributes = ["data-v-component-recent-posts"],

		this.image ="icons/posts.svg";
		this.html = '<div class="mb-3"><label>Your response:</label><textarea class="form-control"></textarea></div>';
		
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
			name: "RecentPosts",
			key: "recentPosts",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:true,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=posts",
			},
		}];
	}

    init(node)
	{
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

let recentPostsComponent = new RecentPostsComponent;

export {
  recentPostsComponent
};
