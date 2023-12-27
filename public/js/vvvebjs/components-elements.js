/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
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

Vvveb.ComponentsGroup['Elements'] = [
/*sections */
"elements/svg-icon",
"elements/carousel", /*
"elements/gallery",
"elements/slider",
"elements/menu",
//"elements/logo",
"elements/tabs",
"elements/accordion",
"elements/flip-box",
"elements/counter",
//"elements/contact-form",
"elements/svg-icon",
"elements/figure",
"elements/subscribe-form",
"elements/testimonial",
"elements/social-icons",
"elements/icon-list",
"elements/divider",
"elements/separator",
"elements/image-box",//card cu imagine
"elements/icon-box",
"elements/animated-headline",
"elements/price-table",
"elements/price-list",
"elements/reviews",
"elements/code",
"elements/image-compare",
"elements/back-to-top",
"elements/blob",
"elements/image-shape",
"elements/image-shape",
"elements/rating",*/
"elements/section", 
"elements/footer", 
"elements/header", 
// cover
//counter
//contact form
//flip box
];

Vvveb.Components.extend("_base","elements/figure", {
    nodes: ["figure"],
	name: "Figure",
    image: "icons/image.svg",
    resizable:true,
    html: `<figure>
		  <img src="${Vvveb.baseUrl}icons/image.svg" alt="Trulli">
		  <figcaption>Fig.1 - Trulli, Puglia, Italy.</figcaption>
		  <div class="border"></div>
		</figure>`,
		
	stylesheets:[
		{
			//the css is added in head when the element is added to page
			'src': Vvveb.baseUrl + 'css/figure.css',
			//the css is removed on save if none of the figure elements are present in the page
			'mustHaveElement':"figure",
		},
	],
	/*
	javascripts:[
		{
			'src': Vvveb.baseUrl + 'css/figure.js',
			//the js is removed on save if none of the figure elements are present in the page
			'mustHaveElement':"figure",
		}
	],*/	
    resizable:true,//show select box resize handlers
    
    properties: [{
        name: "Image",
        key: "src",
        child:"img",
        htmlAttr: "src",
        inputtype: ImageInput
    }, {
        name: "Width",
        key: "width",
        child:"img",
        htmlAttr: "width",
        inputtype: TextInput
    }, {
        name: "Height",
        key: "height",
        child:"img",
        htmlAttr: "height",
        inputtype: TextInput
    }, {
        name: "Alt",
        key: "alt",
        child:"img",
        htmlAttr: "alt",
        inputtype: TextInput
    }, {
        name: "Caption",
        key: "caption",
        child:"figcaption",
        htmlAttr: "innerHTML",
        inputtype: TextareaInput
    }]    
});



