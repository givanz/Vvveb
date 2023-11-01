import {Themes as ThemesModel} from '../model/themes.js';

class ThemesController {

	install(e) {
		let element = e.currentTarget;
		let slug = element.dataset.slug;
		let market = element.dataset.market;
		
		element.classList.add("loading");
		
		$(".loading", element).toggleClass("d-none");
		$(".button-text", element).toggleClass("d-none");
		
		ThemesModel.install(market, slug, function () {
			setTimeout(function () {
				element.classList.remove("loading");
				$(".loading", element).toggleClass("d-none");
				$(".button-text", element).toggleClass("d-none");
				
			}, 5000);
		});
		
		e.preventDefault();
	}
	
	importModal(e) {
		$("#importModal").modal('show');
		//console.log($("#import-form").serialize());
		e.preventDefault();
	}
	
	importTheme(e) {
		$("#import-iframe").show();
		$("#import-form").submit();
		$("#import-options").hide();
		$(e.target).hide();
	}

	import(e) {
		console.log($("#import-form").serialize());
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
		
		$("#importModal").modal('show');
		
		e.preventDefault();
	}
}

let Themes = new ThemesController();
export {Themes};
