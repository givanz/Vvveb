/**
 * Vvveb
 *
 * Copyright (C) 2025  Ziadin Givan
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

/**
 * Tailwind CSS component definitions for the Vvveb page builder.
 *
 * Modeled on components-bootstrap4.js / components-bootstrap5.js: same
 * component ids (html/container, html/gridrow, html/button, ...) so core
 * builder references keep working, with Tailwind utility classes instead of
 * Bootstrap ones.
 *
 * Loading modes:
 *  - Framework swap: load instead of components-bootstrap5.js.
 *  - Theme overlay: place in {theme}/components/*.js; the editor loads it
 *    after the core files, so the registrations below override the Bootstrap
 *    defaults and the "Bootstrap 5" palette group is removed.
 *
 * Note on class properties: the builder swaps class values by removing every
 * entry listed in validValues and adding the selected value, so properties
 * that target `class` use ONE utility class per option. Options that need
 * several coordinated classes (alert schemes, active pagination items) are
 * implemented with onChange handlers that rewrite the node's class list.
 *
 * `primary` colors (bg-primary, text-primary, ...) require a matching
 * `theme.extend.colors.primary` entry in the site's tailwind.config.js, as
 * used by the landing-tailwind theme.
 */

Vvveb.ComponentsGroup['Tailwind'] = [
	"html/container", "html/flexrow", "html/gridrow", "html/button", "html/buttongroup",
	"html/alert", "html/card", "html/listgroup", "html/badge", "html/progress",
	"html/navbar", "html/breadcrumbs", "html/pagination", "html/hero", "html/spinner",
	"html/form", "html/textinput", "html/selectinput", "html/textareainput", "html/fileinput",
	"html/table"
];

//the framework layer this replaces is no longer needed in the palette
delete Vvveb.ComponentsGroup['Bootstrap 5'];

const tw_base_sort = 100;//start sorting from 100 so extended properties render first

//single-class background utilities (requires primary color in tailwind.config.js)
const twBgColorClasses = ["", "bg-primary", "bg-blue-600", "bg-green-600", "bg-red-600", "bg-yellow-500", "bg-sky-500", "bg-gray-100", "bg-gray-900", "bg-white"];

function twBgColorOptions() {
	return [
		{value: "", text: "Default"},
		{value: "bg-primary", text: "Primary"},
		{value: "bg-blue-600", text: "Blue"},
		{value: "bg-green-600", text: "Green"},
		{value: "bg-red-600", text: "Red"},
		{value: "bg-yellow-500", text: "Yellow"},
		{value: "bg-sky-500", text: "Sky"},
		{value: "bg-gray-100", text: "Gray"},
		{value: "bg-gray-900", text: "Dark"},
		{value: "bg-white", text: "White"}
	];
}

//named color schemes used by alerts/badges; each scheme maps to a set of
//utility classes applied together through onChange
const twSchemeClasses = {
	"":       ["rounded-lg border p-4", "border-gray-200", "bg-gray-50", "text-gray-700"],
	"info":   ["rounded-lg border p-4", "border-sky-200", "bg-sky-50", "text-sky-800"],
	"success":["rounded-lg border p-4", "border-green-200", "bg-green-50", "text-green-800"],
	"warning":["rounded-lg border p-4", "border-yellow-200", "bg-yellow-50", "text-yellow-800"],
	"danger": ["rounded-lg border p-4", "border-red-200", "bg-red-50", "text-red-800"],
	"primary":["rounded-lg border p-4", "border-primary-light", "bg-primary-light", "text-primary"],
	"dark":   ["rounded-lg border p-4", "border-gray-700", "bg-gray-800", "text-gray-100"]
};

/**
 * Replace all scheme classes on a node with the ones for the selected scheme.
 * Keeps unrelated classes (layout, spacing added by the user).
 */
function twApplyScheme(node, scheme, markerClass) {
	const classes = twSchemeClasses[scheme] || twSchemeClasses[""];
	const all = new Set();
	for (const key in twSchemeClasses) {
		for (const cls of twSchemeClasses[key]) {
			all.add(cls);
		}
	}
	//drop every known scheme class except the shared base, then add the new set
	const kept = (node.getAttribute("class") || "").split(/\s+/).filter(c => c && (!all.has(c) || c === markerClass));
	node.setAttribute("class", [...new Set(kept.concat(classes))].join(" "));
	return node;
}

