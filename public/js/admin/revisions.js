function revisionAction(action, data, callback) {
	$.ajax({
		type: "GET",
		url: window.location.pathname + "?module=content/revisions&action=" + action,//set your server side save script url
		data: data,
		cache: false,
	}).done(callback).fail(function (data) {
		displayToast("bg-danger", "Revision", "Error!");
		//alert(data.responseText);
		//console.log(data.responseText);
	});		
}

function revision(childElement) {
	let item = $(childElement).parents("[data-type]")
	if (item.length) {
		return Object.assign({}, item.get(0).dataset);
	}
	return {}
}

$(".revisions").on("click", ".btn-load", function (e) {
	let data = revision(this);
	let contentEditor = tinymce.get( $(this).parents(".tab-pane").find("[data-v-" + data.type + "-content-content]").attr("id") );
	revisionAction("revision", data, function (data, text) {
		if (data.content) {
			contentEditor.setContent(data["content"]);
			displayToast("bg-success", "Revision", data["created_at"] + " Revision loaded!");
	}});
	
	//e.preventDefault();
	return false;
});

$(".revisions").on("click", ".btn-compare", function (e) {
	let data = revision(this);
	let item = $(this).parents("[data-v-revision]")

	revisionAction("revision", data, function (data, text) {
		item.remove();
	});	

	//e.preventDefault();
	return false;
});

$(".revisions").on("click", ".btn-delete", function (e) {
	let data = revision(this);
	let item = $(this).parents("[data-v-revision]")
	let count = $(item).parents(".tab-pane").find("[data-v-" + data.type + "-content-revision_count]");

	revisionAction("delete", data, function (data, text) {
		displayToast("bg-success", "Revision", "Revision deleted!");
		item.remove();
		count.html(Math.max(count.html() - 1, 0));
	});	

	//e.preventDefault()
	return false;
});
