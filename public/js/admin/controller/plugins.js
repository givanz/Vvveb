import {Plugins as PluginsModel} from '../model/plugins.js';

function showImportModal() {
	let modal = document.getElementById("importModal");

	if (modal && window.bootstrap?.Modal) {
		bootstrap.Modal.getOrCreateInstance(modal).show();
	}
}

class PluginsController {

	install(e, element) {
		element = element ?? e.currentTarget;
		if (!element || !element.dataset) {
			return;
		}

		let slug = element.dataset.slug;
		let market = element.dataset.market;
		let loading = element.querySelector('.loading');
		let text = element.querySelector('.button-text');

		element.classList.add("loading");

		if (loading) loading.classList.remove("d-none");
		if (text) text.classList.add("d-none");

		PluginsModel.install(market, slug, function (data) {
			setTimeout(function () {
				element.classList.remove("loading");
				if (loading) loading.classList.add("d-none");
				if (text) text.classList.remove("d-none");
			}, 5000);
		});

		showImportModal();
		e.preventDefault();
	}

	showModal() {
		let modal = document.getElementById("importModal");

		if (modal && window.bootstrap?.Modal) {
			bootstrap.Modal.getOrCreateInstance(modal).show();
		}
	}

	importModal(e) {
		showImportModal();
		e.preventDefault();
	}

	importTheme(e, element) {
		element = element ?? e.target;
		let iframe = document.getElementById("import-iframe");
		let form = document.getElementById("import-form");
		let options = document.getElementById("import-options");

		if (iframe) iframe.style.display = "";
		if (form) form.submit();
		if (options) options.style.display = "none";
		if (element) element.style.display = "none";
		e.preventDefault();
	}

	import(e, element) {
		let form = document.getElementById("import-form");
		if (form) {
			console.log(new URLSearchParams(new FormData(form)).toString());
		}
	}

	activate(e, element) {
		element = element ?? e.currentTarget;
		if (!element || !element.dataset) {
			return;
		}

		let slug = element.dataset.slug;
		let market = element.dataset.market;

		element.classList.add("loading");

		PluginsModel.install(market, slug, function () {
			setTimeout(function () {
				element.classList.remove("loading");
			}, 5000);
		});

		showImportModal();
		e.preventDefault();
	}
}

let Plugins = new PluginsController();
export {Plugins};
