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
`
`;
 
class ProductGalleryComponent {
	constructor ()
	{
		this.name = "Products";
		this.attributes = ["data-v-component-product-gallery"],

		this.image ="icons/products.svg";
		this.html = template;
		
		this.properties = [];
	}

    init(node)
	{
		if (node.dataset.type != undefined) {
			document.querySelectorAll('.mb-3[data-group="'+ + node.dataset.type + '"]').forEach((el, i) => {
				el.classList.remove("d-none");
			});			
		} else {		
			document.querySelector('.mb-3[data-group]:first').classList.remove("d-none");
		}		
	}
}

let productGalleryComponent = new ProductGalleryComponent;

export {
  productGalleryComponent
};
