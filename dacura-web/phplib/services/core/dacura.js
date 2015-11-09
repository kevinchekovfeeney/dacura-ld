/*
 * Basic defaults...
 */
dacura = {
	system: {
		ajax_url: "",
		mode: "void",
		xhraborted: false,
		lasttoggleid: 1,
		xhr: {"abort": function(){alert("abort");}},
		busyclass: "medium",
		modalConfig: {
			 dialogClass: "modal-message",
			 modal: true
		},
		pagecontext: {
			"collection_id": "<?=$service->getCollectionID()?>", 
			"dataset_id": "<?=$service->getDatasetID()?>", 
			"service" : "<?=$service->servicename?>"
		},
		targets: {
			"resultbox": '.tool-info', 
			"errorbox": '.tool-info', 
			"infobox": '.tool-info', 
			"busybox": '.tool-body'	
		},
		msgs: {
			"busy" : "Submitting request to Dacura Server",
			"fail" : "Service call failed",
			"info" : "Service call completed",
			"warning" : "Service call completed with warnings",
			"success" : "Service call successfully completed",
			"nodata" : "Server response was empty",
			"notjson" : "Failed to parse server response"
		},
		resulticons: {
			"error" : "<img class='result-icon result-error' src='<?=$service->url("image", "capi/error.png");?>'>",		
			"success" : "<img class='result-icon result-success' src='<?=$service->url("image", "capi/success.png");?>'>",
			"warning" : "<img class='result-icon result-warning' src='<?=$service->url("image", "capi/warning.png");?>'>",
			"info" : "<img class='result-icon result-info' src='<?=$service->url("image", "capi/info.png");?>'>",
		}
	}
};

/*
 * Get the current string representing the collection/dataset/servicename context
 * A basic where am I for building links
 */

dacura.system.getcds = function(c, d, s){
	if(typeof c == "undefined"){
		c = dacura.system.pagecontext.collection_id;
	}
	if(typeof s == "undefined"){
		s = dacura.system.pagecontext.service;
	}
	if(typeof d == "undefined"){
		d = dacura.system.pagecontext.dataset_id;
	}
	if(c == "" || c == "all"){
		return s;
	}
	if(d == "" || d == "all"){
		return c + "/" + s;
	}
	return c + "/" + d + "/" + s;	
};

//wrappers for more context finding stuff

dacura.system.apiURL = function(c, d, s){
	var url = "<?=$service->settings['ajaxurl']?>";
	return url + this.getcds(c, d, s);
};

dacura.system.serviceApiURL = function(s){
	c = dacura.system.pagecontext.collection_id;
	d = dacura.system.pagecontext.dataset_id;
	var url = "<?=$service->settings['ajaxurl']?>";
	return url + this.getcds(c, d, s);
}

dacura.system.pageURL = function(c, d, s){
	var url = "<?=$service->settings['install_url']?>";
	return url + this.getcds(c, d, s);
};

dacura.system.switchContext = function(c, d){
	window.location.href = this.pageURL(c, d, dacura.system.pagecontext.service);
};

/*
 * Initialisiation functions telling dacura where to write the different types of messages to
 * Result messages are typically used for reporting results of API interactions, especially updates 
 * Error messages are typically used for reporting random errors in page processing
 * In most cases they are the same thing
 * Busy messages are use for blanking out the screen while the system is busy and doesn't want any more calls
 */
dacura.system.setMessageTargets = function (targets){
	if(typeof targets != "undefined"){
		if(typeof targets.resultbox != "undefined"){
			dacura.system.targets.resultbox = targets.resultbox;
		}
		if(typeof targets.errorbox != "undefined"){
			dacura.system.targets.errorbox = targets.errorbox;
		}
		if(typeof targets.busybox != "undefined"){
			dacura.system.targets.busybox = targets.busybox;
		}
	}
}

/*
 * Default messages that dacura prints for the various classes of messages
 */
