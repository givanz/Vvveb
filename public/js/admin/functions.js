//Tables
var elementIdCounter = 0;
function newId() {
	elementIdCounter++;
	return (Date.now() % 1000000000) * 1000 + elementIdCounter;
}

function adminEscapeHtml(value) {
	return String(value ?? '').replace(/[&<>"']/g, function (char) {
		return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char];
	});
}

function addDataTemplate(element, template) {
	const targetText = template;
	const reg = /#([\w_-]+)#/g;
	let result;
	while ((result = reg.exec(targetText)) !== null) {
		let attr = element.closest("[data-" + result[1] + "]");
		//values come from the page data and must not be able to inject markup
		let value = attr ? attr.getAttribute("data-" + result[1]) : 0;
		template = template.replaceAll("#" + result[1] + "#", () => adminEscapeHtml(value));
	}
	
	return template;
}

function addTemplate(templateSelector, name, parent, element = false, parentElement = "table", container = "tbody", callback) {
	// id = attribute-template 
	// name = product_attribute
	// parent = attribute
	let template;
	if (element) {
		element = element.closest(parentElement);
	} else {
		element = document;
	}

	template = element.querySelector(templateSelector);
	if (!template) {
		template = document.querySelector(templateSelector);
	}

	template = template.cloneNode(true)
	template.classList.remove("d-none");
	
	template.querySelectorAll('[type="date"]').forEach(e => e.setAttribute("value", date()));
	template.querySelectorAll('[type="datetime-local"]').forEach(e => e.setAttribute("value", datetime()));
	template.querySelectorAll("input,select").forEach(e => e.removeAttribute("disabled"));
	
	template = template.outerHTML;
	let rowId = newId();
	template = template.replaceAll(name + "[#" + name + "#]", name + "[" + rowId + "]").
						replaceAll("#" + name + "#", rowId).
						replaceAll(name + "#", name + rowId);
	
	template = addDataTemplate(element, template); 
	
	let row = generateElements(template)[0];
	row.removeAttribute("id");
	if (templateSelector[0] === ".") {
		row.classList.remove(templateSelector.slice(1));
	}
	
	element.querySelector(parent + " " + container).append(row);
	
	if (callback) {
		callback(row);
	}

	return row;
}

function removeRow(element, elementName = false) {
	let row = element.closest("tr");
	if (row && elementName) {
		let id = row.dataset.id;
		if (id && id !== "#") {
			let form = element.closest("form");
			if (form) {
				let input = document.createElement("input");
				input.type = "hidden";
				input.name = "delete[" + elementName + "][]";
				input.value = id;
				form.append(input);
			}
		}
	}
	return row ? row.remove() : undefined;
}

function slugify(str) {
	if (str) {	
		return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove accents
			.replace(/([^\w]+|\s+)/g, '-') // Replace space and other characters by hyphen
			.replace(/\-\-+/g, '-')	// Replaces multiple hyphens by one hyphen
			.replace(/(^-+|-+$)/g, '') // Remove extra hyphens from beginning or end of the string
			.toLowerCase();
	}
	return str;
}


function addTab(element, callback) {
	let nav = element.closest(".nav");
	let content = element.closest(".row").querySelector(".tab-content");

	let navTemplate = nav.querySelector(".tab-nav-template");
	let contentTemplate = content.querySelector(".tab-content-template");
	if (!navTemplate || !contentTemplate) {
		return;
	}
	let id = newId();
	
	navTemplate = navTemplate.cloneNode(true);
	navTemplate.classList.remove("tab-nav-template", "d-none"); 
	contentTemplate = contentTemplate.cloneNode(true);
	contentTemplate.classList.remove("tab-content-template", "d-none"); 

	navTemplate.querySelector("a")?.setAttribute("href", "#tab-" + id);
	contentTemplate.setAttribute("id", "tab-" + id);

	contentTemplate = contentTemplate.outerHTML;
	contentTemplate = contentTemplate.replaceAll('[0]', '[' + id + ']').replaceAll('[#tab#]', '[' + id + ']').replaceAll('#tab#', id).replaceAll('-0-', '-' + id + '-');

	nav.append(navTemplate);
	
	//contentTemplate = addDataTemplate(content, contentTemplate);
	let contentElement = generateElements(contentTemplate)[0];
	contentElement.querySelectorAll("[disabled]").forEach(e => e.removeAttribute("disabled"));
	
	content.append(contentElement);

	if (callback) {
		callback(navTemplate, contentElement);
	}
	
	//keep hidden inputs disabled 
	document.querySelectorAll(".d-none input, .d-none select").forEach(e => e.setAttribute("disabled", "true"));

	document.querySelectorAll("#tab-" + id + " .product_option_id").forEach(e => {
		e.dispatchEvent(new Event("change"));
	});	
	
	navTemplate.querySelector("a")?.click();
}

