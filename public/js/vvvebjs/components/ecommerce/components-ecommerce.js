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

import {productComponent} from './ecommerce/products.js';
import {productsComponent} from './ecommerce/products.js';
import {categoriesComponent} from './ecommerce/categories.js';
import {manufacturersComponent} from './ecommerce/manufacturers.js';
import {cartComponent} from './ecommerce/cart.js';
import {checkoutComponent} from './ecommerce/checkout.js';
import {filtersComponent} from './ecommerce/filters.js';

Vvveb.Components.add("ecommerce/product", productComponent);
Vvveb.Components.add("ecommerce/products", productsComponent);
Vvveb.Components.add("ecommerce/productGallery", productGalleryComponent);
Vvveb.Components.add("ecommerce/categories", categoriesComponent);
Vvveb.Components.add("ecommerce/manufacturers", manufacturersComponent);
Vvveb.Components.add("ecommerce/cart", cartComponent);
Vvveb.Components.add("ecommerce/checkout", checkoutComponent);
Vvveb.Components.add("ecommerce/filters", filtersComponent);



Vvveb.ComponentsGroup['ecommerce'] = ["ecommerce/products", "ecommerce/product", "ecommerce/categories", "ecommerce/manufacturers", "ecommerce/search", "ecommerce/user", "ecommerce/product_gallery", "ecommerce/cart", "ecommerce/checkout", "ecommerce/filters", "ecommerce/product", "ecommerce/slider"];