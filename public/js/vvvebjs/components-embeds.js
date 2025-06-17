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


Vvveb.ComponentsGroup['Embeds'] = ["embeds/embed"];

Vvveb.Components.extend("_base", "embeds/embed", {
    name: "Embed",
    attributes: ["data-component-oembed"],
    image: "icons/code.svg",
    //dragHtml: '<img src="' + Vvveb.baseUrl + 'icons/maps.png">',
	html: `<div data-component-oembed data-url="">
			<div class="alert alert-light  m-5" role="alert">
				<img width="64" src="${Vvveb.baseUrl}icons/code.svg">
				<h6>Enter url to embed</h6>
			</div></div>`,


    properties: [{
        name: "Url",
        key: "url",
		htmlAttr: "data-url",
        inputtype: TextInput,
        onChange: function(node, value) {
			node.innerHTML = `<div class="alert alert-light d-flex justify-content-center">
				  <div class="spinner-border m-5" role="status">
					<span class="visually-hidden">Loading...</span>
				  </div>
				</div>`;
			
			getOembed(value).then(response => {
				  node.innerHTML = response.html;
				  let containerW = node.offsetWidth;
				  let iframe = node.querySelector("iframe");
				  if (iframe) {
					  let ratio = containerW / iframe.offsetWidth;
					  iframe.setAttribute("width", (width * ratio));
					  iframe.setAttribute("height", (height * ratio));
				  }

				  let arr = node.querySelectorAll('script').forEach(script => {
						let newScript = Vvveb.Builder.frameDoc.createElement("script");
						newScript.src = script.src;
						script.replaceWith(newScript);
				  });				  
				  
			}).catch(error => console.log(error));

			return node;
		},	
    },{
        name: "Width",
        key: "width",
        child:"iframe",
        htmlAttr: "width",
        inputtype: CssUnitInput
    },{
        name: "Height",
        key: "height",
        child:"iframe",
        htmlAttr: "height",
        inputtype: CssUnitInput
	}]
});

for (const provider of ["youtube", "vimeo", "dailymotion", "flickr", "smugmug", "scribd", "twitter", "soundcloud", "slideshare", "spotify", "imgur", "issuu", "mixcloud", "ted", "animoto", "tumblr", "kickstarter", "reverbnation", "reddit", "speakerdeck", "screencast", "amazon", "someecards", "tiktok","pinterest", "wolfram", "anghami"])  {
	Vvveb.Components.add("embeds/" + provider, {
		name: provider,
		image: "icons/code.svg",
		html: `<div data-component-oembed data-url="">
				<div class="alert alert-light  m-5" role="alert">
					<img width="64" src="${Vvveb.baseUrl}icons/code.svg">
					<h6>Enter ${provider} url to embed</h6>
				</div></div>`,
	});
	Vvveb.ComponentsGroup['Embeds'].push("embeds/" + provider);
}
