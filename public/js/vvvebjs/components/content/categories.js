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

let template = `
<div class="card categories-widget" data-v-component-content-categories data-v-type="categories">
  <div data-v-if="count > 0" class=" ">
	<h6 class="card-header">Categories</h6>
	<div class="card-body">
	  <ul data-v-cats>                    
		  <li data-v-cat>
			  <a href="/cat/toys" data-v-cat-url data-v-cat-name>Toys</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/computers" data-v-cat-url data-v-cat-name>Computers</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/electronics" data-v-cat-url data-v-cat-name>Electronics</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/tablets" data-v-cat-url data-v-cat-name>Tablets</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/home-kitchen" data-v-cat-url data-v-cat-name>Home and Kitchen</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/books" data-v-cat-url data-v-cat-name>Books</a>
			</li>                                        
			
		  <li data-v-cat>
			  <a href="/cat/category-1" data-v-cat-url data-v-cat-name>category 1</a>
			</li>                                        
	  </ul>
	</div>
  </div>            
</div>			  
`;

class CategoriesComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Categories";
		this.attributes = ["data-v-component-content-categories"],

		this.image ="icons/categories.svg";
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
			name: "Categories",
			key: "categories",
			group:"autocomplete",
			htmlAttr:"data-v-post_id",
			inline:false,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=categories",
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

let categoriesComponent = new CategoriesComponent;

export {
  categoriesComponent
};
