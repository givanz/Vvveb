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
 
if (VvvebTheme === undefined) var VvvebTheme = {};

VvvebTheme.Ajax = {
	call: function(url, parameters, element, selector, callback, requestType = "POST") {
		if (!url) {
			url = '/index.php?module=' +  parameters["module"] + '&action=' + parameters["action"];
		}
		
		if (!selector) {
			url += '&_component_ajax=' + parameters["component"] + '&_component_id=' + parameters["component_id"];
		}
	
		let loading = element?.querySelector('.loading');
		let btn = element?.querySelector('.button-text');
		
		if (loading && loading.classList.contains("d-none")) {
			loading.classList.remove('d-none');
			btn.classList.add('d-none');
		}
		
		if (element?.hasAttribute("button"))  {
			element.setAttribute("disabled", "true");
		}
	
		const controller = new AbortController();
		const signal = controller.signal;
		
		 fetch(url, {
			method: requestType,   
			headers: {
			"X-Requested-With": "XMLHttpRequest",
		  },
		  signal: signal,
		  body: new URLSearchParams(parameters)})
		 .then(response => {
			if (!response.ok) { throw new Error(response) }
			if (response.redirected) { 
				controller.abort();
				window.location.href = response.url; 
			}
			
			return response.text()
		 })
		.then(data => {
			if (selector) {
				let response = new DOMParser().parseFromString(data, "text/html");
				if (Array.isArray (selector) ) {
					for (k in selector) {
						let elementSelector = selector[k];
						let currentElement = document.querySelector(elementSelector);
						let newElement = response.querySelector(elementSelector);
						if (currentElement && newElement) {
							currentElement.replaceWith(newElement);
						}
					}
				} else {
					let currentElement = document.querySelector(selector);
					let newElement = response.querySelector(selector);
					if (currentElement && newElement) {
						currentElement.replaceWith(newElement);
					}
				}
			}

			if (callback) callback(data);
			
			let loading = element?.querySelector('.loading');
			let btn = element?.querySelector('.button-text');
			
			if (loading && btn.classList.contains("d-none")) {
				loading.classList.add('d-none');
				btn.classList.remove('d-none');
			}
			
			if (element.hasAttribute("button")) {
				element.removeAttribute("disabled");
			}
		})
		.catch(error => {
			console.log(error);
			//displayToast("bg-danger", "Revision", "Error!");
		});				
	}
}

VvvebTheme.Cart = {
	
	module: 'cart/cart',
	component: 'cart',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback) {
		parameters['module']       = parameters['module'] ?? this.module;
		parameters['action']       = parameters['action']?? action;
		parameters['component']    = parameters['component'] ?? this.component;
		parameters['component_id'] = parameters['component_id'] ?? this.component_id;
		VvvebTheme.Ajax.call("", parameters, element,  selector, callback);
	},
	
	callback: function(data) {
		/*
			let miniCart = document.querySelectorAll("[data-v-component-cart]");
			if (miniCart) {
				miniCart[0].outerHTML = data;
			}*/
	},
	
	add: function(productId, options, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		if (options) {
			options['product_id'] = productId;
		} else {
			options = {'product_id':productId};
		}
		return this.ajax('add',options, element, selector, callback);
	},
	
	update: function(key, options, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		if (options) {
			options['key'] = key;
		} else {
			options = {'key':key};
		}
		return this.ajax('update',options, element, selector, callback);
	},
 
	remove: function(key, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('remove', {'key':key}, element, selector, callback);
	},	
	
	coupon: function(options, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('coupon', options, element, selector, callback);
	},	
	
	removeCoupon: function(options, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('removeCoupon', options, element, selector, callback);
	}
}

