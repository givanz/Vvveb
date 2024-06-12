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
`<div class="collapse navbar-collapse" id="navbar" data-v-component-menu="header" data-v-slug="main-menu">
	<ul class="navbar-nav ms-auto" data-v-menu-items>
	  <li class="nav-item dropdown" data-v-menu-item data-v-class-if-has-dropdown="category.children > 0">
		
		<a class="nav-link" href="https://themes.vvveb.com" data-v-if="category.children <= 0"  data-v-menu-item-url><span data-v-menu-item-name>Services</span></a>

		<a class="nav-link dropdown-toggle" href="#" data-v-if="category.children > 0" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-v-menu-item-url><span data-v-menu-item-name>Resources</span></a>
		
		<div class="dropdown-menu" data-v-menu-item-recursive>
		  <div data-v-menu-item class="nav-item" data-v-menu-item data-v-class-if-dropdown="category.children > 0">
			<a class="dropdown-item" href="https://github.com/givanz/VvvebJs/wiki" data-v-menu-item-url><span data-v-menu-item-name>User Documentation</span></a>
		  </div> 
		  <div data-v-menu-item class="nav-item" data-v-menu-item data-v-class-if-dropdown="category.children > 0">
			<a class="dropdown-item" href="https://github.com/givanz/VvvebJs/wiki" data-v-menu-item-url><span data-v-menu-item-name>Developer Documentation</span></a>
		  </div> 
		</div>
	  </li>
	  <li class="nav-item" data-v-menu-item>
		<a class="nav-link" href="https://blog.vvveb.com"  data-v-menu-item-url><span data-v-menu-item-name>Blog</span></a>
	  </li>
	  <li class="nav-item" data-v-menu-item>
		<a class="nav-link" href="https://www.vvveb.com/page/contact"  data-v-menu-item-url><span data-v-menu-item-name>Contact</span></a>
	  </li>
	  <li class="nav-item" data-v-menu-item>
		<a class="nav-link" href="https://www.vvveb.com"  data-v-menu-item-url><span data-v-menu-item-name>About us</span></a>
	  </li>
	</ul>
	
</div>
`;			

class MenuComponent extends ServerComponent{
	constructor () {
		super();

		this.name = "Menu";
		this.attributes = ["data-v-component-menu"],

		this.image ="icons/menu.svg";
		this.html = template;

		let options = [];
		for (let i in Vvveb.data.menu) { 
			let menu = Vvveb.data.menu[i];
			options.push({value:menu.slug, text:menu.name});
		}
				
		this.properties = [{
			name: "Menu to display",
			group:"automatic",
			key: "order",
			col:12,
			inline:false,
			htmlAttr:"data-v-slug",
			inputtype: SelectInput,
			data: {
				options
			}
		}];
	}

    init(node) {
	}
}

let menuComponent = new MenuComponent;

export {
  menuComponent
};
