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

Vvveb.ComponentsGroup['Bootstrap 6'] =
["html/container", "html/gridrow", "html/btn", "html/buttongroup", "html/buttontoolbar", "html/alert", "html/card", "html/listgroup", "html/badge", "html/progress", "html/navbar", "html/breadcrumbs", "html/pagination"];


Vvveb.Components.extend("_base", "html/container", {
    classes: ["container", "container-fluid"],
    image: "icons/container.svg",
    html: '<div class="container" style="min-height:150px;"><div class="m-5">Container</div></div>',
    name: "Container",
    properties: [
     {
        name: "Type",
        key: "type",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["container", "container-fluid"],
        data: {
            options: [{
                value: "container",
                text: "Default"
            },{
                value: "container-fluid",
                text: "Fluid"
            }]
        }
    },{
        name: "Background",
        key: "background",
		htmlAttr: "class",
        validValues: bgcolorClasses,
        inputtype: SelectInput,
        data: {
            options: bgcolorSelectOptions
        }
    },{
        name: "Background Color",
        key: "background-color",
		htmlAttr: "style",
        inputtype: ColorInput,
    },{
        name: "Text Color",
        key: "color",
		htmlAttr: "style",
        inputtype: ColorInput,
    }],
});

Vvveb.Components.extend("html/link", "html/btn", {
    classes: ["btn"],
    nodes: null,
    name: "Button",
    image: "icons/button.svg",
    html: '<a class="btn btn-solid theme-primary">Primary</a>',
    properties: [{
        name: "Style",
        key: "style",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["btn-solid", "btn-outline", "btn-subtle", "btn-text", "btn-link"],
        data: {
            options: [{
                value: "btn-solid",
                text: "Solid"
            },{
                value: "btn-outline",
                text: "Outline"
            },{
                value: "btn-subtle",
                text: "Subtle"
            },{
                value: "btn-text",
                text: "Text"
            },{
                value: "btn-link",
                text: "Link"
            }]
        }
    },{
        name: "Color",
        key: "color",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["theme-primary", "theme-accent", "theme-success", "theme-danger", "theme-warning", "theme-info", "theme-secondary", "theme-inverse"],
        data: {
            options: [{
                value: "theme-primary",
                text: "Primary"
            },{
                value: "theme-accent",
                text: "Accent"
            },{
                value: "theme-success",
                text: "Success"
            },{
                value: "theme-danger",
                text: "Danger"
            },{
                value: "theme-warning",
                text: "Warning"
            },{
                value: "theme-info",
                text: "Info"
            },{
                value: "theme-secondary",
                text: "Secondary"
            },{
                value: "theme-inverse",
                text: "Inverse"
            }]
        }
    },{
        name: "Size",
        key: "size",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["btn-lg", "btn-sm"],
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "btn-lg",
                text: "Large"
            },{
                value: "btn-sm",
                text: "Small"
            }]
        }
   	},{
        name: "Autofocus",
        key: "autofocus",
        htmlAttr: "autofocus",
        inputtype: CheckboxInput,
		inline:true,
        col:6,
   	},{
        name: "Disabled",
        key: "disabled",
        htmlAttr: "class",
		inline:true,
        col:6,
        inputtype: ToggleInput,
        validValues: ["disabled"],
        data: {
            on: "disabled",
            off: null
        }
    },{
	key: "link_options",
        inputtype: SectionInput,
        name:false,
        data: {header:"Link"},
    }]
});
	
