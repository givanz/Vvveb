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
`<ul class="list-unstyled" data-v-component-product-manufacturers="sidebar" data-v-type="categories">
<li data-v-manufacturer>
	  <span data-v-manufacturer-name>Mango</span>
</li>
<li data-v-manufacturer>
	  <span data-v-manufacturer-name>Assos</span>
</li>
<li data-v-manufacturer>
	  <span data-v-manufacturer-name>Brand name</span>
</li>
</ul>`;
 
class ManufacturersComponent {
	constructor ()
	{
		this.name = "Manufacturers";
		this.attributes = ["data-v-component-manufacturers"],

		this.image ="icons/factory.svg";
		this.html = template;
		
		this.properties = [];
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

let manufacturersComponent = new ManufacturersComponent;

export {
  manufacturersComponent
};
