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
<form action="/checkout" method="post" enctype="multipart/form-data">
  <div class="container">
	<div class="row">

	  <div class="col-12 col-md-7">
		<div class="card" style="--bs-card-spacer-y: 1.5rem; --bs-card-spacer-x: 1.5rem; ">
		  <div class="card-body">
			<div class="row " data-v-if-not="this.global.user_id">

			  <div class="mb-3 col-6" id="first_name_group">
				<label class="col-form-label" for="first_name">First Name <span class="text-danger text-small">*</span>
				</label>
				<input type="text" class="form-control" id="first_name" name="first_name" value="" minlength="3" required="">
			  </div>
			  <div class="mb-3 col-6" id="last_name_group">
				<label class="col-form-label" for="last_name">Last Name <span class="text-danger text-small">*</span>
				</label>
				<input type="text" class="form-control" id="last_name" name="last_name" value="" minlength="3" required="">
			  </div>
			  <div class="mb-3 col-12 mb-3" id="email_group">
				<label class="col-form-label" for="email">Email Address <span class="text-danger text-small">*</span>
				</label>
				<input type="email" class="form-control" id="email" name="email" value="" required="">
			  </div>
			  

			  <div class="mb-3">
				<div class=" " data-v-if-not="this.global.user_id">
				  <div class="form-check form-check-inline">
					<label class="form-check-label" for="register-account-check">
					  <input class="form-check-input" type="radio" value="true" id="register-account-check" name="register" checked="" onclick="toggleRegister(this)">
					  <span>Register account</span>
					</label>
				  </div>

				  <div class="form-check form-check-inline">
					<label class="form-check-label" for="guest-check">
					  <input class="form-check-input" type="radio" value="false" id="guest-check" name="register" onclick="toggleRegister(this)">
					  <span>Guest checkout</span>
					</label>
				  </div>
				</div>
				<div class="row mb-3 register-account " id="register-account" data-v-if-not="this.global.user_id">
				  <label class="col-form-label" for="register-password">Password</label>

				  <div class="input-group">
					<input type="password" minlength="4" autocorrect="off" autocomplete="current-password" class="form-control" placeholder="Password" id="register-password" name="password" value="" aria-label="Password" required="">
					<div class="input-group-append">
					  <button class="btn px-3 border border-start-0" type="button" onclick="togglePasswordInput(this,'register-password')">
						<i class="la la-eye-slash"></i>
					  </button>
					</div>
				  </div>
				</div>                      </div>


			</div>
			<div class="row" data-v-component-address="">                      

			  
			  <div class="billing_address">
				<h5>Billing Address</h5>

				<div class="row">
				  
				  <div class="mb-3 col-12 mb-3" id="company_group">
					<label class="col-form-label" for="billing_company">Company Name</label>
					<input type="text" class="form-control" id="billing_company" name="billing_address[company]" value="">
				  </div>

				  <div class="col-12 mb-3">
					<label class="col-form-label" for="country">Country <span class="text-danger text-small">*</span>
					</label>
					<select class="form-select" id="billing_country_id" name="billing_address[country_id]" data-v-countries="" required="" data-v-region-id="0">
					  <option value="222" data-v-option="">United Kingdom</option><option value="223" data-v-option="">United States</option>                              
					</select>
				  </div>
				  <div class="col-12 mb-3">
					<label class="col-form-label" for="country">region <span class="text-danger text-small">*</span>
					</label>
					<select class="form-select" id="billing_region_id" name="billing_address[region_id]" data-v-regions="" required=""><option value="3513">Aberdeen</option></select>
				  </div>
				  <div class="col-12 mb-3">
					<label class="col-form-label" for="street_address">Address <span class="text-danger text-small">*</span>
					</label>
					<input type="text" class="form-control mb-3" id="billing_address_1" name="billing_address[address_1]" value="" placeholder="Street address" minlength="5" required="">
					<input type="text" class="form-control" id="billing_address_2" name="billing_address[address_2]" placeholder="Apartment, suite, unit etc. (optional)" minlength="3" value="">
				  </div>
				  <div class="col-12 mb-3">
					<label class="col-form-label" for="post_code">Postcode <span class="text-danger text-small">*</span>
					</label>
					<input type="text" class="form-control" id="billing_post_code" name="billing_address[post_code]" minlength="3" value="">
				  </div>
				  <div class="col-12 mb-3">
					<label class="col-form-label" for="city">Town/City <span class="text-danger text-small">*</span>
					</label>
					<input type="text" class="form-control" id="billing_city" name="billing_address[city]" minlength="3" value="" required="">
				  </div>
				  <div class="col-12 mb-3">
					<label class="col-form-label" for="phone_number">Phone No <span class="text-danger text-small">*</span>
					</label>
					<input type="text" class="form-control" id="phone_number" name="phone_number" min="0" placeholder="Phone number" minlength="3" value="">
				  </div>

				</div>
			  </div>

			</div>


			<div class="form-check mb-1 form-control-lg">
			  <input class="form-check-input" type="checkbox" value="true" id="shipping-form-check" name="no_shipping" onclick="toggleShippingAddress(this)">
			  <label class="form-check-label text-small" for="shipping-form-check">
