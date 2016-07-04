/** 
 * @file The dacura tool javascript library
 * @author Chekov
 * @license GPL V2
 */

/**
 * @namespace tool
 * @memberof dacura
 * @summary dacura.tool
 * @description The dacura javascript tool library - provides functionality to tool pages
 */

/**
* @typedef DacuraTool
* @type {Object}
* @memberOf dacura.tool
* @property {Object} [tables] - a set of listing tables that are linked to the Dacura API
* @property {Object} [buttons] - a set of buttons that are linked to Dacura API calls
* @property {Object} [forms] - a set of HTML forms that are populated from Dacura API calls
* @property {Object} [subscreens] - Dacura screens are divided into a set of sub-screens, 
* each containing a coherent set of functions - the subscreen object is [subscreenid: subscreenpageconfig]
*/
dacura.tool = {
	tables: {},
	buttons: {},
	subscreens: {},
	forms: {}
}

/**
 * @typedef ToolInitConfig
 * @type {Object}
 * @memberOf dacura.tool
 * @summary the data structure that is passed to the init function of the dacura tool
 * @property {ToolHeaderConfig} [header] - a tool header configuration 
 * @property {string} [tabbed] - the html id of a screen that will hold tabs for subscreens
 * @property {ButtonSet} [buttons] - an object containing button configuration settings
 * @property {TableSet} [tables] - an object containing table configuration settings
 */

/** 
 * @typedef ButtonSet
 * @type Object
 * @memberOf dacura.tool
 * @description an associative array of button id: button configuration 
 */

/** 
 * @typedef TableSet
 * @type Object
 * @description an associative array of table id: table configuration 
 */


/**
 * @typedef ToolHeaderConfig
 * @type {Object}
 * @property {string} [title] - The main title that appears in the tool header
 * @property {string} [subtitle] - The subtitle that appears in the tool header
 * @property {string} [image] - URL of the image that appears in the tool header
 * @property {string} [description] - the description text that appears in the tool header
 */

/**
 * @function init
 * @memberof dacura.tool
 * @summary dacura.tool.init
 * @description Initialises the dacura tool, draws screens, draw headers, draw buttons, draw tables
 * @param {ToolInitConfig} [options] a tool initialisation configuration object
 */
dacura.tool.init = function(options){

	dacura.tool.header.init();
	if(typeof options.header == 'object'){
		dacura.tool.header.update(options.header);
	}
	if(typeof options.tabbed != "undefined"){
		dacura.tool.initScreens(options.tabbed);
	}
	if(typeof options.buttons != "undefined"){
		dacura.tool.initButtons(options.buttons);
	}
	if(typeof options.tables != "undefined"){
		dacura.tool.initTables(options.tables);
	}
	if(typeof options.forms != "undefined"){
		var opts = options.forms;
		for(var i = 0; i < options.forms.ids.length; i++){
			dacura.tool.form.init(options.forms.ids[i], opts);		
		}
	}
};

/**
 * @function initScreens
 * @memberof dacura.tool
 * @summary Transforms a dacura tool page by taking all of the divs that have css class 'dacura-subscreen' and making them into tabs
 * @param {string} holder the id of the div that holds the sub-screens
 */
dacura.tool.initScreens = function(holder, forms){
	var listhtml = "<ul class='subscreen-tabs'>";
	var i = 0;
	$('.dacura-subscreen').each(function(){
		//build page configuration from subscreen id
		dacura.tool.subscreens[this.id] = {
				resultbox: "#" + this.id + "-msgs", 
				busybox: "#" + this.id + "-contents",
				sequence: i++,
				mopts: {}
		};
		listhtml += "<li><a href='"+ '#'+ this.id + "'>" + $('#' + this.id).attr("title") + "</a></li>";
		$('#' + this.id).attr("title", "");
		$('#' + this.id).wrapInner("<div class='tool-tab-contents' id='" + this.id + "-contents'></div>");
		$('#' + this.id).prepend("<div class='tool-tab-info' id='" + this.id + "-msgs'></div>");
		var intro = $('#'+ this.id + " .subscreen-intro-message").html();
		if(intro && intro.length > 0){
			$('#'+ this.id + " .subscreen-intro-message").attr("title", "");
			dacura.system.writeHelpMessage(intro, "#" + this.id + "-msgs");
		}
	});
	listhtml += "</ul>";
	$('#'+holder).prepend(listhtml).show();
	if(typeof forms == "object"){
		for(var i in forms){
			dacura.tool.form.init(i, forms[i]);
		}
	}

	if(typeof $.fn.dataTable != "undefined"){
		$('#'+holder).tabs( {
	        "activate": function(event, ui) {
	            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
	            $("select.dacura-select").selectmenu("refresh");
	        }
	    });
	}
	else {
		$('#'+holder).tabs();
	}
};

dacura.tool.enableTab = function(holder, tab){
	$('#'+holder).tabs( "enable", "#" + tab );
};

dacura.tool.disableTab = function(holder, tab){
	$('#'+holder).tabs( "disable", "#" + tab );
};

/**
 * @function loadSubscreen loads a subscreen and collapses the regular contents of the screen
 * @memberof dacura.tool
 * @param {string} screen - html id of the screen that the subscreen belongs to
 * @param {string} subscreen - html id of the subscreen 
 * @param {string} collapsedtext - text that will replace the collapsed screen
 * @param {string} header - text that will go into the header of the subscreen
 * 
 */
dacura.tool.loadSubscreen = function(screen, subscreen, collapsedtext, header){
	$('#' + screen).before("<div class='dch subscreen-revertbar'>" + collapsedtext + "</div>");
	$('#' + screen).hide("blind", {}, "slow");
	$('.subscreen-revertbar').show().click(function(){
		dacura.tool.unloadSubscreen(screen, subscreen);
	});
	$('#' + subscreen + "-msgs").remove();
	$('#' + subscreen + "-title").remove();
	$('#' + subscreen).prepend("<div class='tool-tab-info' id='" + subscreen + "-msgs'></div>");
	$('#' + subscreen).prepend("<div class='subscreen-title' id='" + subscreen + "-title'>" + header + "</div>");
	$('#' + subscreen).show();
	pconf = {resultbox: "#" + subscreen + "-msgs", errorbox: "#" + subscreen + "-msgs", 
			mopts: {"icon": true, "closeable": false, scrollTo: true}};
	return pconf;
}