function twSchemeOptions() {
	return [
		{value: "", text: "Default"},
		{value: "primary", text: "Primary"},
		{value: "info", text: "Info"},
		{value: "success", text: "Success"},
		{value: "warning", text: "Warning"},
		{value: "danger", text: "Danger"},
		{value: "dark", text: "Dark"}
	];
}

//shared tailwind input styling for form controls
const twInputClass = "w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary";

Vvveb.Components.extend("_base", "html/container", {
	classes: ["container"],
	image: "icons/container.svg",
	name: "Container",
	html: '<div class="container mx-auto px-4" style="min-height:150px;"><div class="py-12">Container</div></div>',
	properties: [{
		name: "Width",
		key: "width",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["container", "max-w-7xl", "max-w-screen-lg", "max-w-3xl", "max-w-full"],
		data: {
			options: [{
				value: "container",
				text: "Container"
			},{
				value: "max-w-7xl",
				text: "Max width 7xl"
			},{
				value: "max-w-screen-lg",
				text: "Max width screen lg"
			},{
				value: "max-w-3xl",
				text: "Max width 3xl"
			},{
				value: "max-w-full",
				text: "Full width"
			}]
		}
	},{
		name: "Centered",
		key: "centered",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["mx-auto"],
		data: {
			on: "mx-auto",
			off: ""
		}
	},{
		name: "Horizontal padding",
		key: "padding",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["px-0", "px-4", "px-6", "px-8"],
		data: {
			options: [{
				value: "px-0",
				text: "None"
			},{
				value: "px-4",
				text: "Small"
			},{
				value: "px-6",
				text: "Medium"
			},{
				value: "px-8",
				text: "Large"
			}]
		}
	},{
		name: "Background",
		key: "background",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: twBgColorClasses,
		data: {
			options: twBgColorOptions()
		}
	}],
});

/** Flex row: direction / alignment / gap via single-class utilities */
Vvveb.Components.add("html/flexrow", {
	name: "Flex Row",
	image: "icons/grid_row.svg",
	classes: ["flex"],
	html: '<div class="flex flex-wrap items-center gap-4"><div class="rounded-lg bg-gray-100 p-6">Item 1</div><div class="rounded-lg bg-gray-100 p-6">Item 2</div><div class="rounded-lg bg-gray-100 p-6">Item 3</div></div>',
	properties: [{
		name: "Direction",
		key: "direction",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["flex-row", "flex-row-reverse", "flex-col", "flex-col-reverse"],
		data: {
			options: [{
				value: "flex-row",
				text: "Row"
			},{
				value: "flex-row-reverse",
				text: "Row reverse"
			},{
				value: "flex-col",
				text: "Column"
			},{
				value: "flex-col-reverse",
				text: "Column reverse"
			}]
		}
	},{
		name: "Justify content",
		key: "justify",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["justify-start", "justify-center", "justify-end", "justify-between", "justify-around"],
		data: {
			options: [{
				value: "justify-start",
				text: "Start"
			},{
				value: "justify-center",
				text: "Center"
			},{
				value: "justify-end",
				text: "End"
			},{
				value: "justify-between",
				text: "Space between"
			},{
				value: "justify-around",
				text: "Space around"
			}]
		}
	},{
		name: "Align items",
		key: "align",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["items-start", "items-center", "items-end", "items-stretch", "items-baseline"],
		data: {
			options: [{
				value: "items-start",
				text: "Start"
			},{
				value: "items-center",
				text: "Center"
			},{
				value: "items-end",
				text: "End"
			},{
				value: "items-stretch",
				text: "Stretch"
			},{
				value: "items-baseline",
				text: "Baseline"
			}]
		}
	},{
		name: "Wrap",
		key: "wrap",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["flex-wrap"],
		data: {
			on: "flex-wrap",
			off: ""
		}
	},{
		name: "Gap",
		key: "gap",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["gap-0", "gap-2", "gap-4", "gap-6", "gap-8"],
		data: {
			options: [{
				value: "gap-0",
				text: "None"
			},{
				value: "gap-2",
				text: "Small"
			},{
				value: "gap-4",
				text: "Medium"
			},{
				value: "gap-6",
				text: "Large"
			},{
				value: "gap-8",
				text: "Extra large"
			}]
		}
	}]
});

