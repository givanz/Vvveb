var noWrapNodes = "p > img, p > video, p > figure, p > iframe";
var wrapNodes = ['B','I','SPAN', 'TABLE', 'P', 'H1', 'H2', 'H3', 'H4'];

function fixWrapper(doc){
	let body = doc.body;

	body.querySelectorAll(noWrapNodes + ",.post-content > *").forEach(e => {
		//if the element is the only node inside the paragraph and is not an inline element
		if (!e.nextSibling && !e.previousSibling && (wrapNodes.indexOf(e.tagName) == -1)) {
			if (e.parentNode.parentNode.tagName !== "HTML") {
				let div = body.ownerDocument.createElement('div');
				e.parentNode.parentNode.replaceChild(div, e.parentNode);
				//e.parentNode.parentNode.insertBefore(div, e.parentNode);
				div.append(e);
				//e.parentNode.remove();
				//e.parentNode.parentNode.replaceChild(e, e.parentNode);
			}
		}
	});

	if(!body.querySelector('.post-content') && body.tagName == "BODY" && body.id == "tinymce"){
		  const wrappingElement = body.ownerDocument.createElement('div');
		  wrappingElement.setAttribute("class","post-content");
		  
		  
		  while(body.firstChild) {
			wrappingElement.appendChild(body.firstChild);
		  }
		  
		  body.appendChild(wrappingElement);
    }	
}

const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;

let tinyMceSkin = "oxide";
let tinyMceTheme = 'auto'
if (window.matchMedia("(prefers-color-scheme: dark)").matches || (document.documentElement.dataset.bsTheme == "dark")) {
	tinyMceSkin = "oxide-dark";
	tinyMceTheme = 'dark';
}

let make_wysiwyg = function(inst){
    fixWrapper(inst.contentDocument);
     //set dark theme
    inst.contentDocument.documentElement.setAttribute("data-bs-theme", tinyMceTheme);
}


const tinymce_image_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
	
  const xhr = new XMLHttpRequest();
  xhr.withCredentials = false;
  xhr.open('POST', uploadUrl);

  xhr.upload.onprogress = (e) => {
    progress(e.loaded / e.total * 100);
  };

  xhr.onload = () => {
    if (xhr.status === 403) {
      reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
      return;
    }

    if (xhr.status < 200 || xhr.status >= 300) {
      reject('HTTP Error: ' + xhr.status);
      return;
    }
	/*
    const json = JSON.parse(xhr.responseText);

    if (!json || typeof json.location != 'string') {
      reject('Invalid JSON: ' + xhr.responseText);
      return;
    }

    resolve(json.location);*/
	resolve(mediaPath + "/" + xhr.responseText);
  };

  xhr.onerror = () => {
    reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
  };

  const formData = new FormData();
  formData.append('file', blobInfo.blob(), blobInfo.filename());
  formData.append('file', blobInfo.blob(), blobInfo.filename());

  formData.append("mediaPath", mediaPath);
  formData.append("onlyFilename", true);


  xhr.send(formData);
});

let tinyThemeFonts = '';
if (typeof themeFonts !== 'undefined' && themeFonts) {
	for (const font in themeFonts) {
		tinyThemeFonts += font + ';';
	}
}