/**
 * @function unloadSubscreen 
 * @memberof dacura.tool
 * @summary unloads a subscreen and replaces it with the regular contents of the screen
 * @param {string} screen - html id of the screen that the subscreen belongs to
 * @param {string} subscreen - html id of the subscreen 
 */
dacura.tool.unloadSubscreen = function(screen, subscreen){
	$('#' + screen).show("blind", {}, "slow");
	$('.subscreen-revertbar').remove();
	$('#' + subscreen).hide();
}

/**
 * @function clearResultMessages 
 * @summary clears all of the result messages from the page
 * @memberof dacura.tool
 */
dacura.tool.clearResultMessages = function(){
	for(screenid in dacura.tool.subscreens){
		dacura.system.clearResultMessage(dacura.tool.subscreens[screenid].resultbox);
	}
}

/**
 * @function initButtons
 * @memberof dacura.tool
 * @summary dacura.tool.initButtons
 * @description provides the facility to initialise a set of buttons on tool initialisation 
 * @param {ButtonSet} buttons buttonid:DacuraButtonConfig array of button configs indexed by button ids
 */
dacura.tool.initButtons = function(buttons){
	for(var key in buttons){
		dacura.tool.button.init(key, buttons[key]);
	}		
}

/**
 * @function initTables
 * @memberof dacura.tool
 * @summary dacura.tool.initButtons
 * @description provides the facility to initialise a set of tables on tool initialisation 
 * @param {TableSet} tables tableid:DacuraTableConfig array of table configs indexed by table ids
 */
dacura.tool.initTables = function(listings){
	for(var key in listings){
		dacura.tool.table.init(key, listings[key]);
	}		
}

/**
 * @namespace header
 * @memberof dacura.tool
 * @summary dacura.tool.header
 * @description The dacura javascript helper functions for drawing headers on tool pages
 */
dacura.tool.header = {

	/**
	 * @function addBreadcrumb
	 * @memberof dacura.tool.header
	 * @summary adds a breadcrumb to the breadcrumbs list under the page header
	 * @param {string} url - the url that the breadcrumb link points to 
	 * @param {string} txt - text to appear in the breadcrumb link
	 * @param {string} id the id of the breadcrumb element 
	 */
	addBreadcrumb: function (url, txt, id){
		if(typeof id != "undefined"){
			$('#'+id).remove();
			xtra = " id='"+id+"'"
		}
		else {
			xtra = "";
		}
		var zindex = 20 - $("ul.service-breadcrumbs li").length;
		$('ul.service-breadcrumbs').append("<li><a" + xtra + " href='" + url + "' style='z-index:" + zindex + ";'>" + txt + "</a></li>");
	},	
		
	/**
	 * @function init
	 * @memberof dacura.tool.header
	 * @summary dacura.tool.header.init
	 * @description initialises the header by adding a 'close tool' box and setting an event on it
	 */	
	init: function(){
		$('.tool-close a').button({
			text: false,
			icons: {
				primary: "ui-icon-circle-close"
			}
		}).click(function(){
			$('.tool-holder').hide("blind");
		});
		$('.tool-close').show();
	},

	/**
	 * @function removeBreadcrumb
	 * @memberof dacura.tool.header
	 * @summary removes the breadcrumb with the passed id from the breadcrumbs under the page header
	 * @param {string} id the id of the breadcrumb element (as passed to addbreadcrumb below) 
	 */
	removeBreadcrumb: function(id){
		$('#'+id).remove();
	},
	
	/**
	 * @function setTitle 
	 * @memberof dacura.tool.header
	 * @summary set the description on a tool page
	 * @param {string} msg
	 */	
	setDescription: function(msg){
		$('.tool-description').html(msg);
	},
	
	/**
	 * @function setTitle 
	 * @memberof dacura.tool.header
	 * @summary set the title on a tool page
	 * @param {string} msg
	 */	
	setSubtitle: function(msg){
		$('.tool-subtitle').html(msg);
	},

	/**
	 * @function setTitle 
	 * @memberof dacura.tool.header
	 * @summary set the title on a tool page
	 * @param {string} msg
	 */
	setTitle: function(msg){
		$('.tool-title').html(msg);
	},
	
	/**
	 * @function setToolImage
	 * @memberof dacura.tool.header
	 * @summary set the image icon on a tool page
	 * @param {string} img url of the image
	 */	
	setToolImage: function(img){
		$('.tool-image').html("<img class='tool-header-image' url='" + img.url + "' title='" + img.title + "; />");
	},
	
	/**
	 * @function showEntityHeader
	 * @memberof dacura.tool.header
	 * @summary dacura.tool.header.showEntityHeader
	 * @description updates the tool page header when an entity (user, config, ld entity, ontology...) is being updated
	 * @param {title} title the main page title
	 * @param {Object} options a name-value array of attributes to appear in the header
	 */	
	showEntityHeader: function(title, options){
		dacura.tool.header.setTitle(title);
		dacura.tool.header.setSubtitle("");
		var htmlbit = "<table class='entity-headers'><thead><tr>";
		for(var i in options){
			htmlbit += "<th>" +i + "</th>";
		}
		htmlbit += "</tr></thead><tbody><tr>";
		for(var i in options){
			var val = options[i] 
			if(i == 'status'){ //status field is special we wrap it in our span
				val = "<span class='dacura-status "+ val + "'>" + val + "</span>";
			}
			htmlbit += "<td>" + val + "</d>";
		}
		htmlbit += "</tr></tbody></table>";
		dacura.tool.header.setDescription(htmlbit);
	},

	/**
	 * @function update
	 * @memberof dacura.tool.header
	 * @summary dacura.tool.header.update
	 * @description updates the basic elements of the tool page header
	 * @param {ToolHeaderConfig} options the title, subtitle, description and image 
	 */
	update: function(options){
		if(typeof options.title != "undefined"){
			dacura.tool.header.setTitle(options.title);
		}
		if(typeof options.subtitle != "undefined"){
			dacura.tool.header.setSubtitle(options.subtitle);
		}
		if(typeof options.description != "undefined"){
			dacura.tool.header.setDescription(options.description);
		}
		if(typeof options.image != "undefined"){
			dacura.tool.header.setImage(options.image);
		}
	}
}


