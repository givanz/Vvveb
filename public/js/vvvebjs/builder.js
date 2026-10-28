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

var $ = document.querySelector.bind(document);
var $$ = document.querySelectorAll.bind(document);

// Simple JavaScript Templating and buildParams
// John Resig - https://johnresig.com/ - MIT Licensed
(function () {
	let cache = {};
	let startTag = "{%";
	let endTag = "%}";
	let re1 = new RegExp(`((^|${endTag})[^\t]*)'`, "g");
	let re2 = new RegExp(`\t=(.*?)${endTag}`, "g");

	this.tmpl = function tmpl(str, data) {
		// Figure out if we're getting a template, or if we need to
		// load the template - and be sure to cache the result.
		let fn = /^[-a-zA-Z0-9]+$/.test(str) ?
			cache[str] = cache[str] ||
			tmpl(document.getElementById(str).innerHTML) :

			// Generate a reusable function that will serve as a template
			// generator (and which will be cached).
			new Function("obj",
				"let p=[],print=function(){p.push.apply(p,arguments);};" +

				// Introduce the data as local variables using with(){}
				"with(obj){p.push('" +

				// Convert the template into pure JavaScript
				str
					.replace(/[\r\t\n]/g, " ")
					.split(startTag).join("\t")
					.replace(re1, "$1\r")
					.replace(re2, "',$1,'")
					.split("\t").join("');")
					.split(endTag).join("p.push('")
					.split("\r").join("\\'")
				+ "');}return p.join('');");
		// Provide some basic currying to the user
		return data ? fn(data) : fn;
	};
})();


function buildParams(prefix, obj, add) {
	let rbracket = /\[\]$/;

	if (Array.isArray(obj)) {

		// Serialize array item.
		for (const key in obj) {
			let v = obj[key];
			if (rbracket.test(prefix)) {

				// Treat each array item as a scalar.
				add(prefix, v);

			} else {

				// Item is non-scalar (array or object), encode its numeric index.
				buildParams(
					prefix + "[" + (typeof v === "object" && v != null ? key : "") + "]",
					v,
					add
				);
			}
		}

	} else if (typeof obj === "object") {

		// Serialize object item.
		for (const name in obj) {
			buildParams(prefix + "[" + name + "]", obj[name], add);
		}

	} else {

		// Serialize scalar item.
		add(prefix, obj);
	}
}

// Serialize an array of form elements or a set of
// key/values into a query string
function nestedFormData(a) {
	let prefix,
		s = [],
		add = function (key, valueOrFunction) {

			// If value is a function, invoke it and use its return value
			let value = typeof valueOrFunction === "function" ?
				valueOrFunction() :
				valueOrFunction;

			s[s.length] = encodeURIComponent(key) + "=" +
				encodeURIComponent(value == null ? "" : value);
		};

	if (a == null) {
		return "";
	}

	// If an array was passed in, assume that it is an array of form elements.
	if (Array.isArray(a)) {

		// Serialize the form elements
		a.forEach((v, key) => {
			if (v && typeof v === "object" && "name" in v) {
				add(v.name, v.value);
			} else {
				add(key, v);
			}
		});

	} else {

		// If traditional, encode the "old" way (the way 1.3.2 or older
		// did it), otherwise encode params recursively.
		for (const prefix in a) {
			buildParams(prefix, a[prefix], add);
		}
	}

	// Return the resulting serialization
	return s.join("&");
};


let delay = (function () {
	let timer = 0;
	return function (callback, ms) {
		clearTimeout(timer);
		timer = setTimeout(callback, ms);
	};
})();

function isElement(obj) {
	return (typeof obj === "object") &&
		(obj.nodeType === 1) && (typeof obj.style === "object") &&
		(typeof obj.ownerDocument === "object")/* && obj.tagName != "BODY"*/;
}

function generateElements(html) {
	const template = document.createElement('template');
	template.innerHTML = html.trim();
	return template.content.children;
}

function offset(el) {
	const box = el.getBoundingClientRect();
	const docElem = document.documentElement;
	return {
		top: box.top + window.pageYOffset - docElem.clientTop,
		left: box.left + window.pageXOffset - docElem.clientLeft
	};
}

if (typeof Vvveb === 'undefined') var Vvveb = {};

Vvveb.defaultComponent = "_base";
Vvveb.preservePropertySections = true;
//icon = use component icon when dragging | html = use component html to create draggable element
Vvveb.dragIcon = 'icon';
//if empty the html of the component is used to view dropping in real time but for large elements it can jump around for this you can set a html placeholder with this option
Vvveb.dragElementStyle = "background:limegreen;width:100%;height:3px;border:1px solid limegreen;box-shadow:0px 0px 2px 1px rgba(0,0,0,0.14);overflow:hidden;";
Vvveb.dragHtml = '<div style="' + Vvveb.dragElementStyle + '"></div>';

Vvveb.baseUrl = document.currentScript ? document.currentScript.src.replace(/[^\/]*?\.js$/, '') : '';
Vvveb.imgBaseUrl = Vvveb.baseUrl;

Vvveb.ComponentsGroup = {};
Vvveb.SectionsGroup = {};
Vvveb.BlocksGroup = {};
Vvveb.StylesGroup = {};

const NO_CHILDREN_TAGS = {
	input: true,
	textarea: true,
	img: true,
	svg: true,
	iframe: true,
	embed: true,
	col: true,
	area: true,
	hr: true,
	br: true,
	wbr: true,
};

Vvveb.Components = {

	_components: {},

	_nodesLookup: {},

	_attributesLookup: {},

	_classesLookup: {},

	_classesRegexLookup: {},

	_classesRegexCompiled: [],

	componentPropertiesElement: "#right-panel .component-properties",

	componentPropertiesDefaultSection: "content",

	get: function (type) {
		return this._components[type];
	},

	updateProperty: function (type, key, value) {
		let property = this._components[type] ? this._findProperty(this._components[type]["properties"], key) : null;
		if (property) {
			return Object.assign(property, value);
		}
	},

	getProperty: function (type, key) {
		let properties = this._components[type] ? this._components[type]["properties"] : [];
		return this._findProperty(properties, key);
	},

	_findProperty: function (properties, key) {
		for (let property of properties) {
			if (key == property["key"]) {
				return property;
			}
			if (property.properties) {
				let found = this._findProperty(property.properties, key);
				if (found) return found;
			}
		}
	},

	_applyConditionVisibility: function (component, key, value) {
		let conditions = component.conditions && component.conditions[key];
		if (!conditions) return;
		//the changed property definition, passed through to predicate rules
		let property = this._findProperty(component.properties || [], key);
		for (const condition in conditions) {
			let rule = conditions[condition];
			let show;

			if (typeof rule  == "function") {
				//predicate rule decides visibility itself
				show = !!rule.call(component, value, property);
			} else {
				show = (rule == value);
			}

			let el = null;
			try {
				el = document.querySelector('[data-key="' + condition + '"]');
			} catch (err) {}
			if (el) {
				if (show) {
					el.style.display = "";
				} else {
					el.style.display = "none";
				}
			}
		}
	},

	_applyAllConditions: function (component) {
		if (!component || !component.conditions || !component.values) return;
		for (const key in component.conditions) {
			this._applyConditionVisibility(component, key, component.values[key]);
		}
	},

	add: function (type, data) {
		data.type = type;

		this._components[type] = data;

		if (data.nodes) {
			for (const node of data.nodes) {
				this._nodesLookup[node] = data;
			}
		}

		if (data.attributes) {
			if (Array.isArray(data.attributes)) {
				for (const attr of data.attributes) {
					this._attributesLookup[attr] = data;
				}
			} else {
				for (const i in data.attributes) {
					if (typeof this._attributesLookup[i] === 'undefined') {
						this._attributesLookup[i] = {};
					}

					if (typeof this._attributesLookup[i][data.attributes[i]] === 'undefined') {
						this._attributesLookup[i][data.attributes[i]] = {};
					}

					this._attributesLookup[i][data.attributes[i]] = data;
				}
			}
		}

		if (data.classes) {
			for (const cls of data.classes) {
				this._classesLookup[cls] = data;
			}
		}

		if (data.classesRegex) {
			for (const cls of data.classesRegex) {
				this._classesRegexLookup[cls] = data;
				//precompile the regex once so matchNode() doesn't rebuild it for every node
				try {
					this._classesRegexCompiled.push({ regex: new RegExp(cls), component: data });
				} catch (e) {
					console.warn("Vvveb.Components.add: invalid classesRegex skipped:", cls);
				}
			}
		}
	},

	extend: function (inheritType, type, data) {

		let newData = data;
		let inheritData = this._components[inheritType];

		if (inheritData) {
			newData = { ...inheritData, ...data };
			newData.properties = (data.properties ? data.properties : []).concat(inheritData.properties ? inheritData.properties : []);
		}

		//remove duplicates
		/*
		newData.properties = newData.properties.filter((value, index, self) =>
		  index === self.findIndex((t) => (
			t.key === value.key
		  ))
		);*/

		//sort by order
		newData.properties.sort(function (a, b) {
			if (typeof a.sort === "undefined") a.sort = 0;
			if (typeof b.sort === "undefined") b.sort = 0;

			if (a.sort < b.sort)
				return -1;
			if (a.sort > b.sort)
				return 1;
			return 0;
		});

		this.add(type, newData);
	},


	matchNode: function (node) {
		if (!node || !node.tagName) return false;

		if (node.attributes && node.attributes.length) {
			//search for attributes
			for (let i = 0; i < node.attributes.length; i++) {
				const attr = node.attributes[i].name;
				const value = node.attributes[i].value;

				if (attr in this._attributesLookup) {
					let component = this._attributesLookup[attr];

					//currently we check that is not a component by looking at name attribute
					//if we have a collection of objects it means that attribute value must be checked
					if (typeof component["name"] === "undefined") {
						if (value in component) {
							return component[value];
						}
					} else {
						return component;
					}
				}
			}

			for (let i = 0; i < node.attributes.length; i++) {
				const attr = node.attributes[i].name;
				const value = node.attributes[i].value;

				//check for node classes
				if (attr == "class") {
					const classes = value.split(" ");

					for (const cls of classes) {
						if (cls in this._classesLookup)
							return this._classesLookup[cls];
					}

				//regexes are precompiled at registration time (see add())
					if (this._classesRegexCompiled) {
						for (const compiled of this._classesRegexCompiled) {
							if (compiled.regex.exec(value)) {
								return compiled.component;
							}
						}
					}
				}
			}
		}

		const tagName = node.tagName.toLowerCase();
		if (tagName in this._nodesLookup) return this._nodesLookup[tagName];

		return false;
	},

	renderProperties: function (component, properties, componentsPanelSections, container) {
		//component definitions are shared singletons: reset per-render state
		//so conditions/values from a previous selection cannot leak into this one
		if (component) {
			component.conditions = {};
			component.values = {};
		}
		let rendered = this._renderProperties(component, properties, componentsPanelSections, container, null, []);
		//reflect initial values so dependents of already-set controllers start hidden/shown correctly
		this._applyAllConditions(rendered);
		return rendered;
	},

	_renderProperties: function (component, properties, componentsPanelSections, container, baseNode, ancestors) {
		if (!component) component = { conditions: {}, values: {} };
		if (!component.conditions) component.conditions = {};
		if (!component.values) component.values = {};
		let fn = function (component, property, ancestors) {
			if (property.input) {
				property.input.addEventListener('propertyChange', (event) => {
					let selectedElement = Vvveb.Builder.selectedEl;
					let element = selectedElement;
					let value = event.detail.value, input = event.detail.input, origEvent = event.detail.origEvent;

					if (ancestors) {
						for (const ancestor of ancestors) {
							if (ancestor.element) element = ancestor.element;
							if (ancestor.child) element = element.querySelector(ancestor.child) ?? element;
							if (ancestor.parent) element = element.closest(ancestor.parent) ?? element;
						}
					}
					if (property.element) element = property.element;
					if (property.child) element = element.querySelector(property.child) ?? element;
					if (property.parent) element = element.closest(property.parent) ?? element;

					if (property.onChange) {
						let ret = property.onChange(element, value, input, component, origEvent);
						//if on change returns an object then is returning the dom node otherwise is returning the new value
						if (typeof ret == "object") {
							element = ret;
						} else {
							value = ret;
						}
					}/* else */
					if (property.htmlAttr) {
						let oldValue, oldStyle;

						if (property.htmlAttr == "class" && property.validValues) {
							oldValue = element.getAttribute(property.htmlAttr);

							if (property.validValues) {
								element.classList.remove(...property.validValues.filter(v => v));
							}
							if (value) {
								element.classList.add(...value.split(" "));
							}
						}
						else if (property.htmlAttr == "style") {
							//keep old style for undo
							oldStyle = window.FrameDocument.getElementById("vvvebjs-styles").textContent;
							element = Vvveb.StyleManager.setStyle(element, property.key, value);
						} else if (property.htmlAttr == "innerHTML") {
							oldValue = element.innerHTML;
							element = Vvveb.ContentManager.setHtml(element, value);
						} else if (property.htmlAttr == "outerHTML") {
							oldValue = element.outerHTML;
							element = Vvveb.ContentManager.setHtml(element, value, true);
						} else if (property.htmlAttr == "innerText") {
							oldValue = element.innerText;
							element = Vvveb.ContentManager.setText(element, value);
						} else {
							oldValue = element.getAttribute(property.htmlAttr);
							//if value is empty then remove attribute useful for attributes without values like disabled
							if (value) {
								element.setAttribute(property.htmlAttr, value);
							} else {
								element.removeAttribute(property.htmlAttr);
							}
						}

					if (property.htmlAttr == "style") {
						const mutation = {
								type: 'style',
								target: element,
								attributeName: property.htmlAttr,
								oldValue: oldStyle,
								newValue: window.FrameDocument.getElementById("vvvebjs-styles").textContent
							};

							Vvveb.Undo.addMutation(mutation);
						} else {
							Vvveb.Undo.addMutation({
								type: 'attributes',
								target: element,
								attributeName: property.htmlAttr,
								oldValue: oldValue,
								newValue: value
							});
						}
					}

					if (component && component.onChange) {
						element = component.onChange(element, property, value, input);
					}
					
					if (property.child || property.parent) {
						Vvveb.Builder.selectNode(selectedElement);
					} else {
						Vvveb.Builder.selectNode(element);
					}
					
					component.values[property.key] = value;
					Vvveb.Components._applyConditionVisibility(component, property.key, value);
					
					return element;
				});
			}

			return property.input;
		};

		let element;
		let defaultSection = this.componentPropertiesDefaultSection;
		let section;
		if (componentsPanelSections) {
			section = componentsPanelSections[defaultSection].querySelector('.section[data-section="default"]');
		}
		let nodeElement = baseNode || Vvveb.Builder.selectedEl;
		for (let property of properties) {
			if (property.conditions) {
				for (const cname in property.conditions) {
					if (!component.conditions[cname]) component.conditions[cname] = {};
					component.conditions[cname][property.key] = property.conditions[cname];
				}
			}
			
			let element = nodeElement;

			if (property.beforeInit) property.beforeInit(element);

			if (property.element) element = property.element;
			if (property.child) element = element.querySelector(property.child) ?? element;
			if (property.parent) element = element.closest(property.parent) ?? element;

			if (property.data) {
				property.data["key"] = property.key;
			} else {
				property.data = { "key": property.key };
			}

			if (typeof property.group === 'undefined') property.group = null;

			property.input = property.inputtype.init(property.data, element);

			let value;
			if (property.init) {
				property.inputtype.setValue(property.init(element));
			} else if (property.htmlAttr) {
				if (property.htmlAttr == "style") {
					//value = element.css(property.key);//jquery css returns computed style
					value = Vvveb.StyleManager.getStyle(element, property.key);//getStyle returns declared style
				} else
					if (property.htmlAttr == "innerHTML") {
						value = Vvveb.ContentManager.getHtml(element);
					} else if (property.htmlAttr == "outerHTML") {
						value = Vvveb.ContentManager.getHtml(element, true);
					} else if (property.htmlAttr == "innerText") {
						value = Vvveb.ContentManager.getText(element);
					} else {
						value = element.getAttribute(property.htmlAttr);
					}

				//if attribute is class check if one of valid values is included as class to set the select
				if (value && property.htmlAttr == "class" && property.validValues) {
					let valid = value.split(" ").filter(function (el) {
						return property.validValues.indexOf(el) != -1
					});

					if (valid && valid.length) {
						value = valid[0];
					} else {
						value = "";
					}
				}

				if (!value && property.defaultValue) {
					value = property.defaultValue;
				}

				property.inputtype.setValue(value);
			} else {
				if (!value && property.defaultValue) {
					value = property.defaultValue;
				}

				property.inputtype.setValue(value);
			}
			
			if (value !== null && value !== undefined && value !== "") {
				component.values[property.key] = value;
			}
			fn(component, property, ancestors);

			let propertySection = defaultSection;
			if (property.section) {
				propertySection = property.section;
			}

			let row = null;

			if (property.inputtype == SectionInput) {
				section = componentsPanelSections[propertySection].querySelector('.section[data-section="' + property.key + '"]');

				if (Vvveb.preservePropertySections && section) {
					section.replaceChildren();
				} else {
					componentsPanelSections[propertySection].append(property.input);
					section = componentsPanelSections[propertySection].querySelector('.section[data-section="' + property.key + '"]');
				}
			}
			else {
				row = generateElements(tmpl('vvveb-property', property))[0];
				row.querySelector('.input').append(property.input);
				if (container) {
					container.append(row);
				} else {
					section.append(row);
				}
			}

			if (property.inputtype.afterInit) {
				property.inputtype.afterInit(property.input);
			}

			if (property.afterInit) {
				property.afterInit(element, property.input);
			}

			//nested properties render inside the parent and resolve from its element
			if (property.properties && property.properties.length) {
				let subContainer = null;
				if (property.inputtype == SectionInput) {
					subContainer = section;
				} else if (row) {
					subContainer = generateElements('<div class="sub-properties"></div>')[0];
					if (subContainer) {
						row.append(subContainer);
					} else {
						subContainer = row;
					}
				}
				if (subContainer) {
					this._renderProperties(component, property.properties, componentsPanelSections, subContainer, element, (ancestors || []).concat([property]));
				}
			}
		}
		return component;
	},

	render: function (type, panel = false) {

		let component = this._components[type];
		if (!component) return;

		if (!panel) {
			//panel = document.querySelector(this.componentPropertiesElement);
			panel = this.componentPropertiesElement;
		}

		let defaultSection = this.componentPropertiesDefaultSection;
		let componentsPanelSections = {};

		document.querySelectorAll(panel + " .tab-pane").forEach((el, i) => {
			let sectionName = el.dataset.section;
			componentsPanelSections[sectionName] = el;
			for (const item of el.querySelectorAll(
				'label:not([data-header="default"]) + input,' +
				'label:not([data-header="default"]),' +
				'.section:not([data-section="default"])'
			)) {
				item.remove();
			}
		});

		let section = componentsPanelSections[defaultSection].querySelector('.section[data-section="default"]');

		if (!(Vvveb.preservePropertySections && section)) {
			let template = tmpl("vvveb-input-sectioninput", { key: "default", header: component.name });

			componentsPanelSections[defaultSection].replaceChildren();
			componentsPanelSections[defaultSection].append(generateElements(template)[0]);

			section = componentsPanelSections[defaultSection].querySelector(".section");
		}

		componentsPanelSections[defaultSection].querySelector('[data-header="default"] span').innerHTML = component.name;
		section.replaceChildren();

		if (component.beforeInit) component.beforeInit(Vvveb.Builder.selectedEl);

		this.renderProperties(component, component.properties, componentsPanelSections);

		if (component.init) component.init(Vvveb.Builder.selectedEl);
	}
};


Vvveb.Blocks = {

	_blocks: {},

	get: function (type) {
		return this._blocks[type];
	},

	add: function (type, data) {
		data.type = type;
		this._blocks[type] = data;
	},
};

Vvveb.Sections = {

	_sections: {},

	get: function (type) {
		return this._sections[type];
	},

	add: function (type, data) {
		data.type = type;
		this._sections[type] = data;
	},
};

Vvveb.Styles = {

	_styles: {},

	get: function (type) {
		return this._styles[type];
	},

	getByStyle: function (style) {
		for (const name in this._styles) {
			if (this._styles[name].style == style) {
				return name;
			}
		}
	},

	add: function (type, data) {
		data.type = type;
		this._styles[type] = data;
	},
};