let tinyMceOptions = {
  selector: "textarea.html",
  body_class: "container",
  init_instance_callback : make_wysiwyg,
  paste_preprocess: (editor, args) => {
    if (args.content.startsWith('http')) {
		    let url = args.content;
			args.content = '';
			getOembed(url, 800, 600, true).then(response => {
				 if (response && response.html) {
					editor.insertContent( '<div class="iframe" data-url="' + url + '" data-component-iframe>' + response.html.replace('<iframe ','<iframe class="align-center" ') + '</div>' );
 				    let arr = editor.contentDocument.getElementsByTagName('script');
					
					for (let n = 0; n < arr.length; n++) {
						let script = arr[n];
						if (!script.hasAttribute("type")) continue;//process only new scripts that have the tinymce attribute
						let newScript = editor.contentDocument.createElement("script");
						newScript.src = script.src;
						script.replaceWith(newScript);
					}	
				 } else {
					 editor.insertContent(url);
				 }
			}).catch(error => {
				//editor.insertContent(url);
			});
	}	
  },  
  setup: function (editor) {
	//set changes to false to avoid saving unchanged revisions if text is not changed
	let has_changes = document.getElementById(editor.id)?.parentNode.querySelector(".has_changes");
	if (has_changes) {
		has_changes.value = "0";
	}
		
		let gallery;
		editor.on( 'click keyup', function() {
			let node = editor.selection.getNode();
			
			if (gallery) {
				gallery.removeAttribute('data-mce-selected');
			}
			gallery = node.closest('[data-component-gallery]');
			if ( gallery ) {
				gallery.setAttribute( 'data-mce-selected', '2' );
			}
		} );
		
		editor.on( 'dblclick ', function() {
			let node = editor.selection.getNode();
			if (node.tagName == "IMG" || node.hasAttribute("data-mce-object")) {
				let obj = node.getAttribute("data-mce-object");
				if (obj == "video" || obj == "audio") {
					node = node.querySelector("video, audio");
				}
				if (!Vvveb.MediaModal) {
					Vvveb.MediaModal = new MediaModal(true);
					Vvveb.MediaModal.mediaPath = mediaPath;
				}
				Vvveb.MediaModal.type = "single";
				Vvveb.MediaModal.open(null, function (file) {
						node.setAttribute("src", file);
						if (node.parentNode && node.parentNode.tagName == 'A') {
							node.parentNode.setAttribute("href", file);
						}
						/*
						editor.insertContent(editor.dom.createHTML('img', {
							src: file,
							"class": "align-center"
						}));*/
				})
			}
		} );
		
		editor.ui.registry.addButton('gallery', {
        tooltip: 'Insert gallery',
        icon: 'gallery',
        onAction: function () {
			let galleryHTML = `<div class="gallery masonry has-shadow" data-component-gallery="">
				<div class="item"><a href="#"><img src="../../media/posts/1.jpg"> </a></div>
			</div>`;
			
			editor.insertContent(galleryHTML);
			let node = editor.selection.getNode();
			let gallery = node.closest('[data-component-gallery]');
			gallery.setAttribute( 'data-mce-selected', '2' );
			let img = gallery.querySelector("img");
			editor.selection.select(img);


			if (!Vvveb.MediaModal) {
				Vvveb.MediaModal = new MediaModal(true);
				Vvveb.MediaModal.mediaPath = mediaPath;
			}
			Vvveb.MediaModal.type = "multitple";
			Vvveb.MediaModal.open(null, function (src, file, files) {
				if (src) {
					let imgs = "";
					for (img of src.split(",")) if (img) {
						imgs +=
						'<div class="item"><a href="' + img + '">' + editor.dom.createHTML('img', {
							src: img,
							"class": "align-center"
						}) + "</a></div>";
						//gallery.append(generateElements(image)[0]);
						//editor.insertContent(image);
					}
					editor.insertContent(imgs);
				}
			});
		}});	
							

		editor.ui.registry.addButton( 'removegallery', {
			tooltip: 'Remove gallery',
			icon: 'remove',
			onAction: function() {
				//editor.fire( 'cut' );
				let node = editor.selection.getNode();
				let gallery = node.closest('[data-component-gallery]');
				editor.selection.setNode(gallery.prevElementSibling);
				gallery.remove();
			}
		} );	
		
		editor.ui.registry.addButton( 'addimage', {
			tooltip: 'Add',
			icon: 'plus',
			onAction: function() {
				let galleryItemHTML = `<div class="item"><a href="#"><img src="../../media/posts/1.jpg"></a></div>`;
				let node = editor.selection.getNode();
				let gallery = node.closest('[data-component-gallery]');
				editor.selection.setCursorLocation(gallery, gallery.children.length);
				
				
				if (!Vvveb.MediaModal) {
					Vvveb.MediaModal = new MediaModal(true);
					Vvveb.MediaModal.mediaPath = mediaPath;
				}
				Vvveb.MediaModal.type = "multiple";
				Vvveb.MediaModal.open(null, function (src, file, files) {
					if (src) {
						let imgs = "";
						for (img of src.split(",")) if (img) {
							imgs +=
							'<div class="item"><a href="' + img + '">' + editor.dom.createHTML('img', {
								src: img,
								"class": "align-center"
							}) + "</a></div>";
							//gallery.append(generateElements(image)[0]);
							//editor.insertContent(image);
						}
						editor.insertContent(imgs);
					}
				});				
				
				//gallery.append(generateElements(galleryItemHTML)[0]);
				//editor.insertContent(galleryItemHTML);
			}
		} );	
	
		editor.ui.registry.addButton( 'editimage', {
			tooltip: 'Edit',
			icon: 'edit-block',
			onAction: function() {
				if (!Vvveb.MediaModal) {
					Vvveb.MediaModal = new MediaModal(true);
					Vvveb.MediaModal.mediaPath = mediaPath;
				}
				Vvveb.MediaModal.type = "single";
				Vvveb.MediaModal.open(null, function (file) {
						let node = editor.selection.getNode();
						node.setAttribute("src", file);
						if (node.parentNode && node.parentNode.tagName == 'A') {
							node.parentNode.setAttribute("href", file);
						}						
						/*
						editor.insertContent(editor.dom.createHTML('img', {
							src: file,
							"class": "align-center"
						}));*/
				})
			}
		} );		
		
		editor.ui.registry.addButton( 'upimage', {
			tooltip: 'Up',
			icon: 'chevron-up',
			onAction: function() {
				let img = editor.selection.getNode();
				let node = img.closest('div.item') || img;

				oldParent = node.parentNode;
				oldNextSibling = node.nextSibling;

				next = node.previousElementSibling;
				
				if (next) {
					next.before(node);
				} else {
					//node.parentNode.before(node);
				}
				editor.selection.select(img);
			}
		} );
				
				
		editor.ui.registry.addButton( 'downimage', {
			tooltip: 'Down',
			icon: 'chevron-down',
			onAction: function() {
				let img = editor.selection.getNode();
				let node = img.closest('div.item') || img;
				
				oldParent = node.parentNode;
				oldNextSibling = node.nextSibling;

				next = node.nextElementSibling;
				
				if (next) {
					next.after(node);
				} else {
					//node.parentNode.after(node);
				}
				editor.selection.select(img);
			}
		} );
		
	editor.ui.registry.addButton('alignment', {
          type: 'listbox',
          text: 'Alignment',
          icon: '',
          onAction: function(e) {
            tinyMCE.execCommand(this.value());
          },
          values: [
              {icon: 'alignleft', value: 'JustifyLeft'},
              {icon: 'alignright', value: 'JustifyRight'},
              {icon: 'aligncenter', value: 'JustifyCenter'},
              {icon: 'alignjustify', value: 'JustifyFull'},
          ],
          onPostRender: function() {
            // Select the firts item by default
            this.value('JustifyLeft');
          }
      });
	  
	  let masonryState = true;
	  let shadowState = true;
	  editor.ui.registry.addMenuButton('gallery-options', {
        text: '',
		tooltip: 'Gallery options',
        icon: 'gallery',
        fetch: (callback) => {
          const items = [
            {
              type: 'togglemenuitem',
              active: true,
			  icon: 'table-cell-properties',
              text: 'masonry',
			  onAction: () => {
				let node = editor.selection.getNode();
				let gallery = node.closest('[data-component-gallery]');
				gallery.classList.toggle("masonry");
				masonryState = gallery.classList.contains("masonry");
			  },
			  onSetup: (api) => {
				api.setActive(masonryState);
				return () => {};
			  }			  
            },
            {
              type: 'togglemenuitem',
			  active: true,
              text: 'Shadow',
              icon: 'select-all',
              onAction: () => {
				let node = editor.selection.getNode();
				let gallery = node.closest('[data-component-gallery]');
				gallery.classList.toggle("has-shadow");
				shadowState = gallery.classList.contains("has-shadow");
              },
			  onSetup: (api) => {
				api.setActive(shadowState);
				return () => {};
			  }			  
            }
          ];
          callback(items);
        }
      });	 
	  
	  
	let columnsItems = [];  
	let activeColumn = 4;
    for (let i = 1;i <= 12;i++) {
		columnsItems.push({
              type: 'togglemenuitem',
			  value: i,
              text: i + ' Column' + (i > 1 ? 's':''),
              onAction: () => {
				let node = editor.selection.getNode();
				let gallery = node.closest('[data-component-gallery]');
				gallery.style.columnCount = i;
				activeColumn = i;
              },
			  onSetup: (api) => {
				if (api.setActive) {
					api.setActive(activeColumn == i);
				}
				return () => {};
			  }			  
            });
	}
	editor.ui.registry.addMenuButton('columns', {
        text: '',
		tooltip: 'Columns',
        icon: 'duplicate-column',
        fetch: (callback) => {
          callback(columnsItems);
        }
      });

      editor.ui.registry.addButton('alignmentv2', {
            type: 'menubutton',
            text: 'Alignment v2',
            icon: '',
			onAction: function(e) {
				tinyMCE.execCommand(this.value());
			},
            menu: [
                {icon: 'alignleft', onAction: function() { tinyMCE.execCommand('JustifyLeft'); }},
                {icon: 'alignright', onAction: function() { tinyMCE.execCommand('JustifyRight'); }}
            ]
        });		

    editor.ui.registry.addContextToolbar('imagealignment', {
      predicate: (node) => node.hasAttribute("data-component-gallery"),
      items: 'addimage gallery-options columns layout shadow removegallery',
      position: 'node',
      scope: 'node'
    });
	

	 editor.ui.registry.addButton('quickmedia', {
        icon: 'image',
        tooltip: 'Insert media',
        onAction: function () {
			if (!Vvveb.MediaModal) {
				Vvveb.MediaModal = new MediaModal(true);
				Vvveb.MediaModal.mediaPath = mediaPath;
			}
			Vvveb.MediaModal.type = "single";
			Vvveb.MediaModal.open(null, function (src, file, files) {
				if (src) {
					let imgs = "";
					for (img of src.split(",")) if (img) {
						imgs +=
						'<div><a href="' + img + '">' + editor.dom.createHTML('img', {
							src: img,
							"class": "align-center"
						}) + "</a></div>";
					}
					editor.insertContent(imgs);
				}
			});
			//editor.fire("blur");
			editor.dispatch("blur");
        }
      });
      	
      editor.on('GetContent',function (ed, o) {
			
			if (ed.content.indexOf('<div class="post-content">') > -1) {
				ed.content = ed.content.replace('<div class="post-content">', '').slice(0, -6);
			}
            // remove wrapper prior to extracting content
        });
		/*
        editor.on('NodeChange',function (ed, e) {
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        editor.on('NewBlock',function (ed, e) {
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        editor.on('Change',function (ed, e) {
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        */
        editor.on('SetContent',function (ed, e) {
            fixWrapper(this.contentDocument); // if wrapper has been deleted, add it back
        });
		
		
		editor.on('change', function(ed, e)  {
			// text changed set has_changes flag to save revision
			ed.target.container.parentNode.querySelector(".has_changes").value = "1";
		});

		window.dispatchEvent(new CustomEvent("tinymce.setup", {detail: editor}));
		
		
    //product and posts/pages autocomplete
    const onAction = (autocompleteApi, rng, value) => {
      editor.selection.setRng(rng);
      editor.insertContent(value);
      autocompleteApi.hide();
    };

	let insertActions = [
        {
            text: 'Heading 1',
            icon: 'h1',
            action: function () {
                editor.execCommand('mceInsertContent', false, '<h1>Heading 1</h1>')
                editor.selection.select(editor.selection.getNode());
            }
        },
        {
            text: 'Heading 2',  
            icon: 'h2',
            action: function () {
                editor.execCommand('mceInsertContent', false, '<h2>Heading 2</h2>');
                editor.selection.select(editor.selection.getNode());
            }
        },
        {
            text: 'Heading 3',
            icon: 'h3',
            action: function () {
                editor.execCommand('mceInsertContent', false, '<h3>Heading 3</h3>');
                editor.selection.select(editor.selection.getNode());
            }
        },
		{
			type: 'separator'
		},
        {
            text: 'Table',
            icon: 'table',
            action: function () {
                editor.execCommand('mceInsertTable', false, { rows: 2, columns: 2 });
            }
        }, 
        {
            text: 'Gallery',
            icon: 'gallery',
            action: function () {
                editor.ui.registry.getAll().buttons['gallery'].onAction();
            }
        },
		{
            text: 'Image',
            icon: 'image',
            action: function () {
				//editor.execCommand('mceImage');
                //editor.execCommand('InsertImage', false, '../img/logo.png');
				if (!Vvveb.MediaModal) {
					Vvveb.MediaModal = new MediaModal(true);
					Vvveb.MediaModal.mediaPath = mediaPath;
				}
				Vvveb.MediaModal.type = "single";
				Vvveb.MediaModal.open(null, function (file) {
						editor.insertContent('<div><a href="' + file + '">'  + editor.dom.createHTML('img', {
							src: file,
							"class": "align-center"
						}) + "</a></div>");
				});				
            }
        }, {
            text: 'Video',
            icon: 'embed',
            action: function () {
				let header = `<video class="align-center" src="../../media/demo/sample.webm" controls="true">`;
                editor.execCommand('mceInsertContent', false, header);
                editor.selection.select(editor.selection.getNode());            
			}
        }, {
            text: 'Audio',
            icon: 'embed',
            action: function () {
				let header = `<figure class="align-center" data-component-audio><audio src="../../media/demo/sample.webm" controls="true"></audio></figure>`;
                editor.execCommand('mceInsertContent', false, header);
                editor.selection.select(editor.selection.getNode());            
			}
        }, {
			type: 'separator'
		}, {
            text: 'Bulleted list',
            icon: 'unordered-list',
            action: function () {
                editor.execCommand('InsertUnorderedList', false);
            }
        }, {
            text: 'Numbered list',
            icon: 'ordered-list',
            action: function () {
                editor.execCommand('InsertOrderedList', false);
            }
        }, {
            text: 'Separator',
            icon: 'line',
            action: function () {
                editor.execCommand('InsertHorizontalRule');
            }
        }, {
			type: 'separator'
		}, {
            text: 'Header',
            icon: 'unselected',
            action: function () {
				let header = `<div class="p-5 mb-4 bg-body-tertiary rounded-3 text-center">
				  <div class="container-fluid py-5">
					<h1 class="display-5 fw-bold">Custom jumbotron</h1>
					<p class="fs-4">Using a series of utilities, you can create this jumbotron, just like the one in previous versions of Bootstrap. Check out the examples below for how you can remix and restyle it to your liking.</p>
					
					<img src="../../media/placeholder.svg" class="align-center my-4" width="250">
					
					<a href="#" class="btn btn-primary btn-lg" type="button">Example button</a>
				  </div>
				</div>`;
                editor.execCommand('mceInsertContent', false, header);
                //editor.selection.select(editor.selection.getNode());
            }
         }, {
            text: 'Header dark',
            icon: 'color-swatch',
            action: function () {
				let header = `<div class="text-center">
					<div class="h-100 p-5 text-bg-dark rounded-3">
					  <h1 class="display-5 fw-bold">Custom jumbotron</h1>
					  <p class="fs-4">Using a series of utilities, you can create this jumbotron, just like the one in previous versions of Bootstrap. Check out the examples below for how you can remix and restyle it to your liking.</p>
					  <img src="../../media/placeholder.svg" class="align-center my-4" width="250">
					  <a  href="#" class="btn btn-light btn-lg" type="button">Example button</a>
					</div>
				  </div>`;
                editor.execCommand('mceInsertContent', false, header);
                //editor.selection.select(editor.selection.getNode());
            }
        }, {
            text: 'Card',
            icon: 'copy',
            action: function () {
				let header = `<div class="card align-center w-25">
				  <img src="../../media/placeholder.svg" class="card-img-top align-center" width="250">
				  <div class="card-body">
					<h5 class="card-title">Card title</h5>
					<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					<a href="#" class="btn btn-primary">Go somewhere</a>
				  </div>
				</div>`;
                editor.execCommand('mceInsertContent', false, header);
                //editor.selection.select(editor.selection.getNode());
            }
        }, {
            text: 'Button',
            icon: 'link',
            action: function () {
				let header = `<a href="#" type="button" class="btn btn-primary">Primary</a>`;
                editor.execCommand('mceInsertContent', false, header);
                //editor.selection.select(editor.selection.getNode());
            }
        }, {
            text: 'Alert',
            icon: 'warning',
            action: function () {
				let header = `<p class="alert alert-primary" role="alert">
				  💡 A simple primary alert—check it out!
				</p>`;
                editor.execCommand('mceInsertContent', false, header);
                //editor.selection.select(editor.selection.getNode());
            }
        }, {
            text: 'File Download',
            icon: 'new-document',
            action: function () {
				let header = `<a href="#" class="btn btn-primary" type="button" download><span>Download file</span> <i class="la la-download"></i></a>`;
                editor.execCommand('mceInsertContent', false, header);

				let node = editor.selection.getNode();
                editor.selection.select(node);
				node = node.querySelector("a") || node;
				
				if (!Vvveb.MediaModal) {
					Vvveb.MediaModal = new MediaModal(true);
					Vvveb.MediaModal.mediaPath = mediaPath;
				}
				Vvveb.MediaModal.type = "single";
				
				Vvveb.MediaModal.open(null, function (file) {
						node.setAttribute("href", file);
						node.setAttribute("data-mce-href", file);
				});
			}
        }, {
            text: 'Accordion',
            icon: 'accordion',
            action: function () {
				let summary = `<details class="align-center">
					 <summary>More information </summary>
					 <p>Here is the source data that is discussed in the article ...</p>
					</details>`;
                editor.execCommand('mceInsertContent', false, summary);
			}
        }, {
            text: 'Code',
            icon: 'sourcecode',
            action: function () {
				let code = `<pre class="p-4"><code>print "Hello world!"</code></pre>`;
                editor.execCommand('mceInsertContent', false, code);
			}
        },
		/*
		{
			type: 'separator'
        }, {
            text: 'Embed',
            icon: 'embed',
            action: function () {
				//editor.execCommand('mceLink', false, { dialog: true });
				editor.dispatch('contexttoolbar-show', { toolbarKey: 'quicklink' });
                //editor.execCommand('mceMedia');
            }
        }, {
            text: 'Youtube',
            icon: 'embed',
            action: function () {
                editor.execCommand('mceMedia');
            }
        }, {
            text: 'Twitter',
            icon: 'embed-page',
            action: function () {
                editor.execCommand('mceMedia');
            }
        }*/
    ];

    // Register the slash commands autocompleter
    editor.ui.registry.addAutocompleter('slashcommands', {
        ch: '/',
        minChars: 0,
        columns: 1,
        fetch: function (pattern) {
            const matchedActions = insertActions.filter(function (action) {
                return action.type === 'separator' ||
                    action.text.toLowerCase().indexOf(pattern.toLowerCase()) !== -1;
            });

            return new Promise((resolve) => {
                var results = matchedActions.map(function (action) {
                    return {
                        meta: action,
                        text: action.text,
                        icon: action.icon,
                        value: action.text,
                        type: action.type
                    }
                });
                resolve(results);
            });
        },
        onAction: function (autocompleteApi, rng, action, meta) {
            editor.selection.setRng(rng);
            // Some actions don't delete the "slash", so we delete all the slash
            // command content before performing the action
            editor.execCommand('Delete');
            meta.action();
            autocompleteApi.hide(); 
        }
    });

    editor.ui.registry.addAutocompleter('links', {
      ch: '/',
      minChars: 1,
      columns: 1,
      highlightOn: ['char_name'],
      onAction: onAction,
      fetch: (pattern) => {
        return new Promise((resolve) => {
		fetch(linkUrl + "&"+  new URLSearchParams({text:pattern}))
		.then((response) => {
			if (!response.ok) { throw new Error(response) }
			return response.json()
		})
		.then((data) => {
			 const results = data.map(char => ({
				type: 'cardmenuitem',
				value: char.value,
				label: char.text,
				items: [
				  {
					type: 'cardcontainer',
					direction: 'horizontal',
					items: [
					  {
						 type: 'cardimage',
						 src: char.src,
						 alt: char.text,
						 classes: ['w-25', 'me-2']
					  },
					  {
						type: 'cardtext',
						text: char.text,
						name: 'char_name'
					  }
					]
				  }
				]
			  }));
			resolve(results);					 
		})
		.catch(error => {
			console.log(error);
			displayToast("danger", "Error", "Error renaming page!");
		});	
        });
      }
    });
    },
    
  plugins: 'preview searchreplace autolink autosave autoresize directionality code visualblocks visualchars fullscreen image media link table charmap lists wordcount quickbars emoticons table accordion advlist anchor paste_from_word',
  //valid_children : '-p[img],h1[img],h2[img],h3[img],h4[img],+body[img],div[img],div[h1],div[h2],div[h3]',
  //editimage_cors_hosts: ['picsum.photos'],
  menubar: false,//'file edit view insert format tools table help',
  toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | insertfile image gallery media link anchor | ltr rtl table accordion | fullscreen preview code print |  charmap emoticons advlist visualblocks searchreplace',
  toolbar_sticky: true,
  //toolbar_sticky_offset: isSmallScreen ? 102 : 108,
  autosave_ask_before_unload: true,
  autosave_interval: '30s',
  autosave_prefix: '{path}{query}-{id}-',
  autosave_restore_when_empty: false,
  autosave_retention: '2m',
  image_advtab: true,
  /*
  link_list: [
	{ title: 'My page 1', value: 'https://www.tiny.cloud' },
	{ title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  image_list: [
	{ title: 'My page 1', value: 'https://www.tiny.cloud' },
	{ title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  */
  image_class_list: [
	{ title: 'None', value: '' },
	{ title: 'Fluid', value: 'img-fluid' }
  ],
  importcss_append: true,
  relative_urls : false,
  convert_urls : false,  
  file_picker_callback: function (callback, value, meta) {
	if (!Vvveb.MediaModal) {
		Vvveb.MediaModal = new MediaModal(true);
		Vvveb.MediaModal.mediaPath = mediaPath;
	}
	Vvveb.MediaModal.type = "single";
	Vvveb.MediaModal.open(null, (src, file, files) => callback(src));
  },/*
	templates: [{
		title: 'New Table',
		description: 'creates a new table',
		content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>'
	}, {
		title: 'Starting my story',
		description: 'A cure for writers block',
		content: 'Once upon a time...'
	}, {
		title: 'New list with dates',
		description: 'New List with dates',
		content: '<div class="mceTmpl"><span class="cdate">cdate</span><br /><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>'
	}],*/
  template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
  template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
  height: 600,
  image_caption: true,
  quickbars_insert_toolbar: 'quickmedia quicklink quicktable gallery',
  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 h4 blockquote | quickimage quicktable',
  quickbars_image_toolbar: 'alignleft aligncenter alignright | rotateleft rotateright imageoptions upimage downimage | editimage',
  noneditable_noneditable_class: 'mceNonEditable',
  toolbar_mode: 'wrap',
  contextmenu: 'link image editimage table',
  skin: tinyMceSkin,//'oxide',
  content_css: vvvebThemeCss,
  //content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
  content_style: "body {padding: 10px}",
  images_reuse_filename: true,
  images_upload_url: uploadUrl,
  verify_html: false,
  //force_br_newlines: true,
  //forced_root_block : false,
  //force_p_newlines : false,
  //convert_newlines_to_brs : true,
  /*
  forced_root_block : false,
  force_p_newlines : true,
  */ 
  //extended_valid_elements:"div[*]",
  //extended_valid_elements : "iframe[src|width|height|name|align]",
  //invalid_elements: "",
  //forced_root_block : "div",
  //extended_valid_elements : "script[language|type|src]|iframe[src|width|height|name|align]",
  //valid_elements: "*[*]",
  apply_source_formatting : false,
  verify_html : false,
  paste_webkit_styles: "all",
  paste_remove_styles_if_webkit: false,  
  /* we override default upload handler to simulate successful upload*/
  images_upload_handler: tinymce_image_upload_handler,
	formats: {
		alignleft: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "left"
			}
		}, {
			selector: "img,figure,table,video,audio,dl.caption,iframe",
			classes: "align-left"
		}],
		aligncenter: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "center"
			}
		}, {
			selector: "img,figure,table,video,audio,dl.caption,iframe",
			classes: "align-center"
		}],
		alignright: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "right"
			}
		}, {
			selector: "img,figure,table,video,audio,dl.caption,iframe",
			classes: "align-right"
		}],
		strikethrough: {
			inline: "del"
		}
	},	
	
    font_family_formats: "Arial Black=arial black,avant garde; Courier New=courier new,courier;" + tinyThemeFonts,
	external_plugins: {
		//'test': '../tinymce-plugins/test/plugin.js',
	},    
	branding:false,
	media_live_embeds:true,
	media_filter_html:false,
	visual_table_class:"table mce-item-table",
	table_default_attributes: {
		"class": 'table table-bordered',
   },
     link_quicklink: true,
	link_list: linkUrl,   
  table_class_list: [
	{title: 'None', value: 'table'},
	{title: 'striped', value: 'table-striped'},
	{title: 'dark', value: 'table-dark'},
	{title: 'hover', value: 'table-hover'},
	{title: 'bordered', value: 'table-bordered'},
	{title: 'primary', value: 'table-primary'},
	{title: 'secondary', value: 'table-secondary'},
	{title: 'success', value: 'table-success'},
	{title: 'danger', value: 'table-danger'},
	{title: 'warning', value: 'table-warning'},
	{title: 'info', value: 'table-info'},
	{title: 'light', value: 'table-light'},
	{title: 'dark', value: 'table-dark'},
  ],
  table_cell_class_list: [
	{title: 'primary', value: 'table-primary'},
	{title: 'secondary', value: 'table-secondary'},
	{title: 'success', value: 'table-success'},
	{title: 'danger', value: 'table-danger'},
	{title: 'warning', value: 'table-warning'},
	{title: 'info', value: 'table-info'},
	{title: 'light', value: 'table-light'},
	{title: 'dark', value: 'table-dark'},
  ],
  table_row_class_list: [
	{title: 'primary', value: 'table-primary'},
	{title: 'secondary', value: 'table-secondary'},
	{title: 'success', value: 'table-success'},
	{title: 'danger', value: 'table-danger'},
	{title: 'warning', value: 'table-warning'},
	{title: 'info', value: 'table-info'},
	{title: 'light', value: 'table-light'},
	{title: 'dark', value: 'table-dark'},
  ]
};