/** Grid row: responsive columns through md:col-span-* utilities */
Vvveb.Components.add("html/gridcolumn", {
	name: "Grid Column",
	image: "icons/grid_row.svg",
	classesRegex: ["col-span-"],
	html: '<div class="md:col-span-1"><h3>Column</h3></div>',
	properties: [{
		name: "Column",
		key: "column",
		inputtype: GridInput,
		data: {hide_remove:true},

		beforeInit: function(node) {
			let _class = node.getAttribute("class");

			let reg = /([a-zA-Z]+:)?col-span-(\d+)/g;
			let match;

			while ((match = reg.exec(_class)) != null) {
				this.data["col" + ((match[1] != undefined) ? "_" + match[1].replace(':', '') : "")] = match[2];
			}
		},

		onChange: function(node, value, input) {
			//GridInput select names are "col", "col-sm", "col-md"; tailwind prefixes are "", "sm:", "md:"
			let isBase = input.name == "col";
			let twPrefix = isBase ? "" : input.name.replace(/^col-/, '') + ":";
			let rePrefix = isBase ? "" : input.name.replace(/^col-/, '') + "\\:";

			let _class = node.getAttribute("class") || "";

			//remove previous column span for this breakpoint
			_class = _class.replace(new RegExp("(^|\\s)" + rePrefix + "col-span-\\d+"), "$1");
			//add new column size
			if (value) _class += " " + twPrefix + "col-span-" + value;
			node.setAttribute("class", _class.trim());

			return node;
		},
	}]
});

Vvveb.Components.add("html/gridrow", {
	name: "Grid Row",
	image: "icons/grid_row.svg",
	classes: ["grid"],
	html: '<div class="grid grid-cols-1 gap-6 md:grid-cols-3"><div class="md:col-span-1"><h3>Column 1</h3></div><div class="md:col-span-1"><h3>Column 2</h3></div><div class="md:col-span-1"><h3>Column 3</h3></div></div>',
	children: [{
		name: "html/gridcolumn",
		classesRegex: ["col-span-"],
	}],
	beforeInit: function (node) {
		let properties = [];
		let i = 0;

		node.querySelectorAll('[class*="col-span-"]').forEach(el => {
			let _class = el.getAttribute("class");

			let reg = /([a-zA-Z]+:)?col-span-(\d+)/g;
			let match;
			let data = {};

			while ((match = reg.exec(_class)) != null) {
				data["col" + ((match[1] != undefined) ? "_" + match[1].replace(':', '') : "")] = match[2];
			}

			i++;
			properties.push({
				name: "Column " + i,
				key: "column" + i,
				columnNode: el,
				col:12,
				inline:false,
				inputtype: GridInput,
				data: data,
				onChange: function(node, value, input) {
					let column = this.columnNode;

					//if remove button is clicked remove column and render row properties
					if (input.nodeName == 'BUTTON') {
						column.remove();
						Vvveb.Components.render("html/gridrow");
						return node;
					}

					//GridInput select names are "col", "col-sm", "col-md"; tailwind prefixes are "", "sm:", "md:"
					let isBase = input.name == "col";
					let twPrefix = isBase ? "" : input.name.replace(/^col-/, '') + ":";
					let rePrefix = isBase ? "" : input.name.replace(/^col-/, '') + "\\:";

					let _class = column.getAttribute("class") || "";

					//remove previous column span for this breakpoint
					_class = _class.replace(new RegExp("(^|\\s)" + rePrefix + "col-span-\\d+"), "$1");
					//add new column size
					if (value) _class += " " + twPrefix + "col-span-" + value;
					column.setAttribute("class", _class.trim());

					return node;
				},
			});
		});

		//remove all column properties
		this.properties = this.properties.filter(function(item) {
			return item.key.indexOf("column") === -1;
		});

		//add remaining properties to generated column properties
		properties.push(this.properties[0]);

		this.properties = properties;
		return node;
	},

	properties: [{
		name: "Column",
		key: "column1",
		inputtype: GridInput
	},{
		name: "",
		key: "addChild",
		inputtype: ButtonInput,
		data: {text:"Add column", icon:"plus"},
		onChange: function(node)
		{
			if (!node || typeof node.appendChild !== "function") return node;
			let div = (node.ownerDocument || document).createElement("div");
			div.setAttribute("class", "md:col-span-1");
			div.innerHTML = "<h3>Column</h3>";
			node.appendChild(div);

			//render component properties again to include the new column inputs
			Vvveb.Components.render("html/gridrow");

			return node;
		}
	}]
});

