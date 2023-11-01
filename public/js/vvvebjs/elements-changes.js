function ucFirst(str) {
  if (!str) return str;

  return str[0].toUpperCase() + str.slice(1);
}

const snakeCaseToCamelCase = str => str.replace(/(_|^|-)\w/g, letter => `${letter.toUpperCase().replace(/[-_]/, '')}`);

function getElementByXpath(path, element = window.FrameDocument) {
	let elements = window.FrameDocument.evaluate(path, element, null, XPathResult.ANY_TYPE, null);
  
    components = [];
	let currentComponent = elements.iterateNext();
	while (currentComponent) {
	  components.push(currentComponent);
	  currentComponent = elements.iterateNext();
	}  
	
	return components;
}

function getComponents() {

	let elements = getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-component-')] ]",  window.FrameDocument);
	return elements;
}

function getElements() {

	let nodes = window.FrameDocument.querySelectorAll("[data-v-id]");
	let component = false;
	let components = [];
	
	for (let i = 0; i < nodes.length; i++) {
	  let node = nodes[i];
	  let type = "v" + snakeCaseToCamelCase(node.dataset.vType);
      let name = "";
      let componentName = node.dataset.vComponent ?? node.dataset.vType;

	  let fields = []; 
	  let fieldsNodes = getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-')] ]",  node);

	  for (let j = 0; j < fieldsNodes.length; j++) {
		  let fieldNode = fieldsNodes[j];
		  let field = false;
		  let content = "";
		  let preview = "";

		  for ( attr in fieldNode.dataset) {
				
				value = fieldNode.dataset[attr];
				if (attr.indexOf(type) == 0) {
					
					attr = attr.replace(type, "").toLowerCase();

					//console.log(attr, value);
					
					let nodeType = "text";

					if (attr == "image" || fieldNode.nodeName == "IMG") {
						preview = content = fieldNode.getAttribute("src");//fieldNode.src
						nodeType = "img";
					} else if (attr == "url" || fieldNode.nodeName == "A") {
						preview = content = fieldNode.getAttribute("href");//fieldNode.href
						nodeType = "url";
					} else {
						//content = fieldNode.textContent.trim();
						content = fieldNode.innerHTML.trim();
						preview = fieldNode.textContent.substr(0, 100);
					}
					
					field = {"name":attr, "node":fieldNode, "value": content, "preview": preview, nodeType};
					
                    if (attr == "name") {
                        name = content;
                    }
				}
				
		  }
		  
		  
		  if (field) {
			  fields.push(field);
		  }
	  }
	  
	  component = {"id": node.dataset.vId, name, "type":node.dataset.vType, component:componentName, node, fields};
	  components.push(component);
	}

	return components;	
}

function getComponentFields() {

	return getElementByXpath(".//*[ @*[starts-with(name(), 'data-v-component-')] ]//*[ @*[starts-with(name(), 'data-v-')] ]",  window.FrameDocument);
}



class ChangeManager {
	
	styles = {};
	cssContainer = false;
	originalContent = {};
	
	constructor() {
	}

	setOriginalContent() {
		this.originalContent = getElements();
	}
	
	diff(original, changed, includeNode = true, includePreview = true) {
		
		let changes = {};
		let _new = {};
		
		//todo match by component type and id
		for (i in changed) {
			let component = {...changed[i]};//copy by value
			let origComponent = original[i];

			if ((origComponent === undefined)  || (origComponent.id != component.id)) {
				console.warn("id mismatch", component, origComponent);
				continue;
			}
							
			if (origComponent) {
                if (!includeNode) {
                    delete component['node'];
                }
                
				if (component["fields"]) {
					let fields = [];	
				
					for (j in component["fields"]) {
						
						let field = component["fields"][j];
						let origField = origComponent["fields"][j];

						if (field && origField && (field.value != origField.value)) {
							
								if (!changes[i]) {
									changes[i] = component;
									//changes[i]["fields"] = [];
									field["oldValue"] = origField.value;
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
			} else {
				//new
				changes[i] = component;
			}
		}
		
		return changes;
	}

	getChangedElements(includeNode = false, includePreview = false) {
		
		return this.diff(this.originalContent, getElements(), includeNode);
	}
	
	render() {
        let selector = "#save-offcanvas .components";
		let changes = this.getChangedElements(false, true);
        let result = "";
        
        for (i in changes) {
            let component = changes[i];
            let fields = "";
            
            for (j in component["fields"]) {
                let field = component["fields"][j];
                fields += tmpl("vvveb-save-component-field", field);
            }
            
            component["fields"] = fields;
            component["id"] = i;
            result += tmpl("vvveb-save-component", component);
        }
        
        let element = document.querySelector(selector);
        element.innerHTML = result;
        //
	}
}


Vvveb.ChangeManager = new ChangeManager();
