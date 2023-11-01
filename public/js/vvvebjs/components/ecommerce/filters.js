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
 
let template = 
`<div class="widget mt-5" data-v-component-filters>
  <span class="d-flex text-muted mb-2">Color</span>
  <ul class="list-unstyled">
	<li>
	  <div class="form-check form-check-color">
		<input class="form-check-input" type="checkbox" value="" id="color-1">
		<label class="form-check-label" for="color-1">
		  <span class="bg-red"></span> Red
		</label>
	  </div>
	</li>
	<li class="mt-1">
	  <div class="form-check form-check-color">
		<input class="form-check-input" type="checkbox" value="" id="color-2">
		<label class="form-check-label" for="color-2">
		  <span class="bg-blue"></span> Blue
		</label>
	  </div>
	</li>
	<li class="mt-1">
	  <div class="form-check form-check-color">
		<input class="form-check-input" type="checkbox" value="" id="color-3">
		<label class="form-check-label" for="color-3">
		  <span class="bg-green"></span> Green
		</label>
	  </div>
	</li>
	<li class="mt-1">
	  <div class="form-check form-check-color">
		<input class="form-check-input" type="checkbox" value="" id="color-4">
		<label class="form-check-label" for="color-4">
		  <span class="bg-yellow"></span> Yellow
		</label>
	  </div>
	</li>
  </ul>
</div>
`; 
 
class FiltersComponent {
	constructor ()
	{
		this.name = "Filters";
		this.attributes = ["data-v-component-filters"],

		this.image ="icons/filters.svg";
		this.html = template;
		
		this.properties = [{
			name: false,
			key: "type",
			inputtype: RadioButtonInput,
			htmlAttr:"data-v-type",
			data: {
				inline: true,
				extraclass:"btn-group-fullwidth",
				options: [{
					value: "autocomplete",
					text: "Autocomplete import",
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
				console.log(group);
				$('.mb-3[data-v-group]').attr('style','display:none !important');
				$('.mb-3[data-v-group="'+ group + '"]').attr('style','');

				return element;
			}, 		
			onChange : function(element, value, input)  {
				this.setGroup(input.value);

				return element;
			}, 
			init: node => {
				console.log(node, 'init');
				//return this.setGroup('autocomplete');
				//return 'autocomplete';
				return node.dataset.type;
			},            
		},{
			name: "Filters",
			key: "filters",
			group:"autocomplete",
			htmlAttr:"data-v-filters",
			inline:true,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor&action=filtersAutocomplete",
			},
		},{
			name: "Nr. of filters",
			group:"automatic",
			col:6,
			inline:true,
			key: "limit",
			htmlAttr:"data-v-limit",
			inputtype: NumberInput,
			data: {
				value: "8",//default
				min: "1",
				max: "1024",
				step: "1"
			},        
			getFromNode: node => 10
			,
		},{
			name: "Start from page",
			group:"automatic",
			col:6,
			inline:true,
			key: "page",
			htmlAttr:"data-v-page",
			data: {
				value: "1",//default
				min: "1",
				max: "1024",
				step: "1"
			},        
			inputtype: NumberInput,
			getFromNode: node =>  0,
		},{
			name: "Order by",
			group:"automatic",
			key: "order",
			htmlAttr:"data-v-order",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "price_asc",
					text: "Price Ascending"
				}, {
					value: "price_desc",
					text: "Price Descending"
				}, {
					value: "date_asc",
					text: "Date Ascending"
				}, {
					value: "date_desc",
					text: "Date Descending"
				}, {
					value: "sales_asc",
					text: "Sales Ascending"
				}, {
					value: "sales_desc",
					text: "Sales Descending"
				}]
			}
		},{
			name: "Category",
			group:"automatic",
			key: "category",
			htmlAttr:"data-v-category",
			inline:true,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=filtersAutocomplete",
			},

		},{
			name: "Manufacturer",
			group:"automatic",
			key: "manufacturer",
			htmlAttr:"data-v-manufacturer",
			inline:true,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=filtersAutocomplete",
			}
		},{
			name: "Manufacturer 2",
			group:"automatic",
			key: "manufacturer 2",
			htmlAttr:"data-v-manufacturer2",
			inline:true,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=filtersAutocomplete",
			},
		}];
	}

    init(node)
	{
		$('.mb-3[data-v-group]').attr('style','display:none !important');
		if (node.dataset.type != undefined)
		{
			$('.mb-3[data-v-group="'+ node.dataset.type + '"]').attr('style','');
		} else
		{		
			$('.mb-3[data-v-group]:first').attr('style','');
		}
	}
}

let filtersComponent = new FiltersComponent;

export {
  filtersComponent
};