Vvveb.WysiwygEditor = {

	isActive: false,
	oldValue: '',
	doc: false,
	element: null,
	_initToolbar: false,


	editorSetStyle: function (tag, style = {}, toggle = false) {
		let iframe = Vvveb.Builder.iframe;
		let iframeWindow = iframe && iframe.contentWindow;
		if (!iframeWindow || typeof iframeWindow.getSelection !== "function") return this.element;
		let iframeDoc = iframeWindow.document;
		let selection = iframeWindow.getSelection();
		let element = this.element;
		let range;

		if (!tag) {
			tag = "span";
		}

		if (selection && selection.rangeCount > 0) {
			//standard Selection props (baseNode/extentNode only exist in old WebKit, missing in Firefox)
			let anchorNode = selection.anchorNode || selection.baseNode;
			let focusNode = selection.focusNode || selection.extentNode;
			let collapsed = selection.isCollapsed || (anchorNode && anchorNode === focusNode && selection.anchorOffset == selection.focusOffset);

			if (anchorNode && (collapsed ||
				(anchorNode === focusNode && anchorNode.nextSibling == null && anchorNode.previousSibling == null
					&& selection.anchorOffset == 0 && selection.focusOffset == anchorNode.length))) {

				element = anchorNode.parentNode;

			} else {
				range = selection.getRangeAt(0);
				//create in the iframe document, a builder-document node throws WrongDocumentError on insert
				element = iframeDoc.createElement(tag);

				try {
					range.surroundContents(element);
					if (element.childNodes[0]) {
						range.selectNodeContents(element.childNodes[0]);
					} else {
						range.selectNodeContents(element);
					}

				} catch (e) {
					let content = range.extractContents();

					element.appendChild(content);
					range.insertNode(element);

					range.selectNodeContents(element);
				}
			}
		}

		if (element && element.style && style) {
			for (let name in style) {

				if (!style[name] ||
					(toggle && element.style.getPropertyValue(name))) {

					element.style.removeProperty(name);

				} else {
					element.style.setProperty(name, style[name]);
				}
			}
		}

		//if edited text is an empty span remove the span
		if (element && element.tagName == "SPAN" && element.style && element.style.length == 0 && element.attributes && element.attributes.length <= 1) {
			let textNode = iframeDoc.createTextNode(element.textContent);
			if (typeof element.replaceWith === "function") {
				element.replaceWith(textNode);
			} else if (element.parentNode) {
				element.parentNode.replaceChild(textNode, element);
			}
			element = textNode;

			try {
				range = iframeDoc.createRange();
				range.selectNodeContents(element);
				selection.removeAllRanges();
				selection.addRange(range);
			} catch (e) {}
		}

		//select link element to edit link etc
		if (tag == "a" && element && element.tagName) {
			Vvveb.Builder.selectNode(element);
			Vvveb.Builder.loadNodeComponent(element);
		}
		return element;
	},

	init: function (doc) {
		this.doc = doc;
		let self = this;

		//init runs on every iframe load, but the toolbar lives in the builder
		//document: bind it once or every click fires twice (bold on+off = no-op)
		if (!this._initToolbar) {
			this._initToolbar = true;

			let on = function (id, evt, fn) {
				let btn = document.getElementById(id);
				if (btn) btn.addEventListener(evt, fn);
			};

		on("bold-btn", "click", function (e) {
			//doc.execCommand('bold',false,null);
			//self.editorSetStyle("b", {"font-weight" : "bold"}, true);
			self.editorSetStyle(false, { "font-weight": "bold" }, true);
			e.preventDefault();
			return false;
		});

		on("italic-btn", "click", function (e) {
			//doc.execCommand('italic',false,null);
			//self.editorSetStyle("i", {"font-style" : "italic"}, true);
			self.editorSetStyle(false, { "font-style": "italic" }, true);
			e.preventDefault();
			return false;
		});

		on("underline-btn", "click", function (e) {
			//doc.execCommand('underline',false,null);
			//self.editorSetStyle("u", {"text-decoration" : "underline"}, true);
			self.editorSetStyle(false, { "text-decoration": "underline" }, true);
			e.preventDefault();
			return false;
		});

		on("strike-btn", "click", function (e) {
			//doc.execCommand('strikeThrough',false,null);
			//self.editorSetStyle("strike",  {"text-decoration" : "line-through"}, true);
			self.editorSetStyle(false, { "text-decoration": "line-through" }, true);
			e.preventDefault();
			return false;
		});

		on("link-btn", "click", function (e) {
			//doc.execCommand('createLink',false,"#");
			self.editorSetStyle("a");
			e.preventDefault();
			return false;
		});

		on("fore-color", "change", function (e) {
			//doc.execCommand('foreColor',false,this.value);
			self.editorSetStyle(false, { "color": this.value });
			e.preventDefault();
			return false;
		});


		on("back-color", "change", function (e) {
			//doc.execCommand('hiliteColor',false,this.value);
			self.editorSetStyle(false, { "background-color": this.value });
			e.preventDefault();
			return false;
		});

		on("font-size", "change", function (e) {
			//doc.execCommand('fontSize',false,this.value);
			self.editorSetStyle(false, { "font-size": this.value });
			e.preventDefault();
			return false;
		});

		let fontSize = document.getElementById("font-size");
		if (fontSize) {
			let sizes = "<option value=''> - Font size - </option>";
			for (let i = 1; i <= 128; i++) {
				sizes += "<option value='" + i + "px'>" + i + "</option>";
			}
			fontSize.innerHTML = sizes;
		}

		on("font-family", "change", function (e) {
			let select = e.currentTarget || this;
			if (!select.value) {
				e.preventDefault();
				return false;
			}
			let option = select.options && select.options[select.selectedIndex];
			let element = self.editorSetStyle(false, { "font-family": select.value });
			if (option && option.dataset) Vvveb.FontsManager.addFont(option.dataset.provider, select.value, element);
			//doc.execCommand('fontName',false,this.value);
			e.preventDefault();
			return false;
		});

		on("justify-btn", "click", function (e) {
			//let command = "justify" + this.dataset.value;
			//doc.execCommand(command,false,"#");

			//e.target may be a child icon inside the button, read the button itself
			let btn = (e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.value !== undefined) ? e.currentTarget : (e.target.closest ? e.target.closest("#justify-btn") : null);
			let value = btn && btn.dataset ? btn.dataset.value : null;
			if (!value) {
				e.preventDefault();
				return false;
			}
			self.editorSetStyle(false, { "text-align": value });
			e.preventDefault();
			return false;
		});
		}//end toolbar once-block

		if (doc && typeof doc.addEventListener === "function" && !doc._vvvebWysiwygKeys) {
			doc._vvvebWysiwygKeys = true;
			doc.addEventListener('keydown', event => {
				if (event.key === 'Enter') {
					let target = event.target && event.target.closest ? event.target.closest("[contenteditable]") : null;
					if (target) {
						doc.execCommand('insertLineBreak');
						event.preventDefault();
					}
				}
			})
		}
	},

	undo: function (element) {
		if (this.doc && typeof this.doc.execCommand === "function") this.doc.execCommand('undo', false, null);
	},

	redo: function (element) {
		if (this.doc && typeof this.doc.execCommand === "function") this.doc.execCommand('redo', false, null);
	},

	edit: function (element) {
		if (!element || typeof element.setAttribute !== "function") return;
		//finish any previous session so only one element stays editable
		if (this.isActive && this.element && this.element !== element) {
			this.destroy();
		}
		element.setAttribute("contenteditable", true);
		element.setAttribute("spellcheck", false);
		let toolbar = document.getElementById("wysiwyg-editor");
		if (toolbar && toolbar.style) toolbar.style.display = "block";

		this.element = element;
		this.isActive = true;
		this.oldValue = element.innerHTML;

		let setVal = function (id, value) {
			let node = document.getElementById(id);
			if (node) node.value = value ?? "";
		};
		setVal("font-family", Vvveb.StyleManager.getStyle(element, 'font-family'));
		setVal("fore-color", Vvveb.StyleManager.getStyle(element, 'color'));
		setVal("back-color", Vvveb.StyleManager.getStyle(element, 'background-color'));
		if (typeof element.focus === "function") {
			try { element.focus(); } catch (e) {}
		}
	},

	destroy: function (element) {
		element = element || this.element;
		let toolbar = document.getElementById("wysiwyg-editor");
		if (toolbar && toolbar.style) toolbar.style.display = "none";
		this.isActive = false;
		if (this.element === element) this.element = null;
		if (!element || typeof element.removeAttribute !== "function") return;
		element.removeAttribute("contenteditable");
		element.removeAttribute("spellcheck");

		let newValue = "";
		try { newValue = element.innerHTML; } catch (e) {}
		//don't pollute the undo stack (and "unsaved changes" prompt) when nothing changed
		if (newValue === this.oldValue) return;
		Vvveb.Undo.addMutation({
			type: 'characterData',
			target: element,
			oldValue: this.oldValue,
			newValue: newValue
		});
	}
}

