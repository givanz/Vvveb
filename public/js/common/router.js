let Router = {
	
	init: function() {
		let events = [];
		
		$("[data-v-vvveb-action]").each(function () {

			let on = "click";
			if (this.dataset.vVvvebOn) on = this.dataset.vVvvebOn;
			var event = '[data-v-vvveb-action="' + this.dataset.vVvvebAction + '"]';

			if (events.indexOf(event) > -1) return;
			events.push(event);
			
			let namespace = this.dataset.vVvvebAction.split(".");

			if (!window[namespace[0]]) {
				console.error('Controller %s is not available', namespace[0], this);
				return;
			}

			if (!window[namespace[0]][namespace[1]]) {
				console.error('Method %s is not available for %s', namespace[1], namespace[0], this);
				return;
			}

			let fn = window[namespace[0]][namespace[1]];
			$(document).on(on, event, fn);
		});
	},
	
}	

export {Router};
