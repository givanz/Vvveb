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
<div class="mini-cart" data-v-component-cart>
	
	<a class="cart-info nav-link " href role="button" id="cart-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-v-url="cart/cart/index">
		<i class="la la-lg la-shopping-bag"></i>
		<strong class="text-top text-bold" data-v-total_items data-v-if="cart.total_items > 0"></strong>
	</a>
					
					
	<div class="cart-box" aria-labelledby="cart-dropdown">					

	<div>
        <div class="table-responsive">
			<table class="table cart-table align-middle mb-0">
				<tbody>
					
					
					<tr data-v-cart-product>
						<td class="text-center">
							<a href="#40" data-v-cart-product-url>
								<img src="img/demo/product.jpg" alt="iPhone" class="img-rounded" data-v-cart-product-image width=50>
							</a>
						</td>
						<td class="text-start">
							<a href="#40" class="d-block" data-v-cart-product-url data-v-cart-product-name>
								iPhone 5
							</a>
							
							<span data-v-cart-product-quantity>1</span> 
							<i class="la la-times text-muted"></i>
							<span data-v-cart-product-price_tax_formatted>$123.20</span>
						</td>
						<td class="text-end">
							<a type="button" class="btn btn-outline-secondary btn-sm border-0" data-v-vvveb-action="removeFromCart" data-v-cart-product-remove-url>
								<i class="la la-times"></i>
							</a>
						</td>
					</tr>
					<tr data-v-cart-product>
						<td class="text-center">
							<a href="#40" data-v-cart-product-url>
								<img src="img/demo/product.jpg" alt="iPhone" class="img-rounded" data-v-cart-product-image width=50>
							</a>
						</td>
						<td class="text-start">
							<a href="#40" class="d-block" data-v-cart-product-url data-v-cart-product-name>
								iPhone 5
							</a>
							
							<span data-v-cart-product-quantity>1</span> 
							<i class="la la-times text-muted"></i>
							<span data-v-cart-product-price_tax_formatted>$123.20</span>
						</td>
						<td class="text-end">
							<a type="button" class="btn btn-outline-secondary btn-sm border-0" data-v-vvveb-action="removeFromCart" data-v-cart-product-remove-url>
								<i class="la la-times"></i>
							</a>
						</td>
					</tr>
					<tr data-v-if-not="cart.total_items">
							<td colspan="100">
								<div class="d-flex  p-2">
									<div class="text-center p-2 opacity-75">
										<!-- <img src="img/bag.svg" width="20" alt> -->
										<i class="la la-2x la-shopping-bag"></i>
									</div>
									<div class="p-2">
										<strong>Empty cart</strong><br>
										<span class="text-muted">No products added yet!</span>
									</div>
								</div>
							</td>
					</tr>
				 </tbody>

		  </table>
		  </div>
		  
		  <div class="p-3 pt-0 border-top" data-v-if="cart.total_items">
				<div class="table-responsive mb-2" data-v-cart-totals>
					<table class="table mb-0 cart-table cart-total" cellspacing="0">
						  <tfoot>
							  <tr data-v-cart-total>
								 <td colspan="5" class="text-end"><small data-v-cart-total-title>Sub-Total</small>:</td>
								 <td class="text-end">
									<span data-v-cart-total-text data-v-if="total.text"> - </span>
									<span data-v-cart-total-value_formatted data-v-if="total.value > 0">$101.00</span>
								 </td>
							  </tr>
							  <tr data-v-cart-total>
								 <td colspan="5" class="text-end"><small>Eco Tax (2.00):</small></td>
								 <td class="text-end">$2.00</td>
							  </tr>
							  <tr data-v-cart-total>
								 <td colspan="5" class="text-end"><small>VAT (19%):</small></td>
								 <td class="text-end">$20.20</td>
							  </tr>
							  <tr data-v-cart-total>
								 <td colspan="5" class="text-end"><small>Total:</small></td>
								 <td class="text-end">$123.20</td>
							  </tr> 
							  <tr>
								 <td colspan="5" class="text-end">Total:</td>
								 <td class="text-end" data-v-grand-total_formatted>$0</td>
							  </tr>
						   </tfoot>

						</table>
				</div>

		</div>

	  <div class="row mt-2 g-2 px-3 pb-2" data-v-if="cart.total_items">
		<div class="col-6">
			<a href="" class="btn btn-light btn-sm border w-100" data-v-url="cart/cart/index">
				<i class="la la-shopping-cart la-lg"></i><span>View cart</span>
			</a>
		  </div>
		  <div class="col-6">
			<a href="" class="btn btn-primary btn-sm w-100" data-v-url="checkout/checkout/index">
				<span>Checkout</span><i class="la la-arrow-right la-lg"></i>
			</a>
		  </div>
	  </div>


	</div>
	</div>
		
</div>
`;

 class CartComponent {
	constructor ()
	{
		this.name = "Cart";
		this.attributes = ["data-v-component-cart"],

		this.image ="icons/cart.svg";
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

let cartComponent = new CartComponent;

export {
  cartComponent
};