function removeTab(element, elementName) {
	let link = element.closest("a");
	let nav = element.closest(".nav");

	if (elementName) {
		let id = link.dataset.id;
		if (id && id !== "#") {
			let form = element.closest("form");
			if (form) {
				let input = document.createElement("input");
				input.type = "hidden";
				input.name = "delete[" + elementName + "][]";
				input.value = id;
				form.append(input);
			}
		}
	}

	let nextTab = nav.querySelector(".nav-item:not(.d-none) a");
	if (nextTab && window.bootstrap?.Tab) {
		bootstrap.Tab.getOrCreateInstance(nextTab).show();
	}
	let target = link.getAttribute("href");
	if (target) {
		document.querySelector(target)?.remove();
	}
	return link.parentNode.remove();
}


function datetime() {
  let now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  now.setMilliseconds(0)
  now.setSeconds(0)

  return now.toISOString().slice(0, 19).replace('T', ' ');
}

function date() {
  let now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  now.setMilliseconds(0)
  now.setSeconds(0)

  return now.toISOString().slice(0, 10);	
}

function addRow(element) {
	// id = attribute-template
	// name = product_attribute
	// parent = attribute
	let table = element.closest("table");
	let template = table.querySelector("tr.template").cloneNode(true);
	template.classList.remove("template", "d-none");
	let id = newId();
	
	template.querySelectorAll("input,select").forEach(e => e.removeAttribute("disabled"));
	template = template.outerHTML;
	template = template.replaceAll('[0]', '[' + id + ']').
		replaceAll('[#]', '[' + id + ']');
	
	let newEl = generateElements(template)[0];
	table.querySelector("tbody")?.append(newEl);
	return newEl;
}

let optionValues = [];

function optionTypeChange(element) {
	if (typeof productOption === "undefined" || !productOption[element.value]) {
		return;
	}
	let tab = element.closest(".tab-pane");
	if (!tab) {
		return;
	}
	let optionValueId = element.value;
	let option = productOption[element.value];
	let optionType = option['type'];
	
	document.querySelectorAll("a[href='#" + tab.getAttribute("id") + "'] span").forEach(e => e.textContent = option['name']);
	tab.setAttribute("data-type", optionType);
	
	if (optionType == 'radio' || optionType == 'checkbox' || optionType == 'select') {
		tab.querySelectorAll(".default").forEach(e => e.classList.add("d-none"));
		tab.querySelectorAll(".values").forEach(e => e.classList.remove("d-none"));
	} else {
		tab.querySelectorAll(".default").forEach(e => e.classList.remove("d-none"));
		tab.querySelectorAll(".values").forEach(e => e.classList.add("d-none"));
	}
	
	function setValues(values){
		let select = tab.querySelector(".values select.option_value");
		if (!select) {
			return;
		}
		select.innerHTML = "";
		Object.entries(values).forEach(([valueId, valueName]) => {
			let option = generateElements("<option></option>")[0];
			option.value = valueId;
			option.textContent = valueName;
			select.append(option);
		});
	}
	
	if (!optionValues[optionValueId]) {
		fetch(window.location.pathname + '?module=product/product&action=optionValuesAutocomplete&option_id=' + encodeURIComponent(optionValueId))
		.then((response) => {
			if (!response.ok) { throw new Error(response.statusText || response.status) }
			return response.json()
		})
		.then((values) => {
			optionValues[optionValueId] = values;
			setValues(values);				
		})
		.catch(error => {
			console.error(error);
			displayToast("danger", "Error", "Error loading options!");
		});
	} else {
		setValues(optionValues[optionValueId]);
	}
}


