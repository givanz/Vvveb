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
	call: function(parameters, element, selector, callback) {
		let url = '/index.php?module=' +  parameters["module"] + '&action=' + parameters["action"];
		if (!selector) {
			url += '&_component_ajax=' + parameters["component"] + '&_component_id=' + parameters["component_id"];
		}
		$.ajax({
			url,
			type: 'post',
			data: parameters,
			//dataType: 'json',
			beforeSend: function() {
				$('.loading', element).removeClass('d-none');
				$('.button-text', element).addClass('d-none');
				if ($(element).is('button'))  {
					$(element).attr("disabled", "true");
				}
			},
			complete: function() {
				$('.loading', element).addClass('d-none');
				$('.button-text', element).removeClass('d-none');
				if ($(element).is('button')) {
					$(element).removeAttr("disabled");
				}
				//$('#cart > button').button('reset');
			},
			success: function(data) {
				//$("header [data-v-component-cart]")[0].outerHTML = data;
				if (selector) {
					let response = $(data);
					if (Array.isArray (selector) ) {
						for (k in selector) {
							let elementSelector = selector[k];
							let element = $(elementSelector, response);
							if (element.length) {
								$(elementSelector).replaceWith($(elementSelector, response));
							}
						}
					} else {
						$(selector).replaceWith($(selector, response));
					}
				}
				if (callback) callback(data);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});		
		
	}
}

VvvebTheme.Cart = {
	
	module: 'cart/cart',
	component: 'cart',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback) {
		parameters['module'] = this.module;
		parameters['action'] = action;
		parameters['component'] = this.component;
		parameters['component_id'] = this.component_id;
		VvvebTheme.Ajax.call(parameters, element,  selector, callback);
	},
	
	callback: function(data) {
		/*
			let miniCart = $("[data-v-component-cart]");
			if (miniCart.length) {
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
	
	update: function(productId, options, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		if (options) {
			options['product_id'] = productId;
		} else {
			options = {'product_id':productId};
		}
		return this.ajax('update',options, element, selector, callback);
	},
 
	remove: function(productId, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('remove', {'product_id':productId}, element, selector, callback);
	}
}

VvvebTheme.Wishlist = {
	
	module: 'cart/wishlist',
	component: 'wishlist',
	component_id: '0',
	
	ajax: function(action, parameters, element, selector, callback) {
		parameters['module'] = this.module;
		parameters['action'] = action;
		parameters['component'] = this.component;
		parameters['component_id'] = this.component_id;
		VvvebTheme.Ajax.call(parameters, element,  selector, callback);
	},
	
	callback: function(data) {
		/*
			let miniWishlist = $("[data-v-component-wishlist]");
			if (miniWishlist.length) {
				miniWishlist[0].outerHTML = data;
			}*/
	},
	
	add: function(productId, quantity, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('add',{'product_id':productId, 'quantity':quantity}, element, selector, callback);
	},
	
	update: function(productId, quantity, element,  selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('update',{'product_id':productId, 'quantity':quantity}, element, selector, callback);
	},
 
	remove: function(productId, element, selector, callback = false) {
		if (!callback) callback = this.callback;
		return this.ajax('remove', {'product_id':productId}, element, selector, callback);
	}

}

VvvebTheme.Comments = {
	
	module: 'content/post',
	
	ajax: function(action, parameters, element,  selector, callback = false) {
		parameters['module'] = this.module;
		parameters['action'] = action;
		VvvebTheme.Ajax.call(parameters, element, selector, callback);
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
		
		VvvebTheme.Ajax.call(parameters, element, selector, callback);
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
		
		VvvebTheme.Ajax.call(parameters, element, selector, callback = false);
	},
	
	query: function(parameters, element, selector, callback) {
		return this.ajax('index' ,parameters, element, selector);
	},
}

VvvebTheme.Alert  = {
	
	show: function(message) {
		$('.alert-top .message').html(message);
		$('.alert-top').addClass("show").css('display', 'block');
		
		setTimeout(function () {
			$('.alert-top').fadeOut();
		}, 4000);
	}
}