Vvveb.Builder = {

	component: {},
	iframe: null,
	dragMoveMutation: false,
	isPreview: false,
	runJsOnSetHtml: false,
	designerMode: false,
	highlightEnabled: false,
	selectPadding: 0,
	leftPanelWidth: 275,
	ignoreClasses: ["clearfix", "masonry", "has-shadow"],

	_chrome: {},

	_$: function (id) {
		return this._chrome[id] ?? (this._chrome[id] = document.getElementById(id));
	},

	_setDisplay: function (id, value) {
		let el = this._$(id);
		if (el) el.style.display = value;
	},

	_showChrome: function () {
		this._setDisplay("section-actions", "");
		this._setDisplay("highlight-name", "");
		this._setDisplay("select-box", "");
	},

	_hideChrome: function () {
		this._setDisplay("section-actions", "none");
		this._setDisplay("highlight-name", "none");
		this._setDisplay("select-box", "none");
	},

	_componentHtml: function (component) {
		if (!component || !component.html) return "";
		//replace every placeholder so dropping the same component twice can't duplicate ids
		return component.html.replaceAll('RANDOM_ID', Math.floor(Math.random() * 100000));
	},

	init: function (url, callback) {

		let self = this;

		self.loadControlGroups();
		self.loadBlockGroups();
		self.loadSectionGroups();
		self.loadStylesGroups();

		self.selectedEl = null;
		self.highlightEl = null;
		self.initCallback = callback;

		self.documentFrame = document.querySelector("#iframe-wrapper > iframe");
		self.canvas = document.getElementById("canvas");

		if (!url) {
			url = 'about:blank';
		}

		self._loadIframe(url + (url.indexOf('?') > -1 ? '&r=' : '?r=') + Math.random());

		self._initDragdrop();

		self._initBox();

		self.dragElement = null;

		self.highlightEnabled = true;

		self.leftPanelWidth = document.getElementById("left-panel").clientWidth;
	},

	/* controls */

	_loadGroupList: function (listSelector, groups, registry, headSuffix, dragType = "", isComponent = false) {
		let lists = document.querySelectorAll(listSelector);
		let count = 0;

		lists.forEach(function (list) {
			let type = list.dataset.type;
			list.replaceChildren();
			count++;

			for (let group in groups) {
				let headerItem = generateElements(`
					<li class="header" data-section="${group}"  data-search="">
					<label class="header" for="${type}_${headSuffix}_${group}${headSuffix == 'comphead' ? count : ''}">
						${group}<div class="header-arrow"></div>
					</label>
					<input class="header_check" type="checkbox" checked="true" id="${type}_${headSuffix}_${group}${headSuffix == 'comphead' ? count : ''}">
					<ol></ol>
					</li>`)[0];

				list.append(headerItem);

				//use the created element directly instead of re-querying by a selector
				//string that breaks when the group name contains quotes or special chars
				let subList = headerItem.querySelector('ol');
				const items = groups[group];

				for (const itemType of items) {
					const item = registry.get(itemType);

					if (!item) continue;

					let dragAttr = dragType ? ` data-drag-type="${dragType}"` : '';
					let li;

					if (isComponent) {
						li = generateElements(`<li data-section="${group}"${dragAttr} data-type="${itemType}" data-search="${item.name.toLowerCase()}">
							<span>${item.name}</span>
						</li>`)[0];

						if (item.image) {
							li.style.backgroundImage = "url(" + Vvveb.imgBaseUrl + item.image + ")";
							li.style.backgroundRepeat = "no-repeat";
						}
					} else {
						let addBtn = (dragType === 'section')
							? `<div class="add-section-btn" title="Add section"><svg class="svg-icon" aria-hidden="true"><use href="/admin/default/img/icons.svg#plus"></use></svg></div>`
							: '';
						li = generateElements(`<li data-section="${group}"${dragAttr} data-type="${itemType}" data-search="${item.name.toLowerCase()}">
									<span class="name">${item.name}</span>${addBtn}
									<img class="preview" src="" loading="lazy">
								</li>`)[0];

						if (item.image) {
							let image = ((item.image.indexOf('/') == -1) ? Vvveb.imgBaseUrl : '') + item.image;
							li.querySelector("img").setAttribute("src", image);
						}
					}

					subList.append(li);
				}
			}
		});
	},

	loadControlGroups: function () {
		this._loadGroupList(".components-list", Vvveb.ComponentsGroup, Vvveb.Components, "comphead", "component", true);
	},

	loadSectionGroups: function () {
		this._loadGroupList(".sections-list", Vvveb.SectionsGroup, Vvveb.Sections, "sectionhead", "section");
	},

	loadBlockGroups: function () {
		this._loadGroupList(".blocks-list", Vvveb.BlocksGroup, Vvveb.Blocks, "blockhead", "block");
	},

	loadStylesGroups: function () {
		this._loadGroupList(".styles-list", Vvveb.StylesGroup, Vvveb.Styles, "stylehead");
	},

	loadUrl: function (url, callback) {
		let self = this;
		self._setDisplay("select-box", "none");

		self.initCallback = callback;
		if (!Vvveb.Builder.iframe || Vvveb.Builder.iframe.src == url) {
			//no load event will fire, run the callback directly instead of dropping it
			if (callback) callback();
			return;
		}
		Vvveb.Builder.iframe.src = url;
	},

	/* iframe */
	_loadIframe: function (url) {

		let self = this;
		if (!this.documentFrame) return;
		self.iframe = this.documentFrame;
		self.iframe.src = url;

		//attach the load handler only once so repeated _loadIframe calls don't stack listeners
		if (self._iframeLoadListenerAttached) return;
		self._iframeLoadListenerAttached = true;

		return this.documentFrame.addEventListener("load", function () {
			window.FrameWindow = self.iframe.contentWindow;
			window.FrameDocument = self.iframe.contentWindow.document;
			let addSectionBox = self._$("add-section-box");
			let highlightBox = self._$("highlight-box");
			let SelectBox = self._$("select-box");

			highlightBox.style.display = "none";


			window.FrameWindow.addEventListener("beforeunload", function (event) {
				if (Vvveb.Undo.undoIndex >= 0) {
					let dialogText = "You have unsaved changes";
					event.returnValue = dialogText;
					return dialogText;
				}
			});

			window.FrameWindow.addEventListener("unload", function (event) {
				document.querySelector(".loading-message").classList.add("active");
				Vvveb.Undo.reset();
			});

			//prevent accidental clicks on links when editing text
			window.FrameDocument.addEventListener("click", function (event) {
				if (Vvveb.WysiwygEditor.isActive && event.target.closest("a")) {
					event.preventDefault();
					return false;
				}
			});

			const selectBoxPosition = function (event) {
				let target = self.selectedEl;

				highlightBox.style.display = "none";

				if (target) {
					self._positionSelectBox(target, false);
				}
			}

			window.FrameWindow.addEventListener("scroll", selectBoxPosition);
			window.FrameWindow.addEventListener("resize", selectBoxPosition);

			Vvveb.WysiwygEditor.init(window.FrameDocument);
			Vvveb.StyleManager.init(window.FrameDocument);
			Vvveb.ColorPaletteManager.init(window.FrameDocument);

			if (self.initCallback) self.initCallback();

			return self._frameLoaded();
		});

	},

	_frameLoaded: function () {

		let self = Vvveb.Builder;

		self.frameDoc = window.FrameDocument;
		self.frameHtml = window.FrameDocument.querySelector("html");
		self.frameBody = window.FrameDocument.querySelector("body");
		self.frameHead = window.FrameDocument.querySelector("head");

		//insert editor helpers like non editable areas
		self.frameHead.append(generateElements('<link data-vvveb-helpers href="' + Vvveb.baseUrl + '../../css/vvvebjs-editor-helpers.css" rel="stylesheet">')[0]);
		self.frameHtml.setAttribute("data-vvvebjs-editor", "");

		self._initHighlight();

		window.dispatchEvent(new CustomEvent("Vvveb.iframe.loaded", { detail: self.frameDoc }));

		document.querySelector(".loading-message").classList.remove("active");
		//Vvveb.NewSection.init();
		Vvveb.StyleList.load(self.frameDoc);

		//enable save button only if changes are made
		let setSaveButtonState = function (e) {
			if (Vvveb.Undo.hasChanges()) {
				document.querySelectorAll("#top-panel .save-btn").forEach(e => e.removeAttribute("disabled"));
			} else {
				document.querySelectorAll("#top-panel .save-btn").forEach(e => e.setAttribute("disabled", "true"));
			}
		};

		Vvveb.Builder.frameBody.addEventListener("Vvveb.undo.add", setSaveButtonState);
		Vvveb.Builder.frameBody.addEventListener("Vvveb.undo.restore", setSaveButtonState);
	},

	_getElementType: function (el) {
		if (!el) return ["", ""];

		//search for component attribute
		let componentName = '';
		let componentAttribute = '';

		if (el.attributes) {
			for (let j = 0; j < el.attributes.length; j++) {
				let nodeName = el.attributes[j].nodeName;

				if (nodeName.indexOf('data-component') > -1) {
					componentName = nodeName.replace('data-component-', '');
					return [componentName, "component"];
				}

				if (nodeName.indexOf('data-v-component-') > -1) {
					componentName = nodeName.replace('data-v-component-', '');
					return [componentName, "component"];
				}

				if (nodeName.indexOf('data-v-') > -1) {
					componentAttribute = (componentAttribute ? componentAttribute + " - " : "") +
						nodeName.replace('data-v-', '') + " ";
				}
			}
		}

		if (componentAttribute != '') return [componentAttribute, "attribute"];

		if (el.id) {
			componentName = "#" + el.id;
		} else {
			componentName = (el.className && (typeof el.className == "string")) ? "." + el.className.split(" ")[0] : "";
		}

		return [componentName, el.tagName];
	},

	loadNodeComponent: function (node) {
		const data = Vvveb.Components.matchNode(node);
		let component;

		if (data)
			component = data.type;
		else
			component = Vvveb.defaultComponent;

		Vvveb.component = Vvveb.Components.get(component);
		Vvveb.Components.render(component);
		this.selectedComponent = component;

		//if component properties is loaded in left panel tab instead of right panel show tab
		let propertiesTab = document.querySelector(".component-properties-tab a");
		if (propertiesTab && propertiesTab.offsetParent) {//if properites tab is enabled/visible 
			propertiesTab.style.display = "";
			if (typeof bootstrap !== "undefined" && bootstrap.Tab) {
				const bsTab = bootstrap.Tab.getOrCreateInstance(propertiesTab);
				bsTab.show();
			}
		}
	},

	reloadComponent: function () {
		Vvveb.Components.render(this.selectedComponent);
	},

	_moveNode: function (node, sibling, placeFn, fallbackFn) {
		if (!node || !node.parentNode) return;
		const oldParent = node.parentNode;
		const oldNextSibling = node.nextSibling;

		if (sibling) {
			placeFn(sibling, node);
		} else {
			fallbackFn(node);
		}

		Vvveb.Builder.selectNode(node);

		Vvveb.Undo.addMutation({
			type: 'move',
			target: node,
			oldParent: oldParent,
			newParent: node.parentNode,
			oldNextSibling: oldNextSibling,
			newNextSibling: node.nextSibling
		});
	},

	moveNodeUp: function (node) {
		node = node || Vvveb.Builder.selectedEl;
		if (!node) return;

		this._moveNode(
			node,
			node.previousElementSibling,
			(sibling, n) => sibling.before(n),
			(n) => n.parentNode.before(n)
		);
	},

	moveNodeDown: function (node) {
		node = node || Vvveb.Builder.selectedEl;
		if (!node) return;

		this._moveNode(
			node,
			node.nextElementSibling,
			(sibling, n) => sibling.after(n),
			(n) => n.parentNode.after(n)
		);
	},

	cloneNode: function (node) {
		node = node || Vvveb.Builder.selectedEl;
		if (!node || typeof node.cloneNode !== "function") return;

		const clone = node.cloneNode(true);
		if (typeof node.after === "function") node.after(clone);
		if (typeof node.click === "function") node.click();

		Vvveb.Undo.addMutation({
			type: 'childList',
			target: node.parentNode,
			addedNodes: [clone],
			nextSibling: node.nextSibling
		});

	},

	_positionSelectBox: function (target, show = true) {
		let self = this;
		let SelectBox = self._$("select-box");

		if (!target || !SelectBox || !self.frameDoc) {
			if (SelectBox) SelectBox.style.display = "none";
			return;
		}

		let pos = offset(target);
		let scrollLeft = self.frameDoc.scrollLeft ?? 0;
		let scrollTop = self.frameDoc.scrollTop ?? 0;
		let padding = self.selectPadding;

		SelectBox.style.top = (pos.top - scrollTop - padding) + "px";
		SelectBox.style.left = (pos.left - scrollLeft - padding) + "px";
		SelectBox.style.width = ((target.offsetWidth ?? target.clientWidth) + padding * 2) + "px";
		SelectBox.style.height = ((target.offsetHeight ?? target.clientHeight) + padding * 2) + "px";

		if (show) {
			SelectBox.style.display = "block";
		}
	},

	selectNode: function (node) {
		let self = this;
		let SelectBox = self._$("select-box");

		if (!node || !SelectBox) {
			if (SelectBox) SelectBox.style.display = "none";
			return;
		}

		let SelectActions = self._$("select-actions");
		let AddSectionBtn = self._$("add-section-btn");
		let elementType = this._getElementType(node);

		if (self.texteditEl && (self.selectedEl != node)) {
			Vvveb.WysiwygEditor.destroy(self.texteditEl);
			self.selectPadding = 0;
			SelectBox.classList.remove("text-edit");
			if (SelectActions) SelectActions.style.display = "";
			self.texteditEl = null;
		}

		if (elementType[1] == "BODY") {
			if (SelectActions) SelectActions.style.display = "none";
			if (AddSectionBtn) AddSectionBtn.style.display = "none";
		} else {
			if (SelectActions) SelectActions.style.display = "";
			if (AddSectionBtn) AddSectionBtn.style.display = "";
		}

		let target = node;
		self.selectedEl = target;

		try {
			this._positionSelectBox(target);
			let top = parseFloat(SelectBox.style.top);

			//move actions toolbar to bottom if there is no space on top
			if (SelectActions) {
				if (top < 30) {
					SelectActions.style.top = "unset";
					SelectActions.style.bottom = "-25px";
				} else {
					SelectActions.style.top = "";
					SelectActions.style.bottom = "";
				}
			}

			Vvveb.Breadcrumb.loadBreadcrumb(target);

		} catch (err) {
			console.error(err);
			return false;
		}

		let typeEl = document.querySelector("#highlight-name .type");
		if (typeEl) typeEl.textContent = elementType[0];
		let nameEl = document.querySelector("#highlight-name .name");
		if (nameEl) nameEl.textContent = elementType[1];

		window.dispatchEvent(new CustomEvent("Vvveb.Builder.selectNode", { detail: { target } }));
	},

	/* iframe highlight */
	_initHighlight: function () {

		let self = Vvveb.Builder;

		if (!self.frameBody || typeof self.frameBody.addEventListener !== "function") return;

		let highlightMoveQueued = false;
		let highlightMoveLastEvent = null;

		let highlightMove = function (event) {
			highlightMoveLastEvent = event;

			if (highlightMoveQueued) return;
			highlightMoveQueued = true;

			requestAnimationFrame(function () {
				highlightMoveQueued = false;
				if (highlightMoveLastEvent) {
					self._highlightMoveProcess(highlightMoveLastEvent);
					highlightMoveLastEvent = null;
				}
			});
		};

		self.frameBody.addEventListener("mousemove", highlightMove);
		self.frameBody.addEventListener("mouseup", function (event) {
			self._highlightUp(event);
		});
		self.frameBody.addEventListener("dblclick", function (event) {
			self._highlightDbClick(event);
		});
		self.frameBody.addEventListener("click", function (event) {
			self._highlightClick(event);
		});

	},

	_highlightMoveProcess: function (event) {
		let self = this;
			if (self.highlightEnabled == true && event.target/* && isElement(event.target)*/) {

				let target = event.target;
				if (!target || typeof target.getBoundingClientRect !== "function") return;
				self.highlightEl = target;
				let pos = offset(target);
				let height = target.offsetHeight;
				let halfHeight = Math.max(height / 2, 5);
				let width = target.offsetWidth;
				let halfWidth = Math.max(width / 2, 5);
				let prepend = true;

				let x = event.x;
				let y = event.y;

				if (self.isResize) {
					if (!self.selectedEl || !self.initialSize) {
						self.isResize = false;
						return;
					}
					if (!self.initialPosition) {
						self.initialPosition = { x, y };
					}

					let deltaX = x - self.initialPosition.x;
					let deltaY = y - self.initialPosition.y;

					pos = offset(self.selectedEl);

					width = self.initialSize.width;
					height = self.initialSize.height;

					switch (self.resizeHandler) {
						// top
						case "top-left":
							height -= deltaY;
							width -= deltaX;
							break;

						case "top-center":
							height -= deltaY;
							break;

						case "top-right":
							height -= deltaY;
							width += deltaX;
							break;

						// center 
						case "center-left":
							width -= deltaX;
							break;

						case "center-right":
							width += deltaX;
							break;

						// bottom 
						case "bottom-left":
							width -= deltaX;
							height += deltaY;
							break;

						case "bottom-center":
							height += deltaY;
							break;

						case "bottom-right":
							width += deltaX;
							height += deltaY;
							break;
					}

					if (self.resizeMode == "css") {
						self.selectedEl.style.width = width + "px";
						self.selectedEl.style.height = height + "px";
					} else {
						self.selectedEl.setAttribute("width", width);
						self.selectedEl.setAttribute("height", height);
					}

					let SelectBox = self._$("select-box");
					if (!SelectBox) return;
					SelectBox.style.top = pos.top - (self.frameDoc.scrollTop ?? 0) + "px";
					SelectBox.style.left = pos.left - (self.frameDoc.scrollLeft ?? 0) + "px";
					SelectBox.style.width = width + "px";
					SelectBox.style.height = self.selectedEl.offsetHeight + "px";
					SelectBox.style.display = "block";

				} else if (self.isDragging) {
					let parent = self.highlightEl;
					if (!parent || typeof parent.getBoundingClientRect !== "function") return;

					let isBlock = false;
					try {
						isBlock = (window.getComputedStyle(parent).display == "block");
					} catch (e) {}

					//sections only behave as childless for section drags, don't mutate the shared table
					let noChildrenTags = NO_CHILDREN_TAGS;
					if (self.dragType == "section") {
						let closest = (typeof parent.closest === "function") ? parent.closest("section, header, footer, body") : null;
						if (closest) {
							parent = closest;
						}
						noChildrenTags = { ...NO_CHILDREN_TAGS, section: true };
					}

					let parentTagName = (parent.tagName || "").toLowerCase();
					let isVattribute = false;
					//check if node is a data-v-attribute dynamic node that will override the content if added inside
					if (parent.childElementCount == 0 && parent.attributes) {
						for (let attr of parent.attributes) {
							if (attr.name.startsWith("data-v-") && !attr.name.startsWith("data-v-component-")) {
								isVattribute = true;
								break;
							}
						}
					}

					try {
						if ((pos.top < (y - halfHeight)) || (pos.left < (x - halfWidth))) {
							if (noChildrenTags[parentTagName] || isBlock || isVattribute) {
								parent.after(self.dragElement);
							} else {
								if (parent == self.dragElement.parentNode) {
									parent.appendChild(self.dragElement);
								} else {
									parent.append(self.dragElement);
								}
							}

							prepend = true;
						} else {
							if (noChildrenTags[parentTagName] || isBlock || isVattribute) {
								parent.parentNode.insertBefore(self.dragElement, parent);
							} else {
								parent.prepend(self.dragElement);
							}

							prepend = false;
						};

						if (self.designerMode && self.dragElement) {
							let parentOffset = self.dragElement.offsetParent ? offset(self.dragElement.offsetParent) : { left: 0, top: 0 };
							self.dragElement.style.position = "absolute";
							self.dragElement.style.x = x - (parentOffset.left - self.frameDoc.scrollLeft);
							self.dragElement.style.y = y - (parentOffset.top - self.frameDoc.scrollTop);
						}

					} catch (err) {
						console.error(err);
						return false;
					}

					if (!self.designerMode && self.iconDrag) {
						self.iconDrag.style.top = (y + 60) + "px";
						self.iconDrag.style.left = (x + self.leftPanelWidth + 10) + "px";
					}
				}// else //uncomment else to disable parent highlighting when dragging
				{
					//if text editor is open check if the highlighted element is not inside the editor
					if (Vvveb.WysiwygEditor.isActive) {
						if (self.texteditEl && typeof self.texteditEl.contains === "function" && self.texteditEl.contains(event.target)) {
							return true;
						}
					}

					let highlightBox = self._$("highlight-box");
					if (!highlightBox) return;
					highlightBox.setAttribute("style",
						`top:${pos.top - (self.frameDoc.scrollTop ?? 0)}px; 
						 left:${pos.left - (self.frameDoc.scrollLeft ?? 0)}px;
						 width:${width}px; 
						 height:${height}px;
						 display:${event.target.hasAttribute('contenteditable') ? "none" : "block"};
						 border:${self.isDragging ? "1px dashed #0d6efd" : ""};
					`);

					let sectionActions = self._$("section-actions");
					if (sectionActions) {
						if (height < 50) {
							sectionActions.classList.add("outside");
						} else {
							sectionActions.classList.remove("outside");
						}
					}

					let elementType = self._getElementType(event.target);
					let hlType = document.querySelector("#highlight-name .type");
					if (hlType) hlType.textContent = elementType[0];
					let hlName = document.querySelector("#highlight-name .name");
					if (hlName) hlName.textContent = elementType[1];
				}
			}

	},

	/**
	 * Handles mouseup on the frame: finishes resize/drag operations, swaps in
	 * real component html, records undo mutations, and selects the dropped node.
	 */
	_highlightUp: function (event) {
		let self = this;
			self.isResize = false;
			self._setDisplay("section-actions", "");
			self._setDisplay("highlight-name", "");
			if (self.isDragging) {
				self.isDragging = false;
				Vvveb.Builder.highlightEnabled = true;
				if (self.iconDrag) self.iconDrag.remove();
				document.getElementById("component-clone")?.remove();

				if (self.dragMoveMutation === false) {
					if ((self.component && self.component.dragHtml) || Vvveb.dragHtml) { //if dragHtml is set for dragging then set real component html
						if (self.component && self.dragElement) {
							//component may lack html (eg. icon-only drag) - fall back to the drag placeholder
							let html = self._componentHtml(self.component) || Vvveb.dragHtml;
							const newElement = generateElements(html)[0];
							if (newElement) {
								self.dragElement.replaceWith(newElement);
								self.dragElement = newElement;
							}
						}
					}

					if (self.component && self.component.afterDrop && self.dragElement) self.dragElement = self.component.afterDrop(self.dragElement);
				} else {
					if (self.selectedEl && self.selectedEl.classList) self.selectedEl.classList.remove("is-dragged");
					if (self.dragElement && self.selectedEl) self.dragElement.replaceWith(self.selectedEl);
					self.dragElement = self.selectedEl;
				}

				const node = self.dragElement;
				if (!node) {
					self.isDragging = false;
					return;
				}
				self.selectNode(node);
				Vvveb.TreeList.loadComponents();
				Vvveb.TreeList.selectComponent(node);
				Vvveb.Server.renderComponents(node);
				self.loadNodeComponent(node);

				if (self.dragType == "section") {
					node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
				}

				if (self.dragMoveMutation === false) {
					Vvveb.Undo.addMutation({
						type: 'childList',
						target: node.parentNode,
						addedNodes: [node],
						nextSibling: node.nextSibling
					});
				} else {
					self.dragMoveMutation.newParent = node.parentNode;
					self.dragMoveMutation.newNextSibling = node.nextSibling;

					Vvveb.Undo.addMutation(self.dragMoveMutation);
					self.dragMoveMutation = false;
				}
			}
	},

	/**
	 * Handles double-click on the frame: enters inline text editing via the
	 * WYSIWYG editor and wires up select-box tracking listeners.
	 */
	_highlightDbClick: function (event) {
		let self = this;

			if (Vvveb.Builder.isPreview == false && Vvveb.Builder.highlightEnabled) {

				if (!Vvveb.WysiwygEditor.isActive && event.target && typeof event.target.addEventListener === "function") {
					self.selectPadding = 10;
					let target = event.target;
					self.texteditEl = target;

					Vvveb.WysiwygEditor.edit(self.texteditEl);

					const _updateSelectBox = function (event) {
						if (!self.texteditEl) return;
						self._positionSelectBox(self.texteditEl);
					};

					//update select box when the text size is changed
					self.texteditEl.addEventListener("blur", _updateSelectBox);
					self.texteditEl.addEventListener("keyup", _updateSelectBox);
					self.texteditEl.addEventListener("paste", _updateSelectBox);
					self.texteditEl.addEventListener("input", _updateSelectBox);
					_updateSelectBox();

					self._$("select-box").classList.add("text-edit")
					self._$("select-actions").style.display = "none";
					self._$("highlight-box").style.display = "none";
				}
			}
	},

	/**
	 * Handles click on the frame: selects the clicked node, loads its component
	 * properties, and toggles resize handles when supported.
	 */
	_highlightClick: function (event) {
		let self = this;

			if (Vvveb.Builder.isPreview == false && Vvveb.Builder.highlightEnabled) {
				if (event.target) {
					if (Vvveb.WysiwygEditor.isActive) {
						if (self.texteditEl && typeof self.texteditEl.contains === "function" && self.texteditEl.contains(event.target)) {
							return true;
						}
					}

					self.selectNode(event.target);
					Vvveb.TreeList.selectComponent(event.target);
					self.loadNodeComponent(event.target);

					let selectBox = self._$("select-box");
					if (Vvveb.component && Vvveb.component.resizable) {
						if (selectBox) selectBox.classList.add("resizable");
						self.resizeMode = Vvveb.component.resizeMode;
					} else {
						if (selectBox) selectBox.classList.remove("resizable");
					}

					self._setDisplay("add-section-box", "none");
					event.preventDefault();
					return false;
				}
			}

	},

	_initBox: function () {
		let self = this;

		//builder skins may omit buttons, guard so one missing button can't break the rest
		let on = function (id, evt, fn) {
			let btn = document.getElementById(id);
			if (btn) btn.addEventListener(evt, fn);
		};

		on("drag-btn", "mousedown", function (event) {
			//self.dragElement = self.selectedEl.setAttribute("style",Vvveb.dragElementStyle);
			if (event.which == 1) {//left click
				if (!self.selectedEl) return false;
				self.isDragging = true;
				self._showChrome();


				if (self.designerMode) {
					self.dragElement = self.selectedEl;
				} else {
					self.selectedEl.style.position = "";
					self.selectedEl.style.top = "";
					self.selectedEl.style.left = "";

					self.selectedEl.classList.add("is-dragged");
					self.dragElement = generateElements(Vvveb.dragHtml)[0];
				}

				const node = self.selectedEl;

				self.dragMoveMutation = {
					type: 'move',
					target: node,
					oldParent: node.parentNode,
					oldNextSibling: node.nextSibling
				};

				//self.selectNode(false);
				event.preventDefault();
				return false;
			}
		});


		let resizeDown = function (event) {
			if (event.which == 1) {//left click
				if (!self.selectedEl) return false;
				let hideEl = document.querySelectorAll("#section-actions, #highlight-name, #highlight-box");
				if (hideEl) hideEl.forEach(e => e.style.display = "none");

				self.isResize = true;
				self.initialSize = { "width": self.selectedEl.offsetWidth, "height": self.selectedEl.offsetHeight };
				self.initialPosition = false;
				self.resizeHandler = this.className;

				event.preventDefault();
				return false;
			}
		};

		document.querySelectorAll(".resize > div").forEach(e => e.addEventListener("mousedown", resizeDown));

		on("down-btn", "click", function (event) {

			self._setDisplay("select-box", "none");

			Vvveb.Builder.moveNodeDown();

			event.preventDefault();
			return false;
		});

		on("up-btn", "click", function (event) {
			self._setDisplay("select-box", "none");

			Vvveb.Builder.moveNodeUp();

			event.preventDefault();
			return false;
		});

		on("clone-btn", "click", function (event) {

			Vvveb.Builder.cloneNode();

			event.preventDefault();
			return false;
		});

		on("parent-btn", "click", function (event) {

			const node = self.selectedEl && self.selectedEl.parentNode;
			if (!node) {
				event.preventDefault();
				return false;
			}

			self.selectNode(node);
			self.loadNodeComponent(node);
			Vvveb.TreeList.selectComponent(node);

			event.preventDefault();
			return false;
		});

		on("save-reusable-btn", "click", function (event) {

			const node = self.selectedEl;
			if (!node || !node.tagName) {
				event.preventDefault();
				return false;
			}

			let type = 'block';
			if (node.tagName.toLowerCase() == 'section') {
				type = 'section';
			}

			const name = prompt("Enter name for new reusable " + type, '');
			if (name) {
				Vvveb.Builder.saveElement(node, type, name);
			}

			event.preventDefault();
			return false;
		});

		let codeEditorOldValue;
		on("edit-code-btn", "click", function (event) {
			if (!Vvveb.Builder.selectedEl) {
				event.preventDefault();
				return false;
			}
			let value = Vvveb.Builder.selectedEl.innerHTML;

			Vvveb.ModalCodeEditor.show();
			Vvveb.ModalCodeEditor.setValue(value);

			codeEditorOldValue = value;

			event.preventDefault();
			return false;
		});

		let onSave = function (event) {
			if (!Vvveb.Builder.selectedEl) return;
			Vvveb.Builder.selectedEl.innerHTML = event.detail;

			const node = Vvveb.Builder.selectedEl;
			Vvveb.Undo.addMutation({
				type: 'characterData',
				target: node,
				oldValue: codeEditorOldValue,
				newValue: node.innerHTML
			});

			Vvveb.Builder.selectNode(node);
		};

		window.addEventListener("Vvveb.ModalCodeEditor.save", onSave);

		on("translate-code-btn", "click", function (event) {
			let text = (Vvveb.Builder.selectedEl && Vvveb.Builder.selectedEl.innerHTML ? Vvveb.Builder.selectedEl.innerHTML : "").trim();
			// uncomment to use outerHTML, not recommended
			//let text = selectedEl.outerHTML;

			fetch(namespaceUrl + "/translate&action=get", { method: "POST", body: new URLSearchParams({ text, csrf: document.getElementById('csrf')?.value }), credentials: 'include' })
				.then((response) => {
					if (!response.ok) { throw new Error(response) }
					return response.json()
				})
				.then((data) => {
					for (let code in data) {
						let translation = data[code];
						let editor = document.querySelector("#lang-" + code + "-editor textarea");
						if (editor) editor.value = translation;
					}
				})
				.catch(error => {
					console.error(error);
					displayToast("danger", "Error", "Error loading translations!");
				});


			Vvveb.ModalTranslateEditor.show();
			Vvveb.ModalTranslateEditor.setValue(text);


			event.preventDefault();
			return false;
		});

		let onTranslateSave = function (event) {
			let form = document.getElementById("translateForm");
			let data = form ? Object.fromEntries(new FormData(form)) : {};
			data["csrf"] = document.getElementById('csrf')?.value;

			fetch(namespaceUrl + "/translate&action=save", { method: "POST", body: new URLSearchParams(data) })
				.then((response) => {
					if (!response.ok) {
						return Promise.resolve(response.text()).then((responseInText) => {
							return Promise.reject([response, responseInText]);
						});
					}
					return response.json();
				})
				.then((data) => {
					//use toast to show save status
					let success = true;
					let bg = "success";

					if (data.success || data == "success") {
					} else {
						bg = "danger";
					}

					if (typeof data.message === "string") {
						displayToast(bg, "Save", data.message ?? data);
					} else if (Array.isArray(data.message)) {
						for (let message of data.message) {

							if (message.success || data == "success") {
								bg = "success"
							} else {
								bg = "danger";
								success = false;
							}

							displayToast(bg, "Save", message.message);
						}
					} else if (typeof data === "string") {
						displayToast(bg, "Save", data);
					}

				})
				.catch(err => {
					displayToast("danger", "Error", "Error saving translations!");

					let message = err;
					try {
						let [response, responseInText] = err;
						message = responseInText ?? response.statusText ?? "Error uploading!";
					} catch (e) {
					}

					if (typeof err?.text === 'function') err.text().then(errorMessage => {
						let message = errorMessage.substring(0, 200);
						displayToast("danger", "Error", message);
					});

					if (typeof message == "string") {
						displayToast("danger", "Error", message.substring(0, 200));
					}

				});
			if (Vvveb.Builder.selectedEl) Vvveb.Builder.selectedEl.innerHTML = event.detail;
			//selectedEl.outerHTML = text;
		};

		//window.removeEventListener("Vvveb.ModalTranslateEditor.save", onTranslateSave); 
		window.addEventListener("Vvveb.ModalTranslateEditor.save", onTranslateSave);


		on("delete-btn", "click", function (event) {
			self._setDisplay("select-box", "none");

			const node = self.selectedEl;
			if (!node || !node.parentNode || typeof node.remove !== "function") {
				event.preventDefault();
				return false;
			}

			Vvveb.Undo.addMutation({
				type: 'childList',
				target: node.parentNode,
				removedNodes: [node],
				nextSibling: node.nextSibling
			});

			self.selectedEl.remove();
			self.selectedEl = null;
			Vvveb.TreeList.loadComponents();
			Vvveb.SectionList.loadSections();

			event.preventDefault();
			return false;
		});

		let addSectionBox = self._$("add-section-box");
		let addSectionElement;

		on("add-section-btn", "click", function (event) {

			addSectionElement = self.highlightEl;
			if (!addSectionBox || !addSectionElement || !addSectionElement.tagName || !window.FrameWindow) {
				event.preventDefault();
				return false;
			}
			addSectionBox.style.display = "block";
			addSectionBox.classList.remove("only-base");
			let tagName = self.highlightEl.tagName.toLowerCase();
			let tabId = "box-components-tab";
			if (["section", "footer", "header"].includes(tagName)) {
				addSectionBox.classList.add("only-sections");
				tabId = "box-sections-tab";
			} else {
				addSectionBox.classList.remove("only-sections");
			}
			if (typeof bootstrap !== "undefined" && bootstrap.Tab) {
				let bsTab = bootstrap.Tab.getOrCreateInstance(document.getElementById(tabId));
				if (bsTab) bsTab.show();
			}

			let pos = offset(addSectionElement);
			let top = ((pos.top + window.FrameWindow.pageYOffset + addSectionElement.clientTop) - self.frameHtml.scrollTop) + addSectionElement.offsetHeight;
			let left = ((pos.left + window.FrameWindow.pageXOffset + addSectionElement.clientLeft) - self.frameHtml.scrollLeft) + (addSectionElement.offsetWidth / 2) - (addSectionBox.offsetWidth / 2);
			let outerHeight = window.FrameWindow.innerHeight + self.frameHtml.scrollTop;

			if ((left + addSectionBox.offsetWidth) > self.frameHtml.offsetWidth) left = self.frameHtml.offsetWidth - addSectionBox.offsetWidth;
			if (((top + addSectionBox.offsetHeight) + self.frameHtml.scrollTop) > outerHeight) top = top - addSectionBox.offsetHeight;
			//check if box is out of viewport and move inside
			if (left < 0) left = 0;
			if (top < 0) top = 0;

			addSectionBox.style.top = top + "px";
			addSectionBox.style.left = left + "px";

			event.preventDefault();
			return false;
		});

		on("close-section-btn", "click", function (event) {
			if (addSectionBox) addSectionBox.style.display = "none";
		});

		function addSectionComponent(component, after = true) {
			if (!component) return;
			let html = self._componentHtml(component);
			let node = generateElements(html)[0];
			if (!node || !node.tagName) return;
			let tagName = node.tagName.toLowerCase();
			let element = addSectionElement;

			//if section add after current section
			if (["section", "footer", "header", "nav"].includes(tagName)) {
				if (!element && Vvveb.Builder.frameBody) {
					let frameBody = Vvveb.Builder.frameBody;
					element = frameBody.querySelector(':scope > ' + tagName + ':last-of-type') || frameBody.querySelector(':scope > section:last-of-type') || frameBody.querySelector(':scope > header:last-of-type') || frameBody.querySelector(':scope > main') || frameBody.querySelector(':scope > footer:last-of-type');
				} else if (element) {
					while (element = element.parentElement) {
						tagName = element.tagName.toLowerCase();
						if (["section", "footer", "header", "nav"].includes(tagName)) {
							after = true;
							break;
						}
					}
				}

				if (!element) {
					element = addSectionElement;
				}
			}

			if (element) {
				if (after) {
					element.after(node);
				} else {
					element.append(node);
				}
			} else if (Vvveb.Builder.frameBody) {
				let newsection = Vvveb.Builder.frameBody.querySelector('newsection')
				if (newsection) {
					newsection.before(node);
				} else {
					Vvveb.Builder.frameBody.append(node);
				}
			}

			if (component.afterDrop) {
				node = component.afterDrop(node);
			}

			Vvveb.Server.renderComponents(node);
			self.selectNode(node);
			self.loadNodeComponent(node);
			Vvveb.TreeList.loadComponents();
			Vvveb.TreeList.selectComponent(node);

			node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });

			Vvveb.Undo.addMutation({
				type: 'childList',
				target: node.parentNode,
				addedNodes: [node],
				nextSibling: node.nextSibling
			});
		}

		if (addSectionBox) {
		addSectionBox.addEventListener("click", function (event) {
			let element = event.target && event.target.closest ? event.target.closest("ol li") : null;
			if (element) {
				let itemType = element.dataset.dragType;
				let html;
				switch (itemType) {
					case 'component':
						html = Vvveb.Components.get(element.dataset.type);
						break;
					case 'block':
						html = Vvveb.Blocks.get(element.dataset.type);
						break;
					case 'section':
						html = Vvveb.Sections.get(element.dataset.type);
						break;
				}


				let insertAfterEl = document.querySelector("[name='add-section-insert-mode']:checked");
				addSectionComponent(html, (insertAfterEl && insertAfterEl.value == "after"));

				addSectionBox.style.display = "none";
			}
		});
		}

	},

	/* drag and drop */
	_initDragdrop: function () {

		let self = this;
		self.isDragging = false;

		document.addEventListener("mousedown", function (event) {
			let element = event.target && event.target.closest ? event.target.closest(".drag-elements-sidepane ul > li > ol > li[data-drag-type]") : null;
			let html;

			if (element && event.which == 1) {//left click
				document.getElementById("component-clone")?.remove();
				self._hideChrome();

				self.dragType = element.dataset.dragType;
				if (self.dragType == "component") {
					self.component = Vvveb.Components.get(element.dataset.type);
				}
				else if (self.dragType == "section") {
					self.component = Vvveb.Sections.get(element.dataset.type);
				}
				else if (self.dragType == "block") {
					self.component = Vvveb.Blocks.get(element.dataset.type);
				}

				//guard against unknown/unregistered drag types to avoid TypeError below
				if (! self.component) {
					console.warn("Vvveb.Builder: no component registered for dragged item:", element.dataset.type);
					return false;
				}

				if (self.component.dragHtml) {
					html = self.component.dragHtml;
				} else if (Vvveb.dragHtml) {
					html = Vvveb.dragHtml;
				} else {
					html = self._componentHtml(self.component);
				}

				self.dragElement = generateElements(html)[0];
				//self.dragElement.css("border", "1px dashed #4285f4");

				if (self.component.dragStart) self.dragElement = self.component.dragStart(self.dragElement);

				self.isDragging = true;
				if (Vvveb.dragIcon == 'html') {
					self.iconDrag = generateElements(html)[0];
					self.iconDrag.setAttribute("id", "dragElement-clone");
					self.iconDrag.style.position = "absolute";
				}
				else if (self.designerMode == false) {
					self.iconDrag = document.createElement("img");
					self.iconDrag.setAttribute("id", "dragElement-clone");
					self.iconDrag.setAttribute("src", element.style.backgroundImage.replace(/^url\(['"](.+)['"]\)/, '$1'));

					self.iconDrag.style.zIndex = "100";
					self.iconDrag.style.position = "absolute";
					self.iconDrag.style.width = "64px";
					self.iconDrag.style.height = "64px";
					self.iconDrag.style.top = event.y + "px";
					self.iconDrag.style.left = event.x + "px";
				}

				if (self.iconDrag) document.body.append(self.iconDrag);

				event.preventDefault();
				return false;
			}
		});

		document.addEventListener('mouseup', function (event) {
			if (self.iconDrag && self.isDragging == true) {
				self.isDragging = false;
				document.getElementById("component-clone")?.remove();
				self._showChrome();
				self.iconDrag.remove();
				if (self.dragElement) {
					self.dragElement.remove();
				}
			}
		});

		document.addEventListener('mousemove', function (event) {
			if (self.iconDrag && self.isDragging == true) {
				//absolute-positioned drag ghost needs document coords (scroll included),
				//elementFromPoint needs viewport coords (scroll excluded)
				const pageX = event.pageX ?? (event.clientX + window.scrollX);
				const pageY = event.pageY ?? (event.clientY + window.scrollY);
				const clientX = event.clientX ?? (event.pageX - window.scrollX);
				const clientY = event.clientY ?? (event.pageY - window.scrollY);

				self.iconDrag.style.left = (pageX - 60) + "px";
				self.iconDrag.style.top = (pageY - 30) + "px";

				const elementMouseIsOver = document.elementFromPoint(clientX - 60, clientY - 40);

				//if drag elements hovers over iframe switch to iframe mouseover handler
				//TODO: dispatch mousemove to iframe when hovering over it during drag
				/*
				if (elementMouseIsOver && elementMouseIsOver.tagName == 'IFRAME') {
					self.frameBody.dispatchEvent(new MouseEvent("mousemove", {
						bubbles: true,
						cancelable: true,
					}));

					event.stopPropagation();
					self.selectNode(false);
				}
				*/
			}
		});

		document.addEventListener("mouseup", function (event) {
			let element = event.target && event.target.closest ? event.target.closest(".drag-elements-sidepane ul > ol > li > li") : null;
			if (element) {
				self.isDragging = false;
				document.getElementById("component-clone")?.remove()
				self._showChrome();
			}
		});

	},

	removeHelpers: function (html, keepHelperAttributes = false) {
		//tags like stylesheets or scripts 
		html = String(html ?? "");
		html = html.replace(/<[^>]+?data-vvveb-helpers.+?>/gi, "");
		html = html.replace(/<[^>]+?vvvebjs-new-section.+?>.+?<\/newsection>/gims, "");
		//attributes
		if (!keepHelperAttributes) {
			html = html.replace(/\s*data-vvveb-\w+(=["'].*?["'])?\s*/gi, "");
		}

		html = html.replaceAll("vvveb-hidden", "").replaceAll("data-vvvebjs-editor", "");
		return html;
	},

	getHtml: function (keepHelperAttributes = true, filter = true) {
		let doc = window.FrameDocument;
		if (!doc || !doc.documentElement) return "";
		let hasDoctpe = (doc.doctype != null);
		let html = "";

		doc.querySelectorAll("[contenteditable]").forEach(e => e.removeAttribute("contenteditable"));
		doc.querySelectorAll("[spellcheck]").forEach(e => e.removeAttribute("spellcheck"));
		doc.querySelectorAll('script[src^="chrome-extension://"]').forEach(e => e.remove());
		doc.querySelectorAll('script[src^="moz-extension://"]').forEach(e => e.remove());

		// scroll page to top to avoid saving the page in a different state
		// like saving with sticky classes set for navbar etc
		// this.iframe.contentWindow.scrollTo(0,0);
		if (filter) {
			window.dispatchEvent(new CustomEvent("Vvveb.getHtml.before", { detail: doc }));
		}

		if (hasDoctpe) html =
			"<!DOCTYPE "
			+ doc.doctype.name
			+ (doc.doctype.publicId ? ' PUBLIC "' + doc.doctype.publicId + '"' : '')
			+ (!doc.doctype.publicId && doc.doctype.systemId ? ' SYSTEM' : '')
			+ (doc.doctype.systemId ? ' "' + doc.doctype.systemId + '"' : '')
			+ ">\n";

		Vvveb.FontsManager.cleanUnusedFonts();

		html += doc.documentElement.outerHTML;
		html = this.removeHelpers(html, keepHelperAttributes);

		if (filter) {
			window.dispatchEvent(new CustomEvent("Vvveb.getHtml.after", { detail: doc }));
			window.dispatchEvent(new CustomEvent("Vvveb.getHtml.filter", { detail: html }));
		}

		return html;
	},

	loadHtml: function (html) {
		if (!this.iframe) return;
		this.iframe.srcdoc = html;
		/*
		this.iframe.src = "about:blank";
		window.FrameDocument.open();
		window.FrameDocument.write(html);
		window.FrameDocument.close();
		*/
	},

	setHtml: function (html) {
		//documentElement.innerHTML resets <head> each time and the page flickers
		//return window.FrameDocument.documentElement.innerHTML = html;
		if (typeof html !== "string" || !window.FrameDocument) return;

		function getTag(html, tag, outerHtml = false) {
			const start = html.indexOf("<" + tag);
			const end = html.indexOf("</" + tag);

			if (start >= 0 && end >= 0) {
				if (outerHtml)
					return html.slice(start, end + 3 + tag.length);
				else
					return html.slice(html.indexOf(">", start) + 1, end);
			} else {
				return html;
			}
		}

		if (this.runJsOnSetHtml && this.frameBody) {
			this.frameBody.innerHTML = getTag(html, "body");
		}
		else if (window.FrameDocument.body) {
			window.FrameDocument.body.innerHTML = getTag(html, "body");
		}

		//use outerHTML if you want to set body tag attributes
		//window.FrameDocument.body.outerHTML = getTag(html, "body", true);

		//set head html only if changed to avoid page flicker
		if (window.FrameDocument.head) {
			let headHtml = getTag(html, "head");
			if (window.FrameDocument.head.innerHTML != headHtml) {
				window.FrameDocument.head.innerHTML = headHtml;
			}
		}
	},

	saveElement: function (element, type, name, callback) {
		if (!element || !element.outerHTML || !name) return;
		if (type == 'section') {
			Vvveb.Sections.add('reusable/' + name, {
				name,
				image: "img/logo-small.png",
				html: element.outerHTML
			});

			if (Vvveb.SectionsGroup["Reusable"] === undefined) {
				Vvveb.SectionsGroup["Reusable"] = [];
			}

			Vvveb.SectionsGroup["Reusable"].push('reusable/' + name);
			Vvveb.Builder.loadSectionGroups();
		} else {
			Vvveb.Blocks.add('reusable/' + name, {
				name,
				image: "img/logo-small.png",
				html: element.outerHTML
			});

			if (Vvveb.BlocksGroup["Reusable"] === undefined) {
				Vvveb.BlocksGroup["Reusable"] = [];
			}

			Vvveb.BlocksGroup["Reusable"].push('reusable/' + name);
			Vvveb.Builder.loadBlockGroups();
		}

		let data = { type, name, html: element.outerHTML, csrf: document.getElementById('csrf')?.value };

		fetch(saveReusableUrl, { method: "POST", body: new URLSearchParams(data), credentials: 'include' })
			.then((response) => {
				if (!response.ok) { throw new Error(response) }
				return response.json()
			})
			.then((data) => {
				if (callback) callback(data);
				let bg = "success";

				if (data.success || data == "success") {
				} else {
					bg = "danger";
				}

				displayToast(bg, "Save", data.message ?? data);
			})
		.catch(error => {
			console.error(error);
			displayToast("danger", "Error", "Error saving!");
		});
	},

	save: function (data, saveUrl, success, error) {
		if (!data) data = Vvveb.FileManager.getPageData() || {};
		if (!data || typeof data !== "object") data = {};

		if (!Object.hasOwn(data, "file")) {
			data["file"] = Vvveb.FileManager.getCurrentFileName();
		}

		if (!Object.hasOwn(data, "url")) {
			data["url"] = Vvveb.FileManager.getPageData('url');
		}

		data["elements"] = Vvveb.ChangeManager.getChangedElements();
		data["csrf"] = document.getElementById('csrf')?.value ?? "";

		if (!Object.hasOwn(data, "startTemplateUrl")) {
			data["html"] = this.getHtml();
		}

		let detail = { ...Vvveb.FileManager.getPageData(), ...data };
		//allow event to change name or cancel by setting name to false
		window.dispatchEvent(new CustomEvent("Vvveb.Builder.save", {
			detail
		}));
		//data['elements'] = new URLSearchParams(data['elements']);

		return fetch(saveUrl, {
			method: "POST",
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: nestedFormData(data),
			credentials: 'include'
		})
			.then((response) => {
				if (!response.ok) {
					return Promise.resolve(response.text()).then((responseInText) => {
						return Promise.reject([response, responseInText]);
					});
				}
				return response.json();
			})
			.then((data) => {
				if (success) success(data);
				Vvveb.Undo.reset();
				document.querySelectorAll("#top-panel .save-btn").forEach(e => e.setAttribute("disabled", "true"));
				window.dispatchEvent(new CustomEvent("Vvveb.Builder.save", {
					detail: data,
				}));
			})
			.catch((err) => {
				if (error) error(err);

				let message;

				if (Object.hasOwn(err, 'message')) {
					message = err.message;
				} else if (err?.[Symbol.iterator]) {
					let [response, responseInText] = err;
					message = responseInText || response.statusText || "Error saving!";

					if (typeof response?.text === 'function') response.text().then(errorMessage => {
						let message = errorMessage.substring(0, 200);
						displayToast("danger", "Error", message);
					});
				}

				if (message) {
					displayToast("danger", "Error", message.substring(0, 200));
				}
			});
	},

	setDesignerMode: function (designerMode = false) {
		this.designerMode = designerMode;
	}

};

Vvveb.ModalCodeEditor = {
	modal: false,
	modalId: 'codeEditorModal',
	name: 'ModalCodeEditor',
	editor: false,

	init: function (modal = false, editor = false) {
		if (modal) {
			this.modal = modal;
		} else {
			this.modal = document.getElementById(this.modalId);
		}
		if (editor) {
			this.editor = editor;
		} else {
			this.editor = this.modal.querySelector('textarea');
		}

		let self = this;

		this.modal.querySelector('.save-btn').addEventListener("click", function (event) {
			window.dispatchEvent(new CustomEvent("Vvveb." + self.name + ".save", { detail: self.getValue() }));
			self.hide();
			return false;
		});
	},

	show: function (value) {
		if (!this.modal) {
			this.init();
		}

		const bsModal = bootstrap.Modal.getOrCreateInstance(this.modal);
		return bsModal.show();
	},

	hide: function (value) {
		const bsModal = bootstrap.Modal.getOrCreateInstance(this.modal);
		return bsModal.hide();
	},

	getValue: function () {
		return this.editor.value;
	},

	setValue: function (value) {
		if (!this.modal) {
			this.init();
		}
		//enable save button
		document.querySelectorAll("#top-panel .save-btn").forEach(e => e.removeAttribute("disabled"));
		this.editor.value = value;
	},
};

	Vvveb.ModalTranslateEditor = Object.create(Vvveb.ModalCodeEditor);
	Vvveb.ModalTranslateEditor.modalId = 'translateEditorModal';
	Vvveb.ModalTranslateEditor.name = 'ModalTranslateEditor';

Vvveb.CodeEditor = {

	isActive: false,
	oldValue: '',
	doc: false,
	textarea: false,

	init: function (doc) {
		this.textarea = document.querySelector("#vvveb-code-editor textarea");
		this.textarea.value = Vvveb.Builder.getHtml();

		this.textarea.addEventListener("keyup", e => {
			delay(() => Vvveb.Builder.setHtml(this.value), 1000);
		});

		//load code on document changes
		Vvveb.Builder.frameBody.addEventListener("Vvveb.undo.add", () => Vvveb.CodeEditor.setValue());
		Vvveb.Builder.frameBody.addEventListener("Vvveb.undo.restore", () => Vvveb.CodeEditor.setValue());

		//load code when a new url is loaded
		Vvveb.Builder.documentFrame.addEventListener("load", () => Vvveb.CodeEditor.setValue());

		this.isActive = true;
	},

	setValue: function (value) {
		if (this.isActive) {
			this.textarea.value = Vvveb.Builder.getHtml();
		}
	},

	destroy: function (element) {
		//this.isActive = false;
	},

	toggle: function () {
		if (this.isActive != true) {
			this.isActive = true;
			return this.init();
		}
		this.isActive = false;
		this.destroy();
	}
};


Vvveb.CssEditor = {

	isActive: false,
	oldValue: '',
	doc: false,
	textarea: false,
	_boundTextarea: false,
	_pushCss: false,

	init: function (doc) {
		this.textarea = document.getElementById("css-editor")
		if (!this.textarea) return false;
		this.textarea.value = Vvveb.StyleManager.getCss();
		let self = this;
		/*
		document.querySelectorAll('[href="#csscode-tab"]').forEach( t => t.addEventListener("click", e => {
			self.textarea.value = Vvveb.StyleManager.getCss();
		}));
		*/
		//bind once so repeated toggles don't stack handlers; rebind if the node was replaced
		if (this._boundTextarea !== this.textarea) {
			this._boundTextarea = this.textarea;
			this._pushCss = debounce(() => {
				if (self.textarea) Vvveb.StyleManager.setCss(self.textarea.value);
			}, 1000);
			this.textarea.addEventListener("keyup", e => {
				self._pushCss();
			});
		}
		this.isActive = true;
		return true;
	},

	getValue: function () {
		if (!this.textarea) return "";
		return this.textarea.value;
	},

	setValue: function (value, updateStyles = true) {
		if (this.textarea) this.textarea.value = value ?? "";
		if (updateStyles) {
			Vvveb.StyleManager.setCss(value ?? "");
		}
	},

	destroy: function () {
	},

	toggle: function () {
		if (this.isActive != true) {
			return this.init();
		}
		this.isActive = false;
		this.destroy();
	}
};

function displayToast(type, title, message, position = 'bottom', id = null) {
	if (!id) {
		id = position + "-toast";
	}

	let toast = document.getElementById(id);
	let header = toast.querySelector(".toast-header");
	toast.classList.remove("bottom-0", "top-0");
	toast.classList.add(position + "-0");
	toast.querySelector(".toast-body .message").textContent = message;
	header.classList.remove("danger", "success");
		header.classList.add("bg-" + type);
	header.querySelector("strong").textContent = title;
	let toastDisplay = toast.cloneNode(true);
	toast.parentNode.appendChild(toastDisplay);

	let delay = 3000;
	if (type == "danger") {
		delay = 10000;
	}

	let bsToast = new bootstrap.Toast(toastDisplay, { animation: true, delay });
	toastDisplay.addEventListener('hidden.bs.toast', () => {
		toastDisplay.remove();
	});
	bsToast.show();
}

Vvveb.Gui = {

	init: function () {
		if (this._init) return;
		this._init = true;
		document.querySelectorAll("[data-vvveb-action]").forEach(function (el, i) {
			const on = el.dataset.vvvebOn ?? "click";
			const action = Vvveb.Gui[el.dataset.vvvebAction];
			//a typo in data-vvveb-action must not abort binding for the rest of the buttons
			if (typeof action !== "function") {
				console.warn("Vvveb.Gui: unknown action:", el.dataset.vvvebAction);
				return;
			}
			el.addEventListener(on, action);
		});

		this.shortcuts();
	},

	shortcuts: function () {
		let self = this;

		const handleShortcuts = function (e) {
			if (e.ctrlKey) {
				let target = e.target;
				let typing = target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName || ""));
				//don't hijack native editing keys (e.g. undo in text fields), except save
				if (typing && e.key !== 's' && e.key !== 'S') return;
				switch (e.key) {
					case 's':
						e.preventDefault();
						let btn = document.querySelector('.save-btn');
						if (! btn) return;
						let url = btn.dataset.vvvebUrl;
						self.save(null, url, document.querySelector('.save-btn'));
						return;
					case 'z':
						e.preventDefault();
						self.undo();
						return;
					case 'Z':
					case 'y':
						e.preventDefault();
						self.redo();
						return;
					case 'L':
						e.preventDefault();
						self.toggleTreeList();
						return;
					case 'e':
						e.preventDefault();
						self.toggleEditor();
						return;
					case 'P':
						e.preventDefault();
						self.newPage();
						return;
					case 'S':
						e.preventDefault();
						self.newSection();
						return;
				}
			}
		}

		//handle shortcuts from main window and iframe also
		document.addEventListener('keydown', handleShortcuts);
		window.addEventListener('Vvveb.iframe.loaded', () => {
			if (Vvveb.Builder.frameBody) Vvveb.Builder.frameBody.addEventListener('keydown', handleShortcuts);
		});

	},

	undo: function () {
		if (Vvveb.WysiwygEditor.isActive) {
			Vvveb.WysiwygEditor.undo();
		} else {
			Vvveb.Undo.undo();
		}
		Vvveb.Builder.selectNode();
	},

	redo: function () {
		if (Vvveb.WysiwygEditor.isActive) {
			Vvveb.WysiwygEditor.redo();
		} else {
			Vvveb.Undo.redo();
		}
		Vvveb.Builder.selectNode();
	},

	//show modal with html content
	/**
	 * Shows a modal with the current HTML in a textarea.
	 */
	saveModal: function () {
		let editor = document.querySelector('#textarea-modal textarea');
		if (editor) editor.value = Vvveb.Builder.getHtml();
		let modal = document.getElementById('textarea-modal');
		if (!modal) return;
		if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
			let instance = bootstrap.Modal.getOrCreateInstance(modal);
			if (instance) instance.show();
		} else if (typeof modal.modal === "function") {
			modal.modal();
		}
	},

	//show offcanvas with page changes
	showChanges: function () {
		let base, folder, filename, page;
		filename = Vvveb.FileManager.getPageData('file');
		if (!filename) return;
		base = (base = /[^\/]+$/.exec(filename)) ? base[0] : '';
		folder = (folder = /(.+)\//.exec(filename)) ? folder[1] : '/';

		page = Vvveb.FileManager.getCurrentUrl();
		page = (page = /[^\/]+\/?$/.exec(page)) ? page[0] : '';
		page = page.replace(".html", "");
		page = page ? page : "new-template";
		page += ".html";

		let safeOffcanvas = document.querySelector("#save-offcanvas");
		if (!safeOffcanvas) return;
		let element;

		if (element = safeOffcanvas.querySelector("[data-v-filename]")) {
			element.textContent = filename;
		}
		if (element = safeOffcanvas.querySelector("input[data-v-filename]")) {
			element.value = filename;
		}
		if (element = safeOffcanvas.querySelector("[data-v-basename]")) {
			element.value = page;
		}
		if (element = safeOffcanvas.querySelector("[data-v-theme-folders]")) {
			element.value = folder;
		}

		Vvveb.ChangeManager.render();
	},

	//post html content through ajax to save to filesystem/db
	/**
	 * Initiates a save operation with loading state and toast notifications.
	 *
	 * @param {Event} event - Click event.
	 * @param {string} [saveUrl] - Save URL override.
	 * @param {Element} [saveBtn] - The save button element.
	 * @returns {Promise} The save Promise.
	 */
	save: function (event, saveUrl = null, saveBtn = null) {
		let btn = (saveBtn ?? this);
		if (!btn || typeof btn.querySelector !== "function") {
			btn = document.querySelector('.save-btn');
		}
		saveUrl = saveUrl ?? this?.dataset?.vvvebUrl ?? btn?.dataset?.vvvebUrl;
		if (!saveUrl) {
			displayToast("danger", "Error", "Error saving! Missing save url.");
			return Promise.reject(new Error("Missing save url"));
		}
		let pageData = Vvveb.FileManager.getPageData() || {};
		//if offcanvas check if user provided new template name
		if (btn && btn.classList && btn.classList.contains("save-offcanvas")) {
			let templateChoice = document.querySelector("#save-offcanvas [name=template]:checked");
			let folderInput = document.querySelector("#save-offcanvas [name=folder]");
			let fileInput = document.querySelector("#save-offcanvas [name=file]");
			if (templateChoice && templateChoice.value == "new" && folderInput && fileInput) {
				pageData['file'] = folderInput.value + "/" + fileInput.value;
			}
		}

		let loading = btn ? btn.querySelector(".loading") : null;
		if (loading) loading.classList.remove("d-none");
		let buttonText = btn ? btn.querySelector(".button-text") : null;
		if (buttonText) buttonText.classList.add("d-none");

		let detail = { ...pageData, saveUrl, saveBtn };
		const saveEvent = new CustomEvent("Vvveb.Gui.save", { detail });
		window.dispatchEvent(saveEvent);

		return Vvveb.Builder.save(pageData, saveUrl, (data) => {
			//use toast to show save status
			let success = true;
			let bg = "success";

			if (data.success || data == "success") {
			} else {
				bg = "danger";
			}

			if (typeof data.message === "string") {
				displayToast(bg, "Save", data.message ?? data);
			} else if (Array.isArray(data.message)) {
				for (let message of data.message) {

					if (message.success || data == "success") {
						bg = "success"
					} else {
						bg = "danger";
						success = false;
					}

					displayToast(bg, "Save", message.message);
				}
			} else if (typeof data === "string") {
				displayToast(bg, "Save", data);
			}

			const offcanvas = document.getElementById('save-offcanvas');
			if (offcanvas && typeof bootstrap !== "undefined" && bootstrap.Offcanvas) {
				let instance = bootstrap.Offcanvas.getInstance(offcanvas);
				if (instance) instance.hide();
			}

			let loading = btn ? btn.querySelector(".loading") : null;
			if (loading) loading.classList.add("d-none");
			let buttonText = btn ? btn.querySelector(".button-text") : null;
			if (buttonText) buttonText.classList.remove("d-none");

			if (success) {
				document.querySelectorAll("#top-panel .save-btn").forEach(e => e.setAttribute("disabled", "true"));
			}

		}, (error) => {
			let loading = btn ? btn.querySelector(".loading") : null;
			if (loading) loading.classList.add("d-none");
			let buttonText = btn ? btn.querySelector(".button-text") : null;
			if (buttonText) buttonText.classList.remove("d-none");
			let message = error?.statusText ?? "Error saving!";
			displayToast("danger", "Error", message);
		});
	},

	download: function () {
		let src = Vvveb.Builder.iframe && Vvveb.Builder.iframe.src;
		if (!src) return;
		const match = /[^\/]+$/.exec(src);
		const filename = match ? match[0] : 'page.html';
		const uriContent = "data:application/octet-stream," + encodeURIComponent(Vvveb.Builder.getHtml());

		let link = document.createElement('a');
		if ('download' in link) {
			link.dataset.download = filename;
			link.href = uriContent;
			link.target = "_blank";

			document.body.appendChild(link);
			const result = link.click();
			document.body.removeChild(link);
			link.remove();

		} else {
			location.href = uriContent;
		}
	},

	viewport: function () {
		let breakpoint = this && this.dataset ? this.dataset.view : null;
		let canvas = document.getElementById("canvas");
		if (canvas) canvas.classList.remove("responsive");
		let wrapper = document.getElementById("iframe-wrapper");
		if (wrapper) wrapper.removeAttribute("style");
		document.querySelectorAll(".responsive-btns .active").forEach(e => e.classList.remove("active"));

		if (breakpoint && Vvveb.StyleManager.currentBreakpoint == breakpoint) {
			Vvveb.StyleManager.currentBreakpoint = "none";
			breakpoint = null;
		}

		if (Vvveb.Builder.documentFrame) {
			Vvveb.Builder.documentFrame.style.width = "";
			Vvveb.Builder.documentFrame.style.height = "";
		}

		if (breakpoint) {
			let width = Vvveb.StyleManager.breakpoints[breakpoint];
			//unknown size names reset instead of throwing
			if (!width) {
				Vvveb.StyleManager.currentBreakpoint = "none";
				Vvveb.StyleManager.setMedia();
				return;
			}
			if (canvas) canvas.classList.add("responsive");
			if (wrapper) wrapper.style.width = width.replace(".98", "");
			Vvveb.StyleManager.currentBreakpoint = breakpoint;
			if (this && this.classList) this.classList.add("active");
		}
		
		Vvveb.StyleManager.setMedia();
	},

	toggleEditor: function () {
		let toggle = function (selector, cls) {
			let el = document.querySelector(selector);
			if (el) el.classList.toggle(cls);
		};
		toggle("#vvveb-builder", "bottom-panel-expand");
		toggle("#toggleEditorJsExecute", "d-none");
		//hide breadcrumb when showing the editor
		toggle(".breadcrumb-navigator .breadcrumb", "d-none");
		toggle(".breadcrumb-navigator .nav-tabs", "d-none");
		try { Vvveb.CodeEditor.toggle(); } catch (e) { console.error(e); }
		try { Vvveb.CssEditor.toggle(); } catch (e) { console.error(e); }
	},

	toggleHidden: function () {
		if (Vvveb.Builder.frameBody) Vvveb.Builder.frameBody.classList.toggle("vvveb-hidden");
	},

	toggleEditorJsExecute: function () {
		Vvveb.Builder.runJsOnSetHtml = this.checked;
	},

	preview: function () {
		Vvveb.Builder.isPreview = !(Vvveb.Builder.isPreview == true);
		let layer = document.getElementById("iframe-layer");
		if (layer) layer.classList.toggle("d-none");
		let builder = document.getElementById("vvveb-builder");
		if (builder) builder.classList.toggle("preview");
	},

	fullscreen: function () {
		if (typeof launchFullScreen === "function") launchFullScreen(document); // the whole page
	},

	search: function () {
		let searchText = ((this && this.value) || "").toLowerCase();
		let panel = this && this.parentNode && this.parentNode.parentNode ? this.parentNode.parentNode.querySelector("div > ul") : null;
		if (!panel) return;
		panel.querySelectorAll("li ol li").forEach(function (el, i) {
			el.style.display = "none";
			if ((el.dataset.search || "").toLowerCase().indexOf(searchText) > -1) el.style.display = "";
		});
	},

	clearSearch: function (e) {
		let input = this && this.parentNode ? this.parentNode.querySelector("input") : null;
		if (!input) return;
		input.value = "";
		input.dispatchEvent(new KeyboardEvent("keyup", {
			bubbles: true,
			cancelable: true,
		}));
	},

	toggleSections: function (e) {
		if (!this || typeof this.querySelector !== "function") return;
		//toggle buttons render an inline svg sprite icon (very old markup used <i class="la ...">)
		let icon = this.querySelector("svg, i");
		if (!icon) return;
		let tag = (icon.tagName || "").toLowerCase();
		let use = (tag == "svg" && typeof icon.querySelector === "function") ? icon.querySelector("use") : null;
		let closed = (this.dataset.closed == "true");

		if (use && typeof use.getAttribute === "function") {
			let href = use.getAttribute("href") || "";
			let base = href.split("#")[0];
			use.setAttribute("href", base + (closed ? "#minus" : "#plus"));
		} else if (typeof icon.setAttribute === "function") {
			icon.setAttribute("class", closed ? "la la-minus" : "la la-plus");
		}
		this.dataset.closed = closed ? "false" : "true";

		let root = this.parentNode && this.parentNode.parentNode ? this.parentNode.parentNode.parentNode : null;
		if (root && typeof root.querySelectorAll === "function") root.querySelectorAll('input.header_check[type="checkbox"]').forEach(e => e.checked = closed);
	},

	//Pages, file/components tree 
	newPage: function () {

		let newPageModal = document.getElementById('new-page-modal');
		if (!newPageModal) return;
		let form = newPageModal.querySelector("form");
		if (!form || typeof bootstrap === "undefined" || !bootstrap.Modal) return;

		const bsModal = bootstrap.Modal.getOrCreateInstance(newPageModal);
		if (!bsModal) return;
		bsModal.show();

		let submitForm = function (e) {

			let data = {};
			this.querySelectorAll("input[type=text],input[type=number],input[type=checkbox]:checked,input[type=radio]:checked,input[name=image],textarea,select:not(:disabled)").forEach((el, i) => {
				if (el.offsetParent || el.name == 'image') data[el.name] = el.value;
			});

			if (!data['file']) {
				if (!data['title']) {
					displayToast("danger", "Error", "Page title is required!");
					e.preventDefault();
					return false;
				}
				data['file'] = data['title'] + '.html';
			}

			if (!data['name']) {
				data['name'] = data['title'];
			}

			if (data['file']) {
				data['title'] = data['file'].replace('/', '').replace('.html', '');
				//let name = data['name'] = data['folder'].replace('/', '_') + "-" + data['title'];
				if (!data['name']) {
					data['name'] = data['title'];
				}
				data['url'] = data['folder'] + "/" + data['file'];
				data['url'] = (Vvveb.themeBaseUrl || "") + data['url'];
			} else {
				data['className'] = 'page';
			}

			data['file'] = data['file'] ? data['file'] : '';
			data['content'] = data['content'] ? '<div>' + data['content'] + '</div>' : '';
			data['filename'] = data['filename'] ? data['filename'] : data['file'];
			data['startTemplateUrl'] = data['startTemplateUrl'] ? data['startTemplateUrl'] : '';
			e.preventDefault();

			return Vvveb.Builder.save(data, this.action, function (savedData) {
				if (savedData?.success) {
					data.title = data.name;

					if (typeof savedData === 'object' && savedData !== null) {
						data.name = savedData.name ?? data.name;
						data.url = savedData.url ?? data.url;
						data.file = savedData.file ?? (data.folder + "/" + data.file);
						data.title = savedData.title ?? data.title;
					}

					delete data['startTemplateUrl'];
					let page = Vvveb.FileManager.addPage(data.name, data, false, false);
					Vvveb.FileManager.loadPage(data.name);
					Vvveb.FileManager.scrollToPage(page);
					bsModal.hide();


					let pageUrl = window.location.pathname + "?module=editor/page-builder&template=" + encodeURIComponent(data["file"] ?? "") + "&url=" + encodeURIComponent(data["url"] ?? "") + "&name=" + encodeURIComponent(data["title"] ?? "");
					if (data["post_id"]) {
						pageUrl += "&post_id=" + encodeURIComponent(data["post_id"]);
					}
					if (data["product_id"]) {
						pageUrl += "&product_id=" + encodeURIComponent(data["product_id"]);
					}

					window.history.pushState({ name: data.name }, "", pageUrl);
				} else {
					let message = savedData?.message ?? "Error saving!";
					displayToast("danger", "Error", message);
				}
			});
		};

		if (!form.dataset.init) {
			form.addEventListener("submit", submitForm);
			form.dataset.init = true;
		}
	},

	newSection: function (e, onlyBase = false) {

		let addSectionBox = document.getElementById("add-section-box");
		if (!addSectionBox) return false;

		//addSectionElement = self.highlightEl;
		addSectionBox.style.display = "block";

		addSectionBox.classList.add("only-sections");
		if (onlyBase) {
			addSectionBox.classList.add("only-base");
		} else {
			addSectionBox.classList.remove("only-base");
		}

		if (typeof bootstrap !== "undefined" && bootstrap.Tab) {
			let bsTab = bootstrap.Tab.getOrCreateInstance(document.getElementById("box-sections-tab"));
			if (bsTab) bsTab.show();
		}

		if (e && typeof e.preventDefault === "function") e.preventDefault();
		return false;

	},

	setDesignerMode: function () {
		//aria-pressed attribute is updated after action is called and we check for false instead of true
		let pressed = this && typeof this.getAttribute === "function" ? this.getAttribute("aria-pressed") : null;
		Vvveb.Builder.setDesignerMode(pressed == "true");
	},

	//layout
	togglePanel: function (panel, cssVar) {
		panel = document.querySelector(panel);
		let body = document.querySelector("body");
		if (!panel || !body) return false;
		let prevValue = getComputedStyle(body).getPropertyValue(cssVar);
		let visible = false;

		if (prevValue !== "0px") {
			panel.dataset.layoutToggle = prevValue;
			body.style.setProperty(cssVar, "0px");
			panel.style.display = "none";
			visible = false;
		} else {
			prevValue = panel.dataset.layoutToggle;
			body.style.setProperty(cssVar, "");
			panel.style.display = "";
			visible = true;
		}

		return visible;
	},

	toggleFileManager: function () {
		Vvveb.Gui.togglePanel("#filemanager", "--builder-filemanager-height");
	},

	toggleLeftColumn: function () {
		Vvveb.Gui.togglePanel("#left-panel", "--builder-left-panel-width");
	},

	toggleRightColumn: function (e, rightColumnEnabled = null) {
		if (rightColumnEnabled === null) {
			rightColumnEnabled = Vvveb.Gui.togglePanel("#right-panel", "--builder-right-panel-width");
		} else {
			let panel = document.getElementById("right-panel");
			let body = document.querySelector("body");
			if (!panel || !body) return rightColumnEnabled;
			if (rightColumnEnabled) {
				panel.style.display = "none";
				body.style.setProperty("--builder-right-panel-width", "0px");
			} else {
				panel.style.display = "";
				body.style.setProperty("--builder-right-panel-width", "");
			}
		}

		let builderEl = document.getElementById("vvveb-builder");
		let propsTab = document.querySelector(".component-properties-tab");
		if (rightColumnEnabled) {
			if (builderEl) builderEl.classList.remove("no-right-panel");
			if (propsTab) propsTab.classList.add("d-none");
		} else {
			if (builderEl) builderEl.classList.add("no-right-panel");
			if (propsTab) propsTab.classList.remove("d-none");
		}

		Vvveb.Components.componentPropertiesElement = (rightColumnEnabled ? "#right-panel" : "#left-panel #properties") + " .component-properties";
		let componentTab = document.querySelector("#components-tab");

		let propertiesPanel = document.getElementById("properties");
		if (propertiesPanel && propertiesPanel.offsetParent && componentTab && typeof bootstrap !== "undefined" && bootstrap.Tab) {
			const bsTab = bootstrap.Tab.getOrCreateInstance(componentTab);
			componentTab.style.display = "";
			if (bsTab) bsTab.show();
		}

	},

	toggleTreeList: function () {
		let treeList = document.getElementById("tree-list");
		if (!treeList) return;
		treeList.classList.toggle("d-none");
		if (!treeList.offsetParent) {
			let toggle = document.getElementById("toggle-tree-list");
			if (toggle) toggle.classList.remove("active");
		}
	},

	treeListRight: function () {
		let treeList = document.getElementById("tree-list");
		if (!treeList) return;
		let btnIcon = document.querySelector("[data-vvveb-action='treeListRight'] i");
		if (treeList.style.height) {
			treeList.style.height = "";
			treeList.style.right = "";
			treeList.style.top = "";
			treeList.style.left = "";
			treeList.style.width = "";
			if (btnIcon) btnIcon.className = "icon-stop-outline";
		} else {
			treeList.style.height = "auto";
			treeList.style.right = "0";
			treeList.style.top = "0";
			treeList.style.left = "auto";
			treeList.style.width = "300px";
			if (btnIcon) btnIcon.className = "icon-remove-outline";
		}
	},

	treeListPanel: function () {
		let treeList = document.getElementById("tree-list");
		let canvas = document.getElementById("canvas");
		let filemanager = document.getElementById("filemanager");
		if (!treeList || !canvas || !filemanager) return;
		let btnIcon = document.querySelector("[data-vvveb-action='treeListPanel'] i");

		if (treeList.parentNode == canvas) {
			filemanager.after(treeList);
			if (btnIcon) btnIcon.className = "icon-chevron-forward-outline";
		} else {
			canvas.append(treeList);
			if (btnIcon) btnIcon.className = "icon-chevron-back-outline";
		}
	},

	zoomChange: function () {
		let wrapper = document.getElementById("iframe-wrapper");
		if (!wrapper) return;
		let zoom = parseFloat(this && this.value);
		if (isNaN(zoom) || !zoom) zoom = 100;
		let scale = "";
		let height = "";
		if (zoom != 100) {
			scale = "scale(" + zoom + "%)";
			height = ((100 / zoom) * 100) + "%";
		}
		wrapper.style.transform = scale;
		wrapper.style.height = height;
	},

	setState: function () {
		Vvveb.StyleManager.setState(this.value);
		Vvveb.Builder.reloadComponent();
	},

	setTheme: function () {
		Vvveb.StyleManager.setTheme(this.value);
		if (Vvveb.StyleManager.themeMode == "attribute") {
			let frameDoc = Vvveb.Builder.documentFrame && Vvveb.Builder.documentFrame.contentDocument;
			let html = frameDoc ? frameDoc.querySelector("html") : null;
			if (html) html.setAttribute("data-bs-theme", this.value);
		}
		Vvveb.Builder.reloadComponent();
	}
}
Vvveb.StyleManager = {

	styles: {},
	cssContainer: false,
	_preservedAtRules: [],
	breakpoints: {
		'sm': '575.98px',
		'md': '767.98px',
		'lg': '991.98px',
		'xl': '1199.98px',
		'xxl': '1399.98px'
	},
	doc: false,
	inlineCSS: false,
	currentElement: null,
	currentSelector: null,
	currentSelectorIdClass: null,
	currentBreakpoint: "none",
	currentMedia: "none",
	state: "",//hover, active etc
	themeMode: "attribute",//attribute, media
	theme: null,

	init: function (doc) {
		if (doc) {
			this.doc = doc;

			let style = false;
			let _style = false;

			//check if editor style is present
			for (let i = 0; i < doc.styleSheets.length; i++) {
				_style = doc.styleSheets[i];
				if (_style.ownerNode && _style.ownerNode.id == "vvvebjs-styles") {
					style = _style.ownerNode;
					break;
				}
			}

			document.getElementById("vvvebjs-styles-css")?.remove();

			//a new document may have different nodes so drop cached selector
			this.currentElement = null;
			this.currentSelector = null;
			this.currentSelectorIdClass = null;

			//if style element does not exist create it
			if (!style) {
				style = generateElements('<style id="vvvebjs-styles"></style>')[0];
				if (!style && typeof doc.createElement === "function") {
					style = doc.createElement("style");
					style.id = "vvvebjs-styles";
				}
				if (!style) return this.cssContainer;
				doc.head.append(style);
				this.cssContainer = style;
				//fresh sheet, drop styles parsed from the previous document
				this.styles = {};
				this._preservedAtRules = [];
				return this.cssContainer;
			}

			//if it exists
			this.cssContainer = style;
			this.loadCss();

			return this.cssContainer;
		}
	},

	loadCss: function () {
		//reset previous state so deleted selectors don't linger after reload
		this.styles = {};
		this._preservedAtRules = [];

		let rules = null;
		try {
			let sheet = this.cssContainer && this.cssContainer.sheet;
			if (!sheet) return this.styles;
			//may throw SecurityError for cross-origin sheets or be null while parsing
			rules = sheet.cssRules;
			if (!rules) return this.styles;
		} catch (e) {
			console.warn("Vvveb.StyleManager.loadCss: cannot read stylesheet:", e);
			return this.styles;
		}

		for (let j = 0; j < rules.length; j++) {
			let rule = rules[j];
			if (!rule) continue;

			//grouping rule with nested rules (e.g. @media) holds its
			//condition in `media`/`conditionText` instead of selectorText/style
			if (rule.media && rule.cssRules) {
				let media = "";
				try {
					media = rule.media.mediaText || rule.conditionText || "";
				} catch (e) {
					media = "";
				}
				if (!media) media = "none";

				let innerRules = null;
				try {
					innerRules = rule.cssRules;
				} catch (e) {
					console.warn("Vvveb.StyleManager.loadCss: cannot read @media rules:", e);
				}
				if (!innerRules || !innerRules.length) continue;

				for (let k = 0; k < innerRules.length; k++) {
					let inner = innerRules[k];
					if (!inner) continue;
					if (inner.selectorText && inner.style) {
						this._addStyleRule(media, inner.selectorText, inner.style);
					} else if (inner.cssText) {
						//non-style rule nested in @media (e.g. @supports/@keyframes):
						//keep the media context so it still applies after regenerate
						this._preservedAtRules.push(`@media ${media} {\n${inner.cssText}\n}`);
					}
				}
			} else if (rule.selectorText && rule.style) {
				this._addStyleRule("none", rule.selectorText, rule.style);
			} else if (rule.cssText) {
				//at-rules without selector/style (@font-face, @keyframes,
				//@supports, @layer, @import, ...) are kept verbatim so a
				//later generateCss() doesn't silently delete them
				this._preservedAtRules.push(rule.cssText);
			}
		}

		return this.styles;
	},

	_addStyleRule: function (media, selectorText, styleDecl) {
		let selector = (selectorText || "").trim();
		if (!selector || !styleDecl || !styleDecl.length) return;

		this.styles[media] = this.styles[media] ?? {};
		let props = this.styles[media][selector] ?? (this.styles[media][selector] = {});

		for (let k = 0; k < styleDecl.length; k++) {
			let property = typeof styleDecl.item === "function" ? styleDecl.item(k) : styleDecl[k];
			if (!property) continue;
			let value = typeof styleDecl.getPropertyValue === "function" ? styleDecl.getPropertyValue(property) : styleDecl[property];
			if (value == null || value === "") {
				//shorthand or engine quirk fallback, skip truly empty values
				value = styleDecl[property];
				if (value == null || value === "") continue;
			}
			let priority = typeof styleDecl.getPropertyPriority === "function" ? styleDecl.getPropertyPriority(property) : "";
			props[property] = priority ? `${value} !${priority}` : value;
		}

		//don't keep empty blocks (e.g. rules made only of comments)
		if (Object.keys(props).length === 0) {
			delete this.styles[media][selector];
		}
	},

	getSelectorForElement: function (element) {
		if (!element) return '';

		let currentElement = element;
		let selector = [];

		while (currentElement.parentElement) {
			let elementSelector = "";
			let classSelector = Array.from(currentElement.classList).filter(function (className) {
				return className && Vvveb.Builder.ignoreClasses.indexOf(className) == -1;
			}).map(function (className) {
				//escape classes like Tailwind's `w-1/2` or `md:flex` into valid selectors
				return "." + (typeof CSS !== "undefined" && CSS.escape ? CSS.escape(className) : className.replace(/([^a-zA-Z0-9_-])/g, "\\$&"));
			}).join("");

			//element (tag) selector
			let tag = (currentElement.tagName || "").toLowerCase();
			if (!tag) break;
			//exclude top most element body unless the parent element is body
			if (tag == "body" && selector.length > 1) {
				break;
			}

			//stop at a unique element (with id)
			if (currentElement.id) {
				let id = (typeof CSS !== "undefined" && CSS.escape) ? CSS.escape(currentElement.id) : String(currentElement.id).replace(/([^a-zA-Z0-9_-])/g, "\\$&");
				elementSelector = "#" + id;
				selector.push(elementSelector);
				break;
			} else if (classSelector) {
				//class selector
				elementSelector = classSelector;
			} else {
				//element selector
				elementSelector = tag
			}

			if (elementSelector) {
				selector.push(elementSelector);
			}

			currentElement = currentElement.parentElement;
		}

		let selectorText = selector.reverse().join(" > ");

		if (this.themeMode == "attribute" && (this.theme == "light" || this.theme == "dark")) {
			selectorText = `[data-bs-theme="${this.theme}"] ` + selectorText;
		}
			
		return selectorText;
	},

	setState: function (state) {
		this.state = state;
	},

	setTheme: function (theme = null) {//light, dark, null
		this.theme = theme;
		//theme is baked into cached selectors (attribute mode), drop stale cache
		this.currentElement = null;
		this.currentSelector = null;
		this.currentSelectorIdClass = null;
		this.setMedia();
	},

	setMedia: function (media = null) {
		if (!media) {
			media = "";
			
			if (this.themeMode == "media" && (this.theme == "light" || this.theme == "dark")) {
				media = `(prefers-color-scheme: ${this.theme})`;
			}

			if (this.currentBreakpoint != "none") {
				let width = this.breakpoints[this.currentBreakpoint];
				if (width) {
					if (media) {
						media += " and ";
					}

					media += `(max-width: ${width})`;
				}
			}
		}

		if (!media) {
			media = "none";
		}
		
		this.currentMedia = media;
	},
	
	addSelectorState: function (selector) {
		if (!selector) return selector;
		return selector + (this.state ? ":" + this.state : "");
	},

	setStyle: function (element, styleProp, value) {
		let selector;

		if (typeof (element) == "string") {
			selector = element;
		} else {
			let node = element;

			//if property is set with inline style attribute then override it and don't save to css
			//inline text editor sets properties like font-size inline that can't be later overriten from css
			//use getPropertyValue/setProperty so hyphenated names ("font-size") work
			let inlineValue = node && node.style && typeof node.style.getPropertyValue === "function" ?
				node.style.getPropertyValue(styleProp) : (node && node.style ? node.style[styleProp] : "");
			if (inlineValue) {
				if (typeof node.style.setProperty === "function") {
					node.style.setProperty(styleProp, value);
				} else {
					node.style[styleProp] = value;
				}
				return element;
			}

			selector = this.getSelectorForElement(node);
		}

		if (!selector) return element;

		if (this.inlineCSS) {
			//inline mode needs a real element, not a selector string
			if (typeof (element) == "string" || !element.style) return element;
			if (typeof element.style.setProperty === "function") {
				element.style.setProperty(styleProp, value);
			} else {
				element.style[styleProp] = value;
			}
			return element;
		}

		selector = this.addSelectorState(selector);

		const media = this.currentMedia;

		//styles[media][selector][styleProp] = value
		if (!this.styles[media]) {
			this.styles[media] = {};
		}
		if (!this.styles[media][selector]) {
			this.styles[media][selector] = {};
		}
		if (value == null || value === "") {
			//empty value unsets the property instead of writing invalid `prop: ;`
			delete this.styles[media][selector][styleProp];
			if (Object.keys(this.styles[media][selector]).length === 0) {
				delete this.styles[media][selector];
			}
			if (Object.keys(this.styles[media]).length === 0) {
				delete this.styles[media];
			}
		} else {
			this.styles[media][selector][styleProp] = value;
		}

		this.generateCss();

		window.dispatchEvent(new CustomEvent("Vvveb.StyleManager.setStyle", { detail: { element, styleProp, value } }));

		return element;
	},

	setCss: function (css) {
		if (!this.cssContainer) return this.styles;
		this.cssContainer.innerHTML = css;
		this.loadCss();
		return this.styles;
	},

	/**
	 * Returns the current CSS content from the style element.
	 *
	 * @returns {string} Raw CSS text.
	 */
	getCss: function () {
		if (!this.cssContainer) return "";
		return this.cssContainer.innerHTML;
	},

	/**
	 * Generates a full CSS string from the styles map and writes to the stylesheet.
	 *
	 * @returns {string} The generated CSS text.
	 */
	generateCss: function () {
		//refresh container element to avoid issues with changes from code editor
		if (this.doc) {
			this.cssContainer = this.doc.getElementById("vvvebjs-styles") || this.cssContainer;
		}
		if (!this.cssContainer) return "";

		let css = "";
		//re-emit at-rules loadCss() preserved verbatim (@font-face, @keyframes, ...)
		if (this._preservedAtRules && this._preservedAtRules.length) {
			css += this._preservedAtRules.join("\n\n") + "\n\n";
		}
		for (let media in this.styles) {
			let selectors = this.styles[media];
			if (!selectors || Object.keys(selectors).length === 0) continue;
			let indent = "";
			if (media !== "none") {
				css += `@media ${media} {\n\n`;
				indent = `\t`;
			}
			for (let selector in selectors) {
				let props = selectors[selector];
				if (!props || Object.keys(props).length === 0) continue;
				css += indent + `${selector} {\n`;
				for (let property in props) {
					const value = props[property];
					css += indent + `\t${property}: ${value};\n`;
				}
				css += indent + '}\n\n';
			}
			if (media !== "none") {
				css += `}\n\n`;
			}
		}

		return this.cssContainer.innerHTML = css;
	},


	_getCssStyle: function (element, styleProp) {
		if (!element || !styleProp) return "";

		let value = "", el, selector, media;

		el = element;
		//cached selector goes stale when the element's id/classes change
		//(e.g. class edited from the properties panel), so key on them too
		let idClass = (el.id || "") + "\0" + (el.className != null ? String(el.className) : "");
		if (el != this.currentElement || idClass != this.currentSelectorIdClass) {
			selector = this.getSelectorForElement(el);
			this.currentElement = el;
			this.currentSelector = selector;
			this.currentSelectorIdClass = idClass;
		} else {
			selector = this.currentSelector;
		}

		selector = this.addSelectorState(selector);
		media = this.currentMedia;

		let inlineValue = el.style && typeof el.style.getPropertyValue === "function" ?
			el.style.getPropertyValue(styleProp) : (el.style ? el.style[styleProp] : "");
		if (inlineValue) {//check inline
			value = inlineValue;
		} else if (this.styles[media] !== undefined && this.styles[media][selector] !== undefined && this.styles[media][selector][styleProp] !== undefined) {//check defined css
			value = this.styles[media][selector][styleProp];
		} else {
			//use the element's own document, it lives in the iframe not the builder page
			let view = el.ownerDocument && el.ownerDocument.defaultView;
			if (view && typeof view.getComputedStyle === "function") {
				try {
					value = view.getComputedStyle(el, null).getPropertyValue(styleProp);
				} catch (e) {
					value = "";
				}
			}
		}

		return value;
	},

	getStyle: function (element, styleProp) {
		return this._getCssStyle(element, styleProp);
	}
}

Vvveb.ContentManager = {
	getAttr: function (element, attrName) {
		if (!element || typeof element.getAttribute !== "function" || !attrName) return null;
		return element.getAttribute(attrName);
	},

	setAttr: function (element, attrName, value) {
		if (!element || !attrName) return element;
		if (value == null) {
			if (typeof element.removeAttribute === "function") element.removeAttribute(attrName);
		} else if (typeof element.setAttribute === "function") {
			element.setAttribute(attrName, value);
		}
		return element;
	},

	setHtml: function (element, html, outer = false) {
		if (!element) return element;
		if (outer) {
			//parse in the element's own document so the node needs no cross-document adoption
			let doc = element.ownerDocument || document;
			if (!element.parentNode || typeof doc.createElement !== "function") return element;
			const template = doc.createElement('template');
			template.innerHTML = String(html ?? "");
			let newelement = template.content && template.content.children && template.content.children[0];
			if (!newelement) return element;
			element.parentNode.replaceChild(newelement, element);
			return newelement;
		} else {
			element.innerHTML = html ?? "";
		}

		return element;
	},

	getHtml: function (element, outer = false) {
		if (!element) return "";
		if (outer) {
			return element.outerHTML ?? "";
		} else {
			return element.innerHTML ?? "";
		}
	},

	setText: function (element, text) {
		if (!element) return element;
		element.textContent = text ?? "";
		return element;
	},

	getText: function (element) {
		if (!element) return "";
		return element.textContent ?? "";
	},
};

function getNodeTree(node, parent, allowedComponents, idToNode = {}) {

	function getNodeTreeTraverse(node, parent, id = '') {

		if (node.hasChildNodes()) {
			for (let j = 0; j < node.childNodes.length; j++) {

				const child = node.childNodes[j];

				//skip text and comments nodes
				if (child.nodeType == 3 || child.nodeType == 8) {
					continue;
				}

				let element;
				let matchChild;
				if (child && child["attributes"] != undefined &&
					(matchChild = Vvveb.Components.matchNode(child))) {

					if (Array.isArray(allowedComponents)
						&& allowedComponents.indexOf(matchChild.type) == -1) {

						element = getNodeTreeTraverse(child, parent);
						continue;
					}

					let title;
					for (const attr of ["id", "title", "aria-label"]) {
						title = child.getAttribute(attr);
						if (title) {
							break;
						}
					}

					element = {
						name: matchChild.name,
						image: matchChild.image,
						type: matchChild.type,
						title,
						node: child,
						id: id + '-' + j,
						children: []
					};

					element.children = [];
					parent.push(element);
					idToNode[id + '-' + j] = child;

					element = getNodeTreeTraverse(child, element.children, id + '-' + j);
				} else {
					element = getNodeTreeTraverse(child, parent, id + '-' + j);
				}
			}
		}

		return false;
	}

	getNodeTreeTraverse(node, parent, '1');
}

function drawComponentsTree(tree) {
	let j = 1;
	let prefix = Math.floor(Math.random() * 100);

	function drawComponentsTreeTraverse(tree) {
		let list = document.createElement("ol");
		j++;

		for (let i in tree) {
			let node = tree[i];
			let id = node.id;
			let li;

			if (!id) {
				id = prefix + '-' + j + '-' + i;
			}

			let title = (node.title ? friendlyName(node.title.substring(0, 21)) : "");
			if (title) {
				title = ` - <span class="text-secondary">${title}</span>`;
			}

			const componentName = node.name;
			const componentImage = Vvveb.imgBaseUrl + node.image;

			if (tree[i].children.length > 0) {
				li = generateElements('<li data-component="' + componentName + '">\
								<label for="id' + id + '" style="background-image:url(' + componentImage + ')">\
									<span>' + componentName + '</span>' + title + '\
								</label>\
								<input type="checkbox" id="id' + id + '">\
							</li>')[0];
				li.append(drawComponentsTreeTraverse(node.children));
			}
			else {
				li = generateElements('<li data-component="' + componentName + '" class="file">\
							<label for="id' + id + '" style="background-image:url(' + componentImage + ')">\
								<span>' + componentName + '</span>' + title + '\
							</label>\
							<input type="checkbox" id="id' + id + '">\
							</li>')[0];
			}

			li._treeNode = node.node;
			list.append(li);
		}

		return list;
	}

	return drawComponentsTreeTraverse(tree);
}


let selected = null;
let dragover = null;

Vvveb.SectionList = {

	selector: '.sections-container',
	allowedComponents: {},

	init: function (allowedComponents = {}) {

		this.allowedComponents = allowedComponents;

		document.querySelector(this.selector).addEventListener("click", function (e) {
			let element = e.target.closest(":scope > div .controls");
			if (element) {
				let node = element.parentNode._node;
				if (node) {
					node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
					//node.click();
					Vvveb.Builder.selectNode(node);
					Vvveb.Builder.loadNodeComponent(node);
				}
			}
		});

		document.querySelector(this.selector).addEventListener("dblclick", function (e) {
			let element = e.target.closest(":scope > div");
			if (element) {
				const node = element._node;
				node.click();
			}
		});


		document.querySelector(this.selector).addEventListener("click", function (e) {
			let element = e.target.closest("li[data-component] label");
			if (element) {
				let node = element.parentNode._node;
				if (node) {
					node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
					node.click();
				}
			}
		});

		document.querySelector(this.selector).addEventListener("mouseenter", function (e) {
			let element = e.target.closest("li[data-component] label");
			if (element) {
				const node = element.parentNode._node;
				node.style.outline = "1px dashed blue";
			}
		});

		document.querySelector(this.selector).addEventListener("mouseleave", function (e) {
			let element = e.target.closest("li[data-component] label");
			if (element) {
				const node = element.parentNode._node;
				node.style.outline = "";
				if (node.getAttribute("style") == "") node.removeAttribute("style");
			}
		});

		document.querySelector(this.selector).addEventListener("dragstart", this.dragStart);
		document.querySelector(this.selector).addEventListener("dragover", this.dragOver);
		document.querySelector(this.selector).addEventListener("dragend", this.dragEnd);

		document.querySelector(this.selector).addEventListener("click", function (e) {
			let element = e.target.closest(".delete-btn");
			if (element) {
				let section = element.closest(".section-item");
				let node = section._node;
				node.remove();
				section.remove();
				Vvveb.TreeList.loadComponents();

				e.stopPropagation();
				e.preventDefault();
			}
		});

		let sectionIn;
		let img = document.querySelector(".block-preview img");
		document.querySelector(".sections-list").addEventListener("mouseover", function (e) {
			let element = e.target.closest("li[data-type]");
			if (element) {
				if (sectionIn != element) {
					let src = element.querySelector("img").getAttribute("src");
					sectionIn = element;
					img.setAttribute("src", src);
					img.style.display = "";
				}
			} else {
				sectionIn = element;
				img.setAttribute("src", "");
				img.style.display = "none";
			}
		})

		/*
		document.querySelector(this.selector).addEventListener("click", ".up-btn", function (e) {
			let section = e.target.closest(".section-item");
			let node = section._node;
			Vvveb.Builder.moveNodeUp(node);
			Vvveb.Builder.moveNodeUp(section);
			
			e.preventDefault();
		});


		document.querySelector(this.selector).addEventListener("click", ".down-btn", function (e) {
			let section = e.target.closest(".section-item");
			let node = section._node;
			Vvveb.Builder.moveNodeDown(node);
			Vvveb.Builder.moveNodeDown(section);
			
			e.preventDefault();
		});
		*/


		let self = this;
		document.querySelector(".sections-list").addEventListener("click", function (e) {
			let element = e.target.closest(".add-section-btn");
			if (element) {
				let item = element.closest("li");
				let section = Vvveb.Sections.get(item.dataset.type);
				let node = generateElements(section.html)[0];
				let sectionType = node.tagName.toLowerCase();
				let afterSection = Vvveb.Builder.frameBody.querySelector(":scope > " + sectionType + ":last-of-type");

				if (afterSection) {
					afterSection.after(node);
				} else {
					if (sectionType == "nav") {
						afterSection = Vvveb.Builder.frameBody.querySelector(":scope > nav:first,> header:last-of-type");

						if (afterSection) {
							afterSection.before(node);
						} else {
							Vvveb.Builder.frameBody.append(node);
						}
					} else if (sectionType != "footer") {
						afterSection = Vvveb.Builder.frameBody.querySelector("body > footer:last-of-type");

						if (afterSection) {
							afterSection.before(node);
						} else {
							Vvveb.Builder.frameBody.append(node);
						}
					} else {
						let newsection = Vvveb.Builder.frameBody.querySelector('newsection')
						if (newsection) {
							newsection.before(node);
						} else {
							Vvveb.Builder.frameBody.append(node);
						}
					}
				}

				//node.click();

				Vvveb.Server.renderComponents(node);
				Vvveb.Builder.selectNode(node);
				Vvveb.Builder.loadNodeComponent(node);
				/*
				Vvveb.Builder.frameHtml.animate({
					scrollTop: node.offset().top
				}, 1000);
				
				delay(() => node.click(), 1000);
				*/

				Vvveb.Undo.addMutation({
					type: 'childList',
					target: node.parentNode,
					addedNodes: [node],
					nextSibling: node.nextSibling
				});


				self.loadSections();
				Vvveb.TreeList.loadComponents();
				Vvveb.TreeList.selectComponent(node);
				node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });

				e.preventDefault();
			}
		});

		document.querySelector(this.selector).addEventListener("click", function (e) {
			let element = e.target.closest(".properties-btn");
			if (element) {
				let section = element.closest(".section-item");
				let node = section._node;
				node.click();

				e.preventDefault();
			}
		});

	},

	getSections: function () {
		let sections = [];
		let sectionList =
			window.FrameDocument.body.querySelectorAll(':scope > section, :scope > header, :scope > footer, :scope > main, :scope > nav');

		sectionList.forEach(function (node, i) {
			let id = node.id ? node.id : (node.title ? node.title : node.ariaLabel ?? node.className);
			if (!id) {
				id = 'section-' + Math.floor(Math.random() * 1000);
			}
			let section = {
				name: id.replace(/[^\w+]+/g, ' '),
				id: node.id,
				type: node.tagName.toLowerCase(),
				node: node
			};
			sections.push(section);
		});

		return sections;
	},

	loadComponents: function (sectionListItem, section, allowedComponents = {}) {

		let tree = [];
		getNodeTree(section, tree, allowedComponents);

		let html = drawComponentsTree(tree);
		document.querySelector("ol", sectionListItem).replaceWith(html);
	},


	addSection: function (data) {
		let section = generateElements(tmpl("vvveb-section", data))[0];
		section._node = data.node;
		document.querySelector(this.selector).append(section);

		//this.loadComponents(section, data.node, this.allowedComponents);
	},

	loadSections: function () {
		let sections = this.getSections();
		let container = document.querySelector(this.selector);

		container.replaceChildren();
		for (let i in sections) {
			this.addSection(sections[i]);
		}

	},

	//drag and drop 
	dragOver: function (e) {
		let element = e.target.closest("div");
		if (element) {
			if (e.target != dragover &&
				e.target.className == "section-item") {

				if (dragover) {
					dragover.classList.remove("drag-over");
				}

				dragover = e.target;
				dragover.classList.add("drag-over");
			}
		}
	},

	dragEnd: function (e) {
		let element = e.target.closest("div");
		if (element) {

			if (dragover) {
				let parent = selected.parentNode;
				let selectedNode = selected._node;
				let replaceNode = dragover._node;

				if ((dragover.offsetTop > selected.offsetTop)) {
					//replace section item list
					parent.insertBefore(selected, dragover.nextElementSibling);
					//replace section
					replaceNode.parentNode.insertBefore(selectedNode, replaceNode.nextElementSibling);
				} else {
					//replace section item list
					parent.insertBefore(selected, dragover);
					//replace section
					replaceNode.parentNode.insertBefore(selectedNode, replaceNode);
				}

				dragover.classList.remove("drag-over");

				let node = selectedNode;

				Vvveb.Undo.addMutation({
					type: 'move',
					target: node,
					oldParent: node.parentNode,
					oldNextSibling: node.nextSibling
				});
			}

			selected = null;
			dragover = null;
		}
	},

	dragStart: function (e) {
		let element = e.target.closest("div");
		if (element) {
			selected = e.target
		}
	},
}

Vvveb.StyleList = {

	selector: '.sections-container',
	allowedComponents: {},

	applyStyle: function (style) {
		let doc = window.FrameDocument;
		let themeStyle = doc.getElementById("vvvebjs-theme-style");

		if (!style) {
			if (themeStyle) themeStyle.remove();
			return;
		}

		//if style element does not exist create it			
		if (!themeStyle) {
			themeStyle = doc.createElement("link");
			themeStyle.id = "vvvebjs-theme-style";
			themeStyle.rel = "stylesheet";
			themeStyle.media = "screen";
			doc.head.append(themeStyle);
		}

		themeStyle.href = style;
	},

	load: function (doc) {
		let themeStyle = window.FrameDocument.getElementById("vvvebjs-theme-style");
		if (themeStyle) {
			let style = Vvveb.Styles.getByStyle(themeStyle.getAttribute("href"));
			if (style) {
				document.querySelector('.styles-list [data-type="' + style + '"]')?.classList.add("active");
			}
		}
	},

	init: function () {

		let self = this;
		document.querySelector(".styles-list").addEventListener("click", function (e) {
			let element = e.target.closest("li");
			if (element) {
				let style = Vvveb.Styles.get(element.dataset.type);

				if (element.classList.contains("active")) {
					element.classList.remove("active");
					style = "";//if already active remove current style
				} else {
					document.querySelector(".styles-list .active")?.classList.remove("active");
					element.classList.add("active");
				}

				self.applyStyle(style.style);

				//console.log(style);

				e.preventDefault();
			}
		});

	}
}

Vvveb.TreeList = {
	selector: '#tree-list',

	container: null,

	tree: [],

	idToNode: {},

	init: function () {
		// header move
		this.container = document.querySelector(this.selector);
		let header = this.container.querySelector(".header");
		let isDown = false;
		let offset = [0, 0];
		let self = this;

		header.addEventListener('mousedown', function (e) {
			if (e.which == 1) {//left click
				isDown = true;
				offset = [
					self.container.offsetLeft - e.clientX,
					self.container.offsetTop - e.clientY
				];
			}
		}, true);

		document.addEventListener('mouseup', function () {
			isDown = false;
		}, true);

		document.addEventListener('mousemove', function (event) {
			if (isDown) {
				event.preventDefault();
				let left = Math.max(event.clientX + offset[0], 0);
				let top = Math.max(event.clientY + offset[1], 0);

				if (left >= 0 && (left < (window.innerWidth - self.container.clientWidth))) self.container.style.left = left + "px";
				if (top >= 0 && (top < (window.innerHeight - self.container.clientHeight))) self.container.style.top = top + "px";
			}
		});

		document.querySelector(this.selector).addEventListener("click", function (e) {
			let element = e.target.closest("li[data-component] label");
			if (element) {
				const node = element.parentNode._treeNode;
				node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
				//node.click();
				Vvveb.Builder.selectNode(node);
				Vvveb.Builder.loadNodeComponent(node);

				document.querySelector(self.selector + " .active")?.classList.remove("active");
				element.querySelector("label").classList.add("active");
			}
		})

		document.querySelector(this.selector).addEventListener("mousemove", function (e) {
			let element = e.target.closest("li[data-component]");
			if (element) {
				const node = element._treeNode;

				node.dispatchEvent(new MouseEvent("mousemove", {
					bubbles: true,
					cancelable: true,
				}));
			}
		})
	},

	selectComponent: function (node) {
		let id;
		for (const i in this.idToNode) {
			if (node == this.idToNode[i]) {
				id = i;
				break;
			}
		}

		if (id) {
			let element = document.getElementById("id" + id);

			this.container.querySelector(".active")?.classList.remove("active");
			//collapse all 
			let checkboxes = this.container.querySelectorAll("input[type=checkbox]:checked");
			for (let i = 0, len = checkboxes.length; i < len; i++) {
				checkboxes[i].checked = false;
				let label = checkboxes[i].labels[0];
				if (label) {
					label.classList.remove("active");
				}
			}

			//expand parents
			if (element) {
				let parent = element;
				let current = element;
				while (parent = current.closest("li")) {
					current = parent.parentNode;
					let input = parent.querySelector("input");
					if (input && input.hasAttribute("type") && input.type == "checkbox") {
						input.checked = true;
					}
				}

				element.checked = true;
				element.labels[0].classList.add("active");
				element.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
			}
		}

		return false;
	},

	loadComponents: function () {
		let list = this.container.querySelector(".tree > ol");
		//if navigator not visible don't load
		if (list.offsetParent === null) return;

		this.tree = [];
		this.idToNode = {};
		getNodeTree(window.FrameDocument.body, this.tree, {}, this.idToNode);

		let ol = drawComponentsTree(this.tree);
		list.replaceWith(ol);
		//list.replaceWith(html);
	},
}

Vvveb.FileManager = {
	tree: false,
	_init: false,
	pages: {},
	currentPage: false,
	allowedComponents: {},

	init: function (allowedComponents = {}) {

		this.allowedComponents = allowedComponents;
		this.tree = document.querySelector("#filemanager .tree > ol");
		if (!this.tree) return;
		this.tree.replaceChildren();
		//attach handlers once, re-calling init only clears the tree and updates the filter
		if (this._init) return;
		this._init = true;

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest("a");
			if (element) {
				e.stopImmediatePropagation();
				if (element.classList.contains('view')) return;
				e.preventDefault();
				return false;
			}
		});

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest(".delete");
			if (element) {
				Vvveb.FileManager.deletePage(element.closest("li"), e);
				e.stopImmediatePropagation();
				e.preventDefault();
				return false;
			}
		});

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest(".rename");
			if (element) {
				Vvveb.FileManager.renamePage(element.closest("li"), e, false);
				e.stopImmediatePropagation();
				e.preventDefault();
				return false;
			}
		});

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest(".duplicate");
			if (element) {
				Vvveb.FileManager.renamePage(element.closest("li"), e, true);
				e.stopImmediatePropagation();
				e.preventDefault();
				return false;
			}
		});

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest ? e.target.closest("li[data-page] label") : null;
			if (element) {
				let page = element.parentNode.dataset.page;
				if (page) {
					Vvveb.FileManager.loadPage(page, Vvveb.FileManager.allowedComponents);

					let data = Vvveb.FileManager.pages[page] || {};

					let pageUrl = window.location.pathname + "?module=editor/page-builder&template=" + encodeURIComponent(data["file"] ?? "") + "&url=" + encodeURIComponent(data["url"] ?? "") + "&name=" + encodeURIComponent(data["title"] ?? "");
					if (data["post_id"]) {
						pageUrl += "&post_id=" + encodeURIComponent(data["post_id"]);
					}
					if (data["product_id"]) {
						pageUrl += "&product_id=" + encodeURIComponent(data["product_id"]);
					}

					window.history.pushState({ name: page }, "", pageUrl);
				}

				e.preventDefault();
				return false;
			}
		});

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest ? e.target.closest("li[data-component] label") : null;
			if (element) {
				let li = element.closest("li[data-component]");
				const node = li && (li._treeNode || li._node);
				if (!node) return;
				node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
				node.click();
			}
		});

		//mouseover bubbles (mouseenter doesn't), so delegate hover from the tree root
		this.tree.addEventListener("mouseover", function (e) {
			let element = e.target.closest ? e.target.closest("li[data-component] label") : null;
			if (element) {
				let li = element.closest("li[data-component]");
				const node = li && (li._treeNode || li._node);
				if (!node) return;

				node.dispatchEvent(new MouseEvent("mousemove", {
					bubbles: true,
					cancelable: true,
				}));
			}
		});
	},

	clear: function () {
		this.pages = {};
		this.currentPage = false;
		if (this.tree) this.tree.replaceChildren();
	},
	
	/**
	 * Returns page list
	 */
	getPages: function () {
		return this.pages;
	},

	deletePage: function (element, e) {
		if (!element) return;
		let page = element.dataset;
		let post_id = Number(element.dataset.post_id ?? 0) || 0;
		let name;
		let _self = this;

		if (!page || !page.page || !this.pages[page.page]) return;

		if (post_id) {
			name = element.querySelector('label span')?.textContent ?? page.file;
		} else {
			name = page.file;
		}

		if (confirm(`Are you sure you want to delete "${name}"?`)) {

			let event = new CustomEvent("Vvveb.FileManager.deletePage", {
				detail: { ...this.pages[page.page], element },
				cancelable: true
			});
			//allow listeners to veto deletion by preventing default
			window.dispatchEvent(event);
			if (event.defaultPrevented) return;

			fetch(deleteFileUrl, { method: "POST", body: new URLSearchParams({ file: page.file, post_id, csrf: document.getElementById('csrf')?.value ?? "" }) })
				.then((response) => {
					if (!response.ok) { return Promise.reject(response); }
					return response.json()
				})
				.then((data) => {
					let bg = "success";
					if (data.success) {
						document.querySelectorAll("#top-panel .save-btn").forEach(e => e.setAttribute("disabled", "true"));
						//drop map entry and selection only after the server confirms,
						//otherwise currentPage keeps pointing at a deleted page
						delete _self.pages[page.page];
						if (_self.currentPage === page.page) _self.currentPage = false;
						element.remove();
					} else {
						bg = "danger";
					}

					displayToast(bg, "Delete", data.message ?? data);
				})
					.catch(error => {
						let message = error?.statusText ?? "Error deleting page!";
						displayToast("danger", "Error", message);

						if (typeof error?.text === 'function') error.text().then(errorMessage => {
							let message = errorMessage.substring(0, 200);
							displayToast("danger", "Error", message);
						})
					});
		}
	},

	renamePage: function (element, e, duplicate = false) {
		if (!element) return;
		let page = element.dataset;
		let post_id = Number(element.dataset.post_id ?? 0) || 0;
		let product_id = Number(element.dataset.product_id ?? 0) || 0;
		let newfile;
		let name;
		let _self = this;

		let label = element.querySelector('label span')?.textContent ?? "";
		name = label.replace(/\.html$/, '');
		if (post_id || product_id) {
			name = prompt(`Enter new name for "${name}"`, name);
			if (name) {
				newfile = page.file;
			}
		} else {
			name = newfile = prompt(`Enter new file name for "${page.file}"`, name.toLowerCase());
		}

		if (newfile && name) {
			let event = new CustomEvent("Vvveb.FileManager.renamePage", {
				detail: { page, newfile, element },
				cancelable: true
			});
			//allow event to change newfile or cancel by preventing default
			window.dispatchEvent(event);
			if (event.defaultPrevented) return;
			newfile = event.detail.newfile ?? newfile;

			if (page) {

				fetch(renameFileUrl, { method: "POST", body: new URLSearchParams({ file: page.file, newfile: newfile, name, duplicate, post_id, product_id, csrf: document.getElementById('csrf')?.value ?? "" }) })
					.then((response) => {
						if (!response.ok) { return Promise.reject(response); }
						return response.json()
					})
					.then((data) => {
						let bg = "success";
						if (data.success) {
							//document.querySelectorAll("#top-panel .save-btn").forEach(e => e.setAttribute("disabled", "true"));
						} else {
							bg = "danger";
						}

						newfile = data.newfile ?? newfile;
						displayToast(bg, "Rename", data.message ?? data);
						if (!data.success) return;
						let entry = _self.pages[page.page];
						if (!entry) return;
						let baseName = newfile.replace(/\.html$/, '');
						let newName = data.name ?? name ?? friendlyName(String(newfile).replace(/.*[\/\\]+/, '')).replace(/\.html$/, '');

						if (duplicate) {
							//clone so source and copy don't share one data object
							let addPage = { ...entry };
							addPage["name"] = data.name ?? baseName;
							addPage["file"] = newfile;
							addPage["title"] = newName;
							addPage["url"] = data.url ?? addPage["url"];
							addPage["post_id"] = data.post_id ?? addPage["post_id"];
							addPage["product_id"] = data.product_id ?? addPage["product_id"];

							if (data.newfile) {
								//addPage["url"] = Vvveb.themeBaseUrl + addPage["url"]
								//addPage["url"] =  page.url.substring(0, page.url.lastIndexOf("/") + 1) + addPage["url"];
							}

							let newPage = Vvveb.FileManager.addPage(baseName, addPage, page.page);
							if (newPage) Vvveb.FileManager.scrollToPage(newPage);
						} else {
							entry["file"] = newfile;
							entry["title"] = newName;
							page.url = data.url ?? (page.url ?? "").replace(page.file, newfile);
							page.file = newfile;
							let labelEl = element.querySelector(":scope > label span");
							if (labelEl) labelEl.textContent = newName;
							let view = element.querySelector(":scope > label a.view");
							if (view) view.setAttribute("href", page.url);
							entry["url"] = page.url;
							entry["file"] = page.file;
						}
					})
					.catch(error => {
						let message = error?.statusText ?? "Error renaming page!";
						displayToast("danger", "Error", message);

						if (typeof error?.text === 'function') error.text().then(errorMessage => {
							let message = errorMessage.substring(0, 200);
							displayToast("danger", "Error", message);
						})
					});
			}
		}
	},

	escSelector: function (value) {
		value = String(value ?? "");
		return (typeof CSS !== "undefined" && CSS.escape) ? CSS.escape(value) : value.replace(/([^a-zA-Z0-9_-])/g, "\\$&");
	},

	addPage: function (name, data, afterPage = false, append = true) {
		let event = new CustomEvent("Vvveb.FileManager.addPage", {
			detail: { name, data },
			cancelable: true
		});
		//allow event to change name or cancel by preventing default (or setting name to false)
		window.dispatchEvent(event);
		if (event.defaultPrevented) return false;
		name = event.detail.name;
		data = event.detail.data;

		if (!name || !data || typeof data !== "object" || !this.tree) {
			return false;
		}

		this.pages[name] = data;
		data['name'] = name;

		let folder = this.tree;
		if (data.folder && data.folder != "/") {
			let folderLi = folder.querySelector('li[data-folder="' + this.escSelector(data.folder) + '"]');
			if (!folderLi) {
				data.folderTitle = friendlyName(data.folder);//data.folder[0].toUpperCase() + data.folder.slice(1);
				try {
					folderLi = generateElements(tmpl("vvveb-filemanager-folder", data))[0];
				} catch (e) {
					folderLi = null;
				}
				if (!folderLi) return false;
				this.tree.append(folderLi);
			}

			folder = (folderLi.querySelector ? folderLi.querySelector("ol") : null);
			if (!folder) return false;
		}

		let page = null;
		try {
			page = generateElements(tmpl("vvveb-filemanager-page", data))[0];
		} catch (e) {
			page = null;
		}
		if (!page) return false;
		let afterEl = afterPage && folder.querySelector('[data-page="' + this.escSelector(afterPage) + '"]');
		if (afterEl) {
			afterEl.after(page);
		} else {
			if (append) {
				folder.append(page);
			} else {
				folder.prepend(page);
			}
		}

		return page;
	},

	addPages: function (pages) {
		if (!pages || typeof pages !== "object") return;
		for (const page in pages) {
			if (pages[page] && pages[page]['name']) this.addPage(pages[page]['name'], pages[page]);
		}
	},

	addComponent: function (name, url, title, page) {
		if (!this.tree || !page) return false;
		let list = this.tree.querySelector("[data-page=\"" + this.escSelector(page) + "\"] > ol");
		if (!list) return false;
		//tmpl() returns an html string, parse it before appending
		let node = null;
		try {
			node = generateElements(tmpl("vvveb-filemanager-component", { name: name, url: url, title: title }))[0];
		} catch (e) {
			node = null;
		}
		if (!node) return false;
		list.append(node);
		return node;
	},

	loadComponents: function (allowedComponents = {}) {
		if (!this.tree || !this.currentPage || !window.FrameDocument || !window.FrameDocument.body) return;

		let tree = [];
		getNodeTree(window.FrameDocument.body, tree, allowedComponents);

		let html = drawComponentsTree(tree);
		this.tree.querySelector("[data-page=\"" + this.escSelector(this.currentPage) + "\"] > ol")?.replaceWith(html);
	},

	getCurrentUrl: function () {
		if (this.currentPage && this.pages[this.currentPage]) {
			return this.pages[this.currentPage]['url'];
		}
	},

	getCurrentPage: function () {
		return this.currentPage;
	},

	getPageData: function (key) {
		if (this.currentPage && this.pages[this.currentPage]) {
			if (key) {
				return this.pages[this.currentPage][key];
			} else {
				return this.pages[this.currentPage];
			}
		}
	},


	getCurrentFileName: function () {
		let entry = this.currentPage && this.pages[this.currentPage];
		if (!entry) return;
		let folder = entry['folder'] ?? '';
		if (folder && !folder.endsWith('/')) folder += '/';
		return folder + entry['file'];
	},

	reloadCurrentPage: function () {
		if (this.currentPage)
			return this.loadPage(this.currentPage);
	},

	loadPage: function (name, allowedComponents = false, disableCache = true, loadComponents = false) {
		let entry = this.pages[name];
		if (!entry || !this.tree) {
			return;
		}
		let url = entry['url'] ?? "";

		//allow event to change url or cancel by preventing default
		let self = this;
		let event = new CustomEvent("Vvveb.FileManager.loadPage", {
			detail: entry,
			cancelable: true,
		});
		window.dispatchEvent(event);
		if (event.defaultPrevented) {
			return;
		}
		url = entry['url'] ?? url;

		if (!url) {
			return;
		}

		let page = this.tree.querySelector("[data-page=\"" + this.escSelector(name) + "\"]");
		if (!page) return;
		//remove active from current active page
		this.tree.querySelector("[data-page].active")?.classList.remove("active");
		//set loaded page as active
		page.classList.add("active");
		//open parent folder if closed
		page.closest("[data-folder]")?.querySelector("input[type=checkbox]")?.setAttribute("checked", true);

		this.currentPage = name;
		document.querySelector(".btn-preview-url")?.setAttribute("href", url);

		if (url) {
			Vvveb.Builder.loadUrl(url + (disableCache ? (url.indexOf('?') > -1 ? '&r=' : '?r=') + Math.random() : ''),
				function () {
					if (loadComponents) { Vvveb.FileManager.loadComponents(allowedComponents); }
					Vvveb.SectionList.loadSections(allowedComponents);
					Vvveb.TreeList.loadComponents();
					if (window.FrameDocument) Vvveb.StyleManager.init(window.FrameDocument);
					Vvveb.ChangeManager.setOriginalContent();
				});
		}
	},

	scrollToPage: function (page) {
		if (!page || typeof page.scrollIntoView !== "function") return;
		page.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
	},
}

