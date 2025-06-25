//Tables
function addTemplate(id, name, parent, element = false, parentElement = "table", container = "tbody", callback) {
	// id = attribute-template 
	// name = product_attribute
	// parent = attribute
	let template;
	let tbody;
	if (element) {
		element = element.closest(parentElement);
	} else {
		element = document;
	}
	template = document.querySelector(id).cloneNode(true);
	
	template.querySelectorAll('[type="date"]', template).forEach(e => e.setAttribute("value", date()));
	template.querySelectorAll('[type="datetime-local"]').forEach(e => e.setAttribute("value", datetime()));
	template.querySelectorAll("input,select").forEach(e => e.removeAttribute("disabled"));
	
	template =template.outerHTML;
	let newId = Math.floor(Math.random() * 10000);
	template = template.replaceAll(name + '[0]', name + '[' + newId + ']').
						replaceAll(name + '[#]', name + '[' + newId + ']').
						replaceAll(name + '#', name + newId ).
						replaceAll("[" + name +"][#]", "[" + name +"][" + newId + "]").
						replace('d-none', '').
						replace('id="' + id + '"', '');
	
	let row = generateElements(template)[0];
	element.querySelector(parent + " " + container).append(row);
	
	if (callback) {
		callback(row);
	}

	return row;
}

function removeRow(element, elementName = false) {
	let row = element.closest("tr");
	if (row && elementName) {
		let form = element.closest("form");
		form.append(generateElements('<input type="hidden" name="delete[' + elementName + '][]" value="' + row.dataset.id + '">')[0]);
	}
	return row.remove();
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
	let newId = Math.floor(Math.random() * 10000);
	
	navTemplate = navTemplate.cloneNode(true);
	navTemplate.classList.remove("tab-nav-template", "d-none"); 
	contentTemplate = contentTemplate.cloneNode(true);
	contentTemplate.classList.remove("tab-content-template", "d-none"); 

	navTemplate.querySelector("a").setAttribute("href", "#tab-" + newId);
	contentTemplate.setAttribute("id", "tab-" + newId);

	contentTemplate = contentTemplate.outerHTML;
	contentTemplate = contentTemplate.replaceAll('[0]', '[' + newId + ']').replaceAll('-0-', '-' + newId + '-').replaceAll('disabled', '');

	nav.append(navTemplate);
	let contentElement = generateElements(contentTemplate)[0];
	content.append(contentElement);

	if (callback) {
		callback(navTemplate, contentElement);
	}
	
	document.querySelectorAll("#tab-" + newId +" .product_option_id").forEach(e => {
		e.dispatchEvent(new Event("change"));
	});	
	
	navTemplate.querySelector("a").click();
}

function removeTab(element, elementName) {
	let link = element.closest("a");
	let nav = element.closest(".nav");

	if (elementName) {
		let form = element.closest("form");
		form.append(generateElements('<input type="hidden" name="delete[' + elementName + '][]" value="' + link.dataset.id + '">')[0]);
	}

	const bsTab = bootstrap.Tab.getOrCreateInstance(nav.querySelector(".nav-item:not(.d-none) a"));
	bsTab.show();
	document.querySelector(link.getAttribute("href")).remove();
	return link.parentNode.remove();
	return false;
}


function datetime() {
  let now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  now.setMilliseconds(null)
  now.setSeconds(null)

  return now.toISOString().slice(0, 19).replace('T', ' ');
}

function date() {
  let now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  now.setMilliseconds(null)
  now.setSeconds(null)

  return now.toISOString().slice(0, 10);	
}

function addRow(element) {
	// id = attribute-template
	// name = product_attribute
	// parent = attribute
	let table = element.closest("table");
	let template = table.querySelector("tr.template").cloneNode(true);
	template.classList.remove("template", "d-none");
	let newId = Math.floor(Math.random() * 10000);
	
	template.querySelectorAll("input,select", template).forEach(e => e.removeAttribute("disabled"));
	template = template.outerHTML;
	template = template.replaceAll('[0]', '[' + newId + ']').
		replaceAll('[#]', '[' + newId + ']');
	
	table.querySelector("tbody").append(generateElements(template)[0]);
	return element;
}

let optionValues = [];

function optionTypeChange(element) {
	let tab = element.closest(".tab-pane");
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
			let options = "";
			for (id in values) {
				options += "<option value='"+ id +"'>" + values[id] + "</option>";  
			}
			tab.querySelector(".values select.option_value", tab).innerHTML = options;
	}
	
	if (!optionValues[optionValueId]) {
		fetch(window.location.pathname + '?module=product/product&action=optionValuesAutocomplete&option_id=' + optionValueId)
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.json()
		})
		.then((values) => {
			optionValues[optionValueId] = values;
			setValues(values);				
		})
		.catch(error => {
			console.log(error.statusText);
			displayToast("danger", "Error", "Error saving!");
		});
	} else {
		setValues(optionValues[optionValueId]);
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
				format: "YYYY-MM-DD",
			}
		});
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
