window.addEventListener("tinymce.options", function (e) { 
	let tinyMceOptions = e.detail;
	
	tinyMceOptions.quickbars_insert_toolbar += '| Embed';
	tinyMceOptions.toolbar += '| Embed';

	return tinyMceOptions;
});

window.addEventListener("tinymce.setup", function (e) { 
	let editor = e.detail;
	
	editor.ui.registry.addButton('Embed', {
		text: "Embed",
		icon: 'browse',
		tooltip: 'Insert oEmbed Url',
		//enabled: true,
		onAction: (_) => {
			
			const url = prompt('Enter embed URL');
			
			if (url) {
				getOembed(url).then(response => {
					 editor.insertContent(response.html);
				}).catch(error => console.log(error));
			}
		}
	});
});