Vvveb.Components.extend("_base", "html/buttongroup", {
    classes: ["btn-group"],
    name: "Button Group",
    image: "icons/button_group.svg",
    html: '<div class="btn-group" role="group" aria-label="Basic example"><button type="button" class="btn btn-solid theme-secondary">Left</button><button type="button" class="btn btn-solid theme-secondary">Middle</button> <button type="button" class="btn btn-solid theme-secondary">Right</button></div>',
	properties: [{
	    name: "Size",
        key: "size",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["btn-group-lg", "btn-group-sm"],
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "btn-group-lg",
                text: "Large"
            },{
                value: "btn-group-sm",
                text: "Small"
            }]
        }
    },{
	    name: "Alignment",
        key: "alignment",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["btn-group", "btn-group-vertical"],
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "btn-group",
                text: "Horizontal"
            },{
                value: "btn-group-vertical",
                text: "Vertical"
            }]
        }
    }]
});
Vvveb.Components.extend("_base", "html/buttontoolbar", {
    classes: ["btn-toolbar"],
    name: "Button Toolbar",
    image: "icons/button_toolbar.svg",
    html: '<div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">\
		  <div class="btn-group me-3" role="group" aria-label="First group">\
			<button type="button" class="btn btn-solid theme-secondary">1</button>\
			<button type="button" class="btn btn-solid theme-secondary">2</button>\
			<button type="button" class="btn btn-solid theme-secondary">3</button>\
			<button type="button" class="btn btn-solid theme-secondary">4</button>\
		  </div>\
		  <div class="btn-group me-3" role="group" aria-label="Second group">\
			<button type="button" class="btn btn-solid theme-secondary">5</button>\
			<button type="button" class="btn btn-solid theme-secondary">6</button>\
			<button type="button" class="btn btn-solid theme-secondary">7</button>\
		  </div>\
		  <div class="btn-group" role="group" aria-label="Third group">\
			<button type="button" class="btn btn-solid theme-secondary">8</button>\
		  </div>\
		</div>'
});
Vvveb.Components.extend("_base","html/alert", {
    classes: ["alert"],
    name: "Alert",
    image: "icons/alert.svg",
    html: '<div class="alert theme-warning alert-dismissible fade show" role="alert">\
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">\
			<!--span aria-hidden="true">&times;</span-->\
		  </button>\
		  <strong>Holy guacamole!</strong> You should check in on some of those fields below.\
		</div>',
    properties: [{
        name: "Type",
        key: "type",
        htmlAttr: "class",
        validValues: ["theme-primary", "theme-accent", "theme-success", "theme-danger", "theme-warning", "theme-info", "theme-secondary", "theme-inverse"],
        inputtype: SelectInput,
        data: {
            options: [{
                value: "theme-primary",
                text: "Primary"
            },{
                value: "theme-secondary",
                text: "Secondary"
            },{
                value: "theme-accent",
                text: "Accent"
            },{
                value: "theme-success",
                text: "Success"
            },{
                value: "theme-danger",
                text: "Danger"
            },{
                value: "theme-warning",
                text: "Warning"
            },{
                value: "theme-info",
                text: "Info"
            },{
                value: "theme-inverse",
                text: "Inverse"
            }]
        }
    }]
});
Vvveb.Components.extend("_base", "html/badge", {
    classes: ["badge"],
    image: "icons/badge.svg",
    name: "Badge",
    html: '<span class="badge theme-primary">Primary badge</span>',
    properties: [{
        name: "Color",
        key: "color",
        htmlAttr: "class",
        validValues:["theme-primary", "theme-accent", "theme-secondary", "theme-success", "theme-danger", "theme-warning", "theme-info", "theme-inverse"],
        inputtype: SelectInput,
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "theme-primary",
                text: "Primary"
            },{
                value: "theme-accent",
                text: "Accent"
            },{
                value: "theme-secondary",
                text: "Secondary"
            },{
                value: "theme-success",
                text: "Success"
            },{
                value: "theme-warning",
                text: "Warning"
            },{
                value: "theme-danger",
                text: "Danger"
            },{
                value: "theme-info",
                text: "Info"
            },{
                value: "theme-inverse",
                text: "Inverse"
            }]
        }
     }]
});
Vvveb.Components.extend("_base", "html/card", {
    classes: ["card"],
    image: "icons/panel.svg",
    name: "Card",
    html: '<div class="card">\
		  <img class="card-img-top bg-secondary" src="' +  Vvveb.baseUrl + 'icons/image.svg" alt="Card image cap">\
		  <div class="card-body">\
			<h4 class="card-title">Card title</h4>\
			<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card\'s content.</p>\
			<a href="#" class="btn btn-solid theme-primary">Go somewhere</a>\
		  </div>\
		</div>'
});

