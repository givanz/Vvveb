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
 * https://github.com/givanz/Vvveb
 */

Vvveb.ComponentsGroup['Elements'] = [
/*sections */
"elements/font-icon",
"elements/carousel", 
"elements/gallery",
"elements/slider",
"elements/tabs",
"elements/accordion",
"elements/flip-box",
//"elements/counter",
"elements/svg-icon",
"elements/figure",
//"elements/testimonial",
"elements/social-icons",
//"elements/icon-list",
//"elements/divider",
//"elements/separator",
//"elements/image-box",
//"elements/icon-box",
//"elements/animated-headline",
//"elements/price-table",
//"elements/price-list",
//"elements/reviews",
"elements/code",
//"elements/image-compare",
//"elements/back-to-top",
//"elements/blob",
//"elements/image-shape",
//"elements/image-shape",
//"elements/rating",
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
		
	stylesheets:[{
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
    },{
        name: "Width",
        key: "width",
        child:"img",
        htmlAttr: "width",
        inputtype: CssUnitInput
    },{
        name: "Height",
        key: "height",
        child:"img",
        htmlAttr: "height",
        inputtype: CssUnitInput
    },{
        name: "Alt",
        key: "alt",
        child:"img",
        htmlAttr: "alt",
        inputtype: TextInput
    },{
        name: "Caption",
        key: "caption",
        child:"figcaption",
        htmlAttr: "innerHTML",
        inputtype: TextareaInput
    }]    
});



