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
`<div class="search-area toggle-hover">
	<form action="/search" method="get" data-v-action="/search">
		<input type="hidden" name="route" value="search">
		<div class="input-group">
			<input type="search" name="search" class="form-control" id="headerSearch" placeholder="Type for search" data-v-vvveb-action="search" data-v-vvveb-on="keyup">
			<button class="btn border-0" type="submit">
				<div class="la-flip-horizontal">
					<i class="la la-search la-lg" aria-hidden="true"></i>
				</div>
			</button>
		</div>			  
	</form>
</div>`;

class SearchComponent extends ServerComponent{
	constructor () {
		super();

		this.name = "Search";
		this.attributes = ["data-v-component-languages"],
		//this.userServerTemplate = true,

		this.image ="icons/flag.svg";
		this.html = template;
		
		this.properties = [{
			name: "Search",
			group:"automatic",
			key: "order",
			col:12,
			inline:false,
			htmlAttr:"data-v-language_id",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "1",
					text: "Default"
				}, {
					value: "2",
					text: "Date added 1"
				}, {
					value: "3",
					text: "Date added"
				}, {
					value: "4",
					text: "Date modified"
				}, {
					value: "5",
					text: "Sales"
				}]
			}
		}];
	}

    init(node)
	{
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 
		
		$('.mb-3[data-group="'+ source + '"]').attr('style','');
	}
}

let languageComponent = new SearchComponent;

export {
  languageComponent
};