Ship To A Different Address </label>
			</div>

			<div id="checkout-shipping-container" class="shipping_address mb-2" style="display: none;">
			  <h5>Shipping Address</h5>

			  <div class="row">
				<div class="mb-3 col-6" id="first_name_group">
				  <label class="col-form-label" for="first_name">First Name <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="first_name" name="shipping_address[first_name]" value="" minlength="3" required="" disabled="">
				</div>
				<div class="mb-3 col-6" id="last_name_group">
				  <label class="col-form-label" for="last_name">Last Name <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="last_name" name="shipping_address[last_name]" value="" minlength="3" required="" disabled="">
				</div>
				<div class="mb-3 col-12 mb-3" id="email_group">
				  <label class="col-form-label" for="email">Email Address <span class="text-danger text-small">*</span>
				  </label>
				  <input type="email" class="form-control" id="email" name="shipping_address[email]" value="" required="" disabled="">
				</div>
				<div class="mb-3 col-12 mb-3" id="company_group">
				  <label class="col-form-label" for="company">Company Name</label>
				  <input type="text" class="form-control" id="company" name="shipping_address[company]" value="" disabled="">
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="country">Country <span class="text-danger text-small">*</span>
				  </label>
				  <select class="form-select" id="shipping_country_id" name="shipping_address[country_id]" data-v-countries="" disabled="">
					<option value="222" data-v-country="" data-v-country-country_id="">
					  United Kingdom
					</option><option value="223" data-v-country="" data-v-country-country_id="">
					  United States
					</option>                            
				  </select>
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="country">region <span class="text-danger text-small">*</span>
				  </label>
				  <select class="form-select" id="shipping_region_id" name="shipping_address[region_id]" data-v-regions=""><option value="3513">Aberdeen</option></select>
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="street_address">Address <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control mb-3" id="shipping_shipping_address_1" name="shipping_address[address_1]" value="" placeholder="Street address" minlength="5" required="" disabled="">
				  <input type="text" class="form-control" id="shipping_shipping_address_2" name="shipping_address[address_2]" placeholder="Apartment, suite, unit etc. (optional)" minlength="3" value="" disabled="">
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="post_code">Postcode <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="shipping_post_code" name="shipping_address[post_code]" minlength="3" value="" disabled="">
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="city">Town/City <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="shipping_city" name="shipping_address[city]" minlength="3" value="" disabled="">
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="state">Province <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="shipping_region_id" name="shipping_address[region_id]" minlength="3" value="" disabled="">
				</div>
				<div class="col-12 mb-3">
				  <label class="col-form-label" for="phone_number">Phone No <span class="text-danger text-small">*</span>
				  </label>
				  <input type="text" class="form-control" id="phone_number" name="shipping_address[phone_number]" min="0" placeholder="Phone number" minlength="3" value="" disabled="">
				</div>

			  </div>
			</div>

			<div class="mb-3">
			  <div class="form-check mb-1">
				<input type="checkbox" class="form-check-input" id="terms" name="terms" required="">
				<label class="form-check-label" for="terms">