//Icon
Vvveb.Components.add("elements/icon", {
    nodes: ["i.icon"],
    name: "Font Icon",
    image: "icons/star.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   
/*
V.Resources.Icons =
[{
	value: `stopwatch.svg`,
	text: "Star"
}, 
{
	value: `envelope.svg`,
	text: "Sections"
}, {
	value: `star.svg`,
	text: "Flipbox"
}];*/

Vvveb.Components.extend("_base","elements/svg-icon", {
    nodes: ["svg"],
    name: "Svg Icon",
    image: "icons/star.svg",
    html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="64" height="64">
		<path d="M 30.335938 12.546875 L 20.164063 11.472656 L 16 2.132813 L 11.835938 11.472656 L 1.664063 12.546875 L 9.261719 19.394531 L 7.140625 29.398438 L 16 24.289063 L 24.859375 29.398438 L 22.738281 19.394531 Z"/>
    </svg>`,
    properties: [{
		name: "Icon",
		key: "icon",
		inline:true,
		inputtype: HtmlListSelectInput,
		onChange:function(element, value, input, component) {
			var newElement = $(value);
			let attributes = element.prop("attributes");
			
			//keep old svg size and colors
			$.each(attributes, function() {
				if (this.name == "viewBox") return;
                newElement.attr(this.name, this.value);
            });
            
			element.replaceWith(newElement);
			return newElement;
		},
		data: {
			url: Vvveb.baseUrl + "../../resources/svg/icons/{value}/index.html",
			clickElement:"li",
			insertElement:"svg",
			elements: 'Loading ...',
			options: [{
                value: "eva-icons",
                text: "Eva icons"
            }, {
                value: "ionicons",
                text: "IonIcons"
            }, {
                value: "linea",
                text: "Linea"
            }, {
                value: "remix-icon",
                text: "RemixIcon"
            }, {
                value: "unicons",
                text: "Unicons"
            }, {
                value: "clarity-icons",
                text: "Clarity icons"
            }, {
                value: "jam-icons",
                text: "Jam icons"
            }, {
                value: "ant-design-icons",
                text: "Ant design icons"
            }, {
                value: "themify",
                text: "Themify"
            }, {
                value: "css.gg",
                text: "Css.gg"
            }, {
                value: "olicons",
                text: "Olicons"
            }, {
		value: "open-iconic",
		text: "Open iconic"
            }, {
                value: "boxicons",
                text: "Box icons"
            }, {
                value: "elegant-font",
                text: "Elegant font"
            }, {
                value: "dripicons",
                text: "Dripicons"
            }, {
                value: "feather",
                text: "Feather"
            }, {
                value: "coreui-icons",
                text: "Coreui icons"
            }, {
                value: "heroicons",
                text: "Heroicons"
            }, {
                value: "iconoir",
                text: "Iconoir"
            }, {
                value: "iconsax",
                text: "Iconsax"
            }, {
                value: "ikonate",
                text: "Ikonate"
            }, {
                value: "tabler-icons",
                text: "Tabler icons"
            }, {
                value: "octicons",
                text: "Octicons"
            }, {
                value: "system-uicons",
                text: "System-uicons"
            }, {
                value: "font-awesome",
                text: "FontAwesome"
            }, {
                value: "pe-icon-7-stroke",
                text: "Pixeden icon 7 stroke"
            }, {
                value: "77_essential_icons",
                text: "77 essential icons"
            }, {
                value: "150-outlined-icons",
                text: "150 outlined icons"
            }, {
                value: "material-design",
                text: "Material Design"
            }]
		},
	}, {
		name: "Width",
		key: "width",
		htmlAttr: "width",
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}
   }, {
		name: "Height",
		key: "height",
		htmlAttr: "height",
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}			
   }, {
		name: "Stroke width",
		key: "stroke-width",
		htmlAttr: "stroke-width",
		inputtype: RangeInput,
		data:{
			max: 512,
			min:1,
			step:1
		}			
   },{
		key: "svg_style_header",
		inputtype: SectionInput,
		name:false,
		//sort: base_sort++,
		section: style_section,
		data: {header:"Svg colors"},
	}, {
        name: "Fill Color",
        key: "fill",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "fill",
        inputtype: ColorInput,
   },{
        name: "Color",
        key: "color",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "color",
        inputtype: ColorInput,
   },{
        name: "Stroke",
        key: "Stroke",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "color",
        inputtype: ColorInput,
  	}]
});   


Vvveb.Components.add("elements/svg-element", {
    nodes: ["path", "line", "polyline", "polygon", "rect", "circle", "ellipse", "g"],
    name: "Svg element",
    image: "icons/star.svg",
    html: ``,
    properties: [{
        name: "Fill Color",
        key: "fill",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "fill",
        inputtype: ColorInput,
   },{
        name: "Color",
        key: "color",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "color",
        inputtype: ColorInput,
   },{
        name: "Stroke",
        key: "Stroke",
        //sort: base_sort++,
        col:4,
        inline:true,
		section: style_section,
		htmlAttr: "color",
        inputtype: ColorInput,
  	}, {
  		name: "Stroke width",
		key: "stroke-width",
		htmlAttr: "stroke-width",
		inputtype: RangeInput,
		data:{
			max: 512,
			min:1,
			step:1
		}			
	}]
});  

//Gallery
Vvveb.Components.add("elements/gallery", {
    attributes: ["data-component-gallery"],
    name: "Gallery",
    image: "icons/images.svg",
    html: `
			<div class="gallery masonry has-shadow" data-component-gallery>
				<div class="item">
					<a>
						<img src="../../media/posts/1.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/2.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/3.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/4.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/5.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/6.jpg">
					</a>
				</div>
				<div class="item">
					<a>
						<img src="../../media/posts/7.jpg">
					</a>
				</div>
			</div>
			`,
		properties: [{
			name: "Masonry layout",
			key: "masonry",
			htmlAttr: "class",
			validValues: ["masonry", "flex"],
			inputtype: ToggleInput,
			data: {
				on: "masonry",
				off: "flex"
			},
			setGroup: group => {
				$('.mb-3[data-group]').attr('style','display:none !important');
				$('.mb-3[data-group="'+ group + '"]').attr('style','');
			}, 		
			onChange : function(node, value, input)  {
				this.setGroup(value);
				return node;
			}, 
			init: function (node) {
				if ($(node).hasClass("masonry")) {
					return "masonry";
				} else {
					return "flex";
				}
			},   			
		}, {
			name: "Image shadow",
			key: "shadow",
			htmlAttr: "class",
			validValues: [ "", "has-shadow"],
			inputtype: ToggleInput,
			data: {
				on: "has-shadow",
				off: ""
			},
		}, {
			name: "Horizontal gap",
			key: "column-gap",
			htmlAttr: "style",
			inputtype: CssUnitInput,
			data:{
				max: 100,
				min:0,
				step:1
			}
	   }, {
			name: "Vertical gap",
			key: "margin-bottom",
			htmlAttr: "style",
			child: ".item",
			inputtype: CssUnitInput,
			data:{
				max: 100,
				min:0,
				step:1
			}
	   }, {
			name: "Images per row masonry",
			key: "column-count",
			group:"masonry",
			htmlAttr: "style",
			inputtype: RangeInput,
			data:{
				max: 12,
				min:1,
				step:1
			}
		}, {
			name: "Images per row flex",
			group:"flex",
			key: "flex-basis",
			child: ".item",
			htmlAttr: "style",
			inputtype: RangeInput,
			data:{
				max: 12,
				min:1,
				step:1
			},
			onChange: function(node, value, input, component, inputElement) {
				if (value) {
					value = 100 / value;
					value += "%";
				} 
				
				return value;
			}  			
	   }, {
			name: "",
			key: "addChild",
			inputtype: ButtonInput,
			data: {text:"Add image", icon:"la la-plus"},
			onChange: function(node) {
				 $(node).append('<div class="item"><a><img src="../../media/posts/1.jpg"></a></div>');
				 
				 //render component properties again to include the new image
				 //Vvveb.Components.render("ellements/gallery");
				 
				 return node;
			}
	}],
    init(node)	{
		
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		let source = "flex";
		if ($(node).hasClass("masonry")) {
			source = "masonry";
		} else {
			source = "flex";
		}
		$('.mb-3[data-group="'+ source + '"]').attr('style','');
	}	
});  

//Slider
Vvveb.Components.add("elements/slider", {
    nodes: ["i.icon"],
    name: "Slider",
    image: "icons/slider.svg",
    html: `<section>
				<div class="container>
					<h1>Container</h1>
				</div>
			</section>`,
    properties: [
		  {
			name: "Width",
			key: "width",
			htmlAttr: "width",
			inputtype: RangeInput,
			data:{
				max: 640,
				min:6,
				step:1
			}
		   }, {
			name: "Height",
			key: "height",
			htmlAttr: "height",
			inputtype: RangeInput,
			data:{
				max: 640,
				min:6,
				step:1
		   },
		}
	]
}); 	

//Menu
Vvveb.Components.add("elements/Menu", {
    nodes: ["i.icon"],
    name: "menu",
    image: "icons/navbar.svg",
    html: `<section>
				<div class="container>
					<h1>Container</h1>
				</div>
			</section>`,
    properties: [
	]
}); 	

//Logo
/*
Vvveb.Components.add("elements/logo", {
    nodes: ["i.icon"],
    name: "Logo",
    image: "icons/logo.svg",
    html: `<section>
				<div class="container>
					<h1>Container</h1>
				</div>
			</section>`,
    properties: [
	]
}); 	
*/
//Tabs
Vvveb.Components.add("elements/tab", {
	//attributes: ["data-component-tabs"],
	classes: ["tab-pane"],
    name: "Tab",
    image: "icons/tabs.svg",
    html: ``,
    properties: [{
			name: "Id",
			key: "id",
			htmlAttr: "id",
			inline:false,
			col:6,
			inputtype: TextInput
		}, {
			name: "Class",
			key: "class",
			htmlAttr: "class",
			inline:false,
			col:6,
			inputtype: TextInput
			
		} , {
        name: "Active",
        key: "active",
        htmlAttr: "class",
        validValues: ["", "active"],
        inputtype: ToggleInput,
        inline:true,
        col:6,
        data: {
            on: "active",
            off: ""
        }
    }]
}); 
	
Vvveb.Components.add("elements/tabs", {
    attributes: ["data-component-tabs"],
    name: "Tabs",
    image: "icons/tabs.svg",
    html: `
	<div data-component-tabs>
		<div class="shadow">
			<nav>
			  <div class="nav nav-tabs" id="nav-tab" role="tablist">
				<button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Home</button>
				<button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Profile</button>
				<button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false">Contact</button>
				<button class="nav-link" id="nav-disabled-tab" data-bs-toggle="tab" data-bs-target="#nav-disabled" type="button" role="tab" aria-controls="nav-disabled" aria-selected="false" disabled>Disabled</button>
			  </div>
			</nav>
			<div class="tab-content" id="nav-tabContent">
			  <div class="tab-pane show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Corporis perferendis rem accusantium ducimus animi nesciunt expedita omnis aut quas molestias!</p>
			  </div>
			  <div class="tab-pane" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
				<p>Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin</p>
			  </div>
			  <div class="tab-pane" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab" tabindex="0">
				<p>Quisque sagittis non ex eget vestibulum</p>
			  </div>
			  <div class="tab-pane" id="nav-disabled" role="tabpanel" aria-labelledby="nav-disabled-tab" tabindex="0">
				<p>Sed viverra pellentesque dictum.</p>
			  </div>
			</div>
		</div>
	</div>`,
	
    properties: [{
			//name: "List",
			key: "list",
			component: "elements/tab",
			children :[{
				component: "elements/tab",
				name: "html/gridcolumn",
				classesRegex: ["col-"],
			}],			
			inline:false,
			inputtype: ListInput,
			data: {
				component: "elements/tab",
				selector:".tab-pane",
				children :{
					component: "elements/tab",
					selector:".tab-pane",
					name: "html/gridcolumn",
					classesRegex: ["col-"],
				},				
				options: [{
					name: "btn-default",
					type: "Default",
					suffix: "unu"
				}, {
					name: "btn-asdasd",
					type: "Second",
					suffix: "doi"
				}, {
					name: "btn-435435",
					type: "Third",
					suffix: "trei"
				}]
			}
		}
	]
}); 	

//Accordion
Vvveb.Components.add("elements/accordion", {
    nodes: [".accordion"],
    name: "Accordeon",
    image: "icons/accordion.svg",
    html: `
<div class="accordion" id="accordionExample">
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingOne">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        Accordion Item #1
      </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the first item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingTwo">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
        Accordion Item #2
      </button>
    </h2>
    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingThree">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
        Accordion Item #3
      </button>
    </h2>
    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
      </div>
    </div>
  </div>
</div>    
    
    
    
    <div class="accordion">
    
			  <div class="accordion-item">
				<div class="accordion-header">
				  <button class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="true" data-bs-target="::parent .accordion-collapse">
					Accordion Item #1
				  </button>
				<div class="accordion-collapse collapse show" data-bs-parent=".accordion">
				  <div class="accordion-body">
					<strong> 1This is the first item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
				  </div>
				</div>
				</div>
			  </div>
			  
			  <div class="accordion-item">
				<div class="accordion-header">
				  <button class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="true" data-bs-target="::parent .accordion-collapse">
					Accordion Item #2
				  </button>
				<div class="accordion-collapse collapse show" data-bs-parent=".accordion">
				  <div class="accordion-body">
					<strong>2 This is the first item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
				  </div>
				</div>
				</div>
			  </div>
			  
			  <div class="accordion-item">
				<div class="accordion-header">
				  <button class="accordion-button" type="button" data-bs-toggle="collapse" aria-expanded="true" data-bs-target="::parent .accordion-collapse">
					Accordion Item #3
				  </button>
				<div class="accordion-collapse collapse show" data-bs-parent=".accordion">
				  <div class="accordion-body">
					<strong>3 This is the first item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
				  </div>
				</div>
				</div>
			  </div>
			  
			  
			</div>`,
    properties: [
		{
			name: "Flush",
			key: "flush",
			htmlAttr: "class",
			validValues: ["accordion-flush"],
			inputtype: ToggleInput,
			data: {
				on: "accordion-flush",
				off: ""
			}
		},
	]
}); 

Vvveb.Components.add("elements/flip-box", {
    nodes: [".flip-box"],
    name: "Flip box",
    image: "icons/flipbox.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   
/*
Vvveb.Components.add("elements/contact-form", {
    nodes: [".contact-form"],
    name: "Contact form",
    image: "icons/envelope.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   
*/
Vvveb.Components.add("elements/counter", {
    nodes: [".counter"],
    name: "Counter",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/subscribe-form", {
    nodes: [".counter"],
    name: "Subscribe form",
    image: "icons/bell.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/testimonial", {
    nodes: [".counter"],
    name: "Testimonial",
    image: "icons/testimonial.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/social-icons", {
    nodes: [".counter"],
    name: "Social icons",
    image: "icons/social-icons.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/carousel", {
    name: "Carousel",
    image: "icons/carousel.svg",
    classes: ["swiper"],
    html: `
	  <div class="swiper" data-slides-per-view="3" data-draggable="true">
		<div class="swiper-wrapper">
		  <div class="swiper-slide"><img src="../../media/posts/1.jpg" class="img-fluid"><p>Slide 1</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/2.jpg" class="img-fluid"><p>Slide 2</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/3.jpg" class="img-fluid"><p>Slide 3</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/4.jpg" class="img-fluid"><p>Slide 4</p></div>
		</div>
		<div class="swiper-pagination"></div>

		<!--
		<div class="swiper-button-prev"></div>
		<div class="swiper-button-next"></div>
		-->
		
		<!-- <div class="swiper-scrollbar"></div> -->
	  </div>	
	`,
	afterDrop: function (node)
	{
		//check if swiper js is included and if not add it when drag starts to allow the script to load
		body = Vvveb.Builder.frameBody;
		
		if ($("#swiper-js", body).length == 0)
		{
			let swiperScript = `<script id="swiper-js" src="../../js/libs/swiper/swiper-bundle.min.js"></script> 
			<link id="swiper-css" href="../../js/libs/swiper/swiper-bundle.min.css" rel="stylesheet">
			<script>
			var swiper = [];
			function initSwiper(onlyNew = false) {
				var list = document.querySelectorAll('.swiper' + (onlyNew ? ":not(.swiper-initialized)" : "") );
				list.forEach(el => 
					swiper.push(new Swiper(el, el.dataset))
					//swiper.push(new Swiper(el, { ...{autoplay:{delay: 500}}, ...el.dataset}))		
				);
			}	
			$(document).ready(function() {
				initSwiper();
			  });
			</script>`;				
					
			$(body).append(swiperScript);
		} else {
			Vvveb.Builder.iframe.contentWindow.initSwiper(true);
		}
		
		return node;
	},
	
    onChange: function (node, property, value) {
		let element = node[0];
		if (property.key == "autoplay" && value == true) {
			value = {"waitForTransition":true,"enabled":value,"delay":element.dataset.delay};
		}

		element.swiper.params[property.key] = value;
		element.swiper.originalParams[property.key] = value;
		element.swiper.update();
		return node;
	},

    properties: [
	{
		name: "Slides",
        key: "slidesPerView",
        inputtype: ListInput,
		htmlAttr:"data-slides-per-view",
		inline:true,
		data: {
			"selector":".swiper-slide",
			"container":".swiper-wrapper",
			"prefix":"Slide ",
			"removeElement": false,//handle manually with removeSlide
			//"newElement": `<div class="swiper-slide"><img src="../../media/posts/1.jpg" class="img-fluid"><p>Slide 1</p></div>`
		},
        onChange: function(node, value, input, component, event) {
			let element = node[0];
			let dataset = {};
			for (i in element.dataset) {
				dataset[i] = element.dataset[i];
			}; 

			if (event.action) {
				if (event.action == "add") {
					let random = Math.floor(Math.random() * 6) + 1;
					let index = element.swiper.slides.length + 1;
					element.swiper.appendSlide(`<div class="swiper-slide"><img src="../../media/posts/${random}.jpg" class="img-fluid"><p>Slide ${index}</p></div>`);
					element.swiper.slideTo(index);
					//temporary solution to better update list
					Vvveb.Components.render("elements/carousel");
				}
				if (event.action == "remove") {
					element.swiper.removeSlide(event.index);
				}
			}
			
			setTimeout(function () {
				for (i in dataset) {
					element.swiper.params[i] = dataset[i];
					element.dataset[i] = dataset[i];
				}; 
				element.swiper.update(); 
			}, 1000);
			
			return node;
		},
	},	{
		name: "Slides per view",
        key: "slidesPerView",
        inputtype: NumberInput,
		htmlAttr:"data-slides-per-view",
	},
	{
		name: "Space between",
        key: "spaceBetween",
        inputtype: NumberInput,
		htmlAttr:"data-space-between",
	},	
	{
		name: "Speed",
        key: "speed",
        inputtype: NumberInput,
		htmlAttr:"data-speed",
		data: {step:100},
	},	
	{
		name: "Delay",
        key: "delay",
        inputtype: NumberInput,
		htmlAttr:"data-delay",
		data: {step:100},
    },{
		key: "carousel_options",
        inputtype: SectionInput,
        name:false,
        data: {header:"Options"},
    },{	
		name: "Simulate touch",
        key: "simulateTouch",
		htmlAttr:"data-simulate-touch",
		inputtype: CheckboxInput,
		inline:true,
        col:4
    },{	
		name: "Autoplay",
        key: "autoplay",
		htmlAttr:"data-autoplay",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	}, {
		name: "Auto height",
        key: "autoHeight",
		htmlAttr:"data-auto-height",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{
		name: "Centered slides",
        key: "centeredSlides",
		htmlAttr:"data-centered-slides",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	name: "Center insufficient",
        key: "centerInsufficientSlides",
		htmlAttr:"data-center-insufficient-slides",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	name: "Loop",
        key: "loop",
		htmlAttr:"data-loop",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	name: "Mouse wheel",
        key: "mousewheel",
		htmlAttr:"data-mousewheel",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	
        name: "Pagination",
        key: "pagination",
		htmlAttr:"data-pagination",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	name: "Rewind",
        key: "rewind",
		htmlAttr:"data-rewind",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},{	name: "Scrollbar",
        key: "scrollbar",
		htmlAttr:"data-scrollbar",
		inputtype: CheckboxInput,
		inline:true,
        col:4
	},/*
	{
        name: "direction",
        key: "direction",
		htmlAttr:"data-direction",
		section: style_section,
        col:6,
        inline:false,
        inputtype: RadioButtonInput,
        data: {
			extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "horizontal",
                icon:"la la-arrow-down",
                title: "Horizontal",
                checked:true,
            }, {
                value: "vertical",
                title: "Vertical",
                icon:"la la-arrow-right",
                checked:false,
			}],
		}
    }*/]
});



Vvveb.Components.add("elements/icon-list", {
    nodes: [".counter"],
    name: "Icon list",
    image: "icons/icon-list.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/divider", {
    nodes: [".counter"],
    name: "Divider",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/separator", {
    nodes: [".counter"],
    name: "Separator",
    image: "icons/separator.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/Image box", {
    nodes: [".counter"],
    name: "Image Box",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/Icon box", {
    nodes: [".counter"],
    name: "Image Box",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/animated-headline", {
    nodes: [".counter"],
    name: "Animated headline",
    image: "icons/heading.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/price-table", {
    nodes: [".counter"],
    name: "Price table",
    image: "icons/price-table.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/price-list", {
    nodes: [".counter"],
    name: "Price list",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/reviews", {
    nodes: [".counter"],
    name: "Reviews",
    image: "icons/reviews.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/code", {
    nodes: [".counter"],
    name: "Code",
    image: "icons/code.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
}); 
  
Vvveb.Components.add("elements/image-compare", {
    nodes: [".counter"],
    name: "Image Compare",
    image: "icons/image-compare.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/rating", {
    nodes: [".rating"],
    name: "Rating stars",
    image: "icons/rating.svg",
    html: `<div class="rating">
                <i class="la la-star text-warning"></i>
                <i class="la la-star text-warning"></i>
                <i class="la la-star text-warning"></i>
                <i class="la la-star text-warning"></i>
                <i class="la la-star text-secondary"></i>
            </div>`,
    properties: [
	]
});
