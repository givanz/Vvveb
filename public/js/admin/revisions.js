function revisionAction(action, data, callback) {
	fetch(window.location.pathname + "?module=content/revisions&action=" + action + "&" + (new URLSearchParams(data)).toString())
	.then((response) => {
		if (!response.ok) { throw new Error(response) }
		return response.json()
	})
	.then(callback)
	.catch(error => {
		console.log(error.statusText);
		displayToast("danger", "Revision", "Error!");
	});
}

function revision(childElement) {
	let item = childElement.closest("[data-type]")
	if (item) {
		return Object.assign({}, item.dataset);
	}
	return {}
}

document.querySelector(".revisions")?.addEventListener("click", function (e) {
	let element = e.target.closest(".btn-load");
	if (element) {
		let data = revision(element);
		let contentEditor = tinymce.get( element.closest(".tab-pane").querySelector("[data-v-" + data.type + "-content-content]").id );
		revisionAction("revision", data, function (data) {
			if (data.content) {
				contentEditor.setContent(data["content"]);
				displayToast("success", "Revision", data["created_at"] + " Revision loaded!");
		}});
		
		//e.preventDefault();
		return false;
	}
});
/*
document.querySelector(".revisions").addEventListener("click", function (e) {
	let element = e.target.closest(".btn-compare");
	if (element) {
		let data = revision(this);
		let item = element.closest("[data-v-revision]");

		revisionAction("revision", data, function (data, text) {
			item.remove();
		});	

		//e.preventDefault();
		return false;
	}
});
*/

document.querySelector(".revisions")?.addEventListener("click", function (e) {
	let element = e.target.closest(".btn-delete");
	if (element) {
		let data = revision(element);
		let item = element.closest("[data-v-revision]");
		let tab = element.closest(".tab-pane");
		let count = tab.querySelector("[data-v-" + data.type + "-content-revision_count]");

		if (confirm('Are you sure?')) {
			revisionAction("delete", data, function (data) {
				displayToast("success", "Revision", "Revision deleted!");
				item.remove();
				count.html(Math.max(count.html() - 1, 0));
			});	
		}
		//e.preventDefault()
		return false;
	}
});
