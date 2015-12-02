/*
 * Basic defaults...
 */


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
	return dacura.system.ajax_url + this.getcds(c, d, s);
};

dacura.system.serviceApiURL = function(s){
	c = dacura.system.pagecontext.collection_id;
	d = dacura.system.pagecontext.dataset_id;
	return dacura.system.install_url + this.getcds(c, d, s);
}

dacura.system.pageURL = function(c, d, s){
	return dacura.system.install_url + this.getcds(c, d, s);
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
dacura.system.getTargets = function (targets){
	if(typeof targets == "undefined"){
		targets = dacura.system.targets;
	}
	if(typeof targets.resultbox == "undefined"){
		targets.resultbox = dacura.system.targets.resultbox;
	}
	if(typeof targets.busybox == "undefined"){
		targets.busybox = dacura.system.targets.busybox;
	}
	return targets;
}

/*
 * Default messages that dacura prints for the various classes of messages
 */
dacura.system.getMessages = function(msgs){
	if(typeof msgs == "undefined"){
		msgs = dacura.system.msgs;
	}
	if(typeof msgs.busy == "undefined"){
		msgs.busy = dacura.system.msgs.busy;
	}
	if(typeof msgs.fail == "undefined"){
		msgs.fail = dacura.system.msgs.fail;
	}
	if(typeof msgs.success == "undefined"){
		msgs.success = dacura.system.msgs.success;
	}
	if(typeof msgs.nodata == "undefined"){
		msgs.nodata = dacura.system.msgs.nodata;
	}
	if(typeof msgs.notjson == "undefined"){
		msgs.notjson = dacura.system.msgs.notjson;
	}
	return msgs;
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

dacura.system.showJSONErrorResult = function(json, jqid, tit){
	var tit = "";
	var body = "";
	if(typeof tit != "undefined"){
		if(typeof json.msg_title != "undefined"){
			tit = json.action + ": " + json.msg_title;
		} 
		else if(typeof json.action == "undefined"){
			tit = "Server Error";
		}
		else {
			tit = json.action + " Failed";
		}
	}
	if(typeof json.msg_body != "undefined"){
		body = json.msg_body;
	}
	else {
		body = "JSON Error message returned";
	}
	dacura.system.showErrorResult(body, json, tit, jqid);
}

dacura.system.writeHelpMessage = function (cnt, jqid){
	var html = "<div class='dacura-user-message-box dacura-help'><span class='result-icon result-info'>" + dacura.system.resulticons["info"] + "</span>" + cnt + "</div>";
	$(jqid).html(html);
}

dacura.system.showErrorMessage = dacura.system.showErrorResult;
dacura.system.showInfoMessage = dacura.system.showInfoResult
/*
 * 
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
	$(jqueryid + ' .busy-overlay').remove();
	$("<div class='busy-overlay'/>").css({
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
	$(jqueryid + ' .busy-overlay').html(html);
	if($(jqueryid).height() < 100){
		$(jqueryid).css("min-height", "100px");
	}
	$(jqueryid + ' .'+loaderclass).progressbar(loaderoptions);
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
		self.isAnimating = false;
		var toggle_id = self.lasttoggleid++;
		contents += "<div id='toggle_extra_" + toggle_id + "' class='toggle_extra_message'>Show More Details</div>";
		contents +=	"<div id='message_extra_" + toggle_id + "' class='message_extra dch'>" + extra + "</div>";
		var html = "<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>";
		$(jqueryid).html(html);
		var tgid = '#toggle_extra_' + toggle_id;
		$(tgid).click(function(event) {
			if(!self.isAnimating) {
				self.isAnimating = true;
		        setTimeout("dacura.system.isAnimating = false", 400); 
				$("#message_extra_" + toggle_id).toggle( "slow", function() {
					if($('#message_extra_' + toggle_id).is(":visible")) {
						$(tgid).text("Hide details");
					}
					else {
						$(tgid).text("Show details");				
					}
				});
		    } 
			else {
				alert("animating");
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
	dacura.system.clearBusyMessage();
}

dacura.system.clearResultMessage = function(jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	$(jqid).html("");	
}

dacura.system.clearBusyMessage = function(jqid){
	//alert("clear busy " +  jqid);
	dacura.system.removeBusyOverlay(jqid);
}

dacura.system.removeBusyOverlay = function(jqid){
	if(typeof jqid == "undefined"){
		$(".busy-overlay").remove();
	}
	else {
		$(jqid).css("min-height", "0px");
		$(jqid + " .busy-overlay").remove();	
	}
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
	$('#dacura-header').hide();
	this.goTo("#full-browser-overlay");
}

dacura.system.leaveFullBrowser = function(jqid){
	if(typeof jqid == "undefined"){
		jqid = dacura.system.targets.busybox;
	}
	var working = $("#full-browser-overlay").contents();
	$(jqid).append(working);
	$("#full-browser-overlay").remove();	
	$('#dacura-header').show();
	this.goTo(jqid);
}

dacura.system.goTo = function (jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	if($(jqid).length){
		$("body, html").animate({ 
			scrollTop: $(jqid).offset().top - 50
		}, 600);		
	}
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



dacura.system.invoke = function(ajs, cmsgs, ctargets){
	if(typeof ajs == "undefined"){
		return alert("Dacura Service Invoked with no arguments - coding error!");
	}
	var self = dacura.system;
	var msgs = self.getMessages(cmsgs);
	var targets = self.getTargets(ctargets);
	if(typeof ajs.beforeSend == "undefined"){
		var bb = targets.busybox;
		ajs.beforeSend = function(){
			self.showBusyMessage(msgs.busy, "", bb);
		};	
	}
	if(typeof ajs.always == "undefined"){
		always = function(data_or_jqXHR, textStatus, jqXHR_or_errorThrown){
			dacura.system.clearBusyMessage(targets.busybox);
			if(targets.scrollto){
				dacura.system.goTo(targets.scrollto);
			}
			if(typeof targets.always_callback != "undefined"){
				targets.always_callback(targets);
			}
		};	
	}
	else {
		always = ajs.always;
		delete(ajs.always);
	}
	if(typeof ajs.handleResult == "undefined"){
		ajs.handleResult = function(json, targets){
			self.showSuccessResult();
		}
	}
	if(typeof ajs.handleTextResult == "undefined" ){
		ajs.handleTextResult = function(text){ 
			self.showErrorResult(text, null, msgs.notjson, targets.resultbox);
		}
	}
	if(typeof ajs.handleJSONError == "undefined" ){
		ajs.handleJSONError = function(json, targets, textStatus){
			self.showJSONErrorResult(json, targets.resultbox, msgs.fail + " status[" + textStatus + "]"); 	
		}
	}
	if(typeof ajs.fail == "undefined"){
		fail = function (jqXHR, textStatus, errorThrown){
			if(jqXHR.responseText && jqXHR.responseText.length > 0){
				try{
					jsonerror = JSON.parse(jqXHR.responseText);
				}
				catch(e){
					return dacura.system.showErrorResult(jqXHR.responseText, null, msgs.fail, targets.resultbox);										
				}
				ajs.handleJSONError(jsonerror, targets, textStatus);
			}
			else {
				dacura.system.showErrorResult(msgs.nodata, jqXHR, msgs.fail, targets.resultbox);						
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
						return ajs.handleTextResult(jqXHR.responseText, targets);
					}
				}
				ajs.handleResult(json, targets);
			}
			else {
				self.showErrorResult(self.msgs.nodata, jqXHR, msgs.fail, targets.resultbox);										
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

dacura.system.refreshDacuraListingTable = function(key){
	var drawLTable = function(obj){
		dacura.system.drawDacuraListingTable(key, obj, dacura.system.pageListings[key].settings, dacura.system.pageListings[key].perRow, dacura.system.pageListings[key].rowClick, true);
	};
	var screen = "#" + dacura.system.pageListings[key].screen;
	var targets = {resultbox: screen + "-msgs", busybox: screen + "-contents"};
	dacura.system.pageListings[key].fetch(drawLTable, targets);
}

dacura.system.extractListingValueFromObject = function(property_code, obj){
	var parts = property_code.split("-");
	for(i = 0; i < parts.length; i++){
		if(typeof obj[parts[i]] != "undefined"){
			obj = obj[parts[i]];
		}
		else {
			return null;
		}
	}
	return obj;
}

dacura.system.extractListingValueWithFunction = function(funcname, obj){
	if(funcname == "rowselector"){
		if(typeof obj.id == "undefined" && typeof obj.eurid != "undefined"){
			obj.id = obj.eurid;
		}
		return "<input type='checkbox' class='dacura-select-listing-row' id='drs-" + obj.id + "' />";
	}
	else {
		return window[funcname](obj);							
	}
}


dacura.system.drawDacuraListingTableRow = function(rowid, obj, props){
	var html = "<tr class='dacura-listing-row' id='" + rowid + "'>";
	for(var j = 0; j<props.length; j++){
		html += "<td class='" + props[j] + "'>";
		if(props[j].substring(0,2) == "df"){//indicates that a function is used to populate the field
			html += dacura.system.extractListingValueWithFunction(props[j].substring(4), obj);
		}
		else { //an object property is used
			var val = dacura.system.extractListingValueFromObject(props[j].substring(4), obj);
			if(val !== null){
				html += val;
			}
			else {
				html += "?";
			}
		}
		html += "</td>";
	}
	html += "</tr>";
	return html;
}

dacura.system.getListingTableRowArray = function(obj, props){
	var vals = [];
	for(var j = 0; j<props.length; j++){
		if(props[j].substring(0,2) == "df"){
			vals[vals.length] = dacura.system.extractListingValueWithFunction(props[j].substring(4), obj);							
		}
		else {
			var val = dacura.system.extractListingValueFromObject(props[j].substring(4), obj);
			if(val !== null){
				vals[vals.length] = val;
			}
			else {
				vals[vals.length] = "?";
			}
		}
	}
	return vals;
}

dacura.system.listingTables = {};

dacura.system.selectListingRow = function(rowid){
	//alert(trid);
	var checkbox = $('#'+rowid + " input.dacura-select-listing-row");
	toggleCheckbox(checkbox);	
}

function toggleCheckbox(cbox){
	if(cbox.is(':checked')) {
		cbox.prop( "checked", false)
	} 
	else {
		cbox.prop('checked', 'checked'); 
	}	
}

dacura.system.listingRowSelected = function(event){
	var trid = $(this).closest('tr').attr('id'); // table row ID 
	//alert(trid);
	var checkbox = $('#'+trid + " input.dacura-select-listing-row");
	toggleCheckbox(checkbox);
}

dacura.system.drawDacuraListingTable = function(key, obj, dtsettings, rowClick, cellClick, refresh){
	var props = [];
	var ids = [];
	var rows = [];
	//nuke table body
	$("#" + key + ' tbody').html("");
	//read the table structure from the th ids 
	if(typeof dacura.system.listingTables[key] == "undefined"){
		$("#" + key + ' thead th').each(function(){
			props[props.length] = this.id;
		});
		dacura.system.listingTables[key] = props;
	}
	else {
		props = dacura.system.listingTables[key]; 
	}
	for(var i = 0; i<obj.length; i++){
		var html = dacura.system.drawDacuraListingTableRow(key + "_"+ ids.length, obj[i], props);
		rows[rows.length] = dacura.system.getListingTableRowArray(obj[i], props);
		if(typeof obj[i].id == "undefined"){
			ids[ids.length] = ids.length;
		}
		else {
			ids[ids.length] = obj[i].id;
		}
		ids[ids.length] = obj[i].id;
		$("#" + key + ' tbody').append(html);
	}
	
	if(typeof $.fn.dataTable != "undefined"){
		if(typeof refresh == "undefined" || !refresh){
			$('#' + key).addClass("display");
			$( "#" + key ).dataTable( dtsettings );
		}
		else {
			$( "#" + key ).dataTable().fnClearTable();
			$( "#" + key ).dataTable().fnAddData(rows);
			$( "#" + key ).dataTable().fnDraw();
		}
	}
	//finally apply the events - special hover style
	$("#" + key + ' .dacura-listing-row').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	
	//then apply events to table cells (not rows because we want to be able to do special things with e.g. selector cells
	if(typeof rowClick == "undefined" || rowClick == null){ //rowclicks override cell clicks
		for(var i = 0; i<props.length; i++){
			var tcls = props[i];
			if(tcls.substring(4) == "rowselector"){ //special click events for these...
				$(' .dacura-listing-row td.' + tcls).click( dacura.system.listingRowSelected ); 				
			}
			else {
				$(' .dacura-listing-row td.' + tcls).click( function(event){
					var trid = $(this).closest('tr').attr('id'); // table row ID 
					var index = parseInt(/[^_]*$/.exec(trid)[0]);
					var entid = ids[index];
					if(typeof cellClick == "undefined"){
						window.location.href = dacura.system.pageURL() + "/" + entid;
					}
					else {
						cellClick(entid);
					}
				}); 				
			}
		}/*
		$("#" + key + ' .dacura-listing-row').click( function (event){
			if(typeof rowClick == "undefined"){
				window.location.href = dacura.system.pageURL() + "/" + entid;
			}
			else {
				rowClick(entid);
			}
	    });*/
	}
	else {
		$("#" + key + ' .dacura-listing-row').click(perRow); 
	}
	$('#' + key).show();
}

