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
`;

class CalendarComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Calendar";
		this.attributes = ["data-v-component-calendar"],

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
				},{
					value: "automatic",
					icon:"la la-cog",
					text: "Configuration",
					title: "Configuration",
					extraclass:"btn-sm",
				}],
			},
			
			setGroup: group => {
				document.querySelectorAll('.mb-2[data-group]').forEach(e => e.classList.add("d-none"));
				document.querySelectorAll('.mb-2[data-group="'+ group + '"].d-none').forEach((el, i) => {
					el.classList.remove("d-none");
				});				
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
			name: "Calendar",
			key: "calendar",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:true,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: window.location.pathname + "?module=editor/autocomplete&action=calendar",
			},
		}];
	}

    init(node) {
		document.querySelectorAll('.mb-2[data-group]').forEach((el, i) => {
			el.classList.add("d-none");
		});			
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 

		document.querySelectorAll('.mb-2[data-group="' + source + '"]').forEach(e => e.classList.remove("d-none"));
	}
}

let calendarComponent = new CalendarComponent;

export {
  calendarComponent
};