Vvveb.Breadcrumb = {
	tree: false,

	init: function () {
		this.tree = document.querySelector(".breadcrumb-navigator > .breadcrumb");
		this.tree.replaceChildren();

		this.tree.addEventListener("click", function (e) {
			let element = e.target.closest(".breadcrumb-item");
			if (element) {
				let node = element._node;
				if (node) {
					//node.click();
					Vvveb.Builder.selectNode(node);
					Vvveb.Builder.loadNodeComponent(node);
					node.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
				}

				e.preventDefault();
			}
		});

		let currentHoverNode;
		this.tree.addEventListener("mousemove", function (e) {
			if (e.target == currentHoverNode) return;
			currentHoverNode = e.target;

			let element = e.target.closest(".breadcrumb-item");
			if (element) {
				let node = element._node;

				node.dispatchEvent(new MouseEvent("mousemove", {
					bubbles: true,
					cancelable: true,
				}));
			}
		})
	},

	addElement: function (data, element) {
		let li = generateElements(tmpl("vvveb-breadcrumb-navigaton-item", data))[0];
		li._node = element;
		this.tree.prepend(li);
	},

	loadBreadcrumb: function (element) {
		this.tree.replaceChildren();
		let currentElement = element;

		while (currentElement.parentElement) {
			let elementType = Vvveb.Builder._getElementType(currentElement);
			let el = elementType[1].toLowerCase();

			this.addElement({
				"name": el + " " + elementType[0],
				"className": "el-" + el
			}, currentElement);

			currentElement = currentElement.parentElement;
		}
	}
}


