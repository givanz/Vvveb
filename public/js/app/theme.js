/*
 * Sticky navbar
 * 
 */

window.VvvebTheme = window.VvvebTheme || {ajax:{}};
window.VvvebApp = window.VvvebApp || {};
  	
// When the user scrolls the page, execute navbarSticky
window.onscroll = function() {navbarSticky()};

// Get the navbar
var navbar = document.querySelector((typeof navbarSelector !== 'undefined') ? navbarSelector : ".navbar");
// Get the offset position of the navbar
var sticky = navbar.offsetTop ? navbar.offsetTop : navbar.offsetHeight;

function toggleNavbarTheme () {
    if (navbar.classList.contains("navbar-dark")) {
		navbar.classList.add("navbar-light");
		navbar.classList.remove("navbar-dark");
	} else if (navbar.classList.contains("navbar-light")) {
		navbar.classList.add("navbar-dark");
		navbar.classList.remove("navbar-light");
	}
}


// Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
function navbarSticky(isSticky) {
  if (isSticky == undefined) {
	  isSticky = (window.pageYOffset >= sticky);		
  }
		
  if (isSticky) {
	  if (!navbar.classList.contains("sticky")) {
		navbar.classList.add("sticky");
		toggleNavbarTheme();
	  } 
  } else {
	  if (navbar.classList.contains("sticky")) {
		navbar.classList.remove("sticky");
		toggleNavbarTheme();
	  }
  }
}

function setCookie(name, value) {
	document.cookie = name + "=" + value + ";";
	//try to set cookie to all subdomains
	document.cookie = name + "=" + value + ";path=/;domain=." + window.location.host.replace(/^.*?\./, '') + ";";
}

let themeSwitch = document.querySelector("#color-theme-switch i");
let theme = document.documentElement.dataset.bsTheme;
let themeCookie = document.cookie.match(/theme=(.*?);/);
if (themeCookie && themeCookie[1]) {
	theme = themeCookie[1];
}

if (theme && themeSwitch) {
	if (theme == "dark") {
		let themeSwitch = document.querySelector("#color-theme-switch i");
		themeSwitch.classList.remove("la-sun")
		themeSwitch.classList.add("la-moon");
		document.documentElement.dataset.bsTheme = theme;
	}
}
	

document.addEventListener("click", function (e) { 
	let link = e.target.closest("#color-theme-switch");	
	if (link) {
		let themeSwitch = link.querySelector("i");
		let theme = document.documentElement.dataset.bsTheme;
	
		if (theme == "dark") {
			theme = "light";
			themeSwitch.classList.remove("la-moon");
			themeSwitch.classList.add("la-sun");
		} else if (theme == "light" || theme == "auto" || !theme) {
			theme = "dark";
			themeSwitch.classList.remove("la-sun");
			themeSwitch.classList.add("la-moon");
		} else {
			theme = "auto";
		}

		document.documentElement.dataset.bsTheme = theme;
		//localStorage.setItem("theme", theme);
		setCookie("theme", theme);
		//serverStorage.setItem();
		e.preventDefault();
	}
	
});

