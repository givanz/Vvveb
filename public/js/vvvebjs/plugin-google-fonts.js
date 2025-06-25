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

GoogleFontsManager = {
	url: "https://fonts.googleapis.com/css2?display=swap&family=",
	activeFonts: [],	

	updateFontList: function () {
		let googleFontsLink = Vvveb.Builder.frameHead.querySelector("google-fonts-link");

		if (this.activeFonts.length == 0) {
			googleFontsLink.remove();
			return;
		}

		if (!googleFontsLink) {
			googleFontsLink = generateElements(`<link id="google-fonts-link" href="" rel="stylesheet">`)[0];
			Vvveb.Builder.frameHead.append(googleFontsLink);
		}

		googleFontsLink.setAttribute("href", this.url + this.activeFonts.join("&family="));
	},
	
	removeFont: function (fontName) {
		let index = this.activeFonts.indexOf(fontName);
		this.activeFonts.splice(index, 1);
		this.updateFontList();
	},
	
	addFont: function (fontName) {
		this.activeFonts.push(fontName);
		this.updateFontList();
	}
}


Vvveb.FontsManager.addProvider("google", GoogleFontsManager);
	
let googleFonts = {};
let googlefontNames = [];
//load google fonts list and update wyswyg font selector and style tab font-family list
fetch(Vvveb.baseUrl + "../../resources/google-fonts.json")
.then((response) => {
	if (!response.ok) { throw new Error(response) }
	return response.json()
})
.then((data) => {
	Vvveb.FontsManager.addFontList("google", "Google Fonts", data);
})
.catch(error => {
	console.log(error.statusText);
	displayToast("danger", "Error", "Error loading google fonts!");
});
