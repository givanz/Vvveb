import {Themes as ThemesModel} from '../model/themes.js';

class ThemesController {

	install(e) {
		let element = e.currentTarget;
		let slug = element.dataset.slug;
		let market = element.dataset.market;
		
		element.classList.add("loading");
		
		element.querySelector('.loading').toggleClass("d-none");
		element.querySelector('.button-text').toggleClass("d-none");
		
		ThemesModel.install(market, slug, function () {
			setTimeout(function () {
				element.classList.remove("loading");
				element.querySelector('.loading').toggleClass("d-none");
				element.querySelector('.button-text').toggleClass("d-none");
				
			}, 5000);
		});
		
		e.preventDefault();
	}
	
	importModal(e) {
		document.getElementById("importModal").modal('show');
		e.preventDefault();
	}
	
	importTheme(e) {
		document.getElementById("import-iframe").style.display = "";
		document.getElementById("import-form").submit();
		document.getElementById("import-options").style.display = "none";
		e.target.style.display = "none";
	}

	import(e) {
		console.log(document.getElementById("import-form").serialize());
	}
	
	activate(e) {
		let element = e.currentTarget;
		let slug = element.dataset.slug;
		let market = element.dataset.market;
		
		element.classList.add("loading");
		
		ThemesModel.install(market, slug, function () {
			setTimeout(function () {
				element.classList.remove("loading");
			}, 5000);
		});
		
		document.getElementById("importModal").modal('show');
		
		e.preventDefault();
	}
}

let Themes = new ThemesController();
export {Themes};
