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
 */
 
class ServerComponent {
	constructor () {
		this.fullUpdate = false;
		this.userServerTemplate = false;
	}	

	ajax(callback) {
		let self = this;
		this.url = Vvveb.Builder.iframe.contentWindow.location.href;
		if (this.url.indexOf(".html") > 0) {
			this.url = "/?template=test.html";
		}

		self.element.style.opacity = "0.85";

		let data = new FormData();
		data.append("_component_content", this.content);
		data.append("html", Vvveb.Builder.getHtml());
		
		fetch(this.url + '&_component_ajax=' + this.component + '&_component_id=' + this.index + '&_server_template=' + this.userServerTemplate + '&r=true',{
			method: 'POST', 
			body: data
		})
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.text()
		})
		.then((data) => {
			if (data) {
			
				let newElement = generateElements(data)[0];
				//set fixed height for parent to avoid page flicker
				let parent = self.element.parentNode;
				
				parent.style.minHeight = parent.clientHeight + "px";
				//full update
				if (this.fullUpdate)  {
					self.element.replaceWith(newElement);
				} else {
					self.element.innerHTML = newElement.innerHTML;
					self.element.style.minHeight = self.element.clientHeight + "px";
				}
				
				setTimeout(function () {
					//if (this.fullUpdate) 
					if (parent) parent.style.minHeight = "";
					self.element.style.minHeight = "";
					//self.element.click();
					Vvveb.Builder.selectNode(self.element);
					Vvveb.TreeList.loadComponents();
				}, 500);
			}

			self.element.removeAttribute("style");
			
			if (callback) callback(data);
		})
		.catch(error => {
			console.log(error.statusText);
		});	
	}		
	
	onChange(element, property, value, input)  {
		//search through all components of the same type on page and get index
		let selector = this.attributes
						.map(el => {return "[" + el + "]";})
						.join(",");
		
		
		this.component = this.attributes[0].replace("data-v-component-", "");
		this.index =  Array.prototype.indexOf.call(Vvveb.Builder.frameBody.querySelectorAll(selector), element);
		
		if (this.content != element.outerHTML) {
			let itemClone = element.cloneNode(true);
			itemClone.querySelectorAll(".vvveb-hidden").forEach(e => e.classList.remove("vvveb-hidden"));
			
			this.content = itemClone.outerHTML;
			this.element = element;
			
			let self = this;
			this.throttle = setTimeout(function () {
				clearTimeout(this.throttle);
				self.ajax();
			}, 500);
		}
		return element;
	}
};

export {
  ServerComponent
};