Vvveb.Revisions = {

	updateRevisionsDropdown: function (revisions) {
		revisions = revisions || {};
		let dropdown = document.querySelector(".revisions-dropdown");
		let countEl = document.querySelector(".revisions-count");
		if (!dropdown) return;
		let count = Object.keys(revisions).length;
		if (countEl) countEl.textContent = count;
		dropdown.replaceChildren();

		for (let i in revisions) {
			let revision = revisions[i];
			//revision['url'] =  '' + revision['file'] + ".html";
			try {
				let node = generateElements(tmpl('vvveb-revision-item', revision))[0];
				if (node) dropdown.append(node);
			} catch (e) {
				console.error(e);
			}
		}
	},

	loadRevisions: function (template) {
		let self = this;

		if (!template || typeof revisionsUrl === "undefined") return Promise.resolve();

		//ignore out-of-order responses when pages are switched quickly
		let requestId = (this._requestId = (this._requestId || 0) + 1);
		return fetch(revisionsUrl + "&" + new URLSearchParams({ template }))
				.then((response) => {
					if (!response.ok) { throw new Error(response) }
					return response.json()
				})
				.then((data) => {
					if (requestId === self._requestId) self.updateRevisionsDropdown(data);
				})
				.catch(error => {
					console.error(error);
					displayToast("danger", "Error", "Error loading revision!");
				});
	},

	init: function () {
		let self = this;
		if (this._init) return;
		this._init = true;

		//note: Builder.save dispatches "Vvveb.Builder.save" (capital V) with page data;
		//the lowercase post-save event carries the server response, which has no file
		window.addEventListener("Vvveb.Builder.save", function (event) {
			let data = event.detail || {};
			self.loadRevisions(data['file'] ?? false);
		});

		window.addEventListener("Vvveb.FileManager.loadPage", function (event) {
			let data = event.detail || {};
			self.loadRevisions(data['file'] ?? false);
		});

		let dropdown = document.querySelector(".revisions-dropdown");
		if (!dropdown) return;
		dropdown.addEventListener("click", function (event) {
			let element = event.target && event.target.closest ? event.target.closest(".btn-revision-load") : null;
			if (element) {
				let file = element.dataset.file;

				if (file) {
					fetch(revisionLoadUrl, {
						method: "POST",
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: new URLSearchParams({ file, csrf: document.getElementById('csrf')?.value ?? "" })
					}).then((response) => {
							if (!response.ok) { throw new Error(response) }
							return response.text()
						})
						.then((data) => {
							Vvveb.Builder.setHtml(data);
							displayToast("success", "Load", "Revision loaded");

							if (Vvveb.Builder.frameBody) {
								Vvveb.Builder.frameBody.querySelectorAll("[data-aos]").forEach(e => e.classList.add("aos-init", "aos-animate"));
							}
							document.querySelectorAll("#top-panel .save-btn").forEach(e => e.removeAttribute("disabled"));

							Vvveb.SectionList.loadSections();
							Vvveb.TreeList.loadComponents();
							if (window.FrameDocument) Vvveb.StyleManager.init(window.FrameDocument);
							Vvveb.ChangeManager.setOriginalContent();
						})
						.catch(error => {
							console.error(error);
							displayToast("danger", "Error", "Error loading revisions!");
						});
				}
			} else {
				let element = event.target && event.target.closest ? event.target.closest(".btn-revision-delete") : null;
				if (element && confirm("Are you sure?")) {

					let item = element.closest(".dropdown-item");
					let file = element.dataset.file;

					if (file) {
						fetch(revisionDeleteUrl, {
							method: "POST",
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: new URLSearchParams({ file, csrf: document.getElementById('csrf')?.value ?? "" })
						}).then((response) => {
								if (!response.ok) { throw new Error(response) }
								return response.json()
							}).then((data) => {
								let bg = "success";
								if (data.success) {
									if (item) item.remove();
								} else {
									bg = "danger";
								}

								displayToast(bg, "Delete", data.message ?? data);

								let count = document.querySelector(".revisions-count");
								if (count) count.textContent = Math.max(0, (parseInt(count.textContent) || 1) - 1);
							})
							.catch(error => {
								console.error(error);
								displayToast("danger", "Error", "Error deleting revision!");
							});
					}
				}
			}
		});
	}
}

