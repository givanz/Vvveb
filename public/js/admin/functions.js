//Tables
function addTemplate(id, name, parent) {
	// id = attribute-template
	// name = product_attribute
	// parent = attribute
	let template = $("#" + id).clone();
	$('[type="date"]', template).attr("value", date());
	$('[type="datetime-local"]', template).attr("value", datetime());
	$("input,select", template).removeAttr("disabled");
	
	template =template[0].outerHTML;
	let newId = Math.floor(Math.random() * 10000);
	template = template.replaceAll(name + '[0]', name + '[' + newId + ']').
						replaceAll(name + '[#]', name + '[' + newId + ']').
						replaceAll(name + '#', name + newId ).
						replace('d-none', '').
						replace('id="' + id + '"', '');
	let element = $(template);
	$("#" + parent + " tbody").append(element);
	return element;
}

function removeRow(element, elementName = false) {
	let row = $(element).parents("tr");
	if (elementName) {
		let form = $(element).parents("form");
		form.append('<input type="hidden" name="delete[' + elementName + '][]" value="' + row.data("id") + '">');
	}
	return row.remove();
}


function clearFeaturedMedia(id = "featured-image") {
	$("#" + id + "-input").val("");
	$("#" + id + "-thumb").attr("src","img/placeholder.svg");
}

const slugify = (str) => {
return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove accents
	.replace(/([^\w]+|\s+)/g, '-') // Replace space and other characters by hyphen
	.replace(/\-\-+/g, '-')	// Replaces multiple hyphens by one hyphen
	.replace(/(^-+|-+$)/g, '') // Remove extra hyphens from beginning or end of the string
	.toLowerCase();
}


function addTab(element, event) {
	let nav = $(element).parents(".nav");
	let content = $(".tab-content", $(element).parents(".row"));

	let navTemplate = $(".tab-nav-template", nav);
	let contentTemplate = $(".tab-content-template", content);
	let newId = Math.floor(Math.random() * 10000);
	
	navTemplate = navTemplate.clone().removeClass(["tab-nav-template", "d-none"]); 
	contentTemplate = contentTemplate.clone().removeClass(["tab-content-template", "d-none"]); 

	$("a", navTemplate).attr("href", "#tab-" + newId);
	contentTemplate.attr("id", "tab-" + newId);

	contentTemplate = contentTemplate[0].outerHTML;
	contentTemplate = contentTemplate.replaceAll('[0]', '[' + newId + ']');

	nav.append(navTemplate);
	content.append(contentTemplate);
	
	$("#tab-" + newId +" .product_option_id").change();
	$("a", navTemplate)[0].click();
}

function removeTab(element, elementName) {
	let link = $(element).parents("a");
	let nav = $(element).parents(".nav");

	if (elementName) {
		let form = $(element).parents("form");
		form.append('<input type="hidden" name="delete[' + elementName + '][]" value="' + link.data("id") + '">');
	}
	
	$("a:first", nav).tab('show');
	$(link.attr("href")).remove();
	return link.remove();
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
	let table = $(element).parents("table");
	let template = $("tr.template", table).clone().removeClass(["template", "d-none"]);
	let newId = Math.floor(Math.random() * 10000);
	
	
	$("input,select", template).removeAttr("disabled");
	template = template[0].outerHTML;
	template = template.replaceAll('[0]', '[' + newId + ']').
						replaceAll('[#]', '[' + newId + ']');
	//let element = $(template);
	$("tbody", table).append(template);
	return element;
}

let optionValues = [];

function optionTypeChange(element) {
	let tab = $(element).parents(".tab-pane:first");
	let optionValueId = element.value;
	let option = productOption[element.value];
	let optionType = option['type'];
	
	$("a[href='#" + tab.attr("id") + "'] span").text(option['name']);
	tab.attr("data-type", optionType);
	
	if (optionType == 'radio' || optionType == 'checkbox' || optionType == 'select') {
		$(".default", tab).addClass("d-none");
		$(".values", tab).removeClass("d-none");
	} else {
		$(".default", tab).removeClass("d-none");
		$(".values", tab).addClass("d-none");
	}
	
	function setValues(values){
			let options = "";
			for (id in values) {
				options += "<option value='"+ id +"'>" + values[id] + "</option>";  
			}
			$(".values select.option_value", tab).html(options);
	}
	
	if (!optionValues[optionValueId]) {
		$.ajax(window.location.pathname + '?module=product/product&action=optionValuesAutocomplete&option_id=' + optionValueId).done(function (values) {
			optionValues[optionValueId] = values;
			setValues(values);
		});
	} else {
		setValues(optionValues[optionValueId]);
	}
}

// Date
let datepicker = function () {
	$(this).daterangepicker({
		singleDatePicker: true,
		autoApply: true,
		autoUpdateInput: false,
		locale: {
			format: 'YYYY-MM-DD'
		}
	}, function (start, end) {
		$(this.element).val(start.format('YYYY-MM-DD'));
	});
}

$(document).on('focus', 'input.date', datepicker);
$(document).on('focus', 'input[type="date"]', datepicker);

// Time
let timepicker = function () {
	$(this).daterangepicker({
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
		$(this.element).val(start.format('HH:mm'));
	}).on('show.daterangepicker', function (ev, picker) {
		picker.container.find('.calendar-table').hide();
	});
}

$(document).on('focus', 'input.time', timepicker);

// Date Time
let datetimepicker = function () {
	$(this).daterangepicker({
		singleDatePicker: true,
		autoApply: true,
		autoUpdateInput: false,
		timePicker: true,
		timePicker24Hour: true,
		locale: {
			format: 'YYYY-MM-DD HH:mm'
		}
	}, function (start, end) {
		$(this.element).val(start.format('YYYY-MM-DD HH:mm'));
	});
}

$(document).on('focus', 'input.datetime', datetimepicker);