VvvebTheme.Wishlist = {
	
	module: 'user/wishlist',
	component: 'wishlist',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback) {
		parameters['module'] = this.module;
		parameters['action'] = action;
		parameters['component'] = this.component;
		parameters['component_id'] = this.component_id;
		VvvebTheme.Ajax.call("", parameters, element,  selector, callback);
	},
	
	callback: function(data) {
	},
	
	add: function(productId, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('add',{'product_id':productId}, element, selector, callback);
	},
	
	update: function(productId, quantity, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('update',{'product_id':productId}, element, selector, callback);
	},
 
	remove: function(productId, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('remove', {'product_id':productId}, element, selector, callback);
	}

}

VvvebTheme.Compare = {
	
	module: 'cart/compare',
	component: 'compare',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback) {
		parameters['module'] = this.module;
		parameters['action'] = action;
		parameters['component'] = this.component;
		parameters['component_id'] = this.component_id;
		VvvebTheme.Ajax.call("", parameters, element,  selector, callback);
	},
	
	callback: function(data) {
	},
	
	add: function(productId, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('add',{'product_id':productId}, element, selector, callback);
	},
	
	update: function(productId, quantity, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('update',{'product_id':productId}, element, selector, callback);
	},
 
	remove: function(productId, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('remove', {'product_id':productId}, element, selector, callback);
	}

}

VvvebTheme.Comments = {
	
	module: 'content/post',
	
	ajax: function(action, parameters, element,  selector, callback = false) {
		parameters['module'] = parameters['module'] ?? this.module;
		parameters['action'] = parameters['action'] ?? action;
		VvvebTheme.Ajax.call("", parameters, element, selector, callback);
	},
	
	add: function(parameters, element,  selector, callback = false) {
		return this.ajax('addComment',parameters, element, selector, callback);
	},
	
	update: function(productId, quantity, element, selector, callback = false) {
		return this.ajax('update',{'product_id':productId, 'quantity':quantity}, selector);
	},
	
	remove: function(productId) {
		return this.ajax('remove', {'product_id':productId}, selector);
	}
}

VvvebTheme.User = {
	
	module: 'user/login',
	component: 'user',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback = false) {
		parameters['module'] = parameters['module'] ?? this.module;
		parameters['action'] = parameters['action'] ?? action;
		parameters['component'] = parameters['component'] ?? this.component;
		parameters['component_id'] = parameters['component_id'] ?? this.component_id;
		
		VvvebTheme.Ajax.call("", parameters, element, selector, callback);
	},
	
	login: function(parameters, element, selector, callback = false) {
		return this.ajax('index' ,parameters, element, selector, callback);
	},
}

VvvebTheme.Search = {
	
	module: 'search',
	component: 'search',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback = false) {
		parameters['module'] = parameters['module'] ?? this.module;
		parameters['action'] = parameters['action'] ?? action;
		parameters['component'] = parameters['component'] ?? this.component;
		parameters['component_id'] = parameters['component_id'] ?? this.component_id;
		
		VvvebTheme.Ajax.call("", parameters, element, selector, callback = false);
	},
	
	query: function(parameters, element, selector, callback) {
		return this.ajax('index' ,parameters, element, selector);
	},
}

VvvebTheme.Alert  = {
	
	show: function(message) {
		let alertTop = document.querySelector('.alert-top');
		alertTop.querySelector(".message").innerHTML = message;
		alertTop.classList.add("show");
		alertTop.style.display = "block";
		
		setTimeout(function () {
			alertTop.style.display = "none";
		}, 4000);
	}
}

document.querySelector('.alert-top .btn-close').addEventListener('click', function (e) {
    let alert = this.closest(".alert");
    alert.classList.remove('show')
    alert.style.display = "";
    e.preventDefault();
});

function objectSerialize(serializeArray) {
    let returnObject = {};
    for (let i = 0; i < serializeArray.length; i++){
        returnObject[serializeArray[i]['name']] = serializeArray[i]['value'];
    }
    return returnObject;
}


function elementProduct(element) {
	let product = element.closest("[data-v-product]");
	if (!product) {
		product = element.closest("[data-v-component-product]");
	}
	if (!product) {
		product = element.closest("[data-v-cart-product]");
	}	
	
	return product;
}

