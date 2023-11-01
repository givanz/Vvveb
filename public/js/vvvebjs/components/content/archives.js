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
<div class="card archives-widget" data-v-component-content-archives="sidebar">
  <div data-v-if="count > 0">
	<h6 class="card-header">Archives</h6>
	<div class="card-body">
	  <ul data-v-archives>
		<li data-v-archive>
		  <a href="/2022/05" data-v-archive-url>
			<span data-v-archive-name>May 2022 </span>
		  </a>
		</li><li data-v-archive>
		  <a href="/2022/06" data-v-archive-url>
			<span data-v-archive-name>June 2022 </span>
		  </a>
		</li>                    
	  </ul>
	</div>
  </div>            
</div>
`;

class ArchivesComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Archives";
		this.attributes = ["data-v-component-content-archives"],

		this.image ="icons/archives.svg";
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
			name: "Archives",
			key: "archives",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:false,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=archives",
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

let archivesComponent = new ArchivesComponent;

export {
  archivesComponent
};
