import {Router} from './common/router.js';
import {Themes} from './admin/controller/themes.js';
import {Plugins} from './admin/controller/plugins.js';
import {Table} from './admin/controller/table.js';
import {Cache} from './admin/controller/cache.js';
import {HeartBeat} from './admin/heartbeat.js';

window.themes = Themes;
window.plugins = Plugins;
window.cache = Cache;
window.table = {};

if (window.Vvveb === undefined) window.Vvveb = {};
		
jQuery(document).ready(function() {
	Router.init();
});