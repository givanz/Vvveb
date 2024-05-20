let Router = {
	
	init: function() {
		let events = [];
		
		document.querySelectorAll("[data-v-vvveb-action]").forEach(function (el, i) {

			let on = "click";
			if (el.dataset.vVvvebOn) on = el.dataset.vVvvebOn;
			var event = '[data-v-vvveb-action="' + el.dataset.vVvvebAction + '"]';

			if (events.indexOf(event) > -1) return;
			events.push(event);
			
			let namespace = el.dataset.vVvvebAction.split(".");

			if (!window[namespace[0]]) {
				console.error('Controller %s is not available', namespace[0], el);
				return;
			}

			if (!window[namespace[0]][namespace[1]]) {
				console.error('Method %s is not available for %s', namespace[1], namespace[0], el);
				return;
			}

			let fn = window[namespace[0]][namespace[1]];
			
			el.addEventListener(on, fn);
		});
	},
	
}	

export {Router};