dacura.system.drawDacuraUpdateObject = function(key, obj){
	$("#" + key + ' .dacura-property-input input').each(function(){
		$('#'+this.id).val(obj[this.id.substring(4)]);
	});
	$("#" + key + ' .dacura-property-input textarea').each(function(){
		$('#'+this.id).val(obj[this.id.substring(4)]);
	});
	$("#" + key + ' .dacura-property-input select').each(function(){
		$('#'+this.id).val(obj[this.id.substring(4)]);
		$('#'+this.id).selectmenu("refresh");
	});

}


dacura.system.init = function(options){

	function initToolHeader(header){
		$('.tool-close a').button({
			text: false,
			icons: {
				primary: "ui-icon-circle-close"
			}
		}).click(function(){
			$('.tool-holder').hide("blind");
		});
		$('.tool-close').show();
		if(typeof(header) != "undefined"){
			this.updateToolHeader(header);
		}
	}
	
	function initScreens(tabbed){
		var listhtml = "<ul class='subscreen-tabs'>";
		$('.dacura-subscreen').each(function(){
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
		$('#'+tabbed).prepend(listhtml);
		if(typeof $.fn.dataTable != "undefined"){
			$('#'+tabbed).tabs( {
		        "activate": function(event, ui) {
		            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
		        }
		    });
		}
		else {
			$('#'+tabbed).tabs();
		}
	}

	function initListings(listings){
		for(var key in listings){
			var drawLTable = function(obj, targets){
				dacura.system.drawDacuraListingTable(targets.listingtable, obj, listings[targets.listingtable].settings, listings[targets.listingtable].perRow, listings[targets.listingtable].rowClick)
			}
			var screen = "#" + listings[key].screen;
			var targets = {listingtable: key, resultbox: screen + "-msgs", busybox: screen + "-contents"};
			listings[key].fetch(drawLTable, targets);
		}		
	}
	
	function initButtons(buttons){
		var button_pressed = false;//only allow one update button to be in train per subscreen.
		for(var key in buttons){
			var scrjqid = '#'+buttons[key].screen;
			var busid = scrjqid + "-contents";
			var resid = scrjqid + "-msgs";
			if(typeof buttons[key].submit == "undefined"){
				buttons[key].submit = function(obj){ 
					button_pressed = false;
					alert("no submit function defined for dacura button " + key + "\nsubmitted\n" + JSON.stringify(obj));
				};
			}
			if(typeof buttons[key].validate == "undefined"){
				buttons[key].validate = function(obj){
					return "";
				}; 	
			}		
			if(typeof buttons[key].result == "undefined"){
				buttons[key].result = function(json, targets){
					button_pressed = false;
					dacura.system.showSuccessResult(obj, false, "Success", resid);
				}//; 	
			}
			if(typeof buttons[key].source != "undefined"){
				buttons[key].gather = function(jqid){
					var obj = {};
					$("#" + jqid + ' .dacura-property-input input').each(function(){
						obj[this.id.substring(4)] = $('#'+this.id).val();
					});
					$("#" + jqid + ' .dacura-property-input textarea').each(function(){
						obj[this.id.substring(4)] = $('#'+this.id).val();
					});
					$("#" + jqid + ' .dacura-property-input select').each(function(){
						obj[this.id.substring(4)] = $('#'+this.id).val();
					});
					return obj;
				};
			}
			if(typeof buttons[key].gather == "undefined"){
				buttons[key].gather = function(jqid){ 
					alert("no gather function or data source defined for dacura button " + jqid);
				};
			}

			$('#'+key).button().click(function(){
				if(button_pressed){
					alert("A request is being processed, please be patient");
					return;
				}
				scrjqid = '#'+buttons[this.id].screen;
				busid = scrjqid + "-contents";
				resid = scrjqid + "-msgs";
				button_pressed = true;
				var obj = buttons[this.id].gather(buttons[this.id].screen);
				var errs = buttons[this.id].validate(obj);
				if(errs){
					button_pressed = false;
					dacura.system.showErrorResult(errs, false, "User errors in form input", resid);
				}
				else {
					var targets = {busybox: busid, resultbox: resid, scrollto: resid, always_callback: function(){button_pressed = false}};
					buttons[this.id].submit(obj, buttons[this.id].result, targets);
				}
			});
		}		
	}
	
	function initTool(options){
		initToolHeader(options.header);
		if(typeof options.tabbed != "undefined"){
			initScreens(options.tabbed);
		}
		if(typeof options.buttons != "undefined"){
			initButtons(options.buttons);
		}
		if(typeof options.listings != "undefined"){
			dacura.system.pageListings = options.listings; 
			initListings(options.listings);
		}
		if(typeof options.load != "undefined" && typeof options.entity_id != "undefined"){
			if(typeof options.tabbed != "undefined"){
				draw = function(a, b){
					$('#'+options.tabbed).show();
					options.draw(a, b);
				};
			}
			else {
				draw = options.draw;
			}

			options.load(options.entity_id, draw);
		}
		else {
			if(typeof options.tabbed != "undefined"){
				$('#'+options.tabbed).show();
			}
		}	
	}
	if(options.mode == 'widget'){
		this.busyclass = 'small';
	}	
	else if(options.mode == 'tool'){
		initTool(options);
	}
};

dacura.system.updateToolHeader = function(options){
	if(typeof options.title != "undefined"){
		dacura.system.setToolTitle(options.title);
	}
	if(typeof options.subtitle != "undefined"){
		dacura.system.setToolSubtitle(options.subtitle);
	}
	if(typeof options.description != "undefined"){
		dacura.system.setToolDescription(options.description);
	}
	if(typeof options.image != "undefined"){
		dacura.system.setToolImage(options.image);
	}
}

dacura.system.setToolTitle = function(msg){
	$('.tool-title').html(msg);
};


dacura.system.setToolSubtitle = function(msg){
	$('.tool-subtitle').html(msg);
};

dacura.system.setToolDescription = function(msg){
	$('.tool-description').html(msg);
};

dacura.system.setToolImage = function(img){
	$('.tool-image').html("<img class='tool-header-image' url='" + img.url + "' title='" + img.title + "; />");
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

dacura.system.styleJSONLD = function(jqid) {
	if(typeof jqid == "undefined"){
		jqid = ".rawjson";
	}
	$(jqid).each(function(){
	    var text = $(this).html();
	    if(text){
	    	if(text.length > 50){
	    		presentation = text.substring(0, 50) + "...";
	    	}
	    	else {
	    		presentation = text;
	    	}
		    $(this).html(presentation);
	    	try {
	    		var t = JSON.parse(text);
	    		if(t){
	    			t = JSON.stringify(t, 0, 4);
	    		    $(this).attr("title", t);
	    		}
	    	}
	    	catch (e){
    		    $(this).attr("title", "Failure: " + e.message);	    		
	    	}
	    }
	});
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

function durationConverter(secs){
    var sec_num = parseInt(secs, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time    = hours+':'+minutes+':'+seconds;
    return time;
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

function size(obj){
	return Object.keys(obj).length
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
