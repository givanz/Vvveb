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
`
<div class="container"  data-v-component-products="popular" data-v-limit="1">
	<div class="row">
		<div class="col-md-3" data-v-product>
			<article class="single-product-wrapper">
				<!-- Product Image -->
				<a href="product/product.html" data-v-product-url> </a>
				<div class="product-image">
					<a href="product/product.html" data-v-product-url>

						<img src="img/demo/product.jpg" data-v-product-alt alt="" data-v-product-image="thumb"/>

						<!-- Hover Thumb -->
						<img class="hover-img" src="img/demo/product-2.jpg" data-v-product-alt alt="" data-v-product-image-1="thumb" />
					</a>

					<!-- Favourite -->
					<div class="product-favourite">
						<a href="product/product.html" data-v-product-url data-v-product-title class="la la-heart"></a>
					</div>
				</div>

				<!-- Product Description -->
				<div class="product-content">
					
					<a href="product/product.html" data-v-product-url>
						<h6 data-v-product-name>Product 8</h6>
					</a>
					
					<p class="product-price" data-v-product-price_tax_formatted>100.0000</p>

					<!-- Hover Content -->
					<div class="hover-content">
						<!-- Add to Cart -->
						<div class="add-to-cart-btn">
							<input type="hidden" name="product_id" value="" data-v-product-product_id />
							<a href="" class="btn btn-primary w-100" data-v-product-url="cart/cart/index" data-v-vvveb-action="addToCart" data-product_id="1">
								<span class="loading d-none">
									<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"> </span>
									<span>Add to cart</span>...
								</span>

								<span class="button-text">
									Add to cart
								</span>
							</a>
						</div>
					</div>
				</div>
			</article>
		</div>
	</div>
</div>			
`;

class ProductsComponent extends ServerComponent{
	constructor ()
	{
		super();

		this.name = "Products";
		this.attributes = ["data-v-component-products"],

		this.image ="icons/products.svg";
		this.html = template;
		
		this.properties = [{
			name: false,
			key: "source",
			inputtype: RadioButtonInput,
			inline:false,
			col:12,
			htmlAttr:"data-v-source",
			data: {
				inline: true,
				extraclass:"btn-group-fullwidth",
				options: [{
					value: "automatic",
					icon:"la la-cog",
					text: "Configuration",
					title: "Configuration",
					extraclass:"btn-sm",
					checked:true,
				}, {
					value: "autocomplete",
					text: "Autocomplete",
					title: "Autocomplete",
					icon:"la la-search",
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
			name: "Products",
			key: "products",
			group:"autocomplete",
			htmlAttr:"data-v-product_id",
			inline:false,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/autocomplete&action=products",
			},
		},{
			name: "Nr. of products",
			group:"automatic",
			col:6,
			inline:false,
			key: "limit",
			htmlAttr:"data-v-limit",
			inputtype: NumberInput,
			data: {
				value: "8",//default
				min: "1",
				max: "1024",
				step: "1"
			},        
		},{
			name: "Start from page",
			group:"automatic",
			col:6,
			inline:false,
			key: "page",
			htmlAttr:"data-v-page",
			data: {
				value: "1",//default
				min: "1",
				max: "1024",
				step: "1"
			},        
			inputtype: NumberInput,
		},{
			name: "Order by",
			group:"automatic",
			key: "order",
			col:6,
			inline:false,
			htmlAttr:"data-v-order_by",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "NULL",
					text: "Default"
				}, {
					value: "price",
					text: "Price"
				}, {
					value: "created_at",
					text: "Date added"
				}, {
					value: "updated_at",
					text: "Date modified"
				}/*, {
					value: "sales",
					text: "Sales"
				}*/]
			}
		},{	
			name: "Order direction",
			group:"automatic",
			key: "order",
			col:6,
			inline:false,
			htmlAttr:"data-v-direction",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "asc",
					text: "Ascending"
				}, {
					value: "desc",
					text: "Descending"
				}]
			}
		},{
			name: "Limit to categories",
			group:"automatic",
			key: "category",
			htmlAttr:"data-v-category",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor/autocomplete&action=categories",
			},

		},{
			name: "Limit to manufacturers",
			group:"automatic",
			key: "manufacturer",
			htmlAttr:"data-v-manufacturer",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor/autocomplete&action=manufacturers",
			},
		}];
	}

    init(node)	{
		
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 
		$('.mb-3[data-group="'+ source + '"]').attr('style','');
	}
}

let productsComponent = new ProductsComponent;

export {
  productsComponent
};
