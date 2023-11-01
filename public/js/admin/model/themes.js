class ThemesModel {

	install(market, slug, callback) {
		console.log(market + " --- " + slug);
		callback();
	}
}

let Themes = new ThemesModel();
export {Themes};