//Icon
Vvveb.Components.extend("_base","elements/font-icon", {
    classes: ["la", "lab"],
    name: "Font Icon",
    image: "icons/star.svg",
    html: `<i class="la la-star la-2x"></i>`,
    properties: [
{
		name: "Icon",
		key: "icon",
		inline:true,
		inputtype: HtmlListSelectInput,
		onChange:function(element, value, input, component) {
			element.classList.remove("la", "lab", "lar");
			let className = element.getAttribute('class');
			element.classList.forEach((value, key, listObj) => {
				if (value.startsWith("la-") && value != "la-lg") {
					element.classList.remove(value);
				}
			});

			element.classList.add(...input.className.split(" "));
			return element;
		},
		data: {
			url: Vvveb.baseUrl + "../../resources/{value}.html",
			clickElement:"li",
			insertElement:"i",
			elements: 'Loading ...',
			options: [{
                value: "line-awesome",
                text: "Line-awesome"
            }]
		},
	},{
        name: "Size",
        key: "type",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["","la-lg", "la-2x"],
        data: {
            options: [{
                value: "",
                text: "Normal"
            },{
                value: "la-lg",
                text: "Large"
            },{
                value: "la-2x",
                text: "2x"
            }]
        }
    }]
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
},{
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
			let newElement = generateElements(value)[0];
			let attributes = element.attributes;
			
			//keep old svg size and colors
			for (let i = 0; i < attributes.length; i++) {
				let attr = attributes[i];
				if (attr.name && attr.name != "viewBox") {
					newElement.setAttribute(attr.name, attr.value);
				}
			}
			
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
				},{
					value: "ionicons",
					text: "IonIcons"
				},{
					value: "linea",
					text: "Linea"
				},{
					value: "remix-icon",
					text: "RemixIcon"
				},{
					value: "unicons",
					text: "Unicons"
				},{
					value: "clarity-icons",
					text: "Clarity icons"
				},{
					value: "jam-icons",
					text: "Jam icons"
				},{
					value: "ant-design-icons",
					text: "Ant design icons"
				},{
					value: "themify",
					text: "Themify"
				},{
					value: "css.gg",
					text: "Css.gg"
				},{
					value: "olicons",
					text: "Olicons"
				},{
					value: "open-iconic",
					text: "Open iconic"
				},{
					value: "boxicons",
					text: "Box icons"
				},{
					value: "elegant-font",
					text: "Elegant font"
				},{
					value: "dripicons",
					text: "Dripicons"
				},{
					value: "feather",
					text: "Feather"
				},{
					value: "coreui-icons",
					text: "Coreui icons"
				},{
					value: "heroicons",
					text: "Heroicons"
				},{
					value: "iconoir",
					text: "Iconoir"
				},{
					value: "iconsax",
					text: "Iconsax"
				},{
					value: "ikonate",
					text: "Ikonate"
				},{
					value: "tabler-icons",
					text: "Tabler icons"
				},{
					value: "octicons",
					text: "Octicons"
				},{
					value: "system-uicons",
					text: "System-uicons"
				},{
					value: "font-awesome",
					text: "FontAwesome"
				},{
					value: "pe-icon-7-stroke",
					text: "Pixeden icon 7 stroke"
				},{
					value: "77_essential_icons",
					text: "77 essential icons"
				},{
					value: "150-outlined-icons",
					text: "150 outlined icons"
				},{
					value: "material-design",
					text: "Material Design"
				}]
            },
	   },{
		name: "Width",
		key: "width",
		htmlAttr: "width",
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}
   },{
		name: "Height",
		key: "height",
		htmlAttr: "height",
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}			
   },{
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
	},{
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
        htmlAttr: "stroke",
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
  	},{
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
					<a href="#">
						<img src="../../media/posts/1.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
						<img src="../../media/posts/2.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
						<img src="../../media/posts/3.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
						<img src="../../media/posts/4.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
						<img src="../../media/posts/5.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
						<img src="../../media/posts/6.jpg">
					</a>
				</div>
				<div class="item">
					<a href="#">
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
				document.querySelectorAll(".mb-2[data-group]").forEach(el => el.style.display = "none");
				document.querySelector('.mb-2[data-group="'+ group + '"]').style.display = "";
			}, 		
			onChange : function(node, value, input)  {
				this.setGroup(value);
				return node;
			}, 
			init: function (node) {
				if (node.classList.contains("masonry")) {
					return "masonry";
				} else {
					return "flex";
				}
			},   			
		},{
			name: "Image shadow",
			key: "shadow",
			htmlAttr: "class",
			validValues: [ "", "has-shadow"],
			inputtype: ToggleInput,
			data: {
				on: "has-shadow",
				off: ""
			},
		},{
			name: "Horizontal gap",
			key: "column-gap",
			htmlAttr: "style",
			inputtype: CssUnitInput,
			data:{
				max: 100,
				min:0,
				step:1
			}
	   },{
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
	   },{
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
		},{
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
	   },{
			name: "",
			key: "addChild",
			inputtype: ButtonInput,
			data: {text:"Add image", icon:"la la-plus"},
			onChange: function(node) {
				 node.append(generateElements('<div class="item"><a href="#"><img src="../../media/posts/1.jpg"></a></div>')[0]);
				 
				 //render component properties again to include the new image
				 //Vvveb.Components.render("ellements/gallery");
				 
				 return node;
			}
	}],
    init(node)	{

		document.querySelectorAll(".mb-2[data-group]").forEach(el => el.style.display = "none");
		
		let source = "flex";
		if (node.classList.contains("masonry")) {
			source = "masonry";
		} else {
			source = "flex";
		}
		
		document.querySelector('.mb-2[data-group="'+ source + '"]').style.display = "";
	}	
});  

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
		},{
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
	<div data-component-tabs id="tabs-parentId">
			<nav>
			  <div class="nav nav-tabs" role="tablist">
				<button class="nav-link active" id="nav-tab-parentId-1" data-bs-toggle="tab" data-bs-target="#nav-parentId-1" type="button" role="tab" aria-controls="nav-1" aria-selected="true">Home</button>
				<button class="nav-link" id="nav-tab-parentId-2" data-bs-toggle="tab" data-bs-target="#nav-parentId-2" type="button" role="tab" aria-controls="nav-2" aria-selected="false">Profile</button>
				<button class="nav-link" id="nav-tab-parentId-3" data-bs-toggle="tab" data-bs-target="#nav-parentId-3" type="button" role="tab" aria-controls="nav-3" aria-selected="false">Contact</button>	  </div>
			</nav>
			<div class="tab-content">
			  <div class="tab-pane p-4 show active" id="nav-parentId-1" role="tabpanel" aria-labelledby="nav-tab-1" tabindex="0">
				<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Corporis perferendis rem accusantium ducimus animi nesciunt expedita omnis aut quas molestias!</p>
			  </div>
			  <div class="tab-pane p-4" id="nav-parentId-2" role="tabpanel" aria-labelledby="nav-tab-2" tabindex="0">
				<p>Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin</p>
			  </div>
			  <div class="tab-pane p-4" id="nav-parentId-3" role="tabpanel" aria-labelledby="nav-tab-3" tabindex="0">
				<p>Quisque sagittis non ex eget vestibulum</p>
			  </div>
			</div>
	</div>`,
	afterDrop: function(node) {
		//set unique accordion parent id, this is a bootstrap accordion limitation
		let parentId = Math.ceil(Math.random() * 1000);
		node.id = 'tabs-' + parentId;
		node.innerHTML = node.innerHTML.replaceAll('parentId', parentId);

		return node;
	},	
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
				selector:"> .nav-link",
				container:"nav > .nav-tabs",
				prefix:"Tab ",
				name: "text",
				removeElement: false,//handle manually to delete pane also
				//"newElement": ``
			},
			onChange: function(node, value, input, component, event) {
				let element = node;
				let tabsId = element.id.replace('tabs-','');
				
				let nav = node.querySelector("nav .nav-tabs");
				let content = node.querySelector(".tab-content");

				if (event.action) {
					if (event.action == "add") {
						let random = Math.floor(Math.random() * 100) + 1;
						let index = nav.childElementCount + 1;
						
						nav.append(generateElements(`<button class="nav-link" id="nav-tab-${tabsId}-${index}-${random}" data-bs-toggle="tab" data-bs-target="#tab-${tabsId}-${index}-${random}" type="button" role="tab" aria-controls="tab-${index}-${random}" aria-selected="false">Tab ${index}</button>`)[0]);
						
						content.append(generateElements(`<div class="tab-pane p-4" id="tab-${tabsId}-${index}-${random}" role="tabpanel" aria-labelledby="tab-${tabsId}-${index}-${random}" tabindex="0"><p>Never think of results, just do!</p></div>`)[0]);
						
						//temporary solution to better update list
						Vvveb.Components.render("elements/tabs");
					}
					
					let index = event.index + 1;
					if (event.action == "remove") {
						nav.querySelector("button:nth-child(" + index + ")").remove();
						content.querySelector(".tab-pane:nth-child(" + index + ")").remove();
					} else if (event.action == "select") {
						let tab = nav.querySelector("button:nth-child(" + index + ")");
						Vvveb.Builder.iframe.contentWindow.bootstrap.Tab.getOrCreateInstance(tab).show();
					}
				}
				
				return node;
			},
		}
	]
}); 	


//Accordion
Vvveb.Components.add("elements/accordion", {
    classes: ["accordion"],
    name: "Accordeon",
    image: "icons/accordion.svg",
    html: `<div class="accordion" id="accordion-parentId">
		  <div class="accordion-item">
			<h2 class="accordion-header" id="headingOne-parentId">
			  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne-parentId" aria-expanded="true" aria-controls="collapseOne-parentId">
				Accordion Item #1
			  </button>
			</h2>
			<div id="collapseOne-parentId" class="accordion-collapse collapse show" aria-labelledby="headingOne-parentId" data-bs-parent="#accordion-parentId">
			  <div class="accordion-body">
				<p>Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin</p>
			  </div>
			</div>
		  </div>
		  <div class="accordion-item">
			<h2 class="accordion-header" id="headingTwo-parentId">
			  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo-parentId" aria-expanded="false" aria-controls="collapseTwo">
				Accordion Item #2
			  </button>
			</h2>
			<div id="collapseTwo-parentId" class="accordion-collapse collapse" aria-labelledby="headingTwo-parentId" data-bs-parent="#accordion-parentId">
			  <div class="accordion-body">
				<p>Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin</p>
			  </div>
			</div>
		  </div>
		  <div class="accordion-item">
			<h2 class="accordion-header" id="headingThree-parentId">
			  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree-parentId" aria-expanded="false" aria-controls="collapseThree">
				Accordion Item #3
			  </button>
			</h2>
			<div id="collapseThree-parentId" class="accordion-collapse collapse" aria-labelledby="headingThree-parentId" data-bs-parent="#accordion-parentId">
			  <div class="accordion-body">
				<p>Mauris viverra cursus ante laoreet eleifend. Donec vel fringilla ante. Aenean finibus velit id urna vehicula, nec maximus est sollicitudin</p>
			  </div>
			</div>
		  </div>
		</div>`,
	afterDrop: function(node) {
		//set unique accordion id
		let parentId = Math.ceil(Math.random() * 1000);
		//node.outerHTML = node.outerHTML.replaceAll('parentId', parentId);
		node.id = 'accordion-' + parentId;
		node.innerHTML = node.innerHTML.replaceAll('parentId', parentId);
		
		return node;
	},
    properties: [{
			//name: "List",
			key: "list",
			component: "elements/tab",
			inline:false,
			inputtype: ListInput,
			data: {
				selector:":scope > .accordion-item",
				container:"",
				prefix:"Item ",
				name: "text",
				nameElement: ".accordion-button",
				removeElement: false,//handle manually
				//"newElement": ``
			},
			onChange: function(node, value, input, component, event) {
				let element = node;
				let accordionId = element.id.replace('accordion-','');
				
				if (event.action) {
					if (event.action == "add") {
						let random = Math.floor(Math.random() * 100) + 1;
						let index = element.childElementCount + 1;
						
						node.append(generateElements(`<div class="accordion-item">
							<h2 class="accordion-header" id="heading-${index}-${random}">
							  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${index}-${random}" aria-expanded="false" aria-controls="collapseTwo">Accordion Item #${index}</button>
							</h2>
							<div id="collapse-${index}-${random}" class="accordion-collapse collapse" aria-labelledby="heading-${index}-${random}" data-bs-parent="#accordion-${accordionId}">
							  <div class="accordion-body">
								<p>Begin with the possible; begin with one step.</p>
							  </div>
							</div>
						  </div>`)[0]);
						
						//temporary solution to better update list
						Vvveb.Components.render("elements/accordion");
					}
					
					let index = (event.index + 1);
					if (event.action == "remove") {
						node.querySelector(":scope > .accordion-item:nth-child(" + index + ")").remove();
					} else if (event.action == "select") {
						let el = node.querySelector(":scope > .accordion-item:nth-child(" + index + ")");
						let btn= el.querySelector(".accordion-button");
						let collapse = el.querySelector(" .collapse");

						node.querySelectorAll(":scope > .accordion-item .collapse").forEach(e => e.classList.remove("show"));
						node.querySelectorAll(":scope > .accordion-item .accordion-button").forEach(btn => btn.classList.add("collapsed"));
						collapse.classList.add("show");
						btn.classList.remove("collapsed");
						//el[0].click();
						//Vvveb.Builder.iframe.contentWindow.bootstrap.Collapse.getOrCreateInstance(el[0]).toggle();
					}
				}
				
				return node;
			},
		},{
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
    classes: ["flip-box"],
    name: "Flip box",
    image: "icons/flipbox.svg",
    html: `<div class="flip-box enabled">
		  <div class="flip-box-inner">
			<div class="flip-box-front">
				  <div class="card">
				  <img src="../../media/posts/1.jpg" class="card-img-top" alt="Post">
				  <div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					<a href="#" class="btn btn-primary">Go somewhere</a>
				  </div>
				</div>	
			</div>
			
			<div class="flip-box-back">
				<div class="d-flex align-items-center flex-column">
				  <div class="flex-shrink-0">
					<img src="../../media/posts/2.jpg" alt="Post">
				  </div>
				  <div class="flex-grow-1 ms-3">
					<p>
						This is some content from a media component. You can replace this with any content and adjust it as needed.
					</p>
					
					<a href="#" class="btn btn-primary">Go somewhere</a>
				  </div>
				</div>
			</div>
		  </div>
		</div>`,
		properties: [{
			name: "Width",
			key: "width",
			htmlAttr: "style",
			inputtype: CssUnitInput
		},{
			name: "Height",
			key: "height",
			htmlAttr: "style",
			inputtype: CssUnitInput
		},{
			name: "Enabled",
			key: "enabled",
			htmlAttr: "class",
			validValues: ["enabled"],
			inputtype: ToggleInput,
			data: {
				on: "enabled",
				off: ""
			}
		},{
			name: "Show back",
			key: "back",
			htmlAttr: "class",
			validValues: ["back"],
			inputtype: ToggleInput,
			data: {
				on: "back",
				off: ""
			}
		},
		{
			name: "Vertical",
			key: "vertical",
			htmlAttr: "class",
			validValues: ["vertical"],
			inputtype: ToggleInput,
			data: {
				on: "vertical",
				off: ""
			}
		},
	]
});   

Vvveb.Components.add("elements/counter", {
    nodes: [".counter"],
    name: "Counter",
    image: "icons/stopwatch.svg",
    html: `<i class="font-icon la la-star"></i>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/testimonial", {
    nodes: [".counter"],
    name: "Testimonial",
    image: "icons/testimonial.svg",
    html: `<blockquote cite="https://en.wikipedia.org/wiki/Marcus_Aurelius">
				<p>Today I shall be meeting with interference, ingratitude, insolence, disloyalty, ill-will, and selfishness all of them due to the offenders' ignorance of what is good or evil.</p>
				<cite class="small">
					<a href="https://en.wikipedia.org/wiki/Marcus_Aurelius" class="text-decoration-none" target="blank">Marcus Aurelius</a>
				</cite>	
			</blockquote>`,
    properties: [
	]
});   