function addAllOptionsValues(element) {
	let table = element.closest(".tab-pane ").querySelector("table");
	let row = addRow(table);
	let dropdown = row.querySelector("[data-v-option-value-option_value_id]");
	dropdown.selectedIndex = 0;
	
	for (let i = 1;i < dropdown.childElementCount;i++) {
		row = addRow(table);
		dropdown = row.querySelector("[data-v-option-value-option_value_id]");
		dropdown.selectedIndex = i;
		console.log(row, dropdown, i);
	}
	
}
// Date
let datepicker = function (e) {
	let element = e.target.closest('input.date, input[type="date"]');
	if (element && !element.daterangepicker) {	
		element.daterangepicker =
		new DateRangePicker(element, { 
			singleDatePicker: true,
			autoApply: true,
			autoUpdateInput: false,
			/*
			ranges: {
				'Today': [moment().startOf('day'), moment().endOf('day')],
				'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
			},*/
			locale: {
				format: 'YYYY-MM-DD'
			}
		}, function (start, end) {
			this.element.value = start.format('YYYY-MM-DD');
		});
	}
}

document.addEventListener('focusin', datepicker);

// Date range
let daterangepicker = function (e) {
	let element = e.target.closest('input.daterange');
	if (element && !element.daterangepicker) {	
		element.daterangepicker =
		new DateRangePicker(element, { 
			singleDatePicker: false,
			autoApply: true,
			//opens: 'left',
			//autoUpdateInput: false,
			ranges: {
				'Today': [moment().startOf('day'), moment().endOf('day')],
				'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
				'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
				'This Month': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
				'This Year': [moment().startOf('year').startOf('month').startOf('day'), moment().endOf('year').endOf('month').endOf('day')],
			},
			locale: {
				//format: "YYYY-MM-DD HH:mm:ss",
				format: "YYYY-MM-DD",
			}
		}/*, function (start, end) {
			this.element.value = start.format('YYYY-MM-DD') + " - " + end.format('YYYY-MM-DD');
		}*/);
	}
}

document.addEventListener('focusin', daterangepicker);

// Time
let timepicker = function (e) {
	let element = e.target.closest('input.time');
	if (element && !element.daterangepicker) {	
		element.daterangepicker =
		new DateRangePicker(element, {
			singleDatePicker: true,
			datePicker: false,
			autoApply: true,
			autoUpdateInput: false,
			timePicker: true,
			timePicker24Hour: true,
			locale: {
				format: 'HH:mm'
			}
		}, function (start, end) {
			this.element.value =  start.format('HH:mm');
		});
		
		element.addEventListener('show.daterangepicker', function (ev, picker) {
			picker.container.querySelectorAll('.calendar-table').forEach(e => e.style.display = "none");
		});
	}
}

document.addEventListener('focusin', timepicker);

// Date Time
let datetimepicker = function (e) {
	let element = e.target.closest('input.datetime');
	if (element && !element.daterangepicker) {	
		element.daterangepicker =
		new DateRangePicker(element, {
			singleDatePicker: true,
			autoApply: true,
			autoUpdateInput: false,
			timePicker: true,
			timePicker24Hour: true,
			locale: {
				format: 'YYYY-MM-DD HH:mm'
			}
		}, function (start, end) {
			this.element.value =  start.format('YYYY-MM-DD HH:mm');
		});
	}
}

document.addEventListener('focusin', datetimepicker);


function generateElements(html) {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return template.content.children;
}

function togglePasswordInput(element, input) {
	let password = (input && document.getElementById(input)) || element.parentNode?.querySelector("[type=text],[type=password]");
	if (!password) {
		return;
	}
	let use = element.querySelector("use");
	if (use) {
		let base = (use.getAttribute("href") || "").split("#")[0];
		use.setAttribute("href", base + (password.type == "password" ? "#eye" : "#eye-off"));
	} else {
		let icon = element.querySelector("i");
		if (password.type == "password") {
			icon?.classList.add("icon-eye-outline");
			icon?.classList.remove("icon-eye-off-outline");
		} else {
			icon?.classList.remove("icon-eye-outline");
			icon?.classList.add("icon-eye-off-outline");
		}
	}
	if (password.type == "password") {
		password.type = "text";
	} else {
		password.type = "password";
	}
}