Vvveb.Components.extend("_base", "html/listgroup", {
    name: "List Group",
    image: "icons/list_group.svg",
    classes: ["list-group"],
    html: '<ul class="list-group">\n  <li class="list-group-item">\n    <span class="badge theme-success">14</span>\n    Cras justo odio\n  </li>\n  <li class="list-group-item">\n    <span class="badge theme-primary">2</span>\n    Dapibus ac facilisis in\n  </li>\n  <li class="list-group-item">\n    <span class="badge theme-danger">1</span>\n    Morbi leo risus\n  </li>\n</ul>',
	properties: [{
        name: "Flush",
        key: "flush",
        htmlAttr: "class",
        validValues: ["", "list-group-flush"],
        inputtype: ToggleInput,
        data: {
            on: "list-group-flush",
            off: ""
        }
    },{
        name: "Numbered",
        key: "numbered",
        htmlAttr: "class",
        validValues: ["", "list-group-numbered"],
        inputtype: ToggleInput,
        data: {
            on: "list-group-numbered",
            off: ""
        }
    },{
        name: "Horizontal",
        key: "horizontal",
        htmlAttr: "class",
        validValues: ["", "list-group-horizontal"],
        inputtype: ToggleInput,
        data: {
            on: "list-group-horizontal",
            off: ""
        }
    }]    
});

Vvveb.Components.extend("_base", "html/listitem", {
    name: "List Item",
    classes: ["list-group-item"],
    html: '<li class="list-group-item"><span class="badge theme-primary">14</span> Cras justo odio</li>',
	properties: [{
        name: "Background",
        key: "background",
        htmlAttr: "class",
        validValues:["theme-primary", "theme-accent", "theme-secondary", "theme-success", "theme-danger", "theme-warning", "theme-info", "theme-inverse"],
        inputtype: SelectInput,
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "theme-primary",
                text: "Primary"
            },{
                value: "theme-accent",
                text: "Accent"
            },{
                value: "theme-secondary",
                text: "Secondary"
            },{
                value: "theme-success",
                text: "Success"
            },{
                value: "theme-warning",
                text: "Warning"
            },{
                value: "theme-danger",
                text: "Danger"
            },{
                value: "theme-info",
                text: "Info"
            },{
                value: "theme-inverse",
                text: "Inverse"
            }]
        }
     },	{
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
    },{
        name: "Disabled",
        key: "disabled",
        htmlAttr: "class",
        validValues: ["", "disabled"],
        inputtype: ToggleInput,
        inline:true,
        col:6,
        data: {
            on: "disabled",
            off: ""
        }
    }]    
});

Vvveb.Components.extend("_base", "html/breadcrumbs", {
    classes: ["breadcrumb"],
    name: "Breadcrumbs",
    image: "icons/breadcrumbs.svg",
    html: `<ol class="breadcrumb">
			<li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Home</a></li>
			<li class="breadcrumb-divider" aria-hidden="true"></li>
			<li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Library</a></li>
			<li class="breadcrumb-divider" aria-hidden="true"></li>
			<li class="breadcrumb-item" aria-current="page"><a class="breadcrumb-link active" href="#">Book</a></li>
		  </ol>`
});

