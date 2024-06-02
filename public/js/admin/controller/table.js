class TableController {

	selectRow(e) {
		let selectedCount = document.querySelectorAll(".checkbox input[type='checkbox']:checked");

		if (selectedCount.length > 0) {
			document.querySelector('.bulk-actions').style.display = "block";
		} else {
			document.querySelector('.bulk-actions').style.display = "none";
		}
	}
	
	bulkSelect(e) {
		let bulkCheckbox = this;
		document.querySelectorAll(".checkbox input[type='checkbox']").forEach(e => e.checked = bulkCheckbox.checked);

		if (bulkCheckbox.checked) {
			document.querySelector('.bulk-actions').style.display = "block";
		} else {
			document.querySelector('.bulk-actions').style.display = "none";
		}
	}
}

let Table = new TableController();
export {Table};