Vvveb.Server = {
	url: "",
	component: "",
	index: 0,
	fullUpdate: false,
	userServerTemplate: false,

	ajax: function (callback) {
		let self = this;
		let element = self.element;
		if (!element || !element.style) return Promise.resolve();
		try {
			this.url = Vvveb.Builder.iframe.contentWindow.location.href;
		} catch (e) {
			this.url = this.url || "";
		}
		if (!this.url) return Promise.resolve();

		//remember inline styles so the loading shimmer below doesn't wipe them
		let oldStyle = typeof element.getAttribute === "function" ? element.getAttribute("style") : null;
		element.style.opacity = "0.85";
		element.style.transition = "min-height 0.25s ease-out";
		element.style.interpolateSize = "allow-keywords";

		let cleanup = function () {
			if (oldStyle == null) {
				element.removeAttribute("style");
			} else {
				element.setAttribute("style", oldStyle);
			}
		};

		let data = new FormData();
		data.append("_component_content", this.content);
		data.append("html", Vvveb.Builder.getHtml());
		data.append("csrf", document.getElementById('csrf')?.value ?? "");
		data.append("user_key", typeof userKey !== "undefined" ? userKey : "");
		data.append("admin_id", typeof adminId !== "undefined" ? adminId : "");

		return fetch(this.url + '&_component_ajax=' + encodeURIComponent(this.component) + '&_component_id=' + encodeURIComponent(this.index) + '&_server_template=' + encodeURIComponent(this.userServerTemplate) + '&r=true', {
			method: 'POST',
			body: data,
			credentials: 'include'
		})
			.then((response) => {
				if (!response.ok) { throw new Error(response.statusText || ("HTTP " + response.status)) }
				return response.text()
			})
			.then((data) => {
				if (data) {

					let newElement = generateElements(data)[0];
					if (!newElement) {
						cleanup();
						if (callback) callback(data);
						return;
					}
					//set fixed height for parent to avoid page flicker
					let parent = element.parentNode;

					if (parent) parent.style.minHeight = parent.clientHeight + "px";
					//full update
					if (self.fullUpdate) {
						element.replaceWith(newElement);
						element = self.element = newElement;
					} else {
						element.innerHTML = newElement.innerHTML;
						element.style.minHeight = element.clientHeight + "px";
					}

					Vvveb.Builder.selectNode(element);

					setTimeout(function () {
						//if (self.fullUpdate) 
						if (parent) parent.style.minHeight = "";
						element.style.minHeight = "";
						//element.click();
						Vvveb.Builder.selectNode(element);
						Vvveb.TreeList.loadComponents();
					}, 500);
				}

				cleanup();

				if (callback) callback(data);
			})
			.catch(error => {
				console.error(error);
				cleanup();
			});
	},

	//render a single component
	renderComponent: function (element, component, index) {
		/*
		if (component) {
			this.component = component;
			this.index     = index;
		} else {
			this.component = element.attributes[0].name.replace("data-v-component-", "");
			let selector = "[" + element.attributes[0] + "]";
			this.index =  Array.prototype.indexOf.call(Vvveb.Builder.frameBody.querySelectorAll(selector), element);
		}*/

		if (!element || !element.attributes) return element;

		let name = null;
		for (let attribute of element.attributes) {
			if (attribute.name.startsWith("data-v-component-")) {
				name = attribute.name.replace("data-v-component-", "");
				break;
			}
		}

		if (!name) return element;

		this.component = name;
		if (typeof index == "undefined" || index == null) {
			let selector = "[data-v-component-" + name + "]";
			let list = Vvveb.Builder.frameBody ? Vvveb.Builder.frameBody.querySelectorAll(selector) : [];
			index = Array.prototype.indexOf.call(list, element);
		}
		this.index = index;

		if (element._serverContent != element.outerHTML) {
			let itemClone = element.cloneNode(true);
			itemClone.querySelectorAll(".vvveb-hidden").forEach(e => e.classList.remove("vvveb-hidden"));

			//per-element content + throttle: one shared timer dropped every
			//component but the last when a page rendered several at once
			element._serverContent = itemClone.outerHTML;
			this.content = element._serverContent;
			this.element = element;

			let self = this;
			clearTimeout(element._serverThrottle);
			element._serverThrottle = setTimeout(function () {
				self.content = element._serverContent;
				self.element = element;
				self.component = name;
				self.index = index;
				self.ajax();
			}, 500);
		}

		return element;
	},

	//search for components inside element and render them all
	renderComponents(element) {
		if (!window.FrameDocument || typeof window.FrameDocument.evaluate !== "function") return [];
		const path = ".//*[ @*[starts-with(name(), 'data-v-component-')] ] | ../*[ @*[starts-with(name(), 'data-v-component-')] ]";
		const context = element ?? window.FrameDocument;
		if (!context) return [];
		const elements = window.FrameDocument.evaluate(path, context, null, XPathResult.ANY_TYPE, null);

		const components = [];
		let currentComponent = elements.iterateNext();
		while (currentComponent) {
			components.push(currentComponent);
			currentComponent = elements.iterateNext();
		}

		for (const component of components) {
			this.renderComponent(component);
		}

		return components;
	}
}