Vvveb.Components.add("elements/social-icons", {
    classes: ["social-icons"],
    name: "Social icons",
    image: "icons/social-icons.svg",
    html: `<ul class="social-icons list-unstyled">
				<li>
					<a href="https://facebook.com">
						<i class="lab la-facebook-f la-2x"></i> <span>Facebook</span>
					</a>
				</li>
				<li>
					<a href="https://linkedin.com">
						<i class="lab la-linkedin-in la-2x"></i> <span>Linkedin</span>
					</a>
				</li>		
				<li>
					<a href="https://twitter.com">
						<i class="lab la-twitter la-2x"></i> <span>Twitter</span>
					</a>
				</li>					
				<li>
					<a href="https://instagram.com">
						<i class="lab la-instagram la-2x"></i> <span>Instagram</span>
					</a>
				</li>				
				<li>
					<a href="https://github.com">
						<i class="lab la-github la-2x"></i> <span>Github</span>
					</a>
				</li>
			</ul>`,
		properties: [{
			//name: "List",
			key: "list",
			//component: "elements/tab",
			inline:false,
			inputtype: ListInput,
			data: {
				selector:"> li",
				container:"",
				prefix:"Item ",
				name: "text",
				nameElement: "span",
				removeElement: true,
				//"newElement": ``
			},
			onChange: function(node, value, input, component, event) {
				let element = node;

				if (event.action) {
					if (event.action == "add") {
						node.append(generateElements(`<li>
							<a href="https://twitter.com">
								<i class="lab la-twitter la-2x"></i> <span>Twitter</span>
							</a>
						</li>`)[0]);
						
						//temporary solution to better update list
						Vvveb.Components.render("elements/social-icons");
					}
					if (event.action == "remove") {
						node.querySelector(":scope > li:nth-child(" + event.index + ")").remove();
					} else if (event.action == "select") {
						let el = node.querySelector(":scope > li:nth-child(" + event.index + ")");
						//el[0].click();
						//Vvveb.Builder.iframe.contentWindow.bootstrap.Collapse.getOrCreateInstance(el[0]).toggle();
					}
				}
				
				return node;
			},
		},{
			name: "Inline",
			key: "list-inline",
			htmlAttr: "class",
			validValues: ["list-inline"],
			inputtype: ToggleInput,
			data: {
				on: "list-inline",
				off: ""
			}
		},{
			name: "Unstyled",
			key: "list-unstyled",
			htmlAttr: "class",
			validValues: ["list-unstyled"],
			inputtype: ToggleInput,
			data: {
				on: "list-unstyled",
				off: ""
			}
		}]
}); 