dacura.system.setMessages = function(msgs){
	if(typeof msgs.busy != "undefined"){
		dacura.system.msgs.busy = msgs.busy;
	}
	if(typeof msgs.fail != "undefined"){
		dacura.system.msgs.fail = msgs.fail;
	}
	if(typeof msgs.success != "undefined"){
		dacura.system.msgs.success = msgs.success;
	}
	if(typeof msgs.nodata != "undefined"){
		dacura.system.msgs.nodata = msgs.nodata;
	}
	if(typeof msgs.notjson != "undefined"){
		dacura.system.msgs.notjson = msgs.notjson;
	}
}

dacura.system.showSuccessResult = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.success;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("success", title, jqid, msg, extra);	
}

dacura.system.showInfoResult = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.info;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("info",  title, jqid, msg, extra);
}

dacura.system.showWarningResult = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.warning;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("warning", title, jqid, msg, extra);
}

dacura.system.showErrorResult = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.fail;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("error", title, jqid, msg, extra);
}

dacura.system.showJSONErrorResult = function(json){
	var tit = "";
	if(typeof json.msg_title != "undefined"){
		tit = json.action + ": " + json.msg_title;
	} 
	else {
		tit = json.action + " Unsuccessful";
	}
	var body = "";
	if(typeof json.msg_body != "undefined"){
		body = json.msg_body;
	}
	else {
		body = "";
	}
	dacura.system.showErrorResult(body, json, tit);
}

dacura.system.showErrorMessage = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.fail;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeErrorMessage(title, jqid, msg, extra);	
}

dacura.system.showInfoMessage = function(msg, extra, title, jqid){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.info;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.infobox;
	}
	dacura.system.writeInfoMessage(title, jqid, msg, extra);	
}

/*
 * Two types of busy messages - one creates an overlay on the screen and loads a loading bar
 * It is used for when we want to stop any further user actions during an ajax call - prevent multi-clicking killing us
 * The other creates a spinner type of thing within a particular box
 */
dacura.system.showBusyMessage = function(msg, uopts, jqid){
	if(typeof jqid == "undefined"){
		if(typeof msg == "undefined" || msg == ""){
			msg = dacura.system.msgs.busy;
		}
		if(typeof uopts == "undefined"){
			uopts = {};
		}
		uopts.busyclass = dacura.system.busyclass;
		dacura.system.showBusyOverlay(dacura.system.targets.busybox, msg, uopts);
	}
	else {
		dacura.system.showBusyOverlay(jqid, msg);
	}
}

dacura.system.updateBusyMessage = function(msg, uopts, jqid){
	if(typeof jqid == "undefined"){
		$('.dacura-busy-message').html(msg);
	}
	else {
		$(jqid).html(msg);
	}
}

/* 
 * Busy messages which black out a panel and place a load icon instead.
 */

dacura.system.showBusyOverlay = function(jqueryid, msg, uopts){
	var makeweight = true;
	var busyclass="medium";
	if(typeof uopts != "undefined" && typeof uopts.busyclass != undefined && uopts.busyclass){
		busyclass = uopts.busyclass;
	}
	var loaderclass = "indeterminate-busy";
	if(typeof uopts != "undefined" && typeof uopts.loaderclass != undefined && uopts.loaderclass){
		loaderclass = uopts.loaderclass;
	}
	var loaderoptions = {value: false};
	if(typeof uopts != "undefined" && typeof uopts.loaderoptions != undefined){
		//loaderoptions = uopts.loaderoptions;
	}
	$('#busy-overlay').remove();
	$("<div class='busy-overlay' id='busy-overlay' />").css({
	    position: "absolute",
	    width: "100%",
	    height: "100%",
	    left: 0,
	    top: 0,
	    zIndex: 1000000,  // to be on the safe side
		background: "rgba(255, 255, 255, .8)"
	}).appendTo($(jqueryid).css("position", "relative"));
	
	var html = "<div class='progress-container " + busyclass + "'><div class='" + loaderclass + "'></div></div>";
	html += "<div class='dacura-busy-message'>"+ msg + "</div></div>"; 
	$('#busy-overlay').html(html);
	$('.'+loaderclass).progressbar(loaderoptions);
};