/**
 * @namespace form
 * @memberof dacura.tool
 * @summary dacura.tool.form
 * @description The dacura javascript helper functions for drawing forms on tool pages
 * 
 * Form input elements have a regular format:
 * 
 * formid-fieldid
 * 
 */
dacura.tool.form = {
	
	/**
	 * @function clear
	 * @memberof dacura.tool.form
	 * @summary clears the data from all the input form elements 
	 * @param {string} key - html id of the table
	 */		
	clear: function(key){
		$("#" + key + ' .dacura-property-input input').each(function(){
			$('#'+this.id).val("");
		});
		$("#" + key + ' .dacura-property-input textarea').each(function(){
			$('#'+this.id).val("");
		});
	},	
	
	/**
	 * @function gather
	 * @memberof dacura.tool.form
	 * @summary gathers all the data from input form elements into a hierarchical json object as per the form structure
	 * @param {string} key - html id of the table
	 * @return {Object} obj - the object whose properties have been populated from the form inputs
	 */
	gather: function(key){
		var meta = {};
		var vals = {};
		function readMeta(obj, inputid){
			if(typeof meta[inputid] != "object"){
				meta[inputid] = {};
			}
			var metaid = obj.id.substring(5);
			var metaname = metaid.substring(key.length + inputid.length + 2);
			meta[inputid][metaname] = $('#' + metaid).val();
		}
		function readSubForm(tid, inputid){
			var sform = dacura.tool.form.gather(tid);
			if(typeof sform == "object" && typeof sform.values == "object"){
				vals[inputid] = sform.values;
				for(v in sform.meta){
					if(typeof meta[v] == "undefined"){
						meta[v] = sform.meta[v];
					}
				}
			}
			else {
				vals[inputid] = sform;
			}			
		}
		//section headers in forms with flat embedded styles need to be treated differently - their id and meta data rows are different
		var secid = '#' + key + " > tbody > tr.dacura-property-section > th.dacura-property-meta";
		$(secid).each(function(){
			var inputid = this.id.substring(6+key.length, this.id.lastIndexOf("-"));
			readMeta(this, inputid);
		});	
		var rjqid = '#' + key + " > tbody > tr.dacura-property";
		$(rjqid).each(function(){
			var inputid = this.id.substring(5+key.length);
			nrqid = '#' + key + " > tbody > tr#"+this.id + " > td.dacura-property-meta";
			var f = false;
			$(nrqid).each(function(){
				readMeta(this, inputid);
			});
			if($("table.dacura-property-table", this).length){
				var tid = $("table.dacura-property-table", this).attr("id");
				readSubForm(tid, inputid);
			}
			else {
				vals[inputid] = dacura.tool.form.getVarValue(key, inputid);			
			}
		});
		if(isEmpty(meta)){
			return vals;
		}
		else {
			return {"meta": meta, "values": vals};
		}
	},

	/**
	 * @function getVarValue
	 * @summary retrieves the form values from the passed element id
	 * @memberof dacura.tool.form
	 * @param {string} key - the html id of the form
	 * @param {string} inputid - the html id of the input element
	 * @returns {mixed} the possibly complex value of the element
	 */
	getVarValue: function(key, inputid){
		var val = $('#'+key + "-" + inputid).val(); 
		if(!val){
			var jx = $('#'+key + "-" + inputid + " :checked").attr("id");
			if(jx){
				val = jx.substring(jx.lastIndexOf("-")+1);				
			}
		}
		else if($('#'+key + "-" + inputid).hasClass("dacura-display-json")){
			val = JSON.parse(val);
		}
		return val;
	},
	
	/**
	 * @function init
	 * @summary initialises the form with the passed values...
	 * @memberof dacura.tool.form
	 * @param {string} key - the html id of the form
	 * @param {object} opts - an options array for initialising the form
	 */
	init: function(key, opts){
		if(typeof opts == "object" && (typeof opts.tooltip == "object" || typeof opts.icon != "undefined")){
			$('#'+key+' .dacura-property-help').each(function(){
				$(this).html(dacura.system.getIcon('help-icon', {cls: 'helpicon', title: escapeHtml($(this).html())}));
			});
			if(typeof opts.tooltip == "undefined"){
				opts.tooltip = { content: function () {	return $(this).prop('title');}};
			}
			$('#'+key+' .helpicon').tooltip(opts.tooltip);
		}
		if(typeof opts == "object" && typeof opts.initselects != "undefined"){
			dacura.system.selects();
			dacura.system.selects("select.property-meta", {width: 100});
		}
		$('#'+key+' .image-input-preview').click( function(event) {
			var inputid = this.id.substring(0, this.id.indexOf("-preview"));
			var val = $('#' +inputid ).val();
			dacura.tool.form.fileChooser(opts.fburl, inputid, val, "images", "Choose an image from the collection's files or upload a new one");
		});
		if(typeof opts.fburl != "undefined"){
			$('#'+key+' .image-input').click( function(event) {
				dacura.tool.form.fileChooser(opts.fburl, this.id, this.val, "images", "Choose an image from the collection's files or upload a new one");
			});
		}
		$('#' + key + " td.dacura-property-value span.dacura-radio").buttonset();
		$('#' + key + " button.dacura-formelement-action").button().click( function(e){
			var actid = this.id.substring(key.length + 1);
			if(typeof opts == "object" && typeof opts.actions == "object" && typeof opts.actions[actid] == "function"){
				opts.actions[actid]();
			}
			else {
				jpr(opts.actions);
				alert(actid + " action has no handler defined - must be specified in form initialisation");			
			}
		});
	},
	
	/**
	 * @function fileChooser
	 * @summary launches the file chooser mini-app to allow users to choose a file from the collection area to fill in a form
	 * @memberof dacura.tool.form
	 * @param {string} inputid = the html id of the text box containing the url of the file
	 * @param {string} val = the url of the currently selected file
	 * @param {string} type = the type of file required ('images', 'files', 'media')
	 * @param {string} cid = the current collection id
	 * 
	 */
	fileChooser: function(fburl, inputid, val, type, msg){
		$('body').append("<div id=\""+ inputid + "_kcfinder\">");
		var div = document.getElementById(inputid + '_kcfinder');
		if (div.style.display == "block") {
	        div.style.display = 'none';
	        div.innerHTML = '';
	        return;
	    }
	    window.KCFinder = {
	        callBack: function(url) {
	            window.KCFinder = null;
	            $('#' + inputid).val(url);
	            $('#' + inputid+"-preview img").attr("src", url);
	            div.style.display = 'none';
	            div.innerHTML = '';
	            $('#'+inputid + '_kcfinder').dialog("close");
	        }
	    };
	    var iframeurl = fburl + "?cid="+dacura.system.cid()+"&type="+type+"&dir=" + type + "/" + dacura.system.cid() + "/";
	    div.innerHTML = "<iframe src=\"" + iframeurl + "\" name=\"kcfinder_iframe\" frameborder=\"0\" width=\"100%\" height=\"100%\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" />";
	    div.style.display = 'block';
	    $('#'+inputid + '_kcfinder').dialog({modal: true, width: "700", "height": "400", title: msg});
	},

	/**
	 * @function populate
	 * @summary populates the form with the passed values in struct
	 * @memberof dacura.tool.form
	 * @param {string} key - the html id of the form
	 * @param {object} struct - an object whose fields represent the values to be filled into the form
	 */
	populate: function(key, struct, meta, context){
		context = (typeof context == "string") ? context : "";
		var metabase = key + "-";
		if(context.length > 0) {
			metabase += context;
		}
		for(i in struct){
			if(typeof meta == "object" && typeof meta[i] == "object"){
				for(f in meta[i]){
					$('#' + metabase + i + "-" + f).val(meta[i][f]);	
				}
			}
			if(typeof struct[i] == "object" && this.hasRow(key, i)){
				var vvar = key + "-" + context + i;
				if(this.hasValue(vvar)){
					this.setValue(key, i, JSON.stringify(struct[i], false, 4));
				}
				//see if we have a local textarea...
				else {
					ncontext = context + i + "-";
					this.populate(key, struct[i], meta, ncontext);
				}
			}
			else {
				//alert("setting " + i + "to " + struct[i]);
				this.setValue(key, i, struct[i]);
			}
		}
		dacura.tool.form.refresh(key);
		if(typeof meta == "object"){
			dacura.tool.form.refreshMeta(key);	
		}
	},
	
	
	/**
	 * @function refreshMeta
	 * @memberof dacura.tool.form
	 * @summary refreshes the dynamic UI meta-elements on the form 
	 * @param {string} key - the html id of the table to be refreshed
	 */
	refreshMeta: function(key){
		$('#'+key+" .property-meta").selectmenu("refresh");
	},
	
	/**
	 * @function refreshMeta
	 * @memberof dacura.tool.form
	 * @summary refreshes the dynamic UI elements on the form 
	 * @param {string} key - the html id of the table to be refreshed
	 */
	refresh: function(key){
		$('#'+key+" .dacura-select").selectmenu("refresh");
	},
		
	/**
	 * @function setValue
	 * @memberof dacura.tool.form
	 * @summary sets the value of a particular input element in the form
	 * @param {string} key - the html id of the form element
	 * @param {string} row - the variable name
	 * @param {mixed} val - a value (possibly complex)
	 */
	setValue: function(key, row, val){
		var rowid = 'row-' + key + "-" + row;
		var jqid = '#' + key + " tr#"+rowid;
		if(typeof val == "string"){
			val = escapeQuotes(val);
		}
		$(jqid + ' .dacura-property-input input').val(val);
		$(jqid + ' .dacura-property-input textarea').val(val);
		$(jqid + ' .dacura-property-input select').each(function(){
			$('#'+this.id).val(val);
		});
		$(jqid + ' .dacura-display-value').html(val);
	},
	
	/** 
	 * @function hasValue
	 * @memberof dacura.tool.form
	 * @summary checks for an input value field for a particular input element in the form
	 * @param {string} key - the html id of the form element
	 * @param {string} row - the variable name
	 */
	hasValue: function(key){
		return $('textarea#'+key).length;
	},
	
	hasRow: function(key, row){
		var rowid = 'row-' + key + "-" + row;
		var jqid = '#' + key + " tr#"+rowid;
		return $(jqid).length;
	}
	
}


