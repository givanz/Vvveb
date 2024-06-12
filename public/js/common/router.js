let Router = {
	
	init: function() {
		let events = [];
		
		const handleEvent = e => {
			let element = e.target.closest("[data-v-vvveb-action]");

			if (element) {
				let namespace = element.dataset.vVvvebAction.split(".");

				if (!window[namespace[0]]) {
					console.error('Controller %s is not available', namespace[0], element);
					return;
				}

				if (!window[namespace[0]][namespace[1]]) {
					console.error('Method %s is not available for %s', namespace[1], namespace[0], element);
					return;
				}

				let fn = window[namespace[0]][namespace[1]];
				fn.call(e.target, e, element, this);
				//element.addEventListener(on, fn);
			}
		};
		
		document.addEventListener("click", handleEvent);
		document.querySelectorAll("[data-v-vvveb-action][data-v-vvveb-on]").forEach(e => document.addEventListener(e.dataset.vVvvebOn, handleEvent));
	},
	
}	

export {Router};