/*
 * Write functions are the ones that produce the html - the show functions do the state management on top
 */

/*
 * These might be made different - currently just copies of the above
 */
dacura.system.writeErrorMessage = function(title, jqid, msg, extra){
	dacura.system.writeResultMessage("error", title, jqid, msg, extra);
}

dacura.system.writeWarningMessage = function(title, jqid, msg, extra){
	dacura.system.writeResultMessage("warning", title, jqid, msg, extra);	
}	

dacura.system.writeInfoMessage = function(title, jqid, msg, extra){
	dacura.system.writeResultMessage("info", title, jqid, msg, extra);	
}	

dacura.system.writeResultMessage = function(type, title, jqueryid, msg, extra){
	var self = dacura.system;
	var cls = "dacura-" + type;
	var contents = "<div class='mtitle'><span class='result-icon result-" + type + "'>" + self.resulticons[type] + "</span>"+ title + "<span title='remove this message' class='user-message-close ui-icon-close ui-icon'></span></div>";
	if(typeof msg != "undefined"){
		contents += "<div class='mbody'>" + msg + "</div>";
	}
	if(typeof extra != "undefined" && extra){
		if(typeof extra == "object"){
			extra = JSON.stringify(extra, 0, 4);
		}
		self.extrashown = false;
		self.isAnimating = false;
		var toggle_id = self.lasttoggleid++;
		contents += "<div id='toggle_extra_" + toggle_id + "' class='toggle_extra_message'>Show More Details</div>";
		contents +=	"<div id='message_extra_" + toggle_id + "' class='message_extra dch'>" + extra + "</div>";
		var html = "<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>";
		$(jqueryid).html(html);
		var tgid = '#toggle_extra_' + toggle_id;
		$('.toggle_extra_message').click(function(event) {
			if(!self.isAnimating) {
				self.isAnimating = true;
		        setTimeout("dacura.system.isAnimating = false", 1000); 
				$('.message_extra').toggle( "slow", function() {
					self.extrashown = !self.extrashown;
					if(self.extrashown ){
						$(".toggle_extra_message").text("Hide details");
					}
					else {
						$(".toggle_extra_message").text("Show details");				
					}
				});
		    } 
			else {
		        event.preventDefault();
		    }
		});
	}
	else {
		
		$(jqueryid).html("<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>");
	}
	$('.user-message-close').click(function(){
		$(jqueryid).html("");
	})
	$(jqueryid).show();
};

/*
 * Clearing the various messages
 */
dacura.system.clearMessages = function(){
	dacura.system.clearResultMessage();
	dacura.system.clearErrorMessage();
	dacura.system.clearInfoMessage();
	dacura.system.clearBusyMessage();
}

dacura.system.clearResultMessage = function(jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	$(jqid).html("");	
}

dacura.system.clearErrorMessage = function(jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.errorbox;
	}
	$(jqid).html("");	
}

dacura.system.clearInfoMessage = function(jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.infobox;
	}
	$(jqid).html("");	
}

dacura.system.clearBusyMessage = function(jqid){
	if(typeof jqid == "undefined"){
		dacura.system.removeBusyOverlay();
	}
	else {
		$(jqid).remove();
	}
}

dacura.system.removeBusyOverlay = function(){
	$('#busy-overlay').remove();	
	$('#overlaymakeweight').remove();		
};

/*
 * Switches the calling panel to full-window mode
 */	

dacura.system.goFullBrowser = function(jqid){
	if(typeof jqid == "undefined"){
		jqid = dacura.system.targets.busybox;
	}
	$("<div class='full-browser-overlay' id='full-browser-overlay' />").css({
	    position: "absolute",
	    width: "100%",
	    height: "100%",
	    "min-height": "800px",
	    left: 0,
	    top: 30,
	    zIndex: 999,  // to be on the safe side
		background: "rgba(255, 255, 255, .9)"
	}).appendTo($('#content-container').css("position", "relative"));
	$("#full-browser-overlay").append($(jqid).contents());
	this.goTo("#full-browser-overlay");
}

