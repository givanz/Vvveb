/**
 * Vvveb
 *
 * Copyright (C) 2021  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * https://github.com/givanz/Vvveb
 */

Vvveb.CodeEditor = {
	
	isActive: false,
	oldValue: '',
	doc:false,
	codemirror:false,
	
	init: function(doc) {

		if (this.codemirror == false) {
			this.codemirror = CodeMirror.fromTextArea(document.querySelector("#vvveb-code-editor textarea"), {
				mode: 'text/html',
				lineNumbers: true,
				autofocus: true,
				lineWrapping: true,
				//viewportMargin:Infinity,
				theme: 'duotone-dark'
			});
			
			this.isActive = true;
			this.codemirror.getDoc().on("change", function (e, v) { 
				if (v.origin != "setValue") {
					delay(() => {
						Vvveb.Builder.setHtml(e.getValue());
						//enable save button
						document.querySelectorAll("#top-panel .save-btn").forEach(e => e.removeAttribute("disabled"));
					}, 1000);
				}
			});

			//load code on document changes
			Vvveb.Builder.frameBody.addEventListener("vvveb.undo.add", () => Vvveb.CodeEditor.setValue());
			Vvveb.Builder.frameBody.addEventListener("vvveb.undo.restore", () => Vvveb.CodeEditor.setValue());
			
			//load code when a new url is loaded
			Vvveb.Builder.documentFrame.addEventListener("load", () => Vvveb.CodeEditor.setValue());
			window.addEventListener("vvveb.Builder.selectNode", (e) => Vvveb.CodeEditor.setSelection(e));
		}
		
		this.isActive = true;
		this.setValue();

		return this.codemirror;
	},

	setSelection: function(e) {
		if (e.detail.target) {
		 let value = e.detail.target.outerHTML;

		 let cursor = this.codemirror.getSearchCursor(value/* , CodeMirror.Pos(this.codemirror.firstLine(), 0), {caseFold: true, multiline: true}*/);
		 if(cursor.find(false)){ //move to that position.
		   this.codemirror.setSelection(cursor.from(), cursor.to());
		   this.codemirror.scrollIntoView({from: cursor.from(), to: cursor.to()}, 5);
		 }
		}
	},
	
	setValue: function(value) {
		if (this.isActive == true) {
			let scrollInfo = this.codemirror.getScrollInfo();
			this.codemirror.setValue(Vvveb.Builder.getHtml(true, false));
			this.codemirror.scrollTo(scrollInfo.left, scrollInfo.top);
			let self = this;
			setTimeout(function() {
				self.codemirror.refresh();
			}, 300);
		}
	},

	destroy: function(element) {
		/*
		//save memory by destroying but lose scroll on editor toggle
		this.codemirror.toTextArea();
		this.codemirror = false;
		*/ 
		this.isActive = false;
		window.removeEventListener("vvveb.StyleManager.setStyle", Vvveb.CodeEditor.setStyle);
		window.removeEventListener("vvveb.Builder.selectNode", Vvveb.CodeEditor.setSelection);
	},

	toggle: function() {
		if (this.isActive != true) {
			this.isActive = true;
			return this.init();
		}
		this.isActive = false;
		this.destroy();
	}
}


// override modal code editor to use code mirror
Vvveb.ModalCodeEditor.init = function (modal = false, editor = false) {
	this.modal  = document.getElementById("codeEditorModal");
	this.editor = CodeMirror.fromTextArea(document.querySelector("#codeEditorModal textarea"), {
		mode: 'text/html',
		lineNumbers: true,
		autofocus: true,
		lineWrapping: true,
		//viewportMargin:Infinity,
		theme: 'duotone-dark'
	});
	
	let self = this;
	this.modal.querySelector('.save-btn').addEventListener("click",  function(event) {
		window.dispatchEvent(new CustomEvent("vvveb.ModalCodeEditor.save", {detail: self.getValue()}));
		self.hide();
		return false;
	});
}

Vvveb.ModalCodeEditor.setValue = function (value) {
	let scrollInfo = this.editor.getScrollInfo();
	this.editor.setValue(value);
	this.editor.scrollTo(scrollInfo.left, scrollInfo.top);
	let self = this;
	setTimeout(function() {
		self.editor.refresh();
	}, 300);
};

Vvveb.ModalCodeEditor.getValue = function (value) {
	return this.editor.getValue();
};


Vvveb.CssEditor = {
	
	oldValue: '',
	doc:false,
	textarea:false,
	codemirror:false,
	
	init: function(doc) {
		if (this.codemirror == false) {
			this.textarea = document.getElementById("css-editor");
			this.codemirror = CodeMirror.fromTextArea(this.textarea, {
				mode: 'text/css',
				lineNumbers: true,
				autofocus: true,
				lineWrapping: true,
				//viewportMargin:Infinity,
				theme: 'duotone-dark'
			});		
			
			this.codemirror.getDoc().on("change", function (e, v) { 
				if (v.origin != "setValue")
				delay(() => Vvveb.StyleManager.setCss(e.getValue()), 1000);
			});
			
			window.addEventListener("vvveb.Builder.selectNode", (e) => Vvveb.CssEditor.setSelection(e));
			window.addEventListener("vvveb.StyleManager.setStyle", Vvveb.CssEditor.setStyle);
		}
				
		this.setValue(Vvveb.StyleManager.getCss());
	},

	getValue: function() {
		return this.codemirror.getValue();
	},
	
	setValue: function(value, updateStyles = true) {
		if (value) {
			let scrollInfo = this.codemirror.getScrollInfo();
			this.codemirror.setValue(value);
			this.codemirror.scrollTo(scrollInfo.left, scrollInfo.top);
			let self = this;
			setTimeout(function() {
				self.codemirror.refresh();
			}, 300);
		
			if (updateStyles) {
				Vvveb.StyleManager.setCss(value);
			}
		}
	},

	setStyle: function(e) {
		Vvveb.CssEditor.setValue(Vvveb.StyleManager.getCss(), false);
	},

	setSelection: function(e) {
		if (e.detail.target) {
		 let value = Vvveb.StyleManager.getSelectorForElement(e.detail.target);
		 let cursor = this.codemirror.getSearchCursor(value/* , CodeMirror.Pos(this.codemirror.firstLine(), 0), {caseFold: true, multiline: true}*/);
		 if(cursor.find(false)){ //move to that position.
		   this.codemirror.setSelection(cursor.from(), cursor.to());
		   this.codemirror.scrollIntoView({from: cursor.from(), to: cursor.to()}, 5);
		 }
		}
	},
	
	destroy: function(element) {
		/*
		//save memory by destroying but lose scroll on editor toggle
		this.codemirror.toTextArea();
		this.codemirror = false;
		*/ 
		window.removeEventListener("vvveb.StyleManager.setStyle", Vvveb.CssEditor.setStyle);
		window.removeEventListener("vvveb.Builder.selectNode", Vvveb.CssEditor.setSelection);
		this.isActive = false;
	},

	toggle: function() {
		if (this.isActive != true) {
			this.isActive = true;
			return this.init();
		}
		this.isActive = false;
		this.destroy();
	}
}
