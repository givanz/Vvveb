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

let Input = {

	init: function(name) {
	},

	onChange: function(event, node, input) {
		if (event && event.target) {
			const e = new CustomEvent('propertyChange', { detail: {value : input.value ?? this.value, input: this, origEvent:event} });
			event.currentTarget.dispatchEvent(e);
		}
	},

	renderTemplate: function(name, data) {
		return tmpl("vvveb-input-" + name, data);
	},

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('input');

			if (input) {
				input.value = value;
			}
		}
	},

	render: function(name, data, events) {
		let html = this.renderTemplate(name, data);
		this.element = generateElements(html);
		events = events ?? this.events;

		if (events && this.element[0]) {
			for (const [ev, handler, selector] of events) {
				const fun = this[handler];

				this.element[0].addEventListener(ev, function (ev, selector, fun, target, event) {
					if (event.target.closest(selector)) {
						//target, event, element, input
						return fun.call(event.target, event, target, this);
					}
				}.bind(this, ev, selector, fun, this.element[0]));
			}
		}

		return this.element[0];
	}
};

let TextInput = { ...Input, ...{

	events: [
		//event, listener, child element
		["focusout", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let TextareaInput = { ...Input, ...{

	events: [
		["input", "onChange", "textarea"],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('textarea');

			if (input) {
				input.value = value;
			}
		}
	},

	init: function(data) {
		return this.render("textareainput", data);
	},
  }
}

let CheckboxInput = { ...Input, ...{

	events: [
		["change", "onCheck", "input"],
	],

	onCheck: function(event, node, input) {
		input.value = this.checked;
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		const input = this.element?.[0]?.querySelector('input');
		if (input) {
			input.checked = Boolean(value);
		}
	},

	init: function(data) {
		return this.render("checkboxinput", data);
	},
  }
}

let SelectInput = { ...Input, ...{

	events: [
		["change", "onChange", "select"],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('select');

			if (input) {
				input.value = value;
			}
		}
	},

	init: function(data) {
		return this.render("select", data);
	},
  }
}

let IconSelectInput = { ...SelectInput, ...{

	init: function(data) {
		return this.render("icon-select", data);
	},
  }
}

let HtmlListSelectInput = { ...Input, ...{

	data:{},
	cache:{},

	events: [
		//["click", "onChange", "li"],
		["change", "onListChange", "select"],
		["input", "searchElement", "input.search"],
		["click", "clearSearch", "button.clear-backspace"],
	],

	clearSearch : function(event, element, input) {
		const search = element.querySelector("input.search");
		if (!search) return;

		search.value = "";
		input.searchElement(event, element, input);
		search.dispatchEvent(new KeyboardEvent("keyup", {
			bubbles: true,
			cancelable: true,
		}));
	},

	searchElement : function(event, element, input) {
		const searchText = this.value;

		delay(() => {
			element.querySelectorAll("li").forEach((el) => {
				el.style.display = (!searchText || el.title.indexOf(searchText) > -1) ? "" : "none";
			});
		}, 500);
	},

	onElementClick: function(event, element, input) {
		let data = input.data;
		let svg = this.closest(data.insertElement);
		let value = svg.outerHTML ?? "<svg></svg>";
		input.value = value;
		return input.onChange.call(this, event, element, input);
	},

	onListChange: function(event, element, input) {
		let url = input.data.url ? String(input.data.url).replace('{value}', this.value) : "";

		if (!url) {
			console.warn("Vvveb: missing list url for html-list-select input");
			return;
		}

		let elements = element.querySelector(".elements");

		elements.innerHTML = `<div class="p-4"><div class="spinner-border spinner-border-sm" role="status">
		  <span class="visually-hidden">Loading...</span>
		</div> Loading...</div>`;

		//cache ajax requests
		if (input.cache[url] !== undefined) {
			elements.innerHTML = input.cache[url];
			return;
		}

		fetch(url)
		.then((response) => {
			if (!response.ok) { throw new Error(`HTTP ${response.status} ${response.statusText}`); }
			return response.text()
		})
		.then((text) => {
				input.cache[url] = text;
				elements.innerHTML = text;
		})
		.catch(error => {
			console.error("Vvveb: error loading list", error);
			displayToast("danger", "Error", "Error loading list");
		});
	},

	setValue: function(value) {
		let select = this.element?.[0]?.querySelector("select");
		if (value && select) {
			select.value = value;
		}
	},

	init: function(data) {
		this.data = data;
		//pass a copy so the click binding doesn't stack up on the shared events array
		const events = [...(this.events ?? []), ["click", "onElementClick", data.clickElement]];
		let template = this.render("html-list-select", data, events);
		let select = template.querySelector("select");
		this.onListChange.call(select, new Event('change'), template, this);
		return template;
	},
  }
}

let LinkInput = { ...TextInput, ...{

	events: [
		["change", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let DateInput = { ...TextInput, ...{

	events: [
		["change", "onChange", "input"],
	],

	init: function(data) {
		return this.render("dateinput", data);
	},
  }
}

let RangeInput = { ...Input, ...{

	events: [
		["change", "onRangeChange", "input"],
	],

	onRangeChange: function(event, node, input) {
		this.parentNode.querySelector('input[type=number]').value = this.value;
		this.parentNode.querySelector('input[type=range]').value = this.value;
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		const root = this.element?.[0];
		if (!root) return;

		const number = root.querySelector('input[type=number]');
		const range = root.querySelector('input[type=range]');
		if (number) number.value = value;
		if (range) range.value = value;
	},

	init: function(data) {
		return this.render("rangeinput", data);
	},
  }
}

let NumberInput = { ...Input, ...{

	events: [
		["change", "onChange", "input"],
	],

	init: function(data) {
		return this.render("numberinput", data);
	},
  }
}

let CssUnitInput = { ...Input, ...{

	number:0,
	unit:"px",

	events: [
		["change", "onInputChange", "select"],
		["change", "onInputChange", "input"],
		["input", "onInputChange", "input"],
	],

	onInputChange: function(event, node, input) {
		if (node) {
			let number = node.querySelector("input").value;
			let unit = node.querySelector("select").value;

			if (this.value != "") input[this.name] = this.value;// this.name = unit or number
			if (unit == "") unit = "px";//if unit is not set use default px

			let value = "";
			if (unit == "auto")  {
				node.classList.add("auto");
				value = unit;
			}
			else  {
				node.classList.remove("auto");
				value = number + (unit ? unit : "");
			}

			input.value = value;

			return input.onChange.call(this, event, node, input);
		}
	},

	setValue: function(value) {
		if (value && this.element?.[0]) {
			let element = this.element[0];
			this.number = parseFloat(value);
			this.unit = isNaN(this.number) ? value.trim() : value.replace(String(this.number), '').trim();

			element.classList.toggle("auto", this.unit == "auto");

			const numberInput = element.querySelector("input[type=number]");
			if (numberInput) numberInput.value = this.number;

			const unitSelect = element.querySelector("select");
			if (unitSelect) unitSelect.value = this.unit;
		}
	},

	init: function(data) {
		return this.render("cssunitinput", data);
	},
  }
}

let ColorInput = { ...Input, ...{

	//html5 color input only supports setting values as hex colors even if the picker returns only rgb
	rgb2hex: function(value) {
		if (!value) return value;

		value = value.trim();
		const rgb = value.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);

		if (!rgb || rgb.length !== 4) return value;

		const hex = (part) => ("0" + parseInt(part, 10).toString(16)).slice(-2);
		return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
	},

	events: [
		["change", "onChange", "input"],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('input');

			if (input) {
				input.value = this.rgb2hex(value);
			}
		}
	},

	init: function(data) {
		//if no palette provided use default
		if (!data.palette) {
			data.palette = Vvveb.ColorPalette.getAll();
		}

		return this.render("colorinput", data);
	},
  }
}

let ImageInput = { ...Input, ...{

	events: [
		["focusout", "onChange", "input[type=text]"],
		["change", "onUpload", "input[type=file]"],
	 ],

	setValue: function(value) {
		//don't set blob value to avoid slowing down the page
		if (this.element?.[0] && value && !value.startsWith("data:image")) {
			this.element[0].querySelector('input[type="text"]').value = value;
		}
	},

	/**
	 * Show a local preview right away, upload to the media manager endpoint
	 * (global uploadUrl, same contract as the tinymce image uploader) and
	 * update the value with the returned url.
	 */
	onUpload: async function(event, node, input) {
		const file = this.files?.[0];
		if (!file) return;

		const root = input.element?.[0] ?? node;

		//preview/update immediately with the local data url
		const reader = new FileReader();
		reader.onload = () => {
			input.value = reader.result;
			input.onChange.call(this, event, node, input);
		};
		reader.readAsDataURL(file);

		if (typeof uploadUrl === "undefined" || !uploadUrl) {
			console.warn("Vvveb: no uploadUrl configured, skipping image upload");
			return;
		}

		try {
			const formData = new FormData();
			formData.append("files[]", file, file.name);
			formData.append("mediaPath", window.mediaPath ?? "");
			formData.append("onlyFilename", "true");
			formData.append("csrf", document.querySelector("[name=csrf]")?.value ?? "");

			const response = await fetch(uploadUrl, {method: "POST", body: formData, credentials: "same-origin"});
			if (!response.ok) throw new Error(`HTTP ${response.status} ${response.statusText}`);

			const json = await response.json();
			const result = Array.isArray(json) ? json[0] : json;

			displayToast(result?.success ? "success" : "danger", "Upload", result?.message ?? "Image upload failed");

			const url = result?.file ? (window.mediaPath ?? "") + "/" + result.file : null;
			if (!url) return;

			//replace preview with the uploaded url and update the src input
			const textInput = root.querySelector('input[type="text"]');
			if (textInput) textInput.value = url;

			input.value = url;
			input.onChange.call(this, event, node, input);
		} catch (e) {
			console.error("Vvveb: image upload failed", e);
			displayToast("danger", "Error", "Error uploading image");
		}
	}
 }	
}

let FileUploadInput = { ...TextInput, ...{

	events: [
		["focusout", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let RadioInput = { ...Input, ...{

	events: [
		["change", "onChange", "input"],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('input');

			if (input) {
				const selected = (value == input.value);
				input.checked = selected;
				if (selected) {
					input.setAttribute("checked", "checked");
				} else {
					input.removeAttribute("checked");
				}
			}
		}
	},

	init: function(data) {
		return this.render("radioinput", data);
	},
  }
}

let RadioButtonInput = { ...RadioInput, ...{

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let inputs = this.element[0].querySelectorAll('input');
			let selected = null;
			inputs.forEach((el) => {
				if (value == el.value) {
					selected = el;
				} else {
					el.checked = false;
					el.removeAttribute("checked");
				}
			});

			if (selected) {
				selected.checked = true;
				selected.setAttribute("checked", "checked");
			}
		}
	},

	init: function(data) {
		return this.render("radiobuttoninput", data);
	},
  }
}

let ToggleInput = { ...Input, ...{

	events: [
		["change", "onToggleChange", "input"],
	],

	onToggleChange: function(event, node, input) {
		input.value = this.checked ? this.getAttribute("data-value-on") : this.getAttribute("data-value-off");
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		if (this.element?.[0]) {
			let input = this.element[0].querySelector('input');

			if (input) {
				const selected = (value == input.getAttribute("data-value-on"));
				input.checked = selected;
				if (selected) {
					input.setAttribute("checked", "checked");
				} else {
					input.removeAttribute("checked");
				}
			}
		}
	},

	init: function(data) {
		return this.render("toggle", data);
	},
  }
}

let ValueTextInput = { ...TextInput, ...{

	events: [
		["focusout", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let GridLayoutInput = { ...TextInput, ...{

	events: [
		["focusout", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let ProductsInput = { ...TextInput, ...{

	events: [
		["focusout", "onChange", "input"],
	],

	init: function(data) {
		return this.render("textinput", data);
	},
  }
}

let GridInput = { ...Input, ...{

	events: [
		["change", "onChange", "select" /*'select'*/],
		["click", "onChange", "button" /*'select'*/],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('select');

			if (input) {
				input.value = value;
			}
		}
	},

	init: function(data) {
		return this.render("grid", data);
	},
  }
}

let TextValueInput = { ...Input, ...{

	events: [
		["focusout", "onChange", "input"],
		["click", "onChange", "button" /*'select'*/],
	],

	setValue: function(value) {
		return false;
	},

	init: function(data) {
		return this.render("textvalue", data);
	},
  }
}

let ButtonInput = { ...Input, ...{

	events: [
		["click", "onChange", "button" /*'select'*/],
	],

	setValue: function(value) {
		if (this.element?.[0] && value) {
			let input = this.element[0].querySelector('button');

			if (input) {
				input.value = value;
			}
		}
	},

	init: function(data) {
		return this.render("button", data);
	},
  }
}

let SectionInput = { ...Input, ...{

	setValue: function(value) {
		return false;
	},

	init: function(data) {
		return this.render("sectioninput", data);
	},
  }
}

let PopOverInput = { ...Input, ...{

	setValue: function(value) {
		return false;
	},

	init: function(data) {
		return this.render("popoverinput", data);
	},
  }
}

let ListInput = { ...Input, ...{

	events: [
		["change", "onChange", "select"],
		["click", "remove", ".delete-btn"],
		["click", "add", ".btn-new"],
		["click", "select", ".section-item"],
	],

	remove: function(event, node, input) {
		const sectionItem = this.closest(".section-item");
		if (!sectionItem) return false;

		const index = [...sectionItem.parentNode.children].indexOf(sectionItem);//sectionItem.index();
		const data = input.data;

		if (data.removeElement) {
			let container = input.node;
			if (data.container) {
				container = container.querySelector(data.container) ?? container;
			}
			container.querySelector(data.selector + ":nth-child(" + (index + 1) + ")")?.remove();
		}
		sectionItem.remove();

		event.action = "remove";
		event.index = index;
		input.onChange(event, node, input);
		event.preventDefault();
		return false;
	},

	add: function(event, node, input) {
		const data = input.data ?? {};
		const newElement = data.newElement ?? false;
		if (newElement) {
			let container = input.node;
			if (data.container) {
				container = container.querySelector(data.container) ?? container;
			}
			container.append(generateElements(newElement)[0]);
		}

		event.action = "add";
		input.onChange(event, node, input);
		return false;
	},

	select: function(event, node, input) {
		const sectionItem = this.closest(".section-item");
		if (sectionItem?.parentNode) {
			event.action = "select";
			event.index = [...sectionItem.parentNode.children].indexOf(sectionItem);//sectionItem.index();
			input.onChange(event, node, input);
		}
		return false;
	},

	setValue: function(value) {
	},

	init: function(data, node) {
		this.component = data.component;
		this.selector = data.selector;
		this.node = node;

		let elements = this.node.querySelectorAll(data.container + " " + this.selector);
		let options = [];

		elements.forEach((e, i) => {
			let element = e;
			if (data.nameElement) {
				element = element.querySelector(data.nameElement) ?? e;
			}
			//"text" (or unset) labels items by their text content, otherwise by element id
			const name = (!data.name || data.name == "text") ? element.textContent.slice(0, 15) : element.id;
			options.push({
				name: name,
				type: (data.prefix ?? "") + (i + 1) + (data.suffix ?? ""),
			});
		});

		data.options = options;
		data.elements = elements;
		this.data = data;

		let list = this.render("listinput", data);
		let items = list.querySelectorAll(".section-item");

		items.forEach((item, i) => {
			const container = item.querySelector(".tree") ?? item;
			let properties = {};
			if (data.elementProperties) {
				for (let prop in data.elementProperties) {
					properties[prop] = Object.assign({}, data.elementProperties[prop]);
					properties[prop].element = elements[i];
				}
			}

			Vvveb.Components.renderProperties(null, properties, null, container);
		});

		return list;
	},
  }
}

let AutocompleteInput = { ...Input, ...{

	events: [
		["autocomplete.change", "onAutocompleteChange", "input"],
		["input", "onAutocompleteChange", "input"],
	],

	onAutocompleteChange: function(event, node, input) {
		input.value = event.detail.value;
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		if (this.element && value) {
			this.element.querySelectorAll('input').forEach(e => e.value = value);
		}
	},

	init: function(data) {
		this.element = this.render("textinput", data);
		this.autocomplete = new _AutocompleteInput(this.element.querySelector("input"), data);

		return this.element;
	}
  }
}

let AutocompleteList = { ...Input, ...{

	events: [
		["autocompletelist.change", "onAutocompleteChange", "input"],
	],

	onAutocompleteChange: function(event, node, input) {
		input.value = event.detail;
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		if (this.element && value) {
			this.element.querySelectorAll('input').forEach(e => e.value = value);
		}
	},

	init: function(data) {
		this.element = this.render("textinput", data);
		this.autocomplete = new _AutocompleteList(this.element.querySelector("input"), data);

		return this.element;
	}
  }
}

let TagsInput = { ...Input, ...{

	separator: " ",

	events: [
		["tagsinput.change", "onTagsInputChange", "input"],
	],

	onTagsInputChange: function(event, node, input) {
		let tags = "";
		for (const tag in event.detail[0]) {
			if (tag) {
				if (tags) tags += input.separator;
				tags += tag;
			}			
		}

		input.value = tags;
		return input.onChange.call(this, event, node, input);
	},

	setValue: function(value) {
		if (this.autocomplete && value) {
			this.autocomplete.tagsInput.setValue(value.trim().split(this.separator));
		}
	},

	init: function(data) {
		this.separator = data.separator ?? " ";
		this.element = this.render("tagsinput", data);
		this.autocomplete = new _TagsInput(this.element.querySelector("input"), data);

		return this.element;
	}
  }
}

let NoticeInput = { ...Input, ...{

	init: function(data) {
		return this.render("noticeinput", data);
	},
  }
}