VvvebTheme.Gui = {
	
	init: function() {
		let events = [];
	
		document.querySelectorAll("[data-v-vvveb-action]").forEach(function (el) {

			let on = "click";
			if (el.dataset.vVvvebOn) on = el.dataset.vVvvebOn;
			
			if (events.indexOf(on) > -1) return;
			events.push(on);
			
			document.addEventListener(on, function (e) {
				let element = e.target.closest("[data-v-vvveb-action]");
				if (element) {
					let action = element.dataset.vVvvebAction;
					let elOn = element.dataset.vVvvebOn ?? "click";
					
					if (elOn == on && VvvebTheme.Gui.hasOwnProperty(action)) {
						VvvebTheme.Gui[action].call(e.target, e);
					}
				}
			});
		});
		
		/*
		document.querySelectorAll("[data-v-vvveb-action]").forEach(function (el) {

			let on = "click";
			if (el.dataset.vVvvebOn) on = el.dataset.vVvvebOn;
			let event = '[data-v-vvveb-action="' + el.dataset.vVvvebAction + '"]';

			if (events.indexOf(event + on) > -1) return;
			events.push(event + on);
			
			if (VvvebTheme.Gui.hasOwnProperty(el.dataset.vVvvebAction)) {
				document.addEventListener(on, function (e) {
					let element = e.target.closest("[data-v-vvveb-action]");
					if (element) {
						VvvebTheme.Gui[el.dataset.vVvvebAction].call(e.target, e);
					}
				});
				//document).addEventListener(on, VvvebTheme.Gui[this.dataset.vVvvebAction]);
			}
		});
		*/
	},
	
	addToCart: function(e) {
		let product = elementProduct(this);
		
		let img = product.querySelector("img[data-v-product-image], img[data-v-product-image-src], img[data-v-product-image], img[data-v-product-main-image]");
		let name = product.querySelector("[data-v-product-name]").textContent ?? "";
		let quantity = product.querySelector('[name="quantity"]')?.value ?? 1;
		let id = this.dataset.product_id ?? product.dataset.product_id;
		let options = {quantity};

		if (!id) {
			id = product.dataset.product_id;
			if (!id) {
				id = product.querySelector('input[name="product_id"]').value;
			}
		}
		
		let cart_add_text = 'was added to cart';
		let target = e.target.closest("button, input");

		if (target?.form) {
			options = Object.fromEntries(new URLSearchParams(new FormData(target.form)));
			if (!target.form.checkValidity()) {
				target.form.requestSubmit();
				return false;
			}
		}

		VvvebTheme.Cart.add(id, options, this, '.mini-cart', function() {
			let src = img.getAttribute("src");
			VvvebTheme.Alert.show(`
			<div class="clearfix">
				<img  class="float-start me-2" height="80" src="${src}"> &ensp; 
				<div class="float-start"><a href="#">${name}<a><br> <span class="text-muted">${cart_add_text}<span></span></div>
			</div>
			<div class="row mt-2 g-2 " data-v-if="cart.total_items">
				  <div class="col-6">
					<a href="/cart" class="btn btn-light btn-sm border w-100" data-v-url="cart/cart/index">
					  <i class="la la-shopping-cart la-lg"></i>
					  <span>View cart</span>
					</a>
				  </div>
				  <div class="col-6">
					<a href="/checkout" class="btn btn-primary btn-sm w-100" data-v-url="checkout/checkout/index">
					  <span>Checkout</span>
					  <i class="la la-arrow-right la-lg"></i>
					</a>
				  </div>
			</div>`);
		});
		
		e.preventDefault();
		return false;
	},	
	
	removeFromCart: function(e) {
		
		let product = this.closest("[data-v-product]");
		if (!product) {
			product = this.closest("[data-v-component-product]");
		}
		if (!product) {
			product = this.closest("[data-v-cart-product]");
		}
		let img = product.querySelector("[data-v-product-image],[data-v-product-image], [data-v-cart-product-image]")?.getAttribute("src") ?? "";
		let name = product.querySelector("[data-v-product-name]")?.textContent ?? "";
		let id = this.dataset.product_id;
		let selector = this.dataset.selector ?? '.cart-box';

		if (!id) {
			id = product.dataset.product_id;
			if (!id) {
				id = product.querySelector('input[name="product_id"]').value;
			}
		}
		
		VvvebTheme.Cart.remove(id, this, '.mini-cart', function() {
			VvvebTheme.Alert.show('<img height=50 src="' + img + '"> &ensp; <strong>' +  name +'</strong> was removed from cart');
			product.remove();
		});
		
		e.preventDefault();
		return false;
	},
	
	addToWishlist: function(e) {
		let product = elementProduct(this);
		let id = this.dataset.product_id ?? product.dataset.product_id;
		let img = product.querySelector("img[data-v-product-image], img[data-v-product-image], img[data-v-cart-product-image], img[data-v-product-main-image]")?.getAttribute("src") ?? "";

		if (!id) {
			id = product.dataset.product_id;
			if (!id) {
				id = product.querySelector('input[name="product_id"]').value;
			}
		}

		VvvebTheme.Wishlist.add(id, this, false, function(data) {
			VvvebTheme.Alert.show('<img height=50 src="' + img + '"> &ensp; <strong>' +  name +'</strong> was added to wishlist');
		});
		
		e.preventDefault();
		return false;
	},
	
	addToCompare: function(e) {
		let product = elementProduct(this);
		let id = this.dataset.product_id ?? product.dataset.product_id;
		let img = product.querySelector("img[data-v-product-image], img[data-v-product-image], img[data-v-cart-product-image], img[data-v-product-main-image]")?.getAttribute("src") ?? "";

		if (!id) {
			id = product.dataset.product_id;
			if (!id) {
				id = product.querySelector('input[name="product_id"]').value;
			}
		}

		VvvebTheme.Compare.add(id, this, false, function(data) {
			VvvebTheme.Alert.show('<img height=50 src="' + img + '"> &ensp; <strong>' +  name +'</strong> was added to compare');
		});
		
		e.preventDefault();
		return false;
	},
	

	replyTo: function(e) {
		let commentId = this.dataset.comment_id;
		let commentAuthor = this.dataset.comment_author;
		let commentForm = document.getElementById("comment-form");
		//location.hash = "#comment-form";
		/*
		window.scrollTo({
		  top: commentForm.offsetTop + commentForm.clientHeight,
		  left: 0,
		  behavior: "smooth",
		});*/
		
		commentForm.querySelector("input[name=parent_id]").value = commentId;

		if (commentId > 0) {
			document.querySelector(".replyto").style.display = "";
			document.querySelector(".replyto [data-comment-author]").innerHTML = commentAuthor;
		} else {
			document.querySelector(".replyto").style.display = "none";
		}
		e.preventDefault();
		return false;
	},

	addComment: function(e) {
		let selector = this.dataset.selector ?? ".post-comments";
		let form = this;
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(form)));
		
		VvvebTheme.Comments.add(parameters, this, selector, function () {
			form.reset();
		});
		
		e.preventDefault();
	},	
	
	addReview: function(e) {
		let selector = this.dataset.selector ?? ".product-reviews";
		let form = this;
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(form)));
		parameters['module'] = 'product/product';
		parameters['action'] = 'addReview';
		
		VvvebTheme.Comments.add(parameters, this, selector, function () {
			form.reset();
		});
		
		e.preventDefault();
		
	},		
	
	addQuestion: function(e) {
		let selector = this.dataset.selector ?? ".product-questions";
		let form = this;
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(form)));
		parameters['module'] = 'product/product';
		parameters['action'] = 'addQuestion';
		
		VvvebTheme.Comments.add(parameters, this, selector, function () {
			form.reset();
		});
		
		e.preventDefault();
	},	
	
	search: function (e) {
		clearTimeout(window.searchDebounce);
		
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(this)));
		let element = this;
		let component = element.closest("[data-v-component-search]");
		
		window.searchDebounce = setTimeout(function () {	
			component.css("opacity", 0.5);
			VvvebTheme.Search.query(parameters, element, function(data) { 
				component.outerHTML = data;
		});
		e.preventDefault();
		
		}, 1000);
	},
	
	login: function (e) {
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(this)));
		let componentUser;
		let url = this.dataset.vUrl ?? false;
		let selector = this.dataset.selector ?? '.user-box';

		if (url) {
			VvvebTheme.User.module = url;
		}

		componentUser = this.closest('[data-v-component-user]'); 
		//parameters['component_id'] = document.querySelectorAll('[data-v-component-user]').index(componentUser);

		VvvebTheme.User.login(parameters, this, selector/*, function(data) { 
			
			//document.querySelectorAll("[data-v-component-user]")[0].outerHTML = data;
			componentUser.html(data);
			//	alert("Login");
		}*/);
		e.preventDefault();
	},	
	
	//used to submit any form without refreshing page, used for contact forms
	submit: function (e) {
		let form = this;
		let parameters = Object.fromEntries(new URLSearchParams(new FormData(form)));
		let componentUser;
		let url = this.dataset.vUrl ?? false;
		let selector = this.dataset.selector;
		let loading = this.querySelector("button .loading, .btn .loading");
		
		if (loading) {
			loading.classList.remove("d-none");
			loading.parentNode.querySelector(".button-text")?.classList.add("d-none");
		}

		loadAjax(url, selector, () => {
			if (loading) {
				loading.classList.add("d-none");
				loading.parentNode.querySelector(".button-text")?.classList.remove("d-none");
				form.reset();
			}
		}, parameters, "post");

		e.preventDefault();
	}
}	