dacura.system.leaveFullBrowser = function(jqid){
	if(typeof jqid == "undefined"){
		jqid = dacura.system.targets.busybox;
	}
	var working = $("#full-browser-overlay").contents();
	$(jqid).append(working);
	$("#full-browser-overlay").remove();	
}

dacura.system.goTo = function (jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	$("body, html").animate({ 
		scrollTop: $(jqid).offset().top - 50
	}, 600);		
};


/*
 * The complicated stuff
 * Service calls and their callbacks for accessing the Dacura APIs
 */

dacura.system.default_update = function(msgs){
	if(typeof msgs == "object"){
		for(var i = 0; i < msgs.length; i++){
			dacura.system.updateModal(msgs[i]);
		}
	}	
	dacura.system.updateBusyMessage(msgs);
}


dacura.system.default_always = function(data_or_jqXHR, textStatus, jqXHR_or_errorThrown){
	dacura.system.clearBusyMessage();
	dacura.system.goTo();	
};


dacura.system.invoke = function(ajs, msgs, targets){
	if(typeof ajs == "undefined"){
		return alert("Dacura Service Invoked with no arguments - coding error!");
	}
	var self = dacura.system;
	self.setMessages(msgs);
	self.setMessageTargets(targets); 
	if(typeof ajs.beforeSend == "undefined"){
		ajs.beforeSend = function(){
			self.showBusyMessage();
		};		
	}
	if(typeof ajs.handleResult == "undefined"){
		ajs.handleResult = function(json){
			self.showSuccessResult();
		}
	}
	if(typeof ajs.handleTextResult == "undefined" ){
		ajs.handleTextResult = function(text){ 
			self.showErrorResult(text, null, self.msgs.notjson);
		}
	}
	if(typeof ajs.handleJSONError == "undefined" ){
		ajs.handleJSONError = self.showJSONErrorResult; 
	}
	dacura.system.default_handle_json_error
	if(typeof ajs.always == "undefined"){
		always = self.default_always;
	}
	else {
		always = ajs.always;
		delete(ajs.always);
	}
	if(typeof ajs.fail == "undefined"){
		fail = function (jqXHR, textStatus, errorThrown){
			if(jqXHR.responseText && jqXHR.responseText.length > 0){
				try{
					jsonerror = JSON.parse(jqXHR.responseText);
				}
				catch(e){
					return dacura.system.showErrorResult(jqXHR.responseText);										
				}
				ajs.handleJSONError(jsonerror, textStatus);
			}
			else {
				dacura.system.showErrorResult(self.msgs.nodata, jqXHR);						
			}
		};
	}
	else {
		fail = ajs.fail;
		delete(ajs.fail);
	}
	if(typeof ajs.done == "undefined"){
		done = function(data, textStatus, jqXHR){
			if(data){ //sometimes jquery automatically gives us json
				var lastresult;
				if(typeof data == "object"){
					json = data;
				}
				else {
					try{ //other times we have to parse it
						json = JSON.parse(data);
					}
					catch(e){
						return ajs.handleTextResult(jqXHR.responseText);
					}
				}
				ajs.handleResult(json);
			}
			else {
				self.showErrorResult(self.msgs.nodata, jqXHR);										
			}
		};
	}
	else {
		done = ajs.done;
		delete(ajs.done);
	}
	return $.ajax(ajs).done(done).fail(fail).always(always);
};


/*
 * Function for calling slow ajax functions which print status updates before returning a result 
 * We need access to the onreadystatechange event which is apparently not supported by the jqXHR object
 * this means we have to do things differently here
 * ajs: the ajax setting object that will be sent
 * oncomplete: the function that will be executed on completion
 * onmessage: the function that will be executed on receipt of a message
 * onerror: the function that will be called on 
 */

