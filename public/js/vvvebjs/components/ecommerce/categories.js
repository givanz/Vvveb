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
<div data-v-component-product-categories>
<ul class="list-unstyled" data-v-cats>                  
	<li data-v-cat>
		<a href="/shop/computers" data-v-cat-url>
		  <span data-v-cat-name>Computers</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/electronics" data-v-cat-url>
		  <span data-v-cat-name>Electronics</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/tablets" data-v-cat-url>
		  <span data-v-cat-name>Tablets</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/toys" data-v-cat-url>
		  <span data-v-cat-name>Toys</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/home-kitchen" data-v-cat-url>
		  <span data-v-cat-name>Home and Kitchen</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/books" data-v-cat-url>
		  <span data-v-cat-name>Books</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/category-1" data-v-cat-url>
		  <span data-v-cat-name>category 1</span>
		</a>
	  </li>                                    
	  
	<li data-v-cat>
		<a href="/shop/category-2" data-v-cat-url>
		  <span data-v-cat-name>category 2</span>
		</a>
	  </li>                                    
</ul>
</div>
`;

class CategoriesComponent {
	constructor ()
	{
		this.name = "Categories";
		this.attributes = ["data-v-component-product-categories"],

		this.image ="icons/categories.svg";
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

let categoriesComponent = new CategoriesComponent;

export {
  categoriesComponent
};
