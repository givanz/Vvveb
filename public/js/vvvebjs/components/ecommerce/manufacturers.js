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
`<ul class="list-unstyled" data-v-component-product-manufacturers="sidebar">
<li data-v-manufacturer>
	  <a href="/manufacturer/mango" data-v-manufacturer-url><span data-v-manufacturer-name>Mango</span></a>
</li>
<li data-v-manufacturer>
	  <a href="/manufacturer/mango" data-v-manufacturer-url><span data-v-manufacturer-name>Assos</span></a>
</li>
<li data-v-manufacturer>
	  <a href="/manufacturer/mango" data-v-manufacturer-url><span data-v-manufacturer-name>Brand name</span></a>
</li>
</ul>`;
 
class ManufacturersComponent {
	constructor ()
	{
		this.name = "Manufacturers";
		this.attributes = ["data-v-component-product-manufacturers"],

		this.image ="icons/factory.svg";
		this.html = template;
		
		this.properties = [];
	}

    init(node)
	{
		document.querySelectorAll('.mb-3[data-group]').forEach((el, i) => {
			el.classList.add("d-none");
		});			
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 

		document.querySelectorAll('.mb-3[data-group="' + source + '"]').forEach(e => e.classList.remove("d-none"));
	}
}

let manufacturersComponent = new ManufacturersComponent;

export {
  manufacturersComponent
};