/**
 * @callback bsubmit 
 * @param {Object} obj - the input data object
 * @param {result} result - the callback function that is invoked when the result arrives back
 * @param {DacuraPageConfig} pconfig - the page configuration object
 */

/**
 * @callback bvalidate 
 * @param {Object} obj - the input data object returned by gather
 * @return {string} errmsgs - a string containing any error messages from validation - if the string is empty, the input is valid
 */

/**
 * @callback bgather 
 * @param {Object} obj - the input data object returned by gather
 * @return {string} errmsgs - a string containing any error messages from validation - if the string is empty, the input is valid
 */

/**
 * @callback bresult 
 * @param {Object} json - the result json object returned by the api
 * @param {DacuraPageConfig} pconfig - the page configuration object
 */

/**
 * @typedef  DacuraButtonConfig
 * @type {Object}
 * @property {bsubmit} submit the function that will be called when the button is clicked
 * @property {bvalidate} [validate] the function that is called to validate any gathered data when the button is clicked
 * @property {string} [source] the jquery id of a dacura form from which data should be gathered for submission
 * @property {bgather} [gather] the function that is called to gather input data for the submission
 * @property {bresult} [result=show success result] the function that is called to report the result of the submission
 */

/**
 * @namespace button
 * @memberof dacura.tool
 * @summary dacura.tool.button
 * @description A package of helper code for dealing with buttons and connecting them with the dacura api
 */
