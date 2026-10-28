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

/**
 * Placeholder shown until an url is embedded.
 */
function embedPlaceholder(label = "Enter url to embed") {
	return `<div data-component-oembed data-url="">
			<div class="alert alert-light m-5" role="alert">
				<img width="64" src="${Vvveb.baseUrl}icons/code.svg">
				<h6>${label}</h6>
			</div></div>`;
}

//recreate scripts so they execute inside the frame document
//(scripts inserted via innerHTML never run)
function recreateScripts(node) {
	if (!node || typeof node.querySelectorAll !== "function") return;
	const frameDoc = Vvveb.Builder.frameDoc;
	if (!frameDoc || typeof frameDoc.createElement !== "function") return;

	node.querySelectorAll('script').forEach(script => {
		try {
			const newScript = frameDoc.createElement("script");
			//keep src/async/defer/type and provider data attributes
			for (const attr of script.attributes) {
				newScript.setAttribute(attr.name, attr.value);
			}
			if (!script.src) {
				//inline scripts (player init code etc.) would stay dead otherwise
				newScript.textContent = script.textContent;
			}
			script.replaceWith(newScript);
		} catch (e) {
			//best effort - don't fail the whole embed for one script
			console.warn("Vvveb: could not re-create script", script.src, e);
		}
	});
}

Vvveb.ComponentsGroup['Embeds'] = ["embeds/embed"];

Vvveb.Components.extend("_base", "embeds/embed", {
    name: "Embed",
    attributes: ["data-component-oembed"],
    image: "icons/code.svg",
	html: embedPlaceholder("Enter url to embed"),

    properties: [{
        name: "Url",
        key: "url",
		htmlAttr: "data-url",
        inputtype: TextInput,
        onChange: function(node, value) {
			if (!node) return node;
			value = String(value ?? "").trim();

			//clearing the url restores the placeholder instead of requesting an embed for ""
			if (!value) {
				node.innerHTML = embedPlaceholder();
				return node;
			}

			if (typeof getOembed !== "function") {
				console.error("Vvveb: embed failed, oembed.js is not loaded");
				return node;
			}

			node.innerHTML = `<div class="alert alert-light d-flex justify-content-center">
				  <div class="spinner-border m-5" role="status">
					<span class="visually-hidden">Loading...</span>
				  </div>
				</div>`;

			//ignore stale responses when the url is edited again before load finishes
			const requestId = (node._oembedRequest = (node._oembedRequest || 0) + 1);

			getOembed(value).then(response => {
				if (requestId !== node._oembedRequest) return;
				if (!response || !response.html) {
					throw new Error("Empty embed response");
				}

				node.innerHTML = response.html;

				//scale the iframe up from its intrinsic size to the container width
				const containerW = node.offsetWidth;
				const iframe = node.querySelector("iframe");

				if (iframe && containerW > 0 && iframe.offsetWidth > 0) {
					const ratio = containerW / iframe.offsetWidth;
					iframe.setAttribute("width", Math.round(containerW));
					iframe.setAttribute("height", Math.round(iframe.offsetHeight * ratio));
				}

				recreateScripts(node);
			}).catch(error => {
				if (requestId !== node._oembedRequest) return;
				console.error("Vvveb: embed failed", error);
				//don't leave the spinner spinning forever
				node.innerHTML = `<div class="alert alert-danger m-5" role="alert">
					<h6>Error embedding url</h6>
					<p class="mb-0">The url could not be embedded, please check it and try again.</p>
				</div>`;
			});

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
		name: provider[0].toUpperCase() + provider.slice(1),
		image: "icons/code.svg",
		html: embedPlaceholder(`Enter ${provider} url to embed`),
	});
	if (!Vvveb.ComponentsGroup['Embeds'].includes("embeds/" + provider)) {
		Vvveb.ComponentsGroup['Embeds'].push("embeds/" + provider);
	}
}