dacura.system.slowAjax = function (url, method, args, oncomplete, onmessage, onerror){
	var self = dacura.system;
	if(typeof onerror == "undefined"){
		onerror = function (jqXHR, textStatus, errorThrown){
			alert("slow ajax error");
		};
	}
	if(typeof oncomplete == "undefined"){
		oncomplete = function(data, textStatus, jqXHR){
			if(data){ //sometimes jquery automatically gives us json
				var lastresult;
				if(typeof data == "object"){
					json = data;
				}
				else {
					try{ //other times we have to parse it
						json = JSON.parse(data);
					}
					catch(e){
						return self.showErrorResult(jqXHR.responseText, null, self.msgs.notjson);
					}
				}
				self.showSuccessResult(json);
			}
			else {
				self.showErrorResult(self.msgs.nodata, jqXHR);										
			}
		};
	}
	if(typeof onmessage == "undefined"){
		oncomplete = self.default_update;
	}
	self.xhraborted = false;
	var msgcounter = 0;
    var xhr = $.ajaxSettings.xhr();
    if(method == "POST"){
    	args = $.param( args);
    }
	xhr.multipart = true; 
	xhr.open(method, url, true); 
	var msgcounter = 0;
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhr.onreadystatechange = function() {
		if (xhr.readyState === 3) {
			  var msgs = xhr.responseText.split("}\n{");
			  msgs.splice(0, msgcounter);
			  var len=msgs.length;
			  if(!(len == 1 && msgcounter == 0))
		      {
				  for(var i=0; i<len; i++) {
					  if(i == 0 && msgcounter == 0) msgs[i] = msgs[i] + "}";
					  else if(i == len-1) msgs[i] = '{' + msgs[i];
					  else msgs[i] = '{' + msgs[i] + '}';
				  }
		      }
			  onmessage(msgs);
		      msgcounter += msgs.length;
		}
		else if(xhr.readyState === 4){
			if(!self.xhraborted){
				var msgs = xhr.responseText.split("}\n{");
				if(msgs.length > 1){
					oncomplete("{" + msgs[msgs.length-1]);  					
				}
				else {
					oncomplete(msgs[0]);  					
				}
			}
		}
	};
	xhr.send(args);
	self.xhr = xhr;
};

dacura.system.abortSlowAjax = function(){
	dacura.system.xhraborted = true;
	dacura.system.xhr.abort();
}

dacura.system.modalSlowAjax = function(url, method, args, initmsg){
	var self = dacura.system;
	self.showModal(initmsg, "info");
	var onc = function(res){
		alert("Testing: " + res + " is the result (dacura.js modalslowajax)");
	}
	var onm = function(msgs){
		for(var i = 0; i < msgs.length; i++){
			self.updateModal(msgs[i]);
		}
	}
	self.slowAjax(url, method, args, onc, onm);
};

dacura.system.setModalProperties = function(args){
	for (var key in args) {
		if (args.hasOwnProperty(key)) {
		    dacura.system.modalConfig[key] = args[key];
	    }
	}
};


/*
 * General functions for managing modal messages
 */