dacura.tool.button = {
	/**
	 * @function init
	 * @memberof dacura.tool.button
	 * @summary initialises the button with the passed id and sets up the various callbacks associated with it
	 * @param {string} key the html id of the button
	 * @param {DacuraButtonConfig} conf the button confirugation object
	 */
	init: function(key, conf){
		var button_pressed = false;
		dacura.tool.buttons[key] = conf;
		if(typeof conf.submit != "function"){
			conf.submit = function(obj){ 
				button_pressed = false;
				alert("no submit function defined for dacura button " + key + "\nsubmitted\n" + JSON.stringify(obj));
			};
		}
		if(typeof conf.validate != "function"){
			conf.validate = function(obj){
				return "";
			}; 	
		}		
		if(typeof conf.result != "function"){
			conf.result = function(json, pcfg){
				button_pressed = false;
				dacura.system.showSuccessResult(json, "Success", pcfg.resultbox);
			}	
		}
		else {
			var res = conf.result;
			conf.result = function(json, pcfg){
				button_pressed = false;
				res(json, pcfg);
			}
		}
		if(typeof conf.source != "undefined"){
			conf.gather = function(jqid){
				return dacura.tool.form.gather(conf.source);
			};
		}
		else if(typeof conf.gather != "function"){
			conf.gather = function(jqid){};
		}
		var button_init = {};
		if(typeof conf.test != "undefined" && conf.test){
			button_init.icons = {primary: "dacura-help-button-icon"};
		}
		$('#'+key).button(button_init).click(function(){
			if(button_pressed){
				alert("A request is being processed, please be patient");
				return;
			}
			button_pressed = true;
			var obj = conf.gather(conf.screen);
			var errs = conf.validate(obj);
			if(errs){
				button_pressed = false;
				dacura.system.showErrorResult(errs, "Error in input form data", dacura.tool.subscreens[conf.screen].resultbox, false, dacura.tool.subscreens[conf.screen].mopts);
			}
			else {
				var pconf = dacura.tool.subscreens[conf.screen];
				pconf.always_callback = function(){button_pressed = false};
				conf.submit(obj, conf.result, pconf);
			}
		});
	}
}

/* typedefs and colbacks for tool.table */

/**
 * @callback fetchtable
 * @param {drawTable} - function to draw table 
 * @summary called to fetch table data from the api
 */

/**
 * @callback drawTable
 * @param {[Object]} - array of objects, each one representing a row in the table
 * @param {DacuraPageConfig} pconfig - page configuration object
 * @summary called to handle the return from the fetch api
 */

/**
 * @callback rowClick 
 * @summary called when a user clicks on a row 
 * @param {Event} e - the event that caused rowclick to fire
 * @param {DacuraTableConfig} tconfig - table configuration object
 * @return {string} [entid] - the id of the entity corresponding to the row
 */

/**
 * @callback cellClick 
 * @summary called when a user clicks on a cell 
 * @param {Event} e - the event that cause cellclick to fire
 * @param {DacuraTableConfig} tconfig - table configuration object
 * @return {string} [entid] - the id of the entity corresponding to the row in which the cell appears
 */

/**
 * @callback empty 
 * @summary called when an api call returns an empty set on table initialisation
 * @param {string} key - the html id of the table
 * @param {DacuraTableConfig} tconfig - table configuration object
 */

/**
 * @callback updateselected
 * @summary called when the user selects a set of entities from a table and clicks update
 * @param {[string]} ids - an array of the ids of the selected entities
 * @param {string} ustate - the id of the state that the entity is being updated to
 * @param {number} - num the total number of entities (added to give recursive callbacks a count)
 * @param {DacuraPageConfig} pconfig - the dacura page configuration object
 */

/**
 * @typedef  TableColumnConfig
 * @type {Object}
 * @property {string} id - the id of the object property that goes into the cell
 * @property {string} title - help text that appears in the top of the column
 * @property {string} label - Label that appears at the top of table columns
 */

/**
 * @typedef  DacuraTableConfig
 * @type {Object}
 * @property {string} screen - the html id of the screen that the table appears on (needed to know where to write result messages)
 * @property {fetchtable} fetch - the function that is used to access the dacura api in order to get data to populate the table
 * @property {Object} dtsettings - the settings that will be passed to the datatable init function. see: https://www.datatables.net/reference/option/
 * @property {Object} [refresh] - if present, the table will have a refresh button
 * @property {string} [refresh.label] - the label that will appear on the refresh button
 * @property {Object} [refresh.bconfig=show refresh icons] - the Jquery UI button configuration for the refresh 
 * @property {rowClick} [rowClick] - function to be called when a user clicks on a row
 * @property {cellClick} [cellClick] - function to be called when a user clicks on a cell
 * @property {empty} [empty] - function to be called when the api returns no entries on initialisation
 * @property {Object} [multiselect] - if present the table will allow elements to be selected and updated in a batch
 * @property {Object} [multiselect.options] - html string of options, each of which represents an action / status update that can be carried out on selected rows
 * @property {string} [multiselect.intro] - html string that appears before the update selected button
 * @property {string} [multiselect.container] - html id of the div containing the table
 * @property {string} [multiselect.label] - Label to appear on the update selected button
 * @property {updateselected} [multiselect.update] - function that will be invoked when the user clicks on the updateselected button
 */

/**
 * @namespace table
 * @memberof dacura.tool
 * @summary dacura.tool.table
 * @description A package of helper code for dealing with lists of entities in tables that are fed by the dacura api
 */
