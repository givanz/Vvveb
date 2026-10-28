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
 * https://github.com/givanz/Vvveb
 */

let bgVideoTemplate = '<video playsinline loop muted autoplay src="../../media/demo/sample.webm" poster="../../media/demo/sample.webp"></video>';
let bgYoutubeTemplate = '<div class="iframe-container"><iframe src="https://www.youtube.com/embed/C6fOoy7Se_4?autoplay=1&loop=1&playsinline=1&controls=0&mute=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen=""></iframe></div>';
let bgImageTemplate = '<img src="../../media/demo/sample.webp">';
let defaultSeparatorSvg = '<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 41" width="100%" height="300" fill="var(--bs-body-bg)" preserveAspectRatio="none"><defs><style>.cls-1{fill:inherit}</style></defs><title>rough-edges-bottom</title><path class="cls-1" d="M0,185l125-26,33,17,58-12s54,19,55,19,50-11,50-11l56,6,60-8,63,15v15H0Z" transform="translate(0 -159)"/></svg>';

//background type -> element selector / template used to create it inside .background-container
const backgrounds = {
	"bg-image": {selector: "img",               template: bgImageTemplate},
	"bg-video": {selector: "video",             template: bgVideoTemplate},
	"bg-yt":    {selector: ".iframe-container", template: bgYoutubeTemplate},
};

function getBackgroundType(node) {
	if (!node || typeof node.querySelector !== "function") return "";
	const container = node.querySelector(":scope > .background-container");
	if (!container) return "";

	const isVisible = (el) => el && (el.offsetParent || el.offsetHeight || !el.classList.contains("d-none"));

	let type = "";
	for (const [value, bg] of Object.entries(backgrounds)) {
		if (isVisible(container.querySelector(":scope > " + bg.selector))) {
			type = value;
		}
	}

	return type;
}

let section_sort = 1;

let SectionContent = [{
        name: "Label",
        key: "label",
        sort: section_sort++,
        htmlAttr: "aria-label",
        inputtype: TextInput
    },{
        name: "Container width",
        key: "container-width",
        sort: section_sort++,
		child: ":scope > .container, :scope > .container-fluid",
        htmlAttr: "class",
        validValues: ["container", "container-fluid"],
        inputtype: RadioButtonInput,
        data: {
            extraclass: "btn-group-sm btn-group-fullwidth",
            options: [{
                value: "container",
                icon: "box",
                text: "Boxed",
                title: "Boxed"
            },{
                value: "container-fluid",
                icon: "arrows-horizontal",
                text: "Full",
                title: "Full"
            }]
         }
	},{
        name: "Container height",
        key: "container-height",
        sort: section_sort++,
        child: ":scope > .container:first-child, :scope > .container-fluid:first-child",
        htmlAttr: "class",
        validValues: ["", "vh-100"],
        inputtype: RadioButtonInput,
        data: {
            extraclass: "btn-group-sm btn-group-fullwidth",
            options: [{
                value: "container",
                icon: "maximize",
                text: "Auto",
                title: "Auto",
                checked: true,
            },{
                value: "vh-100",
                icon: "arrows-vertical",
                text: "Full",
                title: "Full"
            }]
         }
	}
];