let delay = (function(){
  let timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();


let urlCache = {};

function preloadUrl(e) {
		delay(() => loadUrl(e, true), 200);
}

		
//ajax url
function loadAjax(url, selector, callback = null, params = {}, method = "get") {
	let options = {method};
	if (method == "post" && params) {
		options.body = new URLSearchParams(params);
	}
	
	if (!url) url = window.location.href;
	
	fetch(url, options).
	then((response) => {
		if (!response.ok) { throw new Error(response) }
		return response.text()
	}).then(function (data) {
		if (selector) {
			let response = new DOMParser().parseFromString(data, "text/html");

			if (Array.isArray (selector) ) {
				for (k in selector) {
					let elementSelector = selector[k];
					let currentElement = document.querySelector(elementSelector);
					let newElement = response.querySelector(elementSelector);
					if (currentElement && newElement) {
						currentElement.replaceWith(newElement);
					}
				}
			} else {
				let currentElement = document.querySelector(selector);
				let newElement = response.querySelector(selector);

				if (currentElement && newElement) {
					currentElement.replaceWith(newElement);
				}
			}
			
			if (callback) callback();
		}		

		window.dispatchEvent(new CustomEvent("vvveb.loadUrl", {detail: {url, selector}}));
	}).catch(error => {
		console.log(error);
	});
}

document.addEventListener("click", function (e) {
	let element = e.target.closest("a[data-url]");
	if (element) {
		let selector = element.dataset.selector ?? "";
		let url = element.getAttribute("href") ?? "";
		
		if (!url) return;
		
		loadAjax(url, selector, () => { 
			if (element.dataset.scroll) {
				let target = document.querySelector(selector);
				target.scrollIntoView({behavior: "smooth", block: element.dataset.scroll ?? "center", inline: "center"});
			}
			window.history.pushState({url, selector}, null); 
		});
		
		e.preventDefault();
	}
});

addEventListener("popstate", checkState);

function checkState(e) {
    if (e.state && e.state.url) {
        loadAjax(e.state.url, e.state.selector);
    }
}

window.history.pushState({url:window.location.pathname, selector:".content-body"}, null, window.location.href);


VvvebTheme.Gui.init();