const selectedProductOptions = {};
let variantId = '';
// product page
function productPage() {
	/*
	document.querySelectorAll('.quantity').forEach(e => e.addEventListener('click', function (e) {
		let btn = e.target.closest(".btn-plus");
		if (btn) {
			let nrInput = btn.parentNode.querySelector("input[type=number]");
			nrInput.value = parseInt(nrInput.value) + 1;
			nrInput.dispatchEvent(new KeyboardEvent("change", {
				bubbles: true,
				cancelable: true,
			}));		
		}
		return false;
	}));

	document.querySelectorAll('.quantity').forEach(e => e.addEventListener('click', function (e) {
		let btn = e.target.closest(".btn-minus");
		if (btn) {
			let nrInput = btn.parentNode.querySelector("input[type=number]");
			nrInput.value = Math.max(1, parseInt(nrInput.value) - 1);
			nrInput.dispatchEvent(new KeyboardEvent("change", {
				bubbles: true,
				cancelable: true,
			}));		
		}
		return false;
	}));
	*/
	function zoom(e) {
		let img = e.currentTarget;
		let offsetX = e.offsetX || (e.touches ? e.touches[0].pageX : 0);
		let offsetY = e.offsetY || (e.touches ? e.touches[0].pageY : 0);
		
		let x = offsetX / img.offsetWidth * 100; 
		let y = offsetY / img.offsetHeight * 100; 
		img.style.backgroundPosition = x + "% " + y + "%";
	}

	document.querySelectorAll('div.zoom').forEach(e => e.addEventListener('mousemove', zoom));
	
	let productOptionsContainer = document.getElementById("product-options");
	if (typeof productVariants !== "undefined" && productVariants && productOptionsContainer) {
			productOptionsContainer.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
				let id = radio.name.match(/(\d+)/)[1] ?? false;
				if (id) {
					selectedProductOptions[id] = radio.value;
				}
			});
			
			productOptionsContainer.addEventListener("click", function (e) {

				let element = e.target.closest('input[type="radio"]');
				if (element) {
					let id = element.name.match(/(\d+)/)[1] ?? false;
					if (id) {
						selectedProductOptions[id] = element.value;
					}
					let variantId = JSON.stringify(selectedProductOptions).replaceAll(/[^\d:,]+/g,'');

					let variant = productVariants[variantId];
					let isStock = variant && variant.stock_quantity > 0 ? true : false;

					if (variant) {
						productOptionsContainer.querySelectorAll('input[type="radio"]').forEach(radio => {
							const currentOptions = Object.assign({}, selectedProductOptions);
							let id = radio.name.match(/(\d+)/)[1] ?? false;
							if (id) {
								currentOptions[id] = radio.value;
								let currentVariantId = JSON.stringify(currentOptions).replaceAll(/[^\d:,]+/g,'');
								let variant = productVariants[currentVariantId];
								let isStock = variant && variant.stock_quantity > 0 ? true : false;
								let text = radio.parentNode.querySelector("[data-v-value-name]");

								if (isStock) {
									text.style.textDecoration = "";
									text.style.opacity = "";
								} else {
									text.style.textDecoration = "line-through";
									text.style.opacity = 0.5;
								}
							}
						});
					}

					document.querySelector("[data-v-product-price_tax_formatted]").innerText = variant ? variant.price_formatted : "";
					document.querySelector("#button-cart").disabled = !isStock;
					document.querySelector("#buynow").disabled = !isStock;
					const product_variant_id = variant ? variant.product_variant_id : "";
					const searchParams = new URLSearchParams(location.search); 
				
					document.querySelector('[name="product_variant_id"]').value = product_variant_id;
					if (product_variant_id) {
						searchParams.set('product_variant_id', product_variant_id); 
					} else {
						searchParams.delete('product_variant_id');
					}
					
					window.history.replaceState({}, "", location.pathname + (Array.from(searchParams).length > 0 ? "?" :"") + searchParams.toString());
				}
		});
	}
}

productPage();

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
        .then(function (registration){console.log('Service worker registered successfully');})
        .catch(function (e){console.error('Error during service worker registration:', e);});
}

function togglePasswordInput(element, input) {
	let password = document.getElementById(input);
	if (password.type == "password") {
		password.type = "text"; 
		let i = element.querySelector("i")
		i.classList.add("la-eye")
		i.classList.remove("la-eye-slash");
	} else {
		password.type = "password";
		let i = element.querySelector("i")
		i.classList.remove("la-eye")
		i.classList.add("la-eye-slash");
	}
}
/*
document.addEventListener("click", function (e) { 
	let link = e.target.closest(".dropdown-toggle");
	if (link) {
		let parent = link.closest(".nav-toggle");
		if (link.classList.contains("show")) {
			parent.classList.add("show"); 
		} else {
			parent.classList.remove("show"); 
		}
	}
});
*/