Vvveb.FontsManager = {

	activeFonts: [],
	providers: {},//{"google":GoogleFontsManager};

	addFontList: function (provider, groupName, fontList) {
		let fonts = {};
		let fontNames = [];

		let fontSelect = generateElements("<optgroup label='" + groupName + "'></optgroup>")[0];
		for (const font in fontList) {
			fontNames.push({ "text": font, "value": font, "data-provider": provider });
			let option = new Option(font, font);
			option.dataset.provider = provider;
			//option.style.setProperty("font-family", font);//font preview if the fonts are loaded in editor
			fontSelect.append(option);
		}
		document.getElementById("font-family").append(fontSelect);

		let list = Vvveb.Components.getProperty("_base", "font-family");
		if (list) {
			list.onChange = function (node, value, input, component) {
				let option = input.options[input.selectedIndex];
				Vvveb.FontsManager.addFont(option.dataset.provider, value, node);
				return node;
			};

			list.data.options.push({ optgroup: groupName });
			list.data.options = list.data.options.concat(fontNames);

			Vvveb.Components.updateProperty("_base", "font-family", { data: list.data });

			//update default font list
			fontList = list.data.options;
		}
	},

	addProvider: function (provider, Obj) {
		this.providers[provider] = Obj;
	},

	//add also element so we can keep track of the used fonts to remove unused ones
	addFont: function (provider, fontFamily, element = false) {
		if (!provider) return;

		let providerObj = this.providers[provider];
		if (providerObj) {
			providerObj.addFont(fontFamily);
			this.activeFonts.push({ provider, fontFamily, element });
		}
	},

	removeFont: function (provider, fontFamily) {
		if (!provider) return;

		let providerObj = this.providers[provider];
		if (provider != "default" && providerObj) {
			providerObj.removeFont(fontFamily);
		}
	},

	//check if the added fonts are still used for the elements they were set and remove unused ones
	cleanUnusedFonts: function () {
		for (let i in this.activeFonts) {
			let elementFont = this.activeFonts[i];
			if (elementFont.element) {
				if (Vvveb.StyleManager.getStyle(elementFont.element, 'font-family').replaceAll('"', '') != elementFont.fontFamily) {
					this.removeFont(elementFont.provider, elementFont.fontFamily);
				}
			}
		}
	}
};