I agree to <a href="/page/terms-conditions" target="_blank" data-v-url="content/page/index" data-v-url-params="{&quot;slug&quot;:&quot;terms-conditions&quot;}">Terms and conditions</a>
				</label>
			  </div>
			  <div class="form-check mb-1">
				<input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
				<label class="form-check-label" for="newsletter">Subscribe to our newsletter</label>
			  </div>
			</div>

		  </div>
		</div>
	  </div>

	  <div class="col-12 col-md-5 ms-lg-auto">
		<div class="card">
		  <div class="card-body">

			<div data-v-component-cart="">                      <div class="table-responsive mb-3">
				<table class="table align-middle mb-0">
				  <tbody>

					<tr data-v-cart-product="" data-product_id="19">
					  <td class="text-center">
						<a href="/checkout?module=product&amp;product_id=19" data-v-cart-product-url="">
						  <img src="/public/media/product/9-1.jpg" alt="iPhone" class="img-rounded" data-v-cart-product-image="" width="50">
						</a>
					  </td>
					  <td class="text-center">
						<a href="/checkout?module=product&amp;product_id=19" class="d-block" data-v-cart-product-url="" data-v-cart-product-name="">Product 19</a>
					  </td>
					  <td class="text-end">
						<span class="text-small">
						  <span data-v-cart-product-quantity="">1</span>
						  <span class="text-muted">x</span>
						  <span data-v-cart-product-price_tax_formatted="">$217.9891</span>
						</span>
					  </td>

					</tr><tr data-v-cart-product="" data-product_id="18">
					  <td class="text-center">
						<a href="/checkout?module=product&amp;product_id=18" data-v-cart-product-url="">
						  <img src="/public/media/product/8-1.jpg" alt="iPhone" class="img-rounded" data-v-cart-product-image="" width="50">
						</a>
					  </td>
					  <td class="text-center">
						<a href="/checkout?module=product&amp;product_id=18" class="d-block" data-v-cart-product-url="" data-v-cart-product-name="">Product 18</a>
					  </td>
					  <td class="text-end">
						<span class="text-small">
						  <span data-v-cart-product-quantity="">1</span>
						  <span class="text-muted">x</span>
						  <span data-v-cart-product-price_tax_formatted="">$109</span>
						</span>
					  </td>

					</tr>                            
				
				 </tbody>

				</table>
			  </div>

			  <div class="p-3 pt-0 " data-v-if="cart.total_items">
				<div class="table-responsive mb-2" data-v-cart-totals="">
				  <table class="table mb-0 cart-table cart-total" cellspacing="0">
					<tfoot>
					  <tr data-v-cart-total="">
						<td colspan="5" class="text-end">
						  <small data-v-cart-total-title="">Sub-total</small>: </td>
						<td class="text-end">
															<span data-v-cart-total-value_formatted="" data-v-if="total.value > 0" class=" ">$299</span>                                
															</td>
					  </tr><tr data-v-cart-total="">
						<td colspan="5" class="text-end">
						  <small data-v-cart-total-title="">Flat rate shipping</small>: </td>
						<td class="text-end">
						  <span data-v-cart-total-text="" data-v-if="total.text" class=" ">Free shipping</span>                                                                  
						  </td>
					  </tr><tr data-v-cart-total="">
						<td colspan="5" class="text-end">
						  <small data-v-cart-total-title="">Pick up shipping</small>: </td>
						<td class="text-end">
						  <span data-v-cart-total-text="" data-v-if="total.text" class=" ">Free shipping</span>                                                                  
						  </td>
					  </tr><tr data-v-cart-total="">
						<td colspan="5" class="text-end">
						  <small data-v-cart-total-title="">VAT (9%)</small>: </td>
						<td class="text-end">
							<span data-v-cart-total-value_formatted="" data-v-if="total.value > 0" class=" ">$26.9991</span>                                
							</td>
					  </tr>                              
					  
					  
					  <tr>
						<td colspan="5" class="text-end">Total:</td>
						<td class="text-end" data-v-grand-total_formatted="">$325.9991</td>
					  </tr>
					</tfoot>

				  </table>
				</div>

			  </div>                    
			  </div>


			<div class="input-group mb-3">
			  <input type="text" class="form-control" id="coupon_code" placeholder="Coupon Code" aria-label="Coupon Code" aria-describedby="button-addon2" value="">
			  <button class="btn btn-primary btn-sm px-4" type="button">Apply</button>
			</div>

			<h6>Shipping</h6>
			<div id="accordion" name="accordion" role="tablist" class="accordion mb-3" data-v-component-checkout-shipping="">
			  <div class="accordion-item" data-v-shipping="" data-shipping_id="">
				<div class="accordion-header" role="tab">
				  <label class="form-check-label accordion-button collapsed" aria-expanded="false" role="button">
					<input class="form-check-input me-2" type="radio" name="shipping_method" value="flat-rate" data-v-shipping-name="" required="">
					<span data-v-shipping-title="">Flat rate</span>
				  </label>
				</div>

				<div class="collapse" role="tabpanel">
				  <div class="accordion-body">
					<p>
					  <span data-v-shipping-description="">Fixed shipping rate</span>
					</p>
				  </div>
				</div>
			  </div><div class="accordion-item" data-v-shipping="" data-shipping_id="">
				<div class="accordion-header" role="tab">
				  <label class="form-check-label accordion-button collapsed" aria-expanded="false" role="button">
					<input class="form-check-input me-2" type="radio" name="shipping_method" value="Pick up" data-v-shipping-name="" required="">
					<span data-v-shipping-title="">Pick up</span>
				  </label>
				</div>

				<div class="collapse" role="tabpanel">
				  <div class="accordion-body">
					<p>
					  <span data-v-shipping-description="">Pick up from store</span>
					</p>
				  </div>
				</div>
			  </div><div class="accordion-item" data-v-shipping="" data-shipping_id="">
				<div class="accordion-header" role="tab">
				  <label class="form-check-label accordion-button collapsed" aria-expanded="false" role="button">
					<input class="form-check-input me-2" type="radio" name="shipping_method" value="weight-shipping" data-v-shipping-name="" required="">
					<span data-v-shipping-title="">Weight shipping</span>
				  </label>
				</div>

				<div class="collapse" role="tabpanel">
				  <div class="accordion-body">
					<p>
					  <span data-v-shipping-description="">Weight based shipping</span>
					</p>
				  </div>
				</div>
			  </div>
			  

			  
			</div>


			<h6>Payment</h6>
			<div id="accordion" name="accordion" role="tablist" class="accordion mb-3" data-v-component-checkout-payment="">
			  <div class="accordion-item" data-v-payment="" data-payment_id="">
				<div class="accordion-header" role="tab">
				  <label class="form-check-label accordion-button collapsed" aria-expanded="false" role="button">
					<input class="form-check-input me-2" type="radio" name="payment_method" value="bank-transfer" data-v-payment-name="" required="">
					<span data-v-payment-title="">Bank transfer</span>
				  </label>
				</div>

				<div class="collapse" role="tabpanel">
				  <div class="accordion-body">
					<p>
					  <span data-v-payment-description="">Bank transfer details</span>
					</p>
				  </div>
				</div>
			  </div><div class="accordion-item" data-v-payment="" data-payment_id="">
				<div class="accordion-header" role="tab">
				  <label class="form-check-label accordion-button collapsed" aria-expanded="false" role="button">
					<input class="form-check-input me-2" type="radio" name="payment_method" value="cash-on-delivery" data-v-payment-name="" required="">
					<span data-v-payment-title="">Cash on delivery</span>
				  </label>
				</div>

				<div class="collapse" role="tabpanel">
				  <div class="accordion-body">
					<p>
					  <span data-v-payment-description="">Pay cash on delivery</span>
					</p>
				  </div>
				</div>
			  </div>                      


				</div>


			<div class="mb-3">
			  <label for="comment">Order Notes</label>
			  <textarea name="comment" id="comment" cols="30" rows="5" class="form-control" placeholder=""></textarea>
			</div>

			<button type="submit" href="" class="btn btn-primary w-100" data-v-url="checkout/checkout/confirm">
Place order <i class="la la-arrow-right"></i>
			</button>
		  </div>
		</div>
	  </div>
	</div>
  </div>
</form>
`;

 class CheckoutComponent {
	constructor ()
	{
		this.name = "Checkout";
		this.attributes = ["data-v-component-checkout"],

		this.image ="icons/checkout.svg";
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

let checkoutComponent = new CheckoutComponent;

export {
  checkoutComponent
};