Vvveb.Components.extend("html/link", "html/button", {
	classes: ["btn"],
	name: "Button",
	image: "icons/button.svg",
	html: '<a href="#" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">Button</a>',
	properties: [{
		name: "Background",
		key: "background",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["bg-primary", "bg-primary-dark", "bg-blue-600", "bg-green-600", "bg-red-600", "bg-gray-100", "bg-gray-900", "bg-white"],
		data: {
			options: [{
				value: "bg-primary",
				text: "Primary"
			},{
				value: "bg-primary-dark",
				text: "Primary dark"
			},{
				value: "bg-blue-600",
				text: "Blue"
			},{
				value: "bg-green-600",
				text: "Green"
			},{
				value: "bg-red-600",
				text: "Red"
			},{
				value: "bg-gray-100",
				text: "Light"
			},{
				value: "bg-gray-900",
				text: "Dark"
			},{
				value: "bg-white",
				text: "White"
			}]
		}
	},{
		name: "Text color",
		key: "text-color",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["text-white", "text-gray-700", "text-gray-900", "text-primary"],
		data: {
			options: [{
				value: "text-white",
				text: "White"
			},{
				value: "text-gray-700",
				text: "Gray"
			},{
				value: "text-gray-900",
				text: "Dark"
			},{
				value: "text-primary",
				text: "Primary"
			}]
		}
	},{
		name: "Border",
		key: "border",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["border"],
		data: {
			on: "border",
			off: ""
		}
	},{
		name: "Rounded",
		key: "rounded",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["rounded-none", "rounded-md", "rounded-lg", "rounded-xl", "rounded-full"],
		data: {
			options: [{
				value: "rounded-none",
				text: "None"
			},{
				value: "rounded-md",
				text: "Medium"
			},{
				value: "rounded-lg",
				text: "Large"
			},{
				value: "rounded-xl",
				text: "Extra large"
			},{
				value: "rounded-full",
				text: "Full"
			}]
		}
	},{
		name: "Padding",
		key: "padding",
		inputtype: SelectInput,
		defaultValue: "md",
		init: function(node) {
			let _class = node.getAttribute("class") || "";
			if (_class.includes("px-6")) return "xl";
			if (_class.includes("px-5")) return "lg";
			if (_class.includes("px-3")) return "sm";
			return "md";
		},
		onChange: function(node, value) {
			const sizes = {
				"sm": ["px-3", "py-2"],
				"md": ["px-5", "py-2.5"],
				"lg": ["px-6", "py-3"],
				"xl": ["px-8", "py-4"]
			};
			const selected = sizes[value] || sizes["md"];
			const all = new Set();
			for (let key in sizes) {
				sizes[key].forEach(cls => all.add(cls));
			}
			const kept = (node.getAttribute("class") || "").split(/\s+/).filter(cl => cl && !all.has(cl));
			node.setAttribute("class", [...new Set(kept.concat(selected))].join(" "));
			return node;
		},
		data: {
			options: [{
				value: "sm",
				text: "Small"
			},{
				value: "md",
				text: "Medium"
			},{
				value: "lg",
				text: "Large"
			},{
				value: "xl",
				text: "Extra large"
			}]
		}
	},{
		name: "Font size",
		key: "font-size",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["text-xs", "text-sm", "text-base", "text-lg"],
		data: {
			options: [{
				value: "text-xs",
				text: "Extra small"
			},{
				value: "text-sm",
				text: "Small"
			},{
				value: "text-base",
				text: "Normal"
			},{
				value: "text-lg",
				text: "Large"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/buttongroup", {
	classes: ["inline-flex"],
	name: "Button Group",
	image: "icons/button_group.svg",
	html: '<div class="inline-flex flex-row rounded-lg border border-gray-300" role="group" aria-label="Basic example">' +
		  '<button type="button" class="border-r border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Left</button>' +
		  '<button type="button" class="border-r border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Middle</button>' +
		  '<button type="button" class="px-4 py-2 text-sm hover:bg-gray-50">Right</button></div>',
	properties: [{
		name: "Orientation",
		key: "orientation",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["flex-row", "flex-col"],
		defaultValue: "flex-row",
		data: {
			options: [{
				value: "flex-row",
				text: "Horizontal"
			},{
				value: "flex-col",
				text: "Vertical"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/alert", {
	classes: ["alert"],
	name: "Alert",
	image: "icons/alert.svg",
	html: '<div class="alert rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800" role="alert">' +
		  '<strong>Warning!</strong> You should check in on some of those fields below.</div>',
	properties: [{
		name: "Color scheme",
		key: "scheme",
		inputtype: SelectInput,
		defaultValue: "warning",
		init: function(node) {
			//detect current scheme from the node's class list
			let _class = node.getAttribute("class") || "";
			for (let scheme in twSchemeClasses) {
				if (!scheme) continue;
				if (twSchemeClasses[scheme].every(cls => cls === "rounded-lg border p-4" || _class.includes(cls))) {
					return scheme;
				}
			}
			return "";
		},
		onChange: function(node, value) {
			return twApplyScheme(node, value);
		},
		data: {
			options: twSchemeOptions()
		}
	}]
});

Vvveb.Components.extend("_base", "html/badge", {
	classes: ["badge"],
	image: "icons/badge.svg",
	name: "Badge",
	html: '<span class="badge inline-flex items-center rounded-full bg-primary-light px-2.5 py-0.5 text-xs font-medium text-primary">Badge</span>',
	properties: [{
		name: "Color scheme",
		key: "scheme",
		inputtype: SelectInput,
		defaultValue: "primary",
		init: function(node) {
			let _class = node.getAttribute("class") || "";
			if (_class.includes("bg-primary")) return "primary";
			if (_class.includes("bg-green-")) return "success";
			if (_class.includes("bg-red-")) return "danger";
			if (_class.includes("bg-yellow-")) return "warning";
			if (_class.includes("bg-sky-")) return "info";
			if (_class.includes("bg-gray-900")) return "dark";
			return "";
		},
		onChange: function(node, value) {
			const schemes = {
				"":        ["bg-gray-100", "text-gray-700"],
				"primary": ["bg-primary-light", "text-primary"],
				"info":    ["bg-sky-100", "text-sky-800"],
				"success": ["bg-green-100", "text-green-800"],
				"warning": ["bg-yellow-100", "text-yellow-800"],
				"danger":  ["bg-red-100", "text-red-800"],
				"dark":    ["bg-gray-900", "text-gray-100"]
			};
			const selected = schemes[value] || schemes[""];
			const all = new Set();
			for (let key in schemes) {
				schemes[key].forEach(cls => all.add(cls));
			}
			const kept = (node.getAttribute("class") || "").split(/\s+/).filter(c => c && !all.has(c));
			node.setAttribute("class", [...new Set(kept.concat(selected))].join(" "));
			return node;
		},
		data: {
			options: twSchemeOptions()
		}
	}]
});

Vvveb.Components.extend("_base", "html/card", {
	classes: ["card"],
	image: "icons/panel.svg",
	name: "Card",
	html: '<div class="card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">' +
		  '<img class="w-full" src="' + Vvveb.baseUrl + 'icons/image.svg" alt="Card image">' +
		  '<div class="p-6">' +
		  '<h4 class="mb-2 text-lg font-semibold">Card title</h4>' +
		  '<p class="mb-4 text-sm text-gray-600">Some quick example text to build on the card title and make up the bulk of the card\'s content.</p>' +
		  '<a href="#" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">Go somewhere</a>' +
		  '</div></div>',
	properties: [{
		name: "Shadow",
		key: "shadow",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["shadow-none", "shadow-sm", "shadow", "shadow-lg", "shadow-xl"],
		data: {
			options: [{
				value: "shadow-none",
				text: "None"
			},{
				value: "shadow-sm",
				text: "Small"
			},{
				value: "shadow",
				text: "Normal"
			},{
				value: "shadow-lg",
				text: "Large"
			},{
				value: "shadow-xl",
				text: "Extra large"
			}]
		}
	},{
		name: "Rounded",
		key: "rounded",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["rounded-none", "rounded-lg", "rounded-xl", "rounded-2xl", "rounded-3xl"],
		data: {
			options: [{
				value: "rounded-none",
				text: "None"
			},{
				value: "rounded-lg",
				text: "Large"
			},{
				value: "rounded-xl",
				text: "Extra large"
			},{
				value: "rounded-2xl",
				text: "2 extra large"
			},{
				value: "rounded-3xl",
				text: "3 extra large"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/listgroup", {
	name: "List Group",
	image: "icons/list_group.svg",
	classes: ["list-group"],
	html: '<ul class="list-group divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">' +
		  '<li class="p-4">Cras justo odio</li>' +
		  '<li class="p-4">Dapibus ac facilisis in</li>' +
		  '<li class="p-4">Morbi leo risus</li>' +
		  '</ul>'
});

Vvveb.Components.extend("_base", "html/listitem", {
	name: "List Item",
	classes: ["list-group-item"],
	html: '<li class="list-group-item p-4">Cras justo odio</li>'
});

Vvveb.Components.extend("_base", "html/breadcrumbs", {
	classes: ["breadcrumb"],
	name: "Breadcrumbs",
	image: "icons/breadcrumbs.svg",
	html: '<ol class="breadcrumb flex flex-wrap items-center gap-2 text-sm text-gray-500">' +
		  '<li><a href="#" class="hover:text-primary">Home</a></li>' +
		  '<li aria-hidden="true">/</li>' +
		  '<li><a href="#" class="hover:text-primary">Library</a></li>' +
		  '<li aria-hidden="true">/</li>' +
		  '<li class="font-medium text-gray-900" aria-current="page">Data</li>' +
		  '</ol>'
});

Vvveb.Components.extend("_base", "html/pagination", {
	classes: ["pagination"],
	name: "Pagination",
	image: "icons/pagination.svg",
	html: '<nav aria-label="Page navigation">' +
	  '<ul class="pagination flex items-center gap-1">' +
		'<li><a class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50" href="#">Previous</a></li>' +
		'<li><a class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50" href="#">1</a></li>' +
		'<li><a class="rounded-md bg-primary px-3 py-1.5 text-sm font-semibold text-white" href="#" aria-current="page">2</a></li>' +
		'<li><a class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50" href="#">3</a></li>' +
		'<li><a class="rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50" href="#">Next</a></li>' +
	  '</ul>' +
	'</nav>',
	properties: [{
		name: "Alignment",
		key: "alignment",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["justify-start", "justify-center", "justify-end"],
		data: {
			options: [{
				value: "justify-start",
				text: "Left"
			},{
				value: "justify-center",
				text: "Center"
			},{
				value: "justify-end",
				text: "Right"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/progress", {
	classes: ["progress"],
	name: "Progress Bar",
	image: "icons/progressbar.svg",
	html: '<div class="progress h-2 w-full rounded-full bg-gray-200"><div class="progress-bar h-full w-1/4 rounded-full bg-primary"></div></div>',
	properties: [{
		name: "Progress",
		key: "progress",
		child:".progress-bar",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["w-1/12", "w-1/4", "w-1/2", "w-3/4", "w-full"],
		data: {
			options: [{
				value: "w-1/12",
				text: "~8%"
			},{
				value: "w-1/4",
				text: "25%"
			},{
				value: "w-1/2",
				text: "50%"
			},{
				value: "w-3/4",
				text: "75%"
			},{
				value: "w-full",
				text: "100%"
			}]
		}
	},{
		name: "Bar color",
		key: "bar-color",
		child:".progress-bar",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["bg-primary", "bg-blue-600", "bg-green-600", "bg-red-600", "bg-yellow-500", "bg-gray-900"],
		data: {
			options: [{
				value: "bg-primary",
				text: "Primary"
			},{
				value: "bg-blue-600",
				text: "Blue"
			},{
				value: "bg-green-600",
				text: "Green"
			},{
				value: "bg-red-600",
				text: "Red"
			},{
				value: "bg-yellow-500",
				text: "Yellow"
			},{
				value: "bg-gray-900",
				text: "Dark"
			}]
		}
	},{
		name: "Track color",
		key: "track-color",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["bg-gray-100", "bg-gray-200", "bg-gray-300"],
		data: {
			options: [{
				value: "bg-gray-100",
				text: "Light"
			},{
				value: "bg-gray-200",
				text: "Medium"
			},{
				value: "bg-gray-300",
				text: "Dark"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/navbar", {
	classes: ["navbar"],
	image: "icons/navbar.svg",
	name: "Nav Bar",
	html: '<nav class="navbar sticky top-0 z-40 border-b border-gray-100 bg-white">' +
		  '<div class="container mx-auto flex items-center justify-between px-4 py-3">' +
		  '<a class="text-lg font-bold" href="#">Brand</a>' +
		  '<button class="rounded-md p-2 hover:bg-gray-100 md:hidden" type="button" aria-label="Toggle navigation">' +
		  '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>' +
		  '</button>' +
		  '<div class="hidden items-center gap-6 md:flex">' +
		  '<a class="text-sm font-medium text-gray-700 hover:text-primary" href="#">Home</a>' +
		  '<a class="text-sm font-medium text-gray-700 hover:text-primary" href="#">Features</a>' +
		  '<a class="text-sm font-medium text-gray-700 hover:text-primary" href="#">Contact</a>' +
		  '<a class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark" href="#">Sign up</a>' +
		  '</div>' +
		  '</div></nav>',
	properties: [{
		name: "Placement",
		key: "placement",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["relative", "sticky", "fixed"],
		data: {
			options: [{
				value: "relative",
				text: "Static"
			},{
				value: "sticky",
				text: "Sticky"
			},{
				value: "fixed",
				text: "Fixed"
			}]
		}
	},{
		name: "Background",
		key: "background",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["bg-white", "bg-gray-50", "bg-gray-900", "bg-primary"],
		data: {
			options: [{
				value: "bg-white",
				text: "White"
			},{
				value: "bg-gray-50",
				text: "Light"
			},{
				value: "bg-gray-900",
				text: "Dark"
			},{
				value: "bg-primary",
				text: "Primary"
			}]
		}
	},{
		name: "Border",
		key: "border",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["border-b"],
		data: {
			on: "border-b",
			off: ""
		}
	}]
});

/** Tailwind equivalent of the bootstrap jumbotron */
Vvveb.Components.extend("_base", "html/hero", {
	classes: ["hero"],
	image: "icons/jumbotron.svg",
	name: "Hero",
	html: '<section class="hero bg-gray-900 py-24 text-center text-white">' +
		  '<div class="container mx-auto px-4">' +
		  '<h1 class="mb-4 text-4xl font-bold md:text-5xl">Hello, world!</h1>' +
		  '<p class="mx-auto mb-8 max-w-2xl text-lg text-gray-300">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>' +
		  '<a class="inline-flex items-center rounded-lg bg-primary px-6 py-3 text-base font-semibold text-white hover:bg-primary-dark" href="#" role="button">Learn more</a>' +
		  '</div></section>',
	properties: [{
		name: "Background",
		key: "background",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["bg-gray-900", "bg-primary", "bg-gradient-to-r", "bg-white"],
		data: {
			options: [{
				value: "bg-gray-900",
				text: "Dark"
			},{
				value: "bg-primary",
				text: "Primary"
			},{
				value: "bg-gradient-to-r",
				text: "Gradient"
			},{
				value: "bg-white",
				text: "Light"
			}]
		}
	},{
		name: "Vertical padding",
		key: "padding",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["py-12", "py-24", "py-32", "py-48"],
		data: {
			options: [{
				value: "py-12",
				text: "Small"
			},{
				value: "py-24",
				text: "Medium"
			},{
				value: "py-32",
				text: "Large"
			},{
				value: "py-48",
				text: "Extra large"
			}]
		}
	},{
		name: "Text align",
		key: "text-align",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["text-left", "text-center", "text-right"],
		data: {
			options: [{
				value: "text-left",
				text: "Left"
			},{
				value: "text-center",
				text: "Center"
			},{
				value: "text-right",
				text: "Right"
			}]
		}
	}]
});

Vvveb.Components.add("html/spinner", {
	name: "Spinner",
	image: "icons/code.svg",
	classes: ["animate-spin"],
	html: '<div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-primary" role="status" aria-label="Loading"></div>',
	properties: [{
		name: "Size",
		key: "size",
		inputtype: SelectInput,
		defaultValue: "md",
		init: function(node) {
			let _class = node.getAttribute("class") || "";
			if (_class.includes("h-16")) return "xl";
			if (_class.includes("h-12")) return "lg";
			if (_class.includes("h-4")) return "sm";
			return "md";
		},
		onChange: function(node, value) {
			const sizes = {
				"sm": ["h-4", "w-4"],
				"md": ["h-8", "w-8"],
				"lg": ["h-12", "w-12"],
				"xl": ["h-16", "w-16"]
			};
			const selected = sizes[value] || sizes["md"];
			const all = new Set();
			for (let key in sizes) {
				sizes[key].forEach(cls => all.add(cls));
			}
			const kept = (node.getAttribute("class") || "").split(/\s+/).filter(cl => cl && !all.has(cl));
			node.setAttribute("class", [...new Set(kept.concat(selected))].join(" "));
			return node;
		},
		data: {
			options: [{
				value: "sm",
				text: "Small"
			},{
				value: "md",
				text: "Medium"
			},{
				value: "lg",
				text: "Large"
			},{
				value: "xl",
				text: "Extra large"
			}]
		}
	},{
		name: "Color",
		key: "color",
		child: "div",
		htmlAttr: "class",
		inputtype: SelectInput,
		validValues: ["border-t-primary", "border-t-blue-600", "border-t-gray-900", "border-t-white"],
		data: {
			options: [{
				value: "border-t-primary",
				text: "Primary"
			},{
				value: "border-t-blue-600",
				text: "Blue"
			},{
				value: "border-t-gray-900",
				text: "Dark"
			},{
				value: "border-t-white",
				text: "White"
			}]
		}
	}]
});

Vvveb.Components.extend("_base", "html/form", {
	nodes: ["form"],
	image: "icons/form.svg",
	name: "Form",
	html: '<form action="" method="POST">' +
		  '<div class="mb-4"><label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email address</label>' +
		  '<input type="email" id="email" class="' + twInputClass + '" placeholder="you@example.com"></div>' +
		  '<div class="mb-4"><label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>' +
		  '<input type="password" id="password" class="' + twInputClass + '"></div>' +
		  '<div class="mb-4 flex items-center gap-2">' +
		  '<input type="checkbox" id="remember" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">' +
		  '<label for="remember" class="text-sm text-gray-700">Remember me</label></div>' +
		  '<button type="submit" class="inline-flex items-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">Submit</button>' +
		  '</form>'
});

Vvveb.Components.extend("_base", "html/textinput", {
	html: '<div class="mb-4"><label class="mb-1 block text-sm font-medium text-gray-700">Text</label>' +
		  '<input type="text" class="' + twInputClass + '"></div>'
});

Vvveb.Components.extend("_base", "html/selectinput", {
	html: '<div class="mb-4"><label class="mb-1 block text-sm font-medium text-gray-700">Choose an option </label>' +
		  '<select class="' + twInputClass + '"><option value="value1">Text 1</option><option value="value2">Text 2</option><option value="value3">Text 3</option></select></div>'
});

Vvveb.Components.extend("_base", "html/textareainput", {
	html: '<div class="mb-4"><label class="mb-1 block text-sm font-medium text-gray-700">Your response:</label>' +
		  '<textarea rows="4" class="' + twInputClass + '"></textarea></div>'
});

Vvveb.Components.extend("_base", "html/fileinput", {
	html: '<div class="mb-4"><input type="file" class="' + twInputClass + ' file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-200"></div>'
});

Vvveb.Components.extend("_base", "html/table", {
	nodes: ["table"],
	classes: ["table"],
	image: "icons/table.svg",
	name: "Table",
	html: '<table class="table min-w-full divide-y divide-gray-200 text-sm">' +
		  '<thead class="bg-gray-50">' +
		  '<tr>' +
		  '<th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>' +
		  '<th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">First Name</th>' +
		  '<th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Name</th>' +
		  '<th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Username</th>' +
		  '</tr>' +
		  '</thead>' +
		  '<tbody class="divide-y divide-gray-200 bg-white">' +
		  '<tr><td class="px-6 py-4">1</td><td class="px-6 py-4">Mark</td><td class="px-6 py-4">Otto</td><td class="px-6 py-4">@mdo</td></tr>' +
		  '<tr><td class="px-6 py-4">2</td><td class="px-6 py-4">Jacob</td><td class="px-6 py-4">Thornton</td><td class="px-6 py-4">@fat</td></tr>' +
		  '<tr><td class="px-6 py-4">3</td><td class="px-6 py-4">Larry</td><td class="px-6 py-4">the Bird</td><td class="px-6 py-4">@twitter</td></tr>' +
		  '</tbody>' +
		  '</table>',
	properties: [{
		name: "Striped rows",
		key: "striped",
		child: "tbody",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["odd:bg-gray-50"],
		data: {
			on: "odd:bg-gray-50",
			off: ""
		}
	},{
		name: "Hover rows",
		key: "hover",
		child: "tbody",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["hover:bg-gray-50"],
		data: {
			on: "hover:bg-gray-50",
			off: ""
		}
	},{
		name: "Compact",
		key: "compact",
		htmlAttr: "class",
		inputtype: ToggleInput,
		validValues: ["text-xs"],
		data: {
			on: "text-xs",
			off: ""
		}
	}]
});