function updateProgressStatus(percent) {
  const progressElement = document.getElementById('page-loading-status');
  if (progressElement) {
	progressElement.style.width = percent + '%';
  }
/*
  if (percent == 0) {
	progressElement.classList.add("d-none");
  } else {
	progressElement.classList.remove("d-none");
  }
*/
}
/*
function fixAnchorUrl() {
    let pathname = location.href.split('#')[0];
    [].forEach.call(document.querySelectorAll("a[href^='#']"), function(a) {
        a.href = pathname + a.getAttribute("href");
    });
}

document.addEventListener('DOMContentLoaded', fixAnchorUrl);
*/
//init specific page js code after page is loaded through ajax
function afterPageLoad() {
	//check if product page to add specific listners
	if(document.querySelector("[data-v-component-product]")) {
		productPage();
	}
	
	if(document.querySelector("[data-v-cart-page]")) {
		//cartPage();
	}
	//get navbar for sticky
	navbar = document.getElementsByClassName("navbar")[0];
	
	//fixAnchorUrl();
}

//theme ajax configuration

//include elements that will be updated on ajax calls, include body > section to trigger whole page update if sections mismatch between different page structures
VvvebTheme.ajax.siteContainer  = VvvebTheme.ajax.siteContainer || ["#site-content", ".inner-page-hero", "body > section", "body > nav"];
VvvebTheme.ajax.progressStatus = VvvebTheme.ajax.progressStatus || updateProgressStatus;
VvvebTheme.ajax.afterLoad      = VvvebTheme.ajax.afterLoad || afterPageLoad;
//include posts, product and menu items for ajax
//VvvebTheme.ajax.selector = "a[data-url], a[data-page-url], a[data-v-url], a[data-v-menu-item-url], a[data-v-post-url], a[data-v-product-url], a[data-v-cat-url], a[data-v-archive-url], a[data-v-admin-url], a[data-v-post-author-url], a[data-v-breadcrumb-item-url], a[data-v-categories-cat-url], a[data-v-cart-product-url]"; 
//skip home for dark hero and contact form for google js code
VvvebTheme.ajax.skipUrl = (VvvebTheme.ajax.skipUrl && VvvebTheme.ajax.skipUrl?.length) || ["/checkout"/*, "/page/contact"*/];

//image lightbox
if (typeof GLightbox !== 'undefined') {
const lightbox = GLightbox();
//let gloptions = {selector:".gallery a, .carousel-item a,a img, img"};	
document.addEventListener("click", function(event) {
	let element = event.target.closest("img");
	
	if (element) {
		let elements = [];
		let gallery = element.closest(".gallery");
		let index = 0;
		let count = 0;
		let filter = function(img) {
					let href = img?.parentNode?.attributes['href']?.nodeValue ?? img.src; 
					if (!href || href == "#") {
						href = img.src;
					}
					
					if (img == element) {
						index = count;
					}
					
					count++;
					return {'type': 'image', href}
		}
		
		if (gallery) {
				elements = [...gallery.querySelectorAll("img")].map(filter);
		} else {
			gallery = element.closest(".carousel");
			if (gallery) {
				elements = [...gallery.querySelectorAll("img")].map(filter);
			}
		} 
		
		if (elements.length == 0) {
			if (element.parentNode && element.parentNode.tagName == "A") {
				if (element.parentNode.href == "" || element.parentNode.href == "#") {
					elements = [{"href": element.src}];
				}
				
				if (element.parentNode.href.split('.').pop() == element.src.split('.').pop()) {
					elements = [{"href": element.parentNode.href}];
				}
			} else {
				//elements = [{"href": element.src}];
			}
		}
		
		if (elements && elements.length) {
			lightbox.setElements(elements);
			//lightbox.open();
			lightbox.openAt(index);
			event.preventDefault();
		}
	}
});
}
