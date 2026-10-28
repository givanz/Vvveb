class TableController {

	selectRow(e) {
		let selectedCount = document.querySelectorAll(".checkbox input[type='checkbox']:checked");
		let bulkActions = document.querySelector('.bulk-actions');

		if (bulkActions) {
			bulkActions.style.display = selectedCount.length > 0 ? "block" : "none";
		}
	}
	
	bulkSelect(e) {
		let bulkCheckbox = this;
		document.querySelectorAll(".checkbox input[type='checkbox']").forEach(e => e.checked = bulkCheckbox.checked);

		let bulkActions = document.querySelector('.bulk-actions');
		if (bulkActions) {
			bulkActions.style.display = bulkCheckbox.checked ? "block" : "none";
		}
	}
}

let Table = new TableController();
export {Table};