dacura.system.updateModal = function(msg, msgclass){
	if(!$('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog( "open");
	}
	$('#dacura-modal').html(msg);
} 

dacura.system.showModal = function(msg, msgclass){
		$('#dacura-modal').dialog(dacura.system.modalConfig).html(msg);		
}

dacura.system.removeModal = function(){
	if($('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog("close").html("This should be invisible");
	}
}


/*
 * Initialisation Functions
 */

dacura.system.init = function(options){
	if(typeof options.mode != "undefined"){
		if(options.mode == 'tool'){
			this.targets.busybox = ".tool-body";	
			this.initTool(options);
		}
		else if(options.mode == 'widget'){
			this.targets.busybox = ".dacura-widget"
			this.busyclass = 'small';
		}
	}
	if(typeof options.msgs != "undefined"){
		this.setMessages(options.msgs);
	}
	if(typeof options.targets != "undefined"){
		this.setMessageTargets(options.targets);
	}
	
}

/*
 * Functions for dealing with the decorations on tool pages
 */

dacura.system.initTool = function(options){
	$('.tool-close a').button({
		text: false,
		icons: {
			primary: "ui-icon-circle-close"
		}
	});
	$('.tool-close').show();
	if(typeof(options.header) != "undefined"){
		this.updateToolHeader(options.header);
	}
};

dacura.system.updateToolHeader = function(options){
	if(typeof options.title != "undefined"){
		$('.tool-title').html(options.title);
		//dacura.system.setToolTitle(options.title);
	}
	if(typeof options.subtitle != "undefined"){
		$('.tool-subtitle').html(options.subtitle);
		//dacura.system.setToolSubtitle(options.subtitle);
	}
	if(typeof options.description != "undefined"){
		$('.tool-description').html(options.description);
		//dacura.system.setToolDescription(options.description);
	}
	if(typeof options.image != "undefined"){
		$('.tool-image').html("<img class='tool-header-image' src='" + options.image + "' title='"+ options.title + "' />");

		dacura.system.setToolImage(options.image);
	}
}

dacura.system.setLDEntityToolHeader = function(ent){
	options = { subtitle: ent.id };
	if(typeof ent.title != "undefined"){
		options.subtitle = ent.title;
	}
	if(typeof ent.image != "undefined"){
		options.image = ent.image;
	}
	options.description = $('#ldentity-header-template').html();
	dacura.system.updateToolHeader(options);
	if(typeof ent.metadetails != "undefined"){
		metadetails = ent.metadetails;
	}
	else {
		metadetails = timeConverter(ent.created);
	}
	$('.ent_type').html("<span class='entity-type'>" + ent.type + "</span>");
	$('.ent_created').html("<span class='entity-details'>" + metadetails + "</span>");
	$('.ent_status').html("<span class='entity-status entity-" + ent.latest_status + "'>" + ent.latest_status + "</span>");
}




dacura.system.setToolSubtitle = function(msg){
	$('.tool-subtitle').html(msg);
};

dacura.system.setToolDescription = function(msg){
	$('.tool-description').html(msg);
};

dacura.system.setToolImage = function(img){
	$('.tool-image').html("<img class='tool-header-image' url='" + img.url + "' title='" + img.title + "; />");
}

/*
 * breadcrumbs on tools
 */

dacura.system.removeServiceBreadcrumb = function(id){
	$('#'+id).remove();
}

dacura.system.addServiceBreadcrumb = function (url, txt, id){
	if(typeof id != "undefined"){
		$('#'+id).remove();
		xtra = " id='"+id+"'"
	}
	else {
		xtra = "";
	}
	var zindex = 20 - $("ul.service-breadcrumbs li").length;
	$('ul.service-breadcrumbs').append("<li><a" + xtra + " href='" + url + "' style='z-index:" + zindex + ";'>" + txt + "</a></li>");
}

//
dacura.system.setLDSingleValue = function(obj, key, val){
	if(typeof obj != "undefined"){
		for(var k in obj){
			if(typeof obj[k][key] != "undefined"){
				obj[k][key] = val;
			}
		}
	}
}


/*
 * Then just a few utility functions
 */

function validateURL(url){
	return /^(https|http):/.test(url);
};

function getMetaProperty(meta, key, def){
	if(typeof meta[key] == "undefined"){
		return def;
	}
	return meta[key];
}



function timeConverter(UNIX_timestamp){
	  var a = new Date(UNIX_timestamp*1000);
	  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	  var year = a.getFullYear() % 100;
	  var month = a.getMonth() + 1;
	  var date = a.getDate();
	  var hour = a.getHours();
	  var min = a.getMinutes();
	  var sec = a.getSeconds();
	  if(hour < 10) hour = "0" + hour;
	  if(min < 10) min = "0" + min;
	  if(sec < 10) sec = "0" + sec;
	  var time = hour + ':' + min + ':' + sec + " " + date + '/' + month + '/' + year ;
	  return time;
}


function isEmpty(obj) {
    // null and undefined are "empty"
    if (obj == null) return true;
    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (obj.length > 0)    return false;
    if (obj.length === 0)  return true;
    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    for (var key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) return false;
    }
    return true;
}

