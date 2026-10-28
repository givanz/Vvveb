ColorInput = { ...ColorInput, ...{

    events: [
        ["change", "onChange", "input"],
	 ],

	setValue: function(value) {
		$('input', this.element).val(value);
		$('i', this.element).css('background-color', value);
	},
	
	init: function(data) {
		let colorinput = this.render("bootstrap-color-picker-input", data);
		let colorpicker = $('.input-group', colorinput).colorpicker();
		return colorinput;
	},
  }
};

