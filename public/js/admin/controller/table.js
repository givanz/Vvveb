class TableController {

	selectRow(e) {
		let selectedCount = $('.checkbox input[type=\'checkbox\']:checked').length;
		console.log(selectedCount);
		if (selectedCount > 0) {
			$('.bulk-actions').fadeIn();
		} else {
			$('.bulk-actions').fadeOut();
		}
	}
	
	bulkSelect(e) {
		$('.checkbox input[type=\'checkbox\']').prop('checked', this.checked);

		if (this.checked) {
			$('.bulk-actions').fadeIn();
		} else {
			$('.bulk-actions').fadeOut();
		}
	}
}

let Table = new TableController();
export {Table};
