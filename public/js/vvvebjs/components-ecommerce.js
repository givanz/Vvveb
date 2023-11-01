/*
Copyright 2017 Ziadin Givan

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

https://github.com/givanz/VvvebJs
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

/*
 
 Orders
 */

Vvveb.ComponentsGroup['ecommerce'] = ["ecommerce/products", "ecommerce/product", "ecommerce/categories", "ecommerce/manufacturers", "ecommerce/search", "ecommerce/user", "ecommerce/product_gallery", "ecommerce/cart", "ecommerce/checkout", "ecommerce/filters", "ecommerce/product", "ecommerce/slider", "ecommerce/reviews", "ecommerce/questions"];
