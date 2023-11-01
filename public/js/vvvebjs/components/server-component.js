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

		self.element.animate({opacity: 0.85}, 50);

		$.ajax({
			url: this.url + '&_component_ajax=' + this.component + '&_component_id=' + this.index + '&_server_template=' + this.userServerTemplate + '&r=true',
			type: 'post',
			data: {_component_content:this.content},
			//dataType: 'json',
			beforeSend: function() {
			},
			complete: function(data) {
				//$('#cart > button').button('reset');
			},
			success: function(data) {
				//$("header [data-v-component-cart]")[0].outerHTML = data;
				if (callback) callback(data);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		})
		.always(function(data) {
		})
		.done(function(data) {
//				self.element[0].outerHTML = data;
//				self.element[0].click();

				//let newElement = self.element.before(data);
				if (data) {
				
					let newElement = $(data);
					//set fixed height for parent to avoid page flicker
					let parent = self.element.parent();
					//
					
					parent.height(parent.height());
					//full update
					if (this.fullUpdate)  {
						self.element.replaceWith(newElement);
					} else {
						self.element.html(newElement.html());
						self.element.height(self.element.height());
					}
					
					setTimeout(function () {
						//if (this.fullUpdate) 
						if (parent) parent.height("");
						self.element.height("");
						//self.element.click();
						Vvveb.Builder.selectNode(self.element);
					}, 250);
				}

				self.element.animate({opacity: 1}, 30);
				
				if (callback) callback(data);
		})
		.fail(function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		});
	}		
	
	onChange(element, property, value, input)  {

				//search through all components of the same type on page and get index
				//if (this.index) {
					let selector = this.attributes
									.map(el => {return "[" + el + "]";})
									.join(",");
					
					
					this.component = this.attributes[0].replace("data-v-component-", "");
					this.index = $(selector,Vvveb.Builder.frameBody).index(element);
				//}
				
				
				if (this.content != element[0].outerHTML) {
					let itemClone = element.clone();
					$(".vvveb-hidden", itemClone).removeClass("vvveb-hidden");
					
					this.content = itemClone[0].outerHTML;
					this.element = element;
					
					let self = this;
					this.throttle = setTimeout(function () {
						clearTimeout(this.throttle);
						self.ajax();
					}, 1000);
				}
				return element;
	}
};

export {
  ServerComponent
};
