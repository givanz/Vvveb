document.addEventListener('change', function (e) {
	let element = e.target.closest('[name="quantity"]');
	if (element) {
		let product = element.closest("[data-v-cart-product]");
		if (product) {
			let key = product.dataset.key;
			let product_id = product.dataset.product_id;
			let quantity = element.value;
			let updateElements = ['#cart-container [data-key="' + key + '"] .price', '#cart-container [data-key="' + key + '"] .total', ".cart-right-column", ".mini-cart"];
			
			delay(() => VvvebTheme.Cart.update(key ?? product_id, {quantity}, element, updateElements), 1000);
		}
	}
});

document.addEventListener('click', function (e) {
	let element = e.target.closest('.btn-coupon, .btn-remove-coupon');
	if (element) {
		let updateElements = [".cart-right-column", ".mini-cart"];
		if (element.classList.contains("btn-remove-coupon")) {
			let coupon = element.parentNode.querySelector(".code").innerHTML;
			let container = e.target.closest("[data-v-cart-coupon]");
			
			VvvebTheme.Cart.removeCoupon({coupon}, element, updateElements);
			container.remove();
		} else {
			let coupon = document.querySelector("[name='coupon']").value;
			VvvebTheme.Cart.coupon({coupon}, element, updateElements);
		}
		e.preventDefault();
	} else
	if (element = e.target.closest('.btn-remove')) {
		let product = element.closest("[data-v-cart-product]");
		if (product) {
			let key = product.dataset.key;
			let quantity = element.value;
			let updateElements = [".cart-right-column", ".mini-cart"];

			product.classList.add("opacity-50");
			VvvebTheme.Cart.remove(key, element, updateElements, () => {
				product.remove();

				//if on cart page and cart empty refresh page
				let cartContainer = document.getElementById("cart-container");
				if (cartContainer && 
					cartContainer.querySelectorAll("[data-v-cart-product]").length == 0 ) {
					cartContainer.remove();
					location.reload();
				}	

			});
			
			e.preventDefault();
		}
	} else
	if (element = e.target.closest(".btn-plus")) {
		let nrInput = element.parentNode.querySelector("input[type=number]");
		nrInput.value = parseInt(nrInput.value) + 1;
		nrInput.dispatchEvent(new KeyboardEvent("change", {
			bubbles: true,
			cancelable: true,
		}));		
		
		e.preventDefault();
		return false;
	} else
	if (element = e.target.closest(".btn-minus")) {
		let nrInput = element.parentNode.querySelector("input[type=number]");
		nrInput.value = Math.max(1, parseInt(nrInput.value) - 1);
		nrInput.dispatchEvent(new KeyboardEvent("change", {
			bubbles: true,
			cancelable: true,
		}));
		
		e.preventDefault();
		return false;		
	}
});
