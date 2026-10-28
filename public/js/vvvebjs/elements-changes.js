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

function ucFirst(str) {
	if (typeof str !== "string" || !str) return str;

	return str[0].toUpperCase() + str.slice(1);
}

const snakeCaseToCamelCase = str => typeof str === "string" ? str.replace(/(_|^|-)\w/g, letter => `${letter.toUpperCase().replace(/[-_]/, '')}`) : str;

function getElementByXpath(path, element = window.FrameDocument) {
	if (!window.FrameDocument || !element) return [];

	let elements;
	try {
		elements = window.FrameDocument.evaluate(path, element, null, XPathResult.ANY_TYPE, null);
	} catch (e) {
		return [];
	}

	const nodes = [];
	try {
		let current = elements.iterateNext();
		while (current) {
			nodes.push(current);
			current = elements.iterateNext();
		}
	} catch (e) {
		return [];
	}

	return nodes;
}

function getComponents() {
	return getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-component-')] ]", window.FrameDocument);
}

function getElements() {
	if (!window.FrameDocument || typeof window.FrameDocument.querySelectorAll !== "function") return [];
	const nodes = window.FrameDocument.querySelectorAll("[data-v-id]");
	let components = [];

	for (const node of nodes) {
		let componentName = node.dataset.vComponent ?? node.dataset.vType;
		if (!componentName) {
			for (const attribute of node.attributes) {
				if (attribute.name.startsWith("data-v-component-")) {
					componentName = attribute.name.replace("data-v-component-", "");
					break;
				}
			}
		}

		//nodes without a component type carry no editable fields, skip them
		if (!componentName) continue;

		const type = "data-v-" + componentName + "-";
		let name = "";

		const fields = [];
		const fieldsNodes = getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-')] ]", node);
		const seenFields = [];//don't add duplicate or children fields

		for (const fieldNode of fieldsNodes) {
			for (const attribute of fieldNode.attributes) {
				let attr = attribute.name;

				if (attr.indexOf(type) !== 0) continue;

				attr = attr.replace(type, "").toLowerCase();
				if (seenFields.includes(attr)) continue;

				let content = "";
				let preview = "";
				let nodeType = "text";

				if (attr == "image" || fieldNode.nodeName == "IMG") {
					preview = content = fieldNode.getAttribute("src") ?? "";
					nodeType = "img";
				} else if (attr == "url" || fieldNode.nodeName == "A") {
					preview = content = fieldNode.getAttribute("href") ?? "";
					nodeType = "url";
				} else {
					content = String(fieldNode.innerHTML ?? "").trim();
					preview = String(fieldNode.textContent ?? "").slice(0, 100);
				}

				if (attr == "name") {
					name = content;
				}

				fields.push({"name": attr, "node": fieldNode, "value": content, "preview": preview, nodeType});
				seenFields.push(attr);
			}
		}

		components.push({"id": node.dataset.vId, name, "type": node.dataset.vType, component: componentName, node, fields});
	}

	return components;
}

function getComponentFields() {
	return getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-component-')] ]//*[ @*[starts-with(name(), 'data-v-')] ]", window.FrameDocument);
}


class ChangeManager {

	originalContent = {};

	setOriginalContent() {
		this.originalContent = getElements();
	}

	diff(original, changed, includeNode = true, includePreview = true) {

		const changes = {};

		const originalById = {};
		for (const i in original) {
			const orig = original[i];
			if (orig && orig.id != null) originalById[orig.id] = orig;
		}

		for (const i in changed) {
			const component = {...changed[i]};//copy by value
			const origComponent = original[i];

			//new components can't be matched against the original snapshot yet
			if (origComponent === undefined) {
				console.warn("new component skipped", component);
				continue;
			}

			if (!component["fields"]) continue;

			if (!includeNode) {
				delete component['node'];
			}

			const origFieldsByName = {};
			for (const j in origComponent["fields"]) {
				const origField = origComponent["fields"][j];
				if (origField) origFieldsByName[origField.name] = origField;
			}

			const fields = [];

			for (const j in component["fields"]) {
				const field = component["fields"][j];
				if (!field) continue;
				const origField = origFieldsByName[field.name];

				if (origField && (field.value != origField.value)) {
					field["oldValue"] = origField.value;

					if (!changes[i]) {
						changes[i] = component;
					}

					if (!includeNode) {
						delete field['node'];
					}

					if (!includePreview) {
						delete field['preview'];
					}

					fields.push(field);
				}
			}

			if (changes[i]) {
				changes[i]["fields"] = fields;
			}
		}

		return changes;
	}

	getChangedElements(includeNode = false, includePreview = false) {
		return this.diff(this.originalContent, getElements(), includeNode, includePreview);
	}

	render() {
		const selector = "#save-offcanvas .components";
		const changes = this.getChangedElements(false, true);
		let result = "";

		for (const i in changes) {
			const component = changes[i];
			let fields = "";

			for (const j in component["fields"]) {
				try {
					fields += tmpl("vvveb-save-component-field", component["fields"][j]);
				} catch (e) {
					console.error(e);
				}
			}

			component["fields"] = fields;
			component["id"] = i;
			try {
				result += tmpl("vvveb-save-component", component);
			} catch (e) {
				console.error(e);
			}
		}

		const element = document.querySelector(selector);
		if (element) {
			element.innerHTML = result;
		}
	}
}


Vvveb.ChangeManager = new ChangeManager();