let SectionBackground = [{
        key: "section_background_header",
        inputtype: SectionInput,
        name:false,
        sort: section_sort++,
        data: {header:"Background"},
   },{
        name: false,
        key: "section-bg",
        sort: section_sort++,
        inputtype: RadioButtonInput,
        data: {
            inline: true,
            extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "none",
                text: "None",
                title: "None",
                checked:true,
            },{
                value: "bg-image",
                icon:"photo",
                text: "Image",
                title: "Image",
            },{/*
                value: "gradient",
                icon:"palette",
                text: "Gradient",
                title: "Gradient",
            },*/
                value: "bg-video",
                icon:"video",
                text: "Video",
                title: "Video",
            },{
                value: "bg-yt",
                icon:"brand-youtube",
                text: "Youtube",
                title: "Youtube",/*
            },{
                value: "slideshow",
                icon:"arrows-horizontal",
                text: "Slider",
                title: "Slider",*/
            }],
        },
		hideGroups : function() {
			document.querySelectorAll('.mb-2[data-group="bg-image"],.mb-2[data-group="bg-video"],.mb-2[data-group="bg-yt"]').forEach(e => e.classList.add("d-none"));
		},

		onChange : function(node, value, input) {
			if (!node || typeof node.querySelector !== "function") return node;
			this.hideGroups();
			document.querySelectorAll(`.mb-2[data-group="${input.value}"].d-none`).forEach((el) => el.classList.remove("d-none"));

			let container = node.querySelector(":scope > .background-container");
			if (!container) {
				container = generateElements('<div class="background-container"></div>')[0];
				node.appendChild(container);
			}

			//hide all background types, then show or create the selected one
			container.querySelectorAll(":scope > *").forEach((el) => el.classList.add("d-none"));

			const bg = backgrounds[value];
			if (bg) {
				const element = container.querySelector(":scope > " + bg.selector);
				if (element) {
					element.classList.remove("d-none");
				} else {
					container.append(generateElements(bg.template)[0]);
					//reselect element to load image / video
					if (typeof node.click === "function") node.click();
				}
			}

			return node;
		},
		init: function(node) {
			this.hideGroups();
			return getBackgroundType(node) || "none";
		},
    },{
        name: "Image",
        key: "src",
        sort: section_sort++,
        htmlAttr: "src",
		child: ":scope > .background-container > img",
		group:"bg-image",
		inline:true,
        inputtype: ImageInput
    },{
        name: "Video",
        key: "src",
        sort: section_sort++,
        htmlAttr: "src",
		child: ":scope > .background-container > video",
		group:"bg-video",
		inline:true,
        inputtype: VideoInput
    },{
        name: "Poster",
        key: "poster",
        sort: section_sort++,
        htmlAttr: "poster",
		child: ":scope > .background-container > video",
		group:"bg-video",
		inline:true,
        inputtype: ImageInput
    },{
        name: "Video",
        key: "src",
        sort: section_sort++,
        htmlAttr: "src",
		child: ":scope > .background-container > .iframe-container > iframe",
		group:"bg-yt",
		inline:true,
        inputtype: TextInput,
        onChange: function(node, value) {
			let url = String(value ?? "").trim();
			if (!url) return "";

			//convert watch/shorts/short-link urls to embed format
			let match = url.match(/[?&]v=([^&#]+)/) || url.match(/youtu\.be\/([^?&#/]+)/) || url.match(/\/shorts\/([^?&#/]+)/);
			if (match) {
				//keep unrelated params like list= or t=, playback params are re-added below
				let rest = "";
				try {
					const parsed = new URL(url, "https://www.youtube.com");
					parsed.searchParams.delete("v");
					["autoplay", "loop", "playsinline", "controls", "mute"].forEach(p => parsed.searchParams.delete(p));
					rest = parsed.searchParams.toString();
				} catch (e) {}
				url = "https://www.youtube.com/embed/" + match[1] + (rest ? "?" + rest : "");
			} else {
				url = url.replace("watch?v=", "embed/");
			}

			//youtube playback params only make sense on youtube urls
			if (/youtu\.?be/.test(url) && !url.includes("autoplay=1")) {
				url += (url.includes("?") ? "&" : "?") + "autoplay=1&loop=1&playsinline=1&controls=0&mute=1";
			}

			return url;
		},
    },{
        name: "Parallax",
        key: "parallax",
        sort: section_sort++,
        child: ":scope > .background-container",
        htmlAttr: "class",
        validValues: ["", "parallax"],
        inputtype: ToggleInput,
        data: {
            className: "",
            on: 'parallax',
            off: ''
        },
     },
];


let SectionOverlay = [{
        key: "section_overlay",
        inputtype: SectionInput,
        name:false,
        sort: section_sort++,
        data: {header:"Overlay"},
	},{
        //name: "Enable",
		name: false,
        key: "overlay",
        sort: section_sort++,
        inline: true,
        //validValues: ["", "active"],
        inputtype: ToggleInput,
        data: {
            className: "",
            on: 'true',
            off: 'false'
        },
		onChange : function(node, value, input) {
			if (!node || typeof node.querySelector !== "function") return node;
			const group = document.querySelectorAll('.mb-2[data-group="overlay"]');
			let overlay = node.querySelector(":scope > .overlay");

			if (value == 'true') {
				group.forEach(e => e.classList.remove("d-none"));

				if (!overlay) {
					overlay = generateElements('<div class="overlay"></div>')[0];
					node.appendChild(overlay);
				} else {
					overlay.classList.remove("d-none");
				}
			} else {
				group.forEach(e => e.classList.add("d-none"));
				if (overlay) overlay.classList.add("d-none");
			}

			return node;
		},
		init: function(node) {
			if (!node || typeof node.querySelector !== "function") return 'false';
			const overlay = node.querySelector(":scope > .overlay");
			const group = document.querySelectorAll('.mb-2[data-group="overlay"]');

			if (overlay && overlay.offsetParent) {
				group.forEach(e => e.classList.remove("d-none"));
				return 'true';
			} else {
				group.forEach(e => e.classList.add("d-none"));
				return 'false';
			}
		}
    },{
        name: "Color",
        key: "background-color",
        sort: section_sort++,
        htmlAttr: "style",
		child: ":scope > .overlay",
		group:"overlay",
        inputtype: ColorInput
   },{
        name: "Opacity",
        key: "opacity",
        sort: section_sort++,
		htmlAttr: "style",
		inline:false,
		group:"overlay",
		child: ":scope > .overlay",
        inputtype: RangeInput,
        data:{
			max: 1,
			min:0,
			step:0.1
       }
}];

/**
 * Property definitions shared by the top and bottom separators.
 */
function sectionSeparatorProperties(name, title) {
	return [{
        key: `section_${name}_separator`,
        inputtype: SectionInput,
        name:false,
        sort: section_sort++,
        data: {header:`${title} Separator`},
	},{
        //name: "Enable",
		name: false,
        key: `${name}_separator`,
        sort: section_sort++,
        inline: true,
        inputtype: ToggleInput,
        data: {
            className: "",
            on: 'true',
            off: 'false'
        },
		onChange : function(node, value, input) {
			if (!node || typeof node.querySelector !== "function") return node;
			const group = document.querySelectorAll(`[data-group="${name}_separator"]`);
			let separator = node.querySelector(`:scope > .${name}.separator`);

			if (value == 'true') {
				group.forEach(e => e.classList.remove("d-none"));

				if (!separator) {
					separator = generateElements(`<div class="separator ${name}">${defaultSeparatorSvg}</div>`)[0];
					node.appendChild(separator);
				} else {
					separator.classList.remove("d-none");
				}
			} else {
				group.forEach(e => e.classList.add("d-none"));
				if (separator) separator.classList.add("d-none");
			}

			return node;
		},
		init: function(node) {
			if (!node || typeof node.querySelector !== "function") return 'false';
			const group = document.querySelectorAll(`[data-group="${name}_separator"]`);
			const separator = node.querySelector(`:scope > .${name}.separator`);

			if (separator && separator.offsetParent) {
				group.forEach(e => e.classList.remove("d-none"));
				return 'true';
			} else {
				group.forEach(e => e.classList.add("d-none"));
				return 'false';
			}
		}
    },{
		name: "Icon",
		key: "icon",
		sort: section_sort++,
		inline:true,
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		inputtype: HtmlListSelectInput,
		onChange:function(element, value, input, component) {
			if (!element || !element.attributes || typeof element.replaceWith !== "function") return element;
			const newElement = generateElements(value || "")[0];
			if (!newElement) return element;
			const attributes = element.attributes;

			//keep old svg size and colors
			for (let i = 0; i < attributes.length; i++) {
				const attr = attributes[i];
				if (attr.name && attr.name != "viewBox") {
					newElement.setAttribute(attr.name, attr.value);
				}
			}

			element.replaceWith(newElement);
			return newElement;
		},
		data: {
			url: Vvveb.baseUrl + "../../resources/svg/separators/{value}/index.html",
			clickElement:"li",
			insertElement:"svg",
			elements: 'Loading ...',
			options: [{
                value: "digital-red-panther",
                text: "Red panther"
            },{
                value: "geometric",
                text: "Geometric"
            },{
                value: "organic",
                text: "Organic"
            }]
		},
	},{
		name: "Width",
		key: "width",
		sort: section_sort++,
		htmlAttr: "width",
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}
   },{
		name: "Height",
		key: "height",
		sort: section_sort++,
		htmlAttr: "height",
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		inputtype: RangeInput,
		data:{
			max: 640,
			min:6,
			step:1
		}
   },{
		name: "Stroke width",
		key: "stroke-width",
		sort: section_sort++,
		htmlAttr: "stroke-width",
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		inputtype: RangeInput,
		data:{
			max: 512,
			min:1,
			step:1
		}
   },{
        name: "Fill Color",
        key: "fill",
        sort: section_sort++,
        col:12,
        inline:true,
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		htmlAttr: "fill",
        inputtype: ColorInput,
   },{
        name: "Color",
        key: "color",
        sort: section_sort++,
        col:12,
        inline:true,
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		htmlAttr: "color",
        inputtype: ColorInput,
   },{
        name: "Stroke",
        key: "stroke",
        sort: section_sort++,
        col:12,
        inline:true,
		group:`${name}_separator`,
		child:`.separator.${name} > svg`,
		htmlAttr: "color",
        inputtype: ColorInput,
  	}
];
}

let SectionBottomSeparator = [{
        key: "section_bottom_separator",
        inputtype: SectionInput,
        name:false,
        sort: section_sort++,
        data: {header:"Bottom Separator"},
	},{
        //name: "Enable",
		name: false,
        key: "top_bottom",
        sort: section_sort++,
        inline: true,
        validValues: ["", "active"],
        inputtype: ToggleInput,
        data: {
            className: "",
            on: "active",
            off: ""
        }
	},
];

/* Section */
let ComponentSectionContent = [
	...SectionContent,
	...SectionBackground,
	...SectionOverlay,
	...sectionSeparatorProperties("top", "Top"),
	...sectionSeparatorProperties("bottom", "Bottom"),
];

let ComponentSectionStyle = [];

let ComponentSectionAdvanced = [];

/**
 * Panel init: hide all optional property groups, then show the ones matching
 * the selected node's current background / overlay / separators state.
 */
function componentsInit(node) {
	if (!node || typeof node.querySelector !== "function") return;

	document.querySelectorAll('.mb-2[data-group]').forEach(e => e.classList.add("d-none"));

	const showSection = function (section) {
		document.querySelectorAll(`.mb-2[data-group="${section}"]`).forEach(e => e.classList.remove("d-none"));
	};

	const bg = getBackgroundType(node);
	if (bg) {
		showSection(bg);
	}

	const overlay = node.querySelector(":scope > .overlay");
	if (overlay?.offsetParent) {
		showSection("overlay");
	}

	const separatorTop = node.querySelector(":scope > .separator.top");
	if (separatorTop?.offsetParent) {
		showSection("top_separator");
	}

	const separatorBottom = node.querySelector(":scope > .separator.bottom");
	if (separatorBottom?.offsetParent) {
		showSection("bottom_separator");
	}
}

Vvveb.Components.extend("_base", "elements/section", {
    nodes: ["section"],
    name: "Section",
    image: "icons/section.svg",
    html: `<section>
				<div class="container">
					<h1>Section</h1>
				</div>
			</section>`,
    properties: [
		...ComponentSectionContent,
		...ComponentSectionStyle,
		...ComponentSectionAdvanced
	],
	init: componentsInit
});

Vvveb.Components.extend("_base", "elements/header", {
    nodes: ["header"],
    name: "Header",
    image: "icons/section.svg",
    html: `<header>
				<div class="container">
					<h1>Section</h1>
				</div>
			</header>`,
    properties: [
		...ComponentSectionContent,
		...ComponentSectionStyle,
		...ComponentSectionAdvanced
	],
    init: componentsInit
});

Vvveb.Components.extend("_base", "elements/footer", {
    nodes: ["footer"],
    name: "Footer",
    image: "icons/section.svg",
    html: `<footer>
				<div class="container">
					<h1>Section</h1>
				</div>
			</footer>`,
    properties: [
		...ComponentSectionContent,
		...ComponentSectionStyle,
		...ComponentSectionAdvanced
	],
    init: componentsInit
});