$('.alert-top').on('close.bs.alert', function (e) {
    e.preventDefault();
    $(this).removeClass('show').css('display', 'none');
});

function objectSerialize(serializeArray) {
    var returnObject = {};
    for (var i = 0; i < serializeArray.length; i++){
        returnObject[serializeArray[i]['name']] = serializeArray[i]['value'];
    }
    return returnObject;
}

VvvebTheme.Gui = {
	
	init: function() {
		let events = [];
		
		$("[data-v-vvveb-action]").each(function () {

			let on = "click";
			if (this.dataset.vVvvebOn) on = this.dataset.vVvvebOn;
			let event = '[data-v-vvveb-action="' + this.dataset.vVvvebAction + '"]';

			if (events.indexOf(event + on) > -1) return;
			events.push(event + on);
			
			if (VvvebTheme.Gui.hasOwnProperty(this.dataset.vVvvebAction)) {
				$(document).on(on, event, VvvebTheme.Gui[this.dataset.vVvvebAction]);
			}
		});

		/*
		for (actionName in VvvebTheme.Gui)
		{
			if (actionName == "init") continue;
			//console.log(actionName);
			$(document).on("click", '[data-v-vvveb-action="' + actionName + '"]', VvvebTheme.Gui[actionName]);
		}*/
	},
	
	addToCart : function (e) {
		let product = $(this).parents("[data-v-product]");
		if (!product.length) {
			product = $(this).parents("[data-v-component-product]");
		}
		if (!product.length) {
			product = $(this).parents("[data-v-cart-product]");
		}
		
		let img = $("img[data-v-product-image], img[data-v-product-image-src], img[data-v-product-image]", product);
		let name = $("[data-v-product-name]:first", product).text();
		let quantity = $('[name="quantity"]:first', product).val() ?? 1;
		let id = this.dataset.product_id;
		let options = {quantity};

		if (!id) {
			id = product[0].dataset.product_id;
			if (!id) {
				id = $('input[name="product_id"]', product).val();
			}
		}
		
		let cart_add_text = 'was added to cart';
		if (e.currentTarget.form) {
			options = objectSerialize($(e.currentTarget.form).serializeArray());
			if (!e.currentTarget.form.checkValidity()) {
				e.currentTarget.form.requestSubmit();
				return false;
			}
		}

		VvvebTheme.Cart.add(id, options, this, '.mini-cart', function() {
			let src = img.attr("src");
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
		
		return false;
	},	
	
	removeFromCart : function (e) {
		
		let product = $(this).parents("[data-v-product]");
		if (!product.length) {
			product = $(this).parents("[data-v-component-product]");
		}
		if (!product.length) {
			product = $(this).parents("[data-v-cart-product]");
		}
		let img = $("[data-v-product-image],[data-v-product-image], [data-v-cart-product-image]", product).attr("src");
		let name = $("[data-v-product-name]", product).text();
		let id = this.dataset.product_id;
		let selector = this.dataset.selector ?? '.cart-box';

		if (!id) {
			id = product[0].dataset.product_id;
			if (!id) {
				id = $('input[name="product_id"]', product).val();
			}
		}
		
		VvvebTheme.Cart.remove(id, this, selector, function(data) {
			VvvebTheme.Alert.show('<img height=50 src="' + img + '"> &ensp; <strong>' +  name +'</strong> was removed from cart');
			product.remove();
		});
		
		return false;
	},
	
	addToWishlist : function (e) {
		return false;
	},
	
	addToCompare : function (e) {
		return false;
	},


	replyTo : function (e) {
		let commentId = this.dataset.comment_id;
		let commentAuthor = this.dataset.comment_author;
		let commentForm = document.getElementById("comment-form");
		//location.hash = "#comment-form";
		window.scrollTo({
		  top: commentForm.offsetTop + commentForm.clientHeight,
		  left: 0,
		  behavior: "smooth",
		});
		
		$("input[name=parent_id]", commentForm).val(commentId);

		if (commentId > 0) {
			$(".replyto").show();
			$(".replyto [data-comment-author]").html(commentAuthor);
		} else {
			$(".replyto").hide();
		}
		
		return false;
	},

	addComment : function (e) {
		let selector = this.dataset.selector ?? ".post-comments";
		let form = this;
		let parameters = $(form).serializeArray();
		
		VvvebTheme.Comments.add(parameters, this, selector, function () {
			form.reset();
		});
		
		e.preventDefault();
		
	},	
	
	search : function (e) {
		clearTimeout(window.searchDebounce);
		
		let parameters = $(this).serializeArray();
		
		window.searchDebounce = setTimeout(function () {	
			$("[data-v-component-search]").css("opacity", 0.5);
			VvvebTheme.Search.query(parameters, this, function(data) { 
				$("[data-v-component-search]")[0].outerHTML = data;
		});
		e.preventDefault();
		
		}, 1000);
	},
	
	login : function (e) {
		let parameters = $(this).serializeArray();
		let componentUser;
		let url = this.dataset.vUrl ?? false;
		let selector = this.dataset.selector ?? '.user-box';
		
		if (url) {
			VvvebTheme.User.module = url;
		}

		componentUser = $(this).parents('[data-v-component-user]'); 
		//parameters['component_id'] = $('[data-v-component-user]').index(componentUser);

		VvvebTheme.User.login(parameters, this, selector/*, function(data) { 
			
			//$("[data-v-component-user]")[0].outerHTML = data;
			componentUser.html(data);
			//	alert("Login");
		}*/);
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

/*
function loadUrl(e, preload = false) {
		let element = e.currentTarget;
		let url = element.href;
		let selector = e.data.update;
		
		if (!selector) selector = "body";
		
		if  (urlCache.hasOwnProperty(url)) {
			let page =  urlCache[url];
			if (page && !preload) {
				$(selector).replaceWith($(selector, $(page)));
			}
			return;
		} else if (!preload) {
			urlCache[url] = false;//set loading flag
		}
		
		$.ajax({
			dataType : 'html',
			url      : url,
			cache: true,
			success  : function(data) {
				
				//if not preloading or cache loading flag set then update page
				if (!preload || (preload && urlCache.hasOwnProperty(url) && urlCache[url] === false)) {
					let page =  $(data);
					$(selector).replaceWith($(selector, page));
				}
				urlCache[url] = data;
			}
		});
		//let selector = e.
		//if (!selector) selector = "body";
		
		e.preventDefault();
		return false;
}

if (preloadUrls) {
		for (url in preloadUrls) {
			let link = preloadUrls[url];

			$("body").on("mouseenter",link["link"], link, preloadUrl);
			$("body").on("click",link["link"], link, loadUrl);
		}
}
*/
		
jQuery(document).ready(function() {
	VvvebTheme.Gui.init();
});

//ajax url
function loadAjax(url, selector, callback = null) {
	$.ajax({
		url
	}).done(function (data) {
		let content = $(selector, data);
		if (content.length) {
			$(selector).html(content.html());
			if (callback) {
				callback();
			}
			
			$(window).trigger("vvveb.loadUrl", {url, selector});
		}
	}).fail(function (data) {
		alert(data.responseText);
	});
}

$("body").on("click", "a[data-url]", function (e) {
	let $this = $(this);
	let selector = this.dataset.selector ?? "";
	let url = $this.attr("href") ?? "";
	
	if (!url) return;
	
	loadAjax(url, selector, () => window.history.pushState({url, selector}, null, url));
	
	e.preventDefault();
});

addEventListener("popstate", checkState);

function checkState(e) {
    if (e.state && e.state.url) {
        loadAjax(e.state.url, e.state.selector);
    }
}

window.history.pushState({url:window.location.pathname, selector:".content-body"}, null, window.location.href);