Vvveb.Components.extend("_base", "html/breadcrumbitem", {
	classes: ["breadcrumb-item"],
    name: "Breadcrumb Item",
    html: '<li class="breadcrumb-item"><a class="breadcrumb-link" href="#">Library</a></li>',
    properties: [{
        name: "Active",
        key: "active",
        htmlAttr: "class",
        validValues: ["", "active"],
        inputtype: ToggleInput,
        data: {
            on: "active",
            off: ""
        }
    }]
});
Vvveb.Components.extend("_base", "html/pagination", {
    classes: ["pagination"],
    name: "Pagination",
    image: "icons/pagination.svg",
    html: '<nav aria-label="Page navigation example">\
	  <ul class="pagination">\
		<li class="page-item"><a class="page-link" href="#">Previous</a></li>\
		<li class="page-item"><a class="page-link" href="#">1</a></li>\
		<li class="page-item"><a class="page-link" href="#">2</a></li>\
		<li class="page-item"><a class="page-link" href="#">3</a></li>\
		<li class="page-item"><a class="page-link" href="#">Next</a></li>\
	  </ul>\
	</nav>',

    properties: [{
        name: "Size",
        key: "size",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["pagination-lg", "pagination-sm"],
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "pagination-lg",
                text: "Large"
            },{
                value: "pagination-sm",
                text: "Small"
            }]
        }
    },{
        name: "Alignment",
        key: "alignment",
        htmlAttr: "class",
        inputtype: SelectInput,
        validValues: ["justify-content-center", "justify-content-end"],
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "justify-content-center",
                text: "Center"
            },{
                value: "justify-content-end",
                text: "Right"
            }]
        }
    }]	
});
Vvveb.Components.extend("_base", "html/pageitem", {
	classes: ["page-item"],
    html: '<li class="page-item"><a class="page-link" href="#">1</a></li>',
    name: "Pagination Item",
    properties: [{
        name: "Link To",
        key: "href",
        htmlAttr: "href",
        child:".page-link",
        inputtype: LinkInput
    },{
        name: "Active",
        key: "active",
        htmlAttr: "class",
        validValues: ["active"],
        inputtype: ToggleInput,
        data: {
            on: "active",
            off: ""
        }
    },{
        name: "Disabled",
        key: "disabled",
        htmlAttr: "class",
        validValues: ["disabled"],
        inputtype: ToggleInput,
        data: {
            on: "disabled",
            off: ""
        }
   }]
});
Vvveb.Components.extend("_base", "html/progress", {
    classes: ["progress"],
    name: "Progress Bar",
    image: "icons/progressbar.svg",
    html: '<div class="progress"><div class="progress-bar w-25"></div></div>',
    properties: [{
        name: "Background",
        key: "background",
		htmlAttr: "class",
        validValues: bgcolorClasses,
        inputtype: SelectInput,
        data: {
            options: bgcolorSelectOptions
        }
    },
    {
        name: "Progress",
        key: "background",
        child:".progress-bar",
		htmlAttr: "class",
        validValues: ["", "w-25", "w-50", "w-75", "w-100"],
        inputtype: SelectInput,
        data: {
            options: [{
                value: "",
                text: "None"
            },{
                value: "w-25",
                text: "25%"
            },{
                value: "w-50",
                text: "50%"
            },{
                value: "w-75",
                text: "75%"
            },{
                value: "w-100",
                text: "100%"
            }]
        }
    }, 
    {
        name: "Progress background",
        key: "background",
        child:".progress-bar",
		htmlAttr: "class",
        validValues: bgcolorClasses,
        inputtype: SelectInput,
        data: {
            options: bgcolorSelectOptions
        }
    },{
        name: "Striped",
        key: "striped",
        child:".progress-bar",
        htmlAttr: "class",
        validValues: ["", "progress-bar-striped"],
        inputtype: ToggleInput,
        data: {
            on: "progress-bar-striped",
            off: "",
        }
    },{
        name: "Animated",
        key: "animated",
        child:".progress-bar",
        htmlAttr: "class",
        validValues: ["", "progress-bar-animated"],
        inputtype: ToggleInput,
        data: {
            on: "progress-bar-animated",
            off: "",
        }
    }]
});
Vvveb.Components.extend("_base", "html/navbar", {
    classes: ["navbar"],
    image: "icons/navbar.svg",
    name: "Nav Bar",
    html: `<nav class="navbar lg:navbar-expand bg-body">
			  <div class="container-fluid">
				<a class="navbar-brand" href="#">Navbar</a>
				<button class="navbar-toggler" type="button" data-bs-toggle="drawer" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
				  <span class="navbar-toggler-icon"></span>
				</button>
				<dialog class="drawer" id="navbarTogglerDemo02">
				  <div class="drawer-header">
					<span class="drawer-title">Menu</span>
					<button type="button" class="btn-close" data-bs-dismiss="drawer" aria-label="Close"></button>
				  </div>
				  <div class="drawer-body">
					<ul class="navbar-nav me-auto mb-2 lg:mb-0">
						<li class="nav-item">
						  <a class="nav-link active" aria-current="page" href="#">Home</a>
						</li>
						<li class="nav-item">
						  <a class="nav-link" href="#">Link</a>
						</li>
						<li class="nav-item">
						  <a class="nav-link disabled">Disabled</a>
						</li>
					</ul>
					<form class="d-flex" role="search">
						<input class="form-control me-3" type="search" placeholder="Search" aria-label="Search">
						<button class="btn btn-outline theme-success" type="submit">Search</button>
					</form>
				  </div>
				</dialog>
			  </div>
			</nav>`,
    
    properties: [{
        name: "Color theme",
        key: "color",
        htmlAttr: "data-bs-theme",
        inputtype: SelectInput,
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "light",
                text: "Light"
            },{
                value: "dark",
                text: "Dark"
            }]
        }
    },{
        name: "Background color",
        key: "background",
        htmlAttr: "class",
        validValues: bgcolorClasses,
        inputtype: SelectInput,
        data: {
            options: bgcolorSelectOptions
        }
    },{
        name: "Placement",
        key: "placement",
        htmlAttr: "class",
        validValues: ["fixed-top", "fixed-bottom", "sticky-top"],
        inputtype: SelectInput,
        data: {
            options: [{
                value: "",
                text: "Default"
            },{
                value: "fixed-top",
                text: "Fixed Top"
            },{
                value: "fixed-bottom",
                text: "Fixed Bottom"
            },{
                value: "sticky-top",
                text: "Sticky top"
            }]
        }
    }]
});

