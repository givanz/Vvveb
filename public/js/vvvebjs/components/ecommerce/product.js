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
<section class="container product" data-v-component-product>


	<div class="row g-0">
		<div class="col-md-6 col-sm-12">
		
			<div id="product-gallery" class="carousel slide" data-bs-ride="carousel" data-bs-touch="true" data-v-product-images>
			  <div class="carousel-inner">
				<div class="carousel-item" data-v-product-image data-v-class-if-active="i = 0" >
					<div class="zoom" data-v-product-image-background-image>
						<img src="img/demo/product.jpg" class="d-block w-100" alt="" data-v-product-image-src>
					</div>
				</div>
				<div class="carousel-item" data-v-product-image>
					<div class="zoom" data-v-product-image-background-image>
						<img src="img/demo/product-2.jpg" class="d-block w-100" alt="" data-v-product-image-src>
					</div>
				</div>
			  </div>
			  <button class="carousel-control-prev" type="button" data-bs-target="#product-gallery" data-bs-slide="prev">
				<span class="carousel-control-prev-icon" aria-hidden="true"></span>
				<span class="visually-hidden">Previous</span>
			  </button>
			  <button class="carousel-control-next" type="button" data-bs-target="#product-gallery" data-bs-slide="next">
				<span class="carousel-control-next-icon" aria-hidden="true"></span>
				<span class="visually-hidden">Next</span>
			  </button>
		  </div>
		  
		  <div class="carousel">
		  
			<div class="carousel-thumbs" data-v-product-images>
				<button type="button" data-bs-target="#product-gallery" class="img-thumbnail" data-bs-slide-to="0" data-v-product-image>
					<img src="" alt="" class="d-block w-100" data-v-product-image-src>
				</button>
				<button type="button" data-bs-target="#product-gallery" class="img-thumbnail" data-bs-slide-to="1" data-v-product-image>
					<img src="" alt="" class="d-block w-100" data-v-product-image-src>
				</button>
				<button type="button" data-bs-target="#product-gallery" class="img-thumbnail" data-bs-slide-to="2" data-v-product-image>
					<img src="" alt="" class="d-block w-100" data-v-product-image-src>
				</button>
			</div>
			
		  </div>
	
	
		
		</div>

		<div class="col-md-6 col-sm-12 p-4" id="product">
			<a href="#"><span class="text-muted">mango</span></a>
			
			<h1 class="product-name" data-v-product-name>One Shoulder Glitter Midi Dress</h1>
			
			
			<div class="mb-2">
				<small class="text-warning"> 
					<i class="la la-star"></i>
					<i class="la la-star"></i>
					<i class="la la-star"></i>
					<i class="la la-star"></i>
					<i class="la la-star-half"></i>
				</small>
				<a href="#reviews-tab-pane" class="ms-2" data-bs-toggle="tab" type="button" data-bs-target="#reviews-tab-pane">(30 reviews)</a>
			</div>

			<p class="product-price">
				<span class="price" data-v-product-price_tax_formatted>$49.00</span>
				<span class="text-decoration-line-through text-secondary text-opacity-75">$350</span>
				<small class="fs-6 ms-2 text-danger">26% Off</small>
			</p>
			
			<!--
			<p class="product-price fs-3">
				<span class="old-price text-muted text-small align-middle text-decoration-line-through" data-v-product-price-discount>$65.00</span>
				<span class="old-currency text-muted text-small align-middle text-decoration-line-through">$</span>
				<span class="price fw-bold" data-v-product-price_tax>$49.00</span>
				<span class="currency">$</span>
			</p>
			-->

			<!-- Form -->
			<form class="cart-form clearfix" method="post" action="/cart">
			
				<div class="cart-fav-box ">
					<!-- Cart -->
					<!-- button type="submit" name="addtocart" value="5" class="btn btn-primary" data-v-product-id data-v-vvveb-action="addToCart">Add to cart</button -->
					
						<input type="hidden" name="product_id" data-v-product-product_id>
						


						<div>
					
							<hr class="border opacity-50">

							<div class="form-group mt-5">
								
								<div class="quantity">
									<div class="input-group spinner">
										<button class="btn btn-minus"><i class="la la-minus"></i></button>
										<input type="number" name="quantity" value="1" size="1" id="input-quantity" class="form-control">
										<button class="btn btn-plus"><i class="la la-plus"></i></button>
									</div>
								</div>

								<button type="button" formaction="/cart/1" id="button-cart" data-loading-text="Loading..." class="btn btn-primary btn-shadow px-4 mx-2 button-cart" data-v-vvveb-action="addToCart">
								
									<span class="loading d-none">
										<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
										</span>
										<span>Add to cart</span>...
									</span>

									<span class="button-text" >
										<i class="la la-shopping-bag la-lg me-2"></i> <span>Add to cart</span>
									</span>
									
								</button> 

								<button type="button" formaction="/checkout/1" id="buynow" data-checkout="nicocheckout" data-loading-text="Loading..." class="btn btn-light btn-shadow border px-4 buynow" data-v-vvveb-action="addToCart">
								
									<span class="loading d-none">
										<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
										</span>
										<span >Add to cart</span>...
									</span>

									<span class="button-text" >
										<span>Buy now</span> <i class="la la-arrow-right la-lg ms-2"></i> 
									</span>
									
								</button>

								<input type="hidden" name="product_id" value="34">&nbsp;

								<div class="product_wish_compare mt-3">
									<button type="button" class="btn btn-sm btn-outline-secondary border-0" title="Add to Wish List"><i class="la la-heart"></i> Add to Wish List</button>
									<button type="button" class="btn btn-sm btn-outline-secondary border-0" title="Compare this Product"><i class="la la-random"></i> Compare this Product</button>
								</div>

							</div>

						</div>
						<!-- 
						<div class="row g-2">
							<div class="col-auto">

								<input name="quantity[3]" value="1" size="5" class="form-control" type="number">  
								
							</div>

							<div class="col-auto">
								<a href="#" class="btn btn-primary px-5" data-v-vvveb-action="addToCart">
								
									<span class="loading d-none">
										<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
										</span>
										<span >Add to cart</span>...
									</span>

									<span class="button-text" >
										<i class="la la-shopping-bag la-lg me-2"></i> Add to cart
									</span>
								
								
								</a>   	
							</div>

							<div class="col-auto">
						
								<a href="#" class="btn btn-light border" data-v-vvveb-action="addToCart">
								
									<span class="loading d-none">
										<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
										</span>
										<span >Add to favorites</span>...
									</span>

									<span class="button-text" >
										<i class="la la-heart la-lg"></i>
									</span>
								
								
								</a>  					
							</div>
							
							<div class="col-auto">
						
									<a href="#" class="btn btn-light border" data-v-vvveb-action="addToCart">
									
										<span class="loading d-none">
											<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
											</span>
											<span >Add to compare</span>...
										</span>

										<span class="button-text" >
											<i class="la la-random la-lg"></i>
										</span>
									
									
									</a>  
								</div>
						</div>
						-->
					
				</div>
			</form>
			
		</div>

			<ul class="nav nav-tabs mt-5" id="productTabs" role="tablist">
			  <li class="nav-item" role="presentation">
				<button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-tab-pane" type="button" role="tab" aria-controls="description-tab-pane" aria-selected="true">
					Description
				</button>
			  </li>
			  <li class="nav-item" role="presentation">
				<button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-tab-pane" type="button" role="tab" aria-controls="reviews-tab-pane" aria-selected="false">
					Reviews
				</button>
			  </li>
			  <li class="nav-item" role="presentation">
				<button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-tab-pane" type="button" role="tab" aria-controls="details-tab-pane" aria-selected="false">
					Details
				</button>
			  </li>			  
			  <li class="nav-item" role="presentation">
				<button class="nav-link" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions-tab-pane" type="button" role="tab" aria-controls="questions-tab-pane" aria-selected="false">
					Questions &amp; Answers
				</button>
			  </li>
			</ul>
			
			<div class="tab-content" id="productTabsContent">
			  <div class="tab-pane show active" id="description-tab-pane" role="tabpanel" aria-labelledby="description-tab" tabindex="0">
			  
				<div class="description" data-v-product-content>
				
					Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin. Praesent at tempus lectus, eleifend blandit felis. Fusce augue arcu, consequat a nisl aliquet, consectetur elementum turpis. Donec iaculis lobortis nisl, et viverra risus imperdiet eu. Etiam mollis posuere elit non sagittis. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc quis arcu a magna sodales venenatis. Integer non diam sit amet magna luctus mollis ac eu nisi. In accumsan tellus ut dapibus blandit.
					
				</div>
			  
			  </div>
			  <div class="tab-pane" id="reviews-tab-pane" role="tabpanel" aria-labelledby="reviews-tab" tabindex="0">
				  
			  </div>
			  <div class="tab-pane" id="details-tab-pane" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
			  </div>
			  <div class="tab-pane" id="questions-tab-pane" role="tabpanel" aria-labelledby="questions-tab" tabindex="0">
				  
			  </div>
			</div>
			
			
	</div>