dacura.tool.table = {
	/**
	 * @function cellval
	 * @memberof dacura.tool.table
	 * @summary fetches the value that is input into a table cell
	 * @param {string} type - either function or object. If function, the function specified in the column configuration will be 
	 * called, if object, the object property from the config will be used.
	 * @param {string} id the property id
	 * @param {Object} obj the object which contains this row's data
	 * @return {string} the html/text that will appear in the cell.  
	 */
	cellval: function(type, id, obj){
		if(typeof obj.id == "undefined" && typeof obj.eurid != "undefined"){
			obj.id = obj.eurid;
		}
		if(type == "function"){
			if(id == "rowselector"){
				if(typeof obj.selectable == "boolean" && !obj.selectable){
					return "";
				}
				var chtml = (typeof obj.selected == 'boolean' && obj.selected) ? "checked " : "";
				return "<input type='checkbox' class='dacura-select-listing-row' id='drs-" + obj.id + "' " + chtml + "/>";
			}
			else {
				if(typeof (window[id]) == "function"){
					return window[id](obj);	
				}
				else {
					alert("Table configuration error - " + id + " is not a function");
				}
			}			
		}
		else {
			if(id=="status"){
				return "<span class='dacura-status "+ obj.status + "'>" + obj.status + "</span>";	
			}
			var parts = id.split("-");//allows deep property references obj-meta-status....
			for(i = 0; i < parts.length; i++){
				if(typeof obj[parts[i]] == "undefined"){
					return "?";
				}
				obj = obj[parts[i]];
			}
			if(typeof obj == "object"){
				obj = JSON.stringify(obj);
			}
			return obj;	
		}
	}, 
	
	/**
	 * @function draw
	 * @memberof dacura.tool.table
	 * @summary draws the table and adds various events to it depending on configuration
	 * @param {string} - key the html id of the table
	 * @param {array[Object]} objs - an array of objects, one per line in the table
	 * @param {DacuraTableConfig} tconfig - table configuration object
	 */	
	draw: function(key, objs, tconfig){
		if(typeof dacura.tool.tables[key] == "undefined"){
			alert("error - attempt to draw table with unknown id: " + key);
		}
		if(typeof dacura.tool.tables[key].properties == "undefined"){
			//read the table structure from the html 
			dacura.tool.tables[key].properties = dacura.tool.table.readprops(key);
		}
		dacura.tool.tables[key].rows = [];
		var ids = [];//place to stash entity ids so that we can use html ids (tableid_rownumber) that will never cause problems.
		for(var i = 0; i<objs.length; i++){
			var html = dacura.tool.table.rowhtml(key + "_"+ ids.length, objs[i], dacura.tool.tables[key].properties);
			$("#" + key + ' tbody').append(html);
			if(typeof objs[i].id == "undefined"){
				ids[ids.length] = ids.length;
			}
			else {
				ids[ids.length] = objs[i].id;
			}
			dacura.tool.tables[key].rows[ids.length-1] = objs[i]; 
		}
		dacura.system.styleJSONLD('#' + key + ' .rawjson');

		if(typeof tconfig.nohover == "undefined" || tconfig.nohover == false){
			$("#" + key + ' .dacura-listing-row').hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
		}
		if(typeof tconfig.rowClick == "function"){ 
			$("#" + key + ' .dacura-listing-row').click(function(e){
				var rowid = this.id.substring(key.length+1);
				var entid = ids[rowid];
				var rowdata = dacura.tool.tables[key].rows[rowid];
				tconfig.rowClick(e, entid, rowdata);
			});
		}
		else {//rowclicks override cell clicks
			for(var i = 0; i<dacura.tool.tables[key].properties.length; i++){
				var tcls = dacura.tool.tables[key].properties[i].id;
				var cellid = dacura.tool.table.getFieldIDFromColumnID(tcls);
				if(cellid == "rowselector"){ //special cell type
					$(' .dacura-listing-row td.' + tcls).click( dacura.tool.table.selectRow); 				
				}
				else {
					if(typeof tconfig.cellClick == "function"){ 
						$(' .dacura-listing-row td.' + tcls).click( function(event){
							var trid = $(event.target).closest('tr').attr('id'); // table row ID 
							var index = parseInt(/[^_]*$/.exec(trid)[0]);
							var entid = ids[index];
							var rowdata = dacura.tool.tables[key].rows[index];
							tconfig.cellClick(event, entid, rowdata, dacura.tool.tables[key].properties[entid]);
						});
					}						
				}
			}
		}

		//alert(key + " " + dacura.tool.tables[key].rows.length);
		$('#' + key).addClass("display");
		$("#" + key).dataTable( tconfig.dtsettings );
		$("#" + key + "_length select").addClass("dt-select");
		if(typeof tconfig.refresh == "object"){
			dacura.tool.table.drawRefreshButton(tconfig.refresh.label, key, tconfig.refresh.bconfig)
		}
		$('#' + key).show();
	},
	
	/**
	 * @function drawRefreshButton 
	 * @memberof dacura.tool.table
	 * @summary draws the refresh entries button into the table header
	 * @param {string} label - the text label to appear in the button
	 * @param {string} tableid - the html id of the table
	 * @param {Object} [bconfig] - the configuration object that will be passed to the jquery-ui button constructor
	 */
	drawRefreshButton: function(label, tableid, bconfig){
		$('#' + tableid + "_wrapper .dataTables_length").after("<button class='table-refresh'>" + label + "</button>");
		if(typeof bconfig != 'object'){
			bconfig = { icons: { primary: "ui-icon-refresh", secondary: "ui-icon-refresh" }};
		}
		$('#' + tableid + "_wrapper .fg-toolbar .table-refresh").button(bconfig).click(function(){
			dacura.tool.table.refresh(tableid);			
		});
	},

	/**
	 * @function getFieldIDFromColumnID
	 * @memberof dacura.tool.table
	 * @summary gives us the object property name from the column id.
	 * @description The property ids need to have a prefix to ensure they are unique when 
	 * multiple tables on the same page use the same property. This relies upon a convention for the ids of table columns
	 * tableid-fieldid - the field id is the segment after the first dash character
	 */
	getFieldIDFromColumnID: function(colid){
		return colid.substring(colid.indexOf("-") + 1);
	},		
	/**
	 * @function init
	 * @memberof dacura.tool.table
	 * @summary initialises a listing table, fetches initial data, etc
	 * @description if no data is passed, the table is initialised using the passed fetch function
	 * @param {string} key the html id of the table
	 * @param {DacuraTableConfig} tconfig the table configuration object
	 * @param {Object} [data] if present, the table will be populated with the attributes of this object 
	 */		
	init: function(key, tconfig, data){
		dacura.tool.tables[key] = tconfig;
		tconfig.container = $('#' + key).parent("div").attr("id");
		var drawLTable = function(obj, pconfig){
			if(obj.length == 0){
				if(typeof tconfig.empty == "function"){
					tconfig.empty(key, tconfig);
				}
			}
			else {
				dacura.tool.table.draw(key, obj, dacura.tool.tables[key]);
			}
		}
		if(typeof data == "undefined" && typeof tconfig.fetch != "function"){
			alert("table " + key + " is not properly configured - neither fetch function nor data defined");
		}
		else if(typeof data == "undefined" && typeof tconfig.fetch == "function"){
			tconfig.fetch(drawLTable, dacura.tool.subscreens[tconfig.screen]);
		}
		else {
			if(isEmpty(data) && typeof tconfig.empty == "function"){
				tconfig.empty(key, tconfig);
			}
			else {
				dacura.tool.table.draw(key, data, tconfig);
			}
		}
		if(typeof tconfig.multiselect == "object"){
			dacura.tool.table.initMultiSelect(key, tconfig.multiselect, tconfig.screen);
		}	
	},
	
	/**
	 * @function initMultiSelect
	 * @memberof dacura.tool.table
	 * @summary initialises the select elements which allow multiple rows in the table to be selected at once
	 * @param {string} key the html id of the table
	 * @param {Object} multi - the configuration object
	 * @param {string} multi.container - the jquery element containing the multi-select
	 * @param {function} multi.update - the function that is called to update the selected elements
	 * @param {string} [multi.intro] - text that goes before the update button
	 * @param {string} [multi.label] - label on the update button
	 * @param {string} [multi.options] - key-value array of options for update types
	 * @param {string} screen - the screen to write output to
	 */		
	initMultiSelect: function(key, multi, screen){
		//mandatory fields...
		if(typeof multi.container != "string" || typeof multi.update != "function"){
			alert("Error in multi-select configuration for table " + key + ". Both a container and an update function must be defined");
			return;
		}
		var shtml = "";
		var lhtml = "";
		if(typeof multi.options == "object"){
			shtml = "<select id='" + key + "-update-status' class='dacura-select'>";
			for(var k in multi.options){
				shtml += "<option value='" + k + "'>" + multi.options[k] + "</option>";
			}
			shtml += "</select>";
		}
		if(typeof multi.intro == "string"){
			lhtml = "<div class='update-table-intro'>" + multi.intro + "</div>";
		}
		var lab = "Update Selected";
		if(typeof multi.label == 'string'){
			lab = multi.label;
		}
		var bhtml = "<button class='dacura-update subscreen-button' id='" + key + "-update-button'>" +  lab + "</button>";
		var thtml = "<table class='update-list-table'><tr><td>" + lhtml + "</td><td class='option'>" + shtml + "</td>";
		thtml += "<td>" + bhtml + "</td></tr></table>";
		var html = "<div id='" + key + "-update-table' class='dacura-update-table-container'>" + thtml + "</div>";
		$('#' + multi.container).html(html);
		var ustate = "";
		if(shtml.length){
			$('#' + key + "-update-status").selectmenu({width: 120});//initialise dacura style selectmenus
		}
		$('#' + key + "-update-button").button({
				icons: {"secondary": "ui-icon-arrowstop-1-e"}, 
				disabled: true
		}).click(function(){
			var ids = dacura.tool.table.selectedids(key);
			var rowdatas = dacura.tool.table.selectedrows(key);
			ustate = $('#' + key + "-update-status").val();//get state from update select
			multi.update(ids, ustate, ids.length, dacura.tool.subscreens[screen], rowdatas);		
		});	
	},
	
	/**
	 * @function loadUpdateButton
	 * @memberof dacura.tool.table
	 * @summary shows the button beneath the table for updating the state of multiple entries at once
	 * @param {string} key - the html id of the table
	 * @param {updateselected} update - the function that will be called when the button is pressed
	 * @param {DacuraPageConfig} pconfig - the page configuration object
	 */
	loadUpdateButton: function(key, update, pconfig){
		$('#' + key + "-update-button").button({
			icons: {"secondary": "ui-icon-arrowstop-1-e"}, 
			disabled: true
		}).click(function(){
			var ids = dacura.tool.table.selectedids(key);
			var ustate = $('#' + key + "-status").val();
			update(ids, ustate, ids.length, pconfig);		
		});	
	},
	
	/**
	 * @function nuke
	 * @memberof dacura.tool.table
	 * @summary removes the data table from the dom entirely
	 * @description if no data is passed, the table is initialised using the passed fetch function
	 * Some of the datatable settings are retained (page length, order) in the table configuration object
	 * @param {string} key the html id of the table
	 * @param {DacuraTableConfig} tconfig the table configuration object
	 * @param {Object} [data] if present, the table will be populated with the attributes of this object 
	 */	
	nuke: function(key, tconfig){
		var dt = $("#" + key).DataTable();
		tconfig.dtsettings.order = dt.order();
		tconfig.dtsettings.pageLength = dt.page.len();
		dt.destroy(true);
	},
	
	/**
	 * @function readprops
	 * @memberof dacura.tool.table
	 * @summary reads the table properties from the ids of the table headers in the html
	 * @param {string} key the html id of the table
	 * @return {array[Object]} - an array of property objects: {id: x, title y, label z}
	 */	
	readprops: function(key){
		var props = [];
		$("#" + key + ' thead th').each(function(){
			props.push({id: this.id, title: $('#'+this.id).attr("title"), label: $('#'+this.id).html(), cssclass: $('#'+this.id).attr("class")});
		});
		return props;
	},

	/**
	 * @function refresh
	 * @memberof dacura.tool.table
	 * @summary refreshes a table by calling the api fetch function and then reincarnating it
	 * @param {string} - key the html id of the table
	 */
	refresh: function(key){
		var tab = dacura.tool.tables[key];
		var drawLTable = function(obj){
			dacura.tool.table.reincarnate(key, obj, tab);			
		}
		var pconf = dacura.tool.subscreens[tab.screen];
		if(typeof pconf.mopts != "object") pconf.mopts = {};
		pconf.mopts.scrollTo = false;
		if(typeof tab.fetch == "function"){
			tab.fetch(drawLTable, pconf);
		}
	},
	
	/**
	 * @function reincarnate
	 * @memberof dacura.tool.table
	 * @summary removes the table from the dom and rebuilds it with the passed array of objects
	 * @param {string} key the html id of the table
	 * @param {array[Object]} objs - an array of objects, one per line in the table
	 * @param {DacuraTableConfig} tconfig - table configuration object
	 */	
	reincarnate: function(key, objs, tconfig){
		if(typeof tconfig == "undefined"){
			tconfig = dacura.tool.tables[key];
		}
		if(typeof tconfig == "undefined") return;
		var cont = tconfig.container;
		dacura.tool.table.nuke(key, tconfig);
		if(typeof dacura.tool.tables[key] == "undefined"){
			alert("error - attempt to reincarnate unknown table " + key);
		}
		else if (typeof dacura.tool.tables[key].properties == "undefined"){
			dacura.tool.tables[key].properties = dacura.tool.table.readprops(key);
		}
		var tab = dacura.tool.tables[key];
		if(typeof tab == "undefined") return;
		var html = "<table id='" + key + "' class='dch dacura-api-listing'><thead><tr>";
		for(var i = 0; i< tab.properties.length; i++){
			var tcls = tab.properties[i].id;
			html += "<th id='"+ tab.properties[i].id + "' ";
			html += "title='" + tab.properties[i].title + "'>";
			html += tab.properties[i].label;
			html += "</th>";
		}
		html += "</thead><tbody></tbody></table>";
		$('#' + cont).prepend(html);
		dacura.tool.table.draw(key, objs, tconfig);
	},
	
	/**
	 * @function rowarray
	 * @memberof dacura.tool.table
	 * @summary returns a table row as a simply array of values
	 * @param {Object} obj - the object whose properties will be used to fill in the values of the array
	 * @param {array[TableColumnConfig]} props - an array of column configuration objects 
	 * @return {array[string]} an array of values ordered as per the table columns
	 */	
	rowarray: function(obj, props){
		var vals = [];
		for(var j = 0; j<props.length; j++){
			var fieldid = getFieldIDFromColumnID(props[j].id);
			if(props[j].substring(0,2) == "df"){
				vals.push(dacura.tool.table.cellval("function", fieldid, obj));
			}
			else {
				vals.push(dacura.tool.table.cellval("object", fieldid, obj));
			}
		}
		return vals;
	},
	
	/**
	 * @function rowhtml
	 * @memberof dacura.tool.table
	 * @summary produces the html for a row in the table
	 * @param {string} rowid - the html id of the table row
	 * @param {Object} obj - the object whose properties will be used to fill in the values of the row
	 * @param {array[TableColumnConfig]} props - an array of column configuration objects
	 * @return html string of the table row 
	 */	
	rowhtml: function(rowid, obj, props){
		var html = "<tr class='dacura-listing-row' id='" + rowid + "'>";
		for(var j = 0; j<props.length; j++){
			var prop = props[j];
			var fieldid = 	dacura.tool.table.getFieldIDFromColumnID(prop.id);
			html += "<td class='" + prop.id + " " + prop.cssclass + "'>";
			if(prop.id.substring(0,2) == "df"){//indicates that a function is used to populate the field
				html += dacura.tool.table.cellval("function", fieldid, obj);
			}
			else { //an object property is used to populate the field directly
				html += dacura.tool.table.cellval("object", fieldid, obj);				
			}
			html += "</td>";
		}
		html += "</tr>";
		return html;
	},

	/**
	 * @function selectedids
	 * @memberof dacura.tool.table
	 * @summary fetches an array of the ids of the elements in the table that are currently selected
	 * @param {string} key - the html id of the table in question
	 */
	selectedids: function(key){
		var ids = [];
		$('#' + key + " input:checkbox").each(function(){
			var fieldid = dacura.tool.table.getFieldIDFromColumnID(this.id);
			if($(this).is(":checked")){
				ids.push(fieldid);
			}
		});
		return ids;
	},
	
	selectedrows: function(key){
		var rows = [];
		$('#' + key + " input:checkbox").each(function(){
			if($(this).is(":checked")){
				var trid = $(this).closest('tr').attr('id'); // table row ID 
				var index = parseInt(/[^_]*$/.exec(trid)[0]);
				var rowdata = dacura.tool.tables[key].rows[index];
				rows.push(rowdata);
			}
		});
		//jpr(rows);
		return rows;
	},
	
	/**
	 * @function selectRow
	 * @memberof dacura.tool.table
	 * @summary called when a table row or cell is clicked on and the cell is configured to be selectable 
	 * @description toggles the state of the row selector checkbox and enables the multi-select update button if
	 * any checkboxes are in a checked state and disables it if none are
	 * @param {event} e - the event that generated the select
	 */	
	selectRow: function(e){
		if ( ! $(e.target).is(':checkbox') ) { //if the event came from the checkbox its already handled
			var trid = $(e.target).closest('tr').attr('id'); // table row ID 
			var checkbox = $('#'+trid + " input.dacura-select-listing-row");
			toggleCheckbox(checkbox);
		}
		var tid = $(e.target).closest('table').attr('id'); 
		if($('#' + tid + " input:checkbox:checked").length > 0){
			$('#' + tid + "-update-button").button( "enable" );
			$('#' + tid + "-status").selectmenu( "enable" );
		}
		else {
			$('#' + tid + "-update-button").button( "disable" );
			$('#' + tid + "-status").selectmenu( "disable" );
		}
	}
};

/**
 * @function openKCFinder
 * @memberof dacura.tool
 * @summary Opens the KC finder file browser 
 * @param {string} field - the jquery selector for the field that the browser will be written into
 * @param {string} src - the url of the kcfinder server side
 * @param {string} dir - the sub-directory to open initially
 * @param {string} type - the type of the file to be uploaded...
 */
dacura.tool.openKCFinder = function(field, src, dir, type) {

    if(typeof type == "string"){
    	src += "?type=" + type;
        if(typeof dir == "string"){
        	src += "&dir=" + type + "/" + dir;
        }
    }
    $(field).html('<div id="kcfinder_div"><iframe name="kcfinder_iframe" src="' + src + '" frameborder="0" width="100%" height="100%" marginwidth="0" marginheight="0" scrolling="no" /></div>');
}