Vvveb.Components.extend("_base", "html/tablebody", {
    nodes: ["tbody"],
    name: "Table Body",
    html: "<tbody><tr><td>Cell 1</td><td>Cell 2</td><td>Cell 3</td></tr></tbody>"
});

Vvveb.Components.extend("_base", "html/gridcolumn", {
    name: "Grid Column",
    image: "icons/grid_column.svg",
    classesRegex: ["col-"],
    classes: ["col"],
    html: '<div class="sm:col-4"><h3>sm:col-4</h3></div>',
    properties: [{
        name: "Column",
        key: "column",
        inline:false,
        inputtype: GridInput,
        data: {hide_remove:true},

		beforeInit: function(node) {
			if (!node) return node;
			let _class = (typeof node.getAttribute === "function" ? node.getAttribute("class") : null) || "";
			
			//Bootstrap 6 uses breakpoint prefixes (sm:col-4) instead of infixes (col-sm-4)
			let reg = /(?:([a-z0-9]+):)?col-(\d+)?/g; 
			let match;

			//clear previous values, beforeInit runs on every selection
			for (const key in this.data) {
				if (key.indexOf("col") === 0) delete this.data[key];
			}

			while ((match = reg.exec(_class)) != null) {
				//keep GridInput data keys (col, col_sm, ..., col_xxl) so the editor template keeps working,
				//map the 2xl prefix back to xxl for the key name
				let prefix = match[1] ?? '';
				if (prefix == "2xl") prefix = "xxl";
				let key = "col" + (prefix ? "_" + prefix : "");
				this.data[key] = match[2] ?? '';
			}
		},
		
		onChange: function(node, value, input) {
			if (!node || !input || !input.name) return node;
			let _class = (typeof node.getAttribute === "function" ? node.getAttribute("class") : null) || "";
			
			//map GridInput select names (col, col-sm, ..., col-xxl) to Bootstrap 6 prefixes (col, sm:col, ..., 2xl:col)
			let prefixMap = {"col":"col", "col-sm":"sm:col", "col-md":"md:col", "col-lg":"lg:col", "col-xl":"xl:col", "col-xxl":"2xl:col", "col-2xl":"2xl:col"};
			let prefix = prefixMap[input.name] ?? input.name;
			//remove previous breakpoint column size
			_class = _class.replace(new RegExp(prefix.replace(":", "\\:") + '-\\d+?'), '');
			//also remove legacy v5 infix class if present
			_class = _class.replace(new RegExp(input.name + '-\\d+?'), '');
			//add new column size
			if (value) _class = (_class +  ' ' + prefix + '-' + value).trim();
			node.setAttribute("class", _class.replace(/\s+/, ' ').trim());
			
			return node;
		},				
	}]
});