</section>
`; 
 
class ProductComponent {
	constructor ()
	{
		this.name = "Product";
		this.attributes = ["data-v-component-product"],

		this.image ="icons/product.svg";
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
				return node.dataset.source;
			},            
		},{
			name: "Product",
			key: "product",
			group:"autocomplete",
			htmlAttr:"data-v-product",
			inline:false,
			col:12,
			inputtype: AutocompleteList,
			data: {
				url: "/admin/?module=editor/editor&action=productAutocomplete",
			},
		},{
			name: "Nr. of product",
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
			getFromNode: node => 10
			,
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
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=productAutocomplete",
			},

		},{
			name: "Manufacturer",
			group:"automatic",
			key: "manufacturer",
			htmlAttr:"data-v-manufacturer",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=productAutocomplete",
			}
		},{
			name: "Manufacturer 2",
			group:"automatic",
			key: "manufacturer 2",
			htmlAttr:"data-v-manufacturer2",
			inline:false,
			col:12,
			inputtype: TagsInput,
			data: {
				url: "/admin/?module=editor&action=productAutocomplete",
			},
		}];
	}

    init(node)
	{
		$('.mb-3[data-v-group]').attr('style','display:none !important');
		if (node.dataset.source != undefined)
		{
			$('.mb-3[data-v-group="'+ node.dataset.source + '"]').attr('style','');
		} else
		{		
			$('.mb-3[data-v-group]:first').attr('style','');
		}
	}
}

let productComponent = new ProductComponent;

export {
  productComponent
};