Vvveb.ColorPalette = {
	colors: {},

	getAll: function () {
		return this.colors;
	},

	add: function (name, color) {
		this.colors[name] = color;
	},

	remove: function (color) {
		delete this.colors[color];
	},
}

function friendlyName(name) {
	name = name.replaceAll("--bs-", "").replace(/[-_]/g, " ").trim();
	return name = name[0].toUpperCase() + name.slice(1);
}

Vvveb.ColorPaletteManager = {

	cssVars: { "font": {}, "color": {}, "dimensions": {} },

	getType: function (type) {
		return this.cssVars[type];
	},

	getAllCSSVariableNames: function (styleSheets = document.styleSheets, selector) {

		for (let i = 0; i < styleSheets.length; i++) {
			try {
				let cssRules = styleSheets[i].cssRules;
				for (let j = 0; j < cssRules.length; j++) {
					try {
						let style = cssRules[j].style;
						if (selector && cssRules[j].selectorText && cssRules[j].selectorText != selector) continue;
						for (let k = 0; k < style.length; k++) {
							let name = style[k];
							let value = style.getPropertyValue(name).trim();
							let type = "";

							if (name.startsWith("--")) {
								//ignore bootstrap rgb variables
								if (name.endsWith("-rgb")) continue;
								//ignore variables depending on other variables
								if (value.startsWith("var(")) continue;

								let friendlyName = name.replace("--bs-", "").replaceAll("-", " ");

								if (value.startsWith("#")) {
									type = "color";
								} else if (value.indexOf('"') >= 0 || value.indexOf("'") >= 0) {
									type = "font";
								} else if (value.endsWith('em') || value.endsWith('px')) {
									type = "dimensions";
								} else if (!isNaN(parseFloat(value))) {
									type = "dimensions";
								}

								if (type) {
									if (!this.cssVars[type]) this.cssVars[type] = {};
									this.cssVars[type][name] = { value, type, friendlyName };
								}
							}
						}
					} catch (error) { }
				}
			} catch (error) { }
		}

		return this.cssVars;
	},

	getCssWithVars: function (styleSheets = document.styleSheets, vars) {
		let cssVars = {};
		let css = "";
		let cssStyles = "";
		for (let i = 0; i < styleSheets.length; i++) {
			try {
				let cssRules = styleSheets[i].cssRules;
				for (let j = 0; j < cssRules.length; j++) {
					try {
						let style = cssRules[j].style;
						//if (selector && cssRules[j].selectorText && cssRules[j].selectorText != selector) continue;
						cssStyles = "";
						for (let k = 0; k < style.length; k++) {
							let name = style[k];
							let value = style.getPropertyValue(name);
							if (name.startsWith('--bs-btn-')) {
								for (let v in vars) {
									if (value == vars[v]) {
										cssVars[name] = v;
										cssStyles += name + ":var(" + v + ");\n";
									}
								}
							}

						}
						if (cssStyles) {
							css += cssRules[j].selectorText + "{\n"
							css += cssStyles;
							css += "}\n";
						}
					} catch (error) { }
				}
			} catch (error) { }
		}
		return cssVars;
	},

	init: function (document) {
		Vvveb.Components.render("config/bootstrap", "#configuration .component-properties");

		//apply current theme color palette
		//let colors = Vvveb.ColorPaletteManager.getType("color");
		let colors = this.cssVars.color;
		for (const name in colors) {
			let color = colors[name].value;

			if (color[0] == "#" && color.length == 7) {//add only valid hex color values 7 char long
				//add color as name to keep values unique
				Vvveb.ColorPalette.add(color, color);
			}
		}
	},

};

Vvveb.NewSection = {
	container: null,
	template: `<newsection id="vvvebjs-new-section">
			  <div class="container">
				<div class="row">
				  <div class="col">
					<button id="new-blank-section">Blank section</button>
					<button id="new-section">Add section</button>
				  </div>
				</div>
			  </div>
			</newsection>`,

	init: function () {
		this.insert();

		this.container.onmouseout = function (event) {
			Vvveb.Builder.highlightEnabled = true;
		};

		this.container.onmouseover = function (event) {
			Vvveb.Builder.highlightEnabled = false;
		};

		this.container.onclick = function (event) {
			if (event.target.id == 'new-blank-section') {
				Vvveb.Gui.newSection(null, true);
			} else {
				if (event.target.id == 'new-section') {
					Vvveb.Gui.newSection(null, false);
				}
			}
		};
	},

	insert: function () {
		if (Vvveb.Builder.frameDoc.getElementById("vvvebjs-new-section")) return;
		let position = 'before';

		let lastSection = Vvveb.Builder.frameBody.querySelector(':scope > footer:last-of-type');
		if (!lastSection) {
			position = 'after';
			lastSection = Vvveb.Builder.frameBody.querySelector(':scope > main:last-of-type') ||
				Vvveb.Builder.frameBody.querySelector(':scope > section:last-of-type');
		}

		this.container = generateElements(this.template)[0];
		if (lastSection) {
			if (position == 'after') {
				lastSection.after(this.container);
			} else {
				lastSection.before(this.container);
			}
		} else {
			Vvveb.Builder.frameBody.append(this.container)
		}

		return this.container;
	}
}

Vvveb.Config = {
	components: [],
	blocks: [],
	plugins: [],

	load: function (url = 'default.json') {
		fetch(url)
			.then(response => response.json())
			.then(data => {
			});
	}
}

// Toggle fullscreen
function launchFullScreen(document) {
	const el = document.documentElement;

	if (document.fullscreenElement) {
		document.exitFullscreen();
	} else if (el.requestFullscreen) {
		el.requestFullscreen();
	} else if (el.webkitRequestFullscreen) {
		el.webkitRequestFullscreen();
	}
}

let fontList = [{
	value: "",
	text: "Default"
}, {
	value: "Arial, Helvetica, sans-serif",
	text: "Arial"
}];


window.addEventListener("popstate", checkState);

function checkState(e) {
	if (e.state && e.state.name) {
		Vvveb.FileManager.loadPage(e.state.name);
	}
}