Vvveb.Components.extend("_base", "html/gridrow", {
    name: "Grid Row",
    image: "icons/grid_row.svg",
    classes: ["row"],
    html: '<div class="row"><div class="sm:col-4"><h3>sm:col-4</h3></div><div class="sm:col-4 col-5"><h3>sm:col-4</h3></div><div class="sm:col-4"><h3>sm:col-4</h3></div></div>',
    children :[{
		name: "html/gridcolumn",
		classesRegex: ["col-"],
	}],
	beforeInit: function (node) {
		if (!node || typeof node.querySelectorAll !== "function") return node;
		let properties = [];
		let i = 0;
		
		node.querySelectorAll('[class*="col-"],.col').forEach(el => {
				let _class = el.getAttribute("class");
			
				//Bootstrap 6 uses breakpoint prefixes (sm:col-4), keep GridInput data keys (col, col_sm, ..., col_xxl)
				let reg = /(?:([a-z0-9]+):)?col-(\d+)?/g; 
				let match;
				let data = {};

				while ((match = reg.exec(_class)) != null) {
					let prefix = match[1] ?? '';
					if (prefix == "2xl") prefix = "xxl";
					let key = "col" + (prefix ? "_" + prefix : "");
					data[key] = match[2] ?? '';
				}
			
			i++;
			properties.push({
				name: "Column " + i,
				key: "column" + i,
				//index: i - 1,
				columnNode: el,
				col:12,
				inline:false,
				inputtype: GridInput,
				data: data,
				onChange: function(node, value, input) {

					let column = this.columnNode;
					if (!column || !node || !input) return node;
					
					//if remove button is clicked remove column and render row properties
					if (input.nodeName == 'BUTTON') {
						column.remove();
						Vvveb.Components.render("html/gridrow");
						return node;
					}

					//if select input then change column class
					let _class = (typeof column.getAttribute === "function" ? column.getAttribute("class") : null) || "";
					
					//map GridInput select names to Bootstrap 6 prefixes
					let prefixMap = {"col":"col", "col-sm":"sm:col", "col-md":"md:col", "col-lg":"lg:col", "col-xl":"xl:col", "col-xxl":"2xl:col", "col-2xl":"2xl:col"};
					let prefix = prefixMap[input.name] ?? input.name;
					//remove previous breakpoint column size (v6 prefix and legacy v5 infix)
					_class = _class.replace(new RegExp(prefix.replace(":", "\\:") + '-\\d+?'), '');
					_class = _class.replace(new RegExp(input.name + '-\\d+?'), '');
					//add new column size
					if (value) _class = (_class +  ' ' + prefix + '-' + value).trim();
					column.setAttribute("class", _class.replace(/\s+/, ' ').trim());
					
					return node;
				},	
			});
		});
		
		//remove all column properties
		this.properties = this.properties.filter(function(item) {
			return item.key.indexOf("column") === -1;
		});

		//add remaining properties to generated column properties, put first 2 align properties first
		this.properties = this.properties.slice(0,4).concat(properties, this.properties.slice(4));

		return node;
	},
    
    properties: [{
        name: "Direction",
        key: "direction",
        htmlAttr: "class",
        inline:false,
        validValues: ["", "flex-row", "flex-row-reverse", "flex-column", "flex-column-reverse"],
        inputtype: RadioButtonInput,
        data: {
			extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "",
                icon:"x",
                //text: "None",
                title: "Default",
                checked:true,
            },{
                value: "flex-row",
                //text: "Left",
                title: "Row - horizontal",
                icon:"arrow-right",
                checked:false,
            },{
                value: "flex-column",
                //text: "Center",
                title: "Column - vertical",
                icon:"arrow-down",
                checked:false,
            },{
                value: "flex-row-reverse",
                //text: "Right",
                title: "Row - reversed",
                icon:"arrow-left",
                checked:false,
            },{
                value: "flex-column-reverse",
                //text: "Center",
                title: "Column - reversed",
                icon:"arrow-up",
                checked:false,
            }],
        },
	},{       
		name: "Vertical align",
        key: "vertical-align",
        htmlAttr: "class",
        inline:false,
        validValues: ["", "align-items-start", "align-items-center", "align-items-end", "align-items-baseline", "align-items-stretch"],
        inputtype: RadioButtonInput,
        data: {
            extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "",
                icon:"x",
                //text: "None",
                title: "None",
                checked:true,
            },{
                value: "align-items-start",
                //text: "Left",
                title: "Start",
                icon:"align-left",
                checked:false,
            },{
                value: "align-items-center",
                //text: "Center",
                title: "Center",
                icon:"align-center",
                checked:false,
            },{
                value: "align-items-end",
                //text: "Right",
                title: "End",
                icon:"align-right",
                checked:false,
            },{
                value: "align-items-baseline",
                title: "Baseline",
                icon:"indent-increase",
                checked:false,
            },{
                value: "align-items-stretch",
                title: "Stretch",
                icon:"align-justified",
                checked:false,
            }],
        },
	},{
        name: "Horizontal align",
        key: "horizontal-align",
        htmlAttr: "class",
        inline:false,
        validValues: ["", "justify-content-start", "justify-content-center", "justify-content-end", "justify-content-around", "justify-content-between", "justify-content-evenly"],
        inputtype: RadioButtonInput,
        data: {
			extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "",
                icon:"x",
                //text: "None",
                title: "None",
                checked:true,
            },{
                value: "justify-content-start",
                //text: "Left",
                title: "Start",
                icon:"align-left",
                checked:false,
            },{
                value: "justify-content-center",
                //text: "Center",
                title: "Center",
                icon:"align-center",
                checked:false,
            },{
                value: "justify-content-end",
                //text: "Right",
                title: "End",
                icon:"align-right",
                checked:false
			},{
                value: "justify-content-around",
                //text: "Left",
                title: "Around",
                icon:"indent-increase",
                checked:false,
            },{
                value: "justify-content-between",
                //text: "Center",
                title: "Between",
                icon:"indent-decrease",
                checked:false,
            },{
                value: "justify-content-evenly",
                //text: "Right",
                title: "Evenly",
                icon:"align-justified",
                checked:false,
            }],
        },
	},{       
        name: "Wrap",
        key: "wrap",
        htmlAttr: "class",
        inline:false,
        validValues: ["", "flex-wrap", "flex-nowrap"],
        inputtype: RadioButtonInput,
        data: {
            extraclass:"btn-group-sm btn-group-fullwidth",
            options: [{
                value: "",
                icon:"x",
                //text: "None",
                title: "None",
                checked:true,
            },{
                value: "flex-wrap",
                //text: "Left",
                title: "Wrap",
                icon:"arrow-back-up",
                checked:false,
            },{
                value: "flex-nowrap",
                //text: "Center",
                title: "No wrap",
                icon:"arrow-right",
                checked:false,
            }],
        },
	},{
		name: "Column",
		key: "column1",
		inputtype: GridInput
	},{
		name: "Column",
		key: "column1",
		inline:true,
		col:12,
		inputtype: GridInput
	},{
		name: "",
		key: "addChild",
		inline:true,
		inputtype: ButtonInput,
		data: {text:"Add column", icon:"plus", className:"btn-secondary"},
		onChange: function(node)
		{
			 if (!node || typeof node.append !== "function") return node;
			 node.append(generateElements('<div class="col-3"><h3>Col-3</h3></div>')[0]);
			 
			 //render component properties again to include the new column inputs
			 Vvveb.Components.render("html/gridrow");
			 
			 return node;
		}
	}]
});