function carouselAfterDrop(node) {
	//check if swiper js is included and if not add it when drag starts to allow the script to load
	body = Vvveb.Builder.frameBody;

	if (!body.querySelector("#swiper-js")) {
		let link = document.createElement('link');
		let lib = document.createElement('script');
		let code = document.createElement('script');
		link.href = '../../js/libs/swiper/swiper-bundle.min.css';
		link.id = 'swiper-css';
		link.rel = 'stylesheet';
		lib.id = 'swiper-js';
		lib.type = 'text/javascript';
		lib.src = '../../js/libs/swiper/swiper-bundle.min.js';
		code.type = 'text/javascript';
		code.text = `
		let swiper = [];
		function initSwiper(onlyNew = false) {
			if (typeof Swiper == "undefined") return;
			let list = document.querySelectorAll('.swiper' + (onlyNew ? ":not(.swiper-initialized)" : "") );
			list.forEach(el => {
				let params = {      
					navigation: {
						nextEl: ".swiper-button-next",
						prevEl: ".swiper-button-prev",
					},      
					pagination: {
						el: ".swiper-pagination",
				  },
				};
				for (i in el.dataset) {
					let param = el.dataset[i];
					if (param[0] = '{') {
						param = JSON.parse(param);
					}
					params[i] = param;
				}
				swiper.push(new Swiper(el, params))
				//swiper.push(new Swiper(el, { ...{autoplay:{delay: 500}}, ...el.dataset}))		
			});
		}

		if (document.readyState !== 'loading') {
			initSwiper();
		  } else {
			document.addEventListener('DOMContentLoaded', initSwiper);
		  }`;			
		
		body.appendChild(link);
		body.appendChild(lib);
		body.appendChild(code);
		
		lib.addEventListener('load', function() {		
			Vvveb.Builder.iframe.contentWindow.initSwiper();
		});
	} else {
		Vvveb.Builder.iframe.contentWindow.initSwiper(true);
	}
	
	return node;
};

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
	afterDrop: carouselAfterDrop,
	
    onChange: function (node, property, value) {
		let element = node;
		if (property.key == "autoplay" && value == true) {
			value = {"waitForTransition":true,"enabled":value,"delay":element.dataset.delay};
		}

		element.swiper.params[property.key] = value;
		element.swiper.originalParams[property.key] = value;
		element.swiper.update();
		return node;
	},

    properties: [{
		name: "Slides",
        key: "slidesPerView",
        inputtype: ListInput,
		htmlAttr:"data-slides-per-view",
		inline:true,
		data: {
			selector:".swiper-slide",
			container:".swiper-wrapper",
			prefix:"Slide ",
			removeElement: false,//handle manually with removeSlide
			//"newElement": `<div class="swiper-slide"><img src="../../media/posts/1.jpg" class="img-fluid"><p>Slide 1</p></div>`
		},
        onChange: function(node, value, input, component, event) {
			let element = node;
			let dataset = {};
			for (i in element.dataset) {
				dataset[i] = element.dataset[i];
			}; 

			if (event.action) {
				if (event.action == "add") {
					let random = Math.floor(Math.random() * 6) + 1;
					let index = element.swiper.slides.length + 1;
					element.swiper.appendSlide(generateElements(`<div class="swiper-slide"><img src="../../media/posts/${random}.jpg" class="img-fluid"><p>Slide ${index}</p></div>`)[0]);
					element.swiper.slideTo(index);
					//temporary solution to better update list
					Vvveb.Components.render("elements/carousel");
				}
				if (event.action == "remove") {
					element.swiper.removeSlide(event.index);
				} else if (event.action == "select") {
					element.swiper.slideTo(event.index, 300, true);
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
	},{
		name: "Slides per view",
        key: "slidesPerView",
        inputtype: NumberInput,
		htmlAttr:"data-slides-per-view",
	},{
		name: "Space between",
        key: "spaceBetween",
        inputtype: NumberInput,
		htmlAttr:"data-space-between",
	},{
		name: "Speed",
        key: "speed",
        inputtype: NumberInput,
		htmlAttr:"data-speed",
		data: {step:100},
	},{
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
	},{
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
	},/*{
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
            },{
                value: "vertical",
                title: "Vertical",
                icon:"la la-arrow-right",
                checked:false,
			}],
		}
    }*/]
});

//Slider
Vvveb.Components.add("elements/slider", {
	name: "Slider",
    image: "icons/slider.svg",
     html: `
	  <div class="swiper" data-slides-per-view="1" data-draggable="true" data-navigation='{"nextEl": ".swiper-button-next","prevEl": ".swiper-button-prev"}'>
		<div class="swiper-wrapper">
		  <div class="swiper-slide"><img src="../../media/posts/1.jpg" class="img-fluid"><p>Slider 1</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/2.jpg" class="img-fluid"><p>Slider 2</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/3.jpg" class="img-fluid"><p>Slider 3</p></div>
		  <div class="swiper-slide"><img src="../../media/posts/4.jpg" class="img-fluid"><p>Slider 4</p></div>
		</div>
		<div class="swiper-pagination"></div>

		<div class="swiper-button-prev"></div>
		<div class="swiper-button-next"></div>
		
		<!-- <div class="swiper-scrollbar"></div> -->
	  </div>	
	`,
	afterDrop: carouselAfterDrop,
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
    image: "icons/dots_three.svg",
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
    nodes: ["code"],
    name: "Code",
    image: "icons/code.svg",
    html: `<code>print "Hello world!"</code>`,
	properties: [{
		name: "Text",
		key: "text",
		inline:false,
		htmlAttr: "innerHTML",
		inputtype: TextareaInput,
		data:{
			rows:20,
		}
	}]
}); 
  
Vvveb.Components.add("elements/image-compare", {
    nodes: [".counter"],
    name: "Image Compare",
    image: "icons/image-compare.svg",
    html: `<div class="c-compare" style="--value:50%;">
	  <img class="c-compare__left" src="img/color.jpg" alt="" />
	  <img class="c-compare__right" src="img/bw.jpg" alt="" />
	</div>`,
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
