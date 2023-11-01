var noWrapNodes = "p > img,p > video, p > figure";
var wrapNodes = ['B','I','SPAN', 'TABLE', 'P', 'H1', 'H2', 'H3', 'H4'];

function fixWrapper(body){
	$(body).find(noWrapNodes + ",.post-content > *").each(function (i, e) {
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

	if($(body).find('.post-content').length == 0 && body.tagName == "BODY" && body.id == "tinymce"){
        $(body).wrapInner('<div class="post-content"></div>');
    }	
}

const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;

let tinyMceSkin = "oxide";
let theme = 'auto'
if (window.matchMedia("(prefers-color-scheme: dark)").matches || $("html").attr("data-bs-theme") == "dark") {
	tinyMceSkin = "oxide-dark";
	theme = 'dark';
}

let make_wysiwyg = function(inst){
    fixWrapper(inst.contentDocument.body);
     //set dark theme
    inst.contentDocument.documentElement.setAttribute("data-bs-theme", theme);
}

var tinyMceOptions = {
  selector: "textarea",
  body_class: "container",
  init_instance_callback : make_wysiwyg,
  setup: function (editor) {
	//set changes to false to avoid saving unchanged revisions if text is not changed
	$(".has_changes", document.getElementById(editor.id).parentNode).val("0");

	 editor.ui.registry.addButton('quickmedia', {
        icon: 'image',
        tooltip: 'Insert media',
        onAction: function () {
			if (!Vvveb.MediaModal) {
				Vvveb.MediaModal = new MediaModal(true);
				Vvveb.MediaModal.mediaPath = mediaPath;
			}
			Vvveb.MediaModal.open(null, function (file) {
                    editor.insertContent("<div>" + editor.dom.createHTML('img', {
                        src: file,
                        "class": "align-center"
                    }) + "</div>");
			});
			//editor.fire("blur");
			editor.dispatch("blur");
			//console.log(editor);
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
			//console.log(ed);
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        editor.on('NewBlock',function (ed, e) {
			//console.log(ed);
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        editor.on('Change',function (ed, e) {
			//console.log(ed);
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
        */
        editor.on('SetContent',function (ed, e) {
            fixWrapper(this.contentDocument.body); // if wrapper has been deleted, add it back
        });
		
		
		editor.on('change', function(ed, e)  {
			// text changed set has_changes flag to save revision
			$(".has_changes", ed.target.container.parentNode).val(1);
		});

		$(window).trigger("vvveb.tinymce.setup", editor);
    },
    
  plugins: 'preview importcss searchreplace autolink autosave autoresize save directionality code visualblocks visualchars fullscreen image link template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help quickbars emoticons table',
  //valid_children : '-p[img],h1[img],h2[img],h3[img],h4[img],+body[img],div[img],div[h1],div[h2],div[h3]',
  //editimage_cors_hosts: ['picsum.photos'],
  menubar: false,//'file edit view insert format tools table help',
  toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | insertfile image media link anchor codesample  | fullscreen  preview save print| ltr rtl | code template |  charmap emoticons table',
  toolbar_sticky: true,
  //toolbar_sticky_offset: isSmallScreen ? 102 : 108,
  autosave_ask_before_unload: true,
  autosave_interval: '30s',
  autosave_prefix: '{path}{query}-{id}-',
  autosave_restore_when_empty: false,
  autosave_retention: '2m',
  image_advtab: true,
  link_list: [
	{ title: 'My page 1', value: 'https://www.tiny.cloud' },
	{ title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  image_list: [
	{ title: 'My page 1', value: 'https://www.tiny.cloud' },
	{ title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  image_class_list: [
	{ title: 'None', value: '' },
	{ title: 'Some class', value: 'class-name' }
  ],
  importcss_append: true,
  relative_urls : false,
  convert_urls : false,  
  file_picker_callback: function (callback, value, meta) {
		if (!Vvveb.MediaModal) {
			Vvveb.MediaModal = new MediaModal(true);
			Vvveb.MediaModal.mediaPath = mediaPath;
		}
		Vvveb.MediaModal.open(null, callback);
	return;
	/* Provide file and text for the link dialog */
	if (meta.filetype === 'file') {
            callback('https://www.google.com/logos/google.jpg', {
                text: 'My text'
            });
	}

	/* Provide image and alt text for the image dialog */
	if (meta.filetype === 'image') {
            callback('https://www.google.com/logos/google.jpg', {
                alt: 'My alt text'
            });
	}

	/* Provide alternative source and posted for the media dialog */
	if (meta.filetype === 'media') {
            callback('movie.mp4', {
                source2: 'alt.ogg',
                poster: 'https://www.google.com/logos/google.jpg'
            });
	}
  },
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
	}],
  template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
  template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
  height: 600,
  image_caption: true,
  quickbars_insert_toolbar: 'quickmedia quicklink quicktable',
  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 h4 blockquote | quickimage quicktable',
  //quickbars_image_toolbar: 'alignleft aligncenter alignright | rotateleft rotateright | imageoptions',
  noneditable_noneditable_class: 'mceNonEditable',
  toolbar_mode: 'wrap',
  contextmenu: 'link image editimage table',
  skin: tinyMceSkin,//'oxide',
  content_css: vvvebThemeCss,
  //content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
  content_style: "body {padding: 10px}",
  images_upload_url: 'postAcceptor.php',
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
  //invalid_elements: "",
  //forced_root_block : "div",
  //valid_elements: "*[*]",
  apply_source_formatting : false,
  verify_html : false,
  /* we override default upload handler to simulate successful upload*/
  images_upload_handler: function (blobInfo, success, failure) {
    setTimeout(function () {
      /* no matter what you upload, we will turn it into TinyMCE logo :)*/
      success('http://moxiecode.cachefly.net/tinymce/v9/images/logo.png');
    }, 2000);
  },
	
	formats: {
		alignleft: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "left"
			}
		}, {
			selector: "img,table,video,audio,dl.caption",
			classes: "align-left"
		}],
		aligncenter: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "center"
			}
		}, {
			selector: "img,table,video,audio,dl.caption",
			classes: "align-center"
		}],
		alignright: [{
			selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li",
			styles: {
				textAlign: "right"
			}
		}, {
			selector: "img,table,video,audio,dl.caption",
			classes: "align-right"
		}],
		strikethrough: {
			inline: "del"
		}
	},	
	
    font_family_formats: "Arial Black=arial black,avant garde; Courier New=courier new,courier; Lato Black=lato; Roboto=roboto;",
	external_plugins: {
		//'test': '../tinymce-plugins/test/plugin.js',
	},    
	branding:false,
	media_live_embeds:false,
	media_filter_html:false,
	visual_table_class:"table mce-item-table",
	table_default_attributes: {
		"class": 'table table-bordered',
   },
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
