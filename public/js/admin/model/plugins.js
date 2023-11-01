class PluginsModel {

	install(market, slug, callback) {
		console.log(market + " --- " + slug);
		console.log(installUrl);
		callback();
	}
}

let Plugins = new PluginsModel();
export {Plugins};
