/** 
 * @file The core dacura javascript library
 * @author Chekov
 * @license GPL V2
 */

/**
 * @namespace dacura
 * @summary dacura
 * @description The basic dacura namespace for javascript functions
 */

 /** 
 * @namespace system 
 * @memberof dacura
 * @summary dacura.system
 * @description Dacura core javascript module. Everything is defined within the dacura.system object
 */

/**
 * @typedef DacuraAPIConfig
 * @type {Object}
 * @property {beforeSend} [beforeSend=show busy overlay] - will be called immediately before the api call is invoked
 * @property {always} [always=clear busy message and optionally scroll to result]- will be called always upon api invocation
 * @property {handleResult} [handleResult=show a success message to the user]- called when an api invocation returns ok with a json payload
 * @property {handleTextResult} [handleTextResult=show an error message to the user]- called when an api invocation returns ok but without a valid json payload
 * can be overriden to allow simple text responses for simple api functions
 * @property {handleJSONError} [handleJSONError=shows a structured json error message]- called when an api invocation returns an error code with a valid json message body
 * @property {fail} [fail=calls either handleError or handleJSONerror depending on the content] - this should only be overriden when you want to throw away 
 * most of the normal behaviour of invoke.  If you override this, handleJSONError will not be invoked automatically
 * @property {done} [done=calls handleResult or handleTextResult, if the result cannot be json-parsed] called upon successful 
 * termination of an invocation.  If you override this, handleResult and handleTextResult will not be called automatically
 */

/**
 * @typedef DacuraPageConfig
 * @type {Object}
 * @property {string} [resultbox=dacura.system.resultbox] - the jquery selector of the div where the result will be written
 * @property {string} [busybox=dacura.system.busybox] - the jquery selector of the div that will be blanked out while the invocation is in progress
 * @property {string} [scrollTo=false] - the jquery selector of the div that will be scrolled to when the result returns (should be either resultbox or false for no scroll)
 * @property {always_callback} [always_callback=void] - called as part of always - regular always will be called and then this callback  
 * @property {ResultMessageConfig} [mopts] - configuration object for result message
 * @property {BusyMessageConfig} [bopts] - configuration object for busy message
 */

/**
 * @typedef DacuraMessagesConfig
 * @type {Object}
 * @property {string} [busy=dacura.system.msgs.busy] - message to write to screen while the system is busy
 * @property {string} [fail=dacura.system.msgs.fail] - message to write if a call fails
 * @property {string} [success=dacura.system.msgs.success] - message to write if a call succeeds
 * @property {string} [nodata=dacura.system.msgs.nodata] - message to write if a call returns no data
 * @property {string} [notjson=dacura.system.msgs.notjson] - message to write if a call returns data which is not in json format
 */

/** 
 * @typedef BusyMessageConfig
 * @type {Object}
 * @property {string} [busyclass="medium"] - css class that will apply to progress bar container (small|medium)
 * @property {string} [loaderclass="indeterminate-busy"] - the class that will be applied to the progress bar 
 * @property {Object} [loaderoptions={value:false}] - the options array that will be passed to the jqueryui progressbar initialisation
 */

/** 
 * @typedef ResultMessageConfig
 * @type {Object}
 * @property {Boolean} [icon=true] - should an icon be shown in the message header
 * @property {Boolean} [closeable=true] - should an icon be shown with a link for dismissing the message
 */

/**
 * @typedef HelpMessageConfig 
 * @type {Object}
 * @property {Boolean} [icon=true] - show a help icon with the message?
 */

/**
 * @callback beforeSend 
 */

/**
 * @callback always 
 * @param data_or_jqXHR
 * @param {string} textStatus - status that comes back from API call
 * @param jqXHR_or_errorThrown
 */

/**
 * @callback always_callback
 * @param {DacuraPageConfig} targets - the configuration for the invocation - where should results be written to?
 */

/**
 * @callback handleResult
 * @param {Object} json - the object returned by the API call
 * @param {DacuraPageConfig} targets - the configuration for the invocation - where should results be written to?
 */

/**
 * @callback handleTextResult
 * @param {string} text - the object returned by the API call
 * @param {DacuraPageConfig} targets - the configuration for the invocation - where should results be written to?
 */

/**
 * @callback handleJSONError
 * @param {Object} json - the object returned by the API call
 * @param {DacuraPageConfig} targets - the configuration for the invocation - where should results be written to?
 * @param {string} textStatus - status that comes back from API call
 */

/**
 * @callback fail
 * @param {Object} jqXHR - Jquery XHR object from API call
 * @param {string} textStatus - status that comes back from API call
 * @param errorThrown - the error thrown 
 */

/**
 * @callback done
 * @param {string} data - data that comes back from API call
 * @param {string} textStatus - status that comes back from API call
 * @param {Object} jqXHR - Jquery XHR object from API call
 */

/**
 * @function cid
 * @memberof dacura.system
 * @summary returns the current collection context
 * @return {string} collection id
 */
dacura.system.cid = function(){
	return dacura.system.pagecontext.collection_id;
}

/**
 *  @function getcds
 *  @memberof dacura.system
 *  @summary Get the current string representing the collection & servicename context. A basic "where am I" for building links
 *  @param {string} [s=current service] - The service name .
 *  @param {string} [c=current collection] - The collection id.
 */
dacura.system.getcds = function(s, c){
	if(typeof c == "undefined"){
		c = dacura.system.pagecontext.collection_id;
	}
	if(typeof s == "undefined"){
		s = dacura.system.pagecontext.service;
	}
	if(c == "" || c == "all"){
		return s;
	}
	return c + "/" + s;
};

/**
 *  @function apiURL
 *  @memberof dacura.system
 *  @summary get the url of the current services API (for the given collection context)
 *  @param {string} [s=current service] - The service name .
 *  @param {string} [c=current collection] - The collection id.
 */
dacura.system.apiURL = function(s, c){
	return dacura.system.ajax_url + this.getcds(s, c);
};

/**
 * @function pageURL
 * @memberof dacura.system
 * @summary get the url of a dacura service page (not api)
 * @param {string} [s=current service] - The service name .
 * @param {string} [c=current collection] - The collection id.
 */
dacura.system.pageURL = function(s, c){
	return dacura.system.install_url + this.getcds(s, c);
};

/**
 * @function switchContext
 * @memberof dacura.system
 * @summary switch from the current context to another one
 * @param {string} c - The collection id.
 * @param {string} [s=current service] - The service name .
 */
dacura.system.switchContext = function(c, s){
	if(typeof s == "undefined"){
		s = dacura.system.pagecontext.service;
	}
	window.location.href = this.pageURL(s, c);
};

/**
 * @function selects
 * @memberof dacura.system
 * @summary dacura.system.selects
 * @description applies a selectmenu configuration to all html selects with the css class dacura-select
 */
dacura.system.selects = function(key, opts){
	if(typeof key == "undefined") key = 'select.dacura-select';
	$(key).selectmenu(opts);
}

/**
 * @function selects
 * @memberof dacura.system
 * @summary dacura.system.refreshselects 
 * @description calls the selectmenu refresh function to update the displays after a value change
 * 
 */
dacura.system.refreshselects = function(){
	$('select.dacura-select').selectmenu("refresh");
}

/**
 * @function styleJSONLD
 * @memberof dacura.system
 * @summary dacura.system.styleJSONLD
 * @description apply a visual style to json ld elements in tables 
 * Shows the full json in the title attribute
 * and shows an abbreviated version in the regular html
 * @param {string} [jqid=.rawjson] - the jquery id of the element to be styled 
 */
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

/**
 * @function getTargets
 * @memberof dacura.system
 * @summary Initialisiation functions telling dacura where to write the different types of messages to
 * @description Result messages are typically used for reporting results of API interactions, especially updates 
 * Busy messages are use for blanking out the screen while the system is busy and doesn't want any more calls
 * @param {DacuraPageConfig} [targets=dacura.system.targets] - the configuration for the invocation - where should results be written to?
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

/**
 * @function getMessages
 * @memberof dacura.system
 * @description Get the set of messages that dacura prints for the various classes of messages
 * calculated by combining the passed messages with dacura's default
 * @param {DacuraMessagesConfig} [msgs=dacura.system.msgs] - the specific messages for this invocation
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

/**
 * @function showSuccessResult
 * @memberof dacura.system
 * @summary Display a success message for the user after a successful invocation of the api
 * @param {string} msg - the body of the message
 * @param {string} [title=dacura.system.msgs.success] - the title to appear on the result message
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where the result will be written
 * @param {Object} [extra] - a object containing extra details of the result
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message
 */
dacura.system.showSuccessResult = function(msg, title, jqid, extra, opts){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.success;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("success", title, jqid, msg, extra, opts);	
}

/**
 * @function showInfoResult
 * @memberof dacura.system
 * @summary Display a informational message for the user after an invocation of the api
 * @param {string} msg - the body of the message
 * @param {string} [title=dacura.system.msgs.info] - the title to appear on the result message
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where the result will be written
 * @param {Object} [extra] - a object containing extra details of the result
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message
 */
dacura.system.showInfoResult = function(msg, title, jqid, extra, opts){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.info;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("info",  title, jqid, msg, extra, opts);
}

/**
 * @function showWarningResult
 * @memberof dacura.system
 * @summary Display a warning message for the user after an invocation of the api
 * @param {string} msg - the body of the message
 * @param {string} [title=dacura.system.msgs.warning] - the title to appear on the result message
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where the result will be written
 * @param {Object} [extra] - a object containing extra details of the result
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message
 */
dacura.system.showWarningResult = function(msg, title, jqid, extra, opts){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.warning;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("warning", title, jqid, msg, extra, opts);
}

/**
 * @function showErrorResult
 * @memberof dacura.system
 * @summary Display an error / failure message for the user after an invocation of the api
 * @param {string} msg - the body of the message
 * @param {string} [title=dacura.system.msgs.fail] - the title to appear on the result message
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where the result will be written
 * @param {Object} [extra] - a object containing extra details of the result
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message
 */
dacura.system.showErrorResult = function(msg, title, jqid, extra, opts){
	if(typeof title == "undefined" || title == ""){
		title = dacura.system.msgs.fail;
	}
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	dacura.system.writeResultMessage("error", title, jqid, msg, extra, opts);
}

/**
 * @function showJSONErrorResult
 * @memberof dacura.system
 * @summary Display an error / failure message for the user after an invocation of the api 
 * @description when a dacura api calls returns a http error code and a json encoded body, this function is called. 
 * It is most especially used by the linked data api where error results often contain complex information
 * @param {Object} json - The object returned in the body of the response
 * @param {string} json.action - The action that was invoked at the API 
 * @param {string} [json.msg_title] - The msg title from the response
 * @param {string} [json.msg_body] - The msg body from the response
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where the result will be written
 * @param {string} [tit=dacura.system.msgs.fail] - the title to appear on the result message
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message
 */
dacura.system.showJSONErrorResult = function(json, jqid, tit, opts){
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
	dacura.system.showErrorResult(body, tit, jqid, json, opts);
}

/**
 * @function writeResultMessage
 * @memberof dacura.system
 * @summary Underlying function that actually does the work of writing the message to the dom  
 * @param {string} type - the type of the result (error, success, warning, info)
 * @param {string} title - the title to appear at the top of the message box
 * @param {string} jqueryid - the jquery selector of the div where the result will be written
 * @param {string} [msg] - the message to appear in the body of the message box
 * @param {Object} [extra] - a object containing extra details of the result
 * @param {ResultMessageConfig} [opts] - an options object containing options about the message 
 */
dacura.system.writeResultMessage = function(type, title, jqueryid, msg, extra, opts){
	if(typeof opts == "undefined"){
		opts = {"icon" : true, "closeable": false};
	}
	var self = dacura.system;
	var cls = "dacura-" + type;
	var contents = "<div class='mtitle'>";
	if(typeof opts.icon != "undefined" && opts.icon){
		contents += "<span class='result-icon result-" + type + "'>" + self.resulticons[type] + "</span>";
	}
	contents += title;
	if(typeof opts.closeable != "undefined" && opts.closeable){
		contents += "<span title='remove this message' class='user-message-close ui-icon-close ui-icon'></span>";
	}
	contents += "</div>";	
	if(typeof msg != "undefined" && msg){
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
	if(typeof opts.closeable != "undefined" && opts.closeable){
		$('.user-message-close').click(function(){
			$(jqueryid).html("");
		})
	}
	if(typeof opts.scrollTo == "boolean" && opts.scrollTo){
		dacura.system.goTo(jqueryid);
	}
	$(jqueryid).show();
};


/**
 * @function showBusyMessage
 * @memberof dacura.system
 * @summary overlays a busy message over a part of the screen
 * @description It is used for when we want to stop any further user actions during an ajax call - prevent multi-clicking killing us
 * @param {string} [msg=dacura.system.msgs.busy] - the message to appear on top of the busy overlay
 * @param {string} [jqid=dacura.system.targets.busybox] - the jquery selector of the div where the result will be written
 * @param {BusyMessageConfig} [uopts] - an options object containing options about the message 
 */
dacura.system.showBusyMessage = function(msg, jqid, uopts){
	if(typeof jqid == "undefined"){
		jqid = dacura.system.targets.busybox; 
	}
	if(typeof msg == "undefined" || msg == ""){
		msg = dacura.system.msgs.busy;
	}
	if(typeof uopts == "undefined"){
		uopts = {busyclass : "medium"};
	}
	dacura.system.showBusyOverlay(jqid, msg, uopts);
}

/**
 * @function showBusyOverlay
 * @memberof dacura.system
 * @summary Overlays a busy message on the screen
 * @param {string} jqueryid - the jquery selector of the div where the result will be written
 * @param {string} msg - the message to appear in the body of the message box
 * @param {BusyMessageConfig} uopts - an options object containing options about the message 
 */
dacura.system.showBusyOverlay = function(jqueryid, msg, uopts){
	var busyclass="medium";
	if(typeof uopts.busyclass != undefined && uopts.busyclass){
		busyclass = uopts.busyclass;
	}
	var loaderclass = "indeterminate-busy";
	if(typeof uopts.loaderclass != undefined && uopts.loaderclass){
		loaderclass = uopts.loaderclass;
	}
	var loaderoptions = {value: false};
	if(typeof uopts.loaderoptions != undefined && uopts.loaderoptions){
		loaderoptions = uopts.loaderoptions;
	}
	$(jqueryid + ' .busy-overlay').remove();
	$("<div class='busy-overlay'/>").css({
	    position: "absolute",
	    width: "100%",
	    height: "100%",
	    left: 0,
	    top: 0,
	    zIndex: 9999,  // to be on the safe side
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

/**
 * @function updateBusyMessage
 * @memberof dacura.system
 * @summary updates the busy message after it has been launced
 * @description It is used for comet calls when the server communicates multiple messages to us in the course of a single invocation
 * @param {string} msg - the message to appear on top of the busy overlay
 * @param {string} [jqid='.dacura-busy-message'] - the jquery selector of the div where the result will be written
 */
dacura.system.updateBusyMessage = function(msg, jqid){
	if(typeof jqid == "undefined"){
		$('.dacura-busy-message').html(msg);
	}
	else {
		$(jqid).html(msg);
	}
}

/**
 * @function writeHelpMessage
 * @memberof dacura.system
 * @summary writes a help message 
 * @description Used for drawing dacura forms
 * @param {string} cnt - the help message 
 * @param {string} jqid - the jquery selector of the div where the message will be written
 * @param {HelpMessageConfig} [opts={"icon": true}] - an options object containing options about the message 

 */
dacura.system.writeHelpMessage = function (cnt, jqid, opts){
	if(typeof opts == "undefined"){
		opts = {"icon": true};
	}
	var html = "<div class='dacura-user-message-box dacura-help'>";
	if(typeof opts.icon != "undefined" && opts.icon){
		html += "<span class='result-icon result-info'>" + dacura.system.resulticons["info"] + "</span>";
	}
	html += cnt + "</div>";
	$(jqid).html(html);
}

/* Clearing the various messages */

/**
 * @function clearMessages
 * @memberof dacura.system
 * @summary clears messages from resultbox and busybox
 * @param {string} [rjqid=dacura.system.targets.resultbox] - the jquery selector of the div where results are written
 * @param {string} [bjqid=".busy-overlay"] - the jquery selector of the div where busy messages are written
 */
dacura.system.clearMessages = function(rjqid, bjqid){
	dacura.system.clearResultMessage(rjqid);
	dacura.system.clearBusyMessage(bjqid);
}

/**
 * @function clearResultMessage
 * @memberof dacura.system
 * @summary clears messages from resultbox
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div where results are written
 */
dacura.system.clearResultMessage = function(jqid){
	if(typeof jqid == "undefined" || jqid == ""){
		jqid = dacura.system.targets.resultbox;
	}
	$(jqid).html("");	
}

/**
 * @function clearBusyMessage
 * @memberof dacura.system
 * @summary clears messages busybox
 * @param {string} [jqid=".busy-overlay"] - the jquery selector of the div where busy messages are written
 */
dacura.system.clearBusyMessage = function(jqid){
	dacura.system.removeBusyOverlay(jqid);
}

/**
 * @function removeBusyOverlay
 * @memberof dacura.system
 * @summary removes busy overlay
 * @param {string} [jqid=".busy-overlay"] - the jquery selector of the div where busy messages are written
 */
dacura.system.removeBusyOverlay = function(jqid){
	if(typeof jqid == "undefined"){
		$(".busy-overlay").remove();
	}
	else {
		$(jqid).css("min-height", "0px");
		$(jqid + " .busy-overlay").remove();	
	}
};

/**
 * @function goFullBrowser
 * @memberof dacura.system
 * @summary Switches the calling panel to full-window mode 
 * @description used to allow full page editing
 * @param {string} [jqid=dacura.system.targets.busybox] - the jquery selector of the div that will go full screen
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

/**
 * @function leaveFullBrowser
 * @memberof dacura.system
 * @summary Switches the calling panel from full-window mode back to normal mode
 * @description used to end full page editing
 * @param {string} [jqid=dacura.system.targets.busybox] - the jquery selector of the div that was full screen
 */
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

/**
 * @function goTo
 * @memberof dacura.system
 * @summary Moves the view panel to the specified box
 * @description used to jump to the results
 * @param {string} [jqid=dacura.system.targets.resultbox] - the jquery selector of the div to go to
 */
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

/**
 * @function default_update
 * @memberof dacura.system
 * @summary Default handler for messages from the server when onmessage has not been defined
 * @param {Object} msgs - an array of messages for the user
 */
dacura.system.default_update = function(msgs){
	if(typeof msgs == "object"){
		for(var i = 0; i < msgs.length; i++){
			dacura.system.updateModal(msgs[i]);
		}
	}	
	dacura.system.updateBusyMessage(msgs);
}


/**
 * @function invoke
 * @memberof dacura.system
 * @summary the single function for accessing the dacura API
 * @description this is the central service offered by the dacura javascript api. 
 * Calling programs define various callbacks and then pass them to this function 
 * which takes care of all of the plumbing in terms of communicating with the server
 * In the majority of cases the default event handlers will do, otherwise, they can be provided
 * @param {DacuraAPIConfig} ajs - the dacura api configuration object which provides callbacks
 * @param {DacuraMessagesConfig} [msgs] - the messages that will be shown to the user in the course of the api invocation
 * @param {DacuraPageConfig} [ctargets] - the messaging configuration for this invocation
 */
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
			self.showBusyMessage(msgs.busy, bb, targets.bopts);
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
		ajs.handleTextResult = function(text, targets){ 
			self.showErrorResult(text, msgs.notjson, targets.resultbox, false, targets.mopts);
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
					return dacura.system.showErrorResult(jqXHR.responseText, msgs.fail, targets.resultbox, false, targets.mopts);										
				}
				ajs.handleJSONError(jsonerror, targets, textStatus, targets.mopts);
			}
			else {
				dacura.system.showErrorResult(msgs.nodata, msgs.fail, targets.resultbox, jqXHR, targets.mopts);						
			}
		};
	}
	else {
		fail = ajs.fail;
		delete(ajs.fail);
	}
	if(typeof ajs.done == "undefined"){
		done = function(data, textStatus, jqXHR){
			if(data){ 
				var lastresult;
				if(typeof data == "object"){
					json = data;
				}
				else { //dacura api should automatically parse the response into a json object, this is just in case...
					try{ 
						json = JSON.parse(data);
					}
					catch(e){
						return ajs.handleTextResult(jqXHR.responseText, targets);
					}
				}
				ajs.handleResult(json, targets);
			}
			else {
				self.showErrorResult(self.msgs.nodata, msgs.fail, targets.resultbox, jqXHR, targets.mopts);										
			}
		};
	}
	else {
		done = ajs.done;
		delete(ajs.done);
	}
	return $.ajax(ajs).done(done).fail(fail).always(always);
};

/**
 * @callback onmsg
 * @param {Object} msgs - array of messages from API
 */

/**
 * @function slowAjax
 * @memberof dacura.system
 * @summary for calling slow ajax functions which print status updates before returning a result 
 * @description We need access to the onreadystatechange event which is apparently not supported by the jqXHR object
 * this means we have to do things differently here
 * @param {string} url - URL of Dacura API
 * @param {string} method - POST | GET 
 * @param {Object} args - associative array of arguments to API
 * @param {done} oncomplete - on complete callback
 * @param {onmsg} onmessage - comet message handler
 * @param {fail} onerror - error callback
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
						return self.showErrorResult(jqXHR.responseText, self.msgs.notjson);
					}
				}
				self.showSuccessResult(json);
			}
			else {
				self.showErrorResult(self.msgs.nodata, self.msgs.fail, jqXHR);										
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

/**
 * @function abortSlowAjax
 * @memberof dacura.system
 * @summary called when the user cancels a long-runnking api call
 */
dacura.system.abortSlowAjax = function(){
	dacura.system.xhraborted = true;
	dacura.system.xhr.abort();
}

/**
 * @function modalSlowAjax
 * @memberof dacura.system
 * @summary for calling slow ajax functions which print status updates to a modal dialogue
 * @param {string} url - URL of Dacura API
 * @param {string} method - POST | GET 
 * @param {Object} args - associative array of arguments to API
 * @param {string} initmsg - user initiallisation message
 */
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

/**
 * @function setModalProperties
 * @memberof dacura.system
 * @summary Set the properties of the modal dialog
 * @param {Object} args - associative array of arguments to API
 */
dacura.system.setModalProperties = function(args){
	for (var key in args) {
		if (args.hasOwnProperty(key)) {
		    dacura.system.modalConfig[key] = args[key];
	    }
	}
};

/**
 * @function setModalProperties
 * @memberof dacura.system
 * @summary update the message in the modal dialog
 * @param {string} msg - the message to write to the dialog
 * @param {string} msgclass - css class to apply to the message
 */
dacura.system.updateModal = function(msg, msgclass){
	if(!$('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog( "open");
	}
	$('#dacura-modal').html(msg);
} 

/**
 * @function showModal
 * @memberof dacura.system
 * @summary Show the modal dialog
 * @param {string} msg - the message to write to the dialog
 * @param {string} msgclass - css class to apply to the message
 */
dacura.system.showModal = function(msg, msgclass){
		$('#dacura-modal').dialog(dacura.system.modalConfig).html(msg);		
}

/**
 * @function removeModal
 * @memberof dacura.system
 * @summary Removes the modal dialog
 */
dacura.system.removeModal = function(){
	if($('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog("close").html("This should be invisible");
	}
}

/**
 * @function init
 * @memberof dacura.system
 * @summary initialises the targets and messages for a page
 * @param {Object} opts an options array
 * @param {DacuraPageConfig} opts.targets the targets settings
 * @param {DacuraMessagesConfig} opts.msgs the messages settings
 */
dacura.system.init = function(opts){
	dacura.system.targets = dacura.system.getTargets(opts.targets);
	dacura.system.msgs = dacura.system.getMessages(opts.msgs);
};

/**
 * @function updateTopbar
 * @memberof dacura.system
 * @summary Update whatever elements on the top bar have changed - called when user's change handle or they change the collection name
 * @param {Object} opts an options array
 * @param {string} opts.uname - update user name
 * @param {string} opts.uicon - update user icon
 * @param {string} opts.cname - update collection name
 * @param {string} opts.cicon - update collection icon
 */
dacura.system.updateTopbar = function(opts){
	if(typeof opts.uname == "string"){
		$('div.topbar-user-context label#uname').html(opts.uname);
	}
	if(typeof opts.cname == "string"){
		$('ul#utopbar-context li.collection-context').attr("title", opts.cname);
		$('ul#utopbar-context li.collection-context a').html(opts.cname);
	}	
	if(typeof opts.cicon == "string"){
		$('li.collection-context img.topbar-icon').attr("src", opts.cicon);
	}	
}

/**
 * @function updateHeader
 * @memberof dacura.system
 * @summary Update whatever elements on the header have changed - currently only happens when collection updates background
 * @param {Object} opts an options array
 * @param {string} opts.background - update background image
 */
dacura.system.updateHeader = function(opts){
	if(typeof opts.background == "string"){
		$('#content-container').css('background-image', 'url(' + opts.background  + ')');
	}
}


/* some simple utility functions */
/**
 * @function validateURL
 * @summary basic url validation
 * @param {string} url - the url to be validated
 * @return {Boolean} - true if it is a valid url
 */
function validateURL(url){
	return /^(https|http):/.test(url);
};

/**
 * @function getMetaProperty
 * @summary gets a property from a meta array or a default if it is not present
 * @param {Object} meta - the meta array
 * @param {string} key - the key to use
 * @param {Object} def - the default value to use if the key is not present
 * @return {Object} - the value of meta.key or def if it does not exist
 */
function getMetaProperty(meta, key, def){
	if(typeof meta[key] == "undefined"){
		return def;
	}
	return meta[key];
}

/**
 * @function durationConverter
 * @summary prints out a duration in human readable form
 * @param {Number} secs - number of seconds
 */
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

/**
 * @function timeConverter
 * @summary prints out a date time duration in human readable form
 * @param {Number} UNIX_timestamp - number of seconds since 1970
 */
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

/**
 * @function size
 * @summary gets the count of an associative array / object
 * @param {Object} obj - the object 
 * @return {Number} - the number of elements in the object
 */
function size(obj){
	return Object.keys(obj).length
}

/**
 * @function isEmpty
 * @summary is the object / associative array empty?
 * @param {Object} obj - the object 
 * @return {Boolean} - true if the object is empty
 */
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

/**
 * @function toggleCheckbox
 * @summary Toggles the state of a checkbox
 * @param cbox jquery checkbox object
 */
function toggleCheckbox(cbox){
	if(cbox.is(':checked')) {
		cbox.prop( "checked", false)
	} 
	else {
		cbox.prop('checked', 'checked'); 
	}	
}

/**
 * @function escapeQuotes
 * @param text the string
 * @returns a string with the quotes escaped
 */
function escapeQuotes(text) {
	var map = {
	    '"': '\\"',
	    "'": ''
    };
	return text.replace(/"']/g, function(m) { return map[m]; });
}

/**
 * @function escapeHtml
 * @param text the string
 * @returns a string with the quotes escaped
 */
function escapeHtml(text) {
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * @function nvArrayToOptions
 * @summary Produces a list of html options to populate a select from a passed name-value array
 * @param {Object} nv - the name-value array object
 * @param {string} [selected] - the id of the element that is selected by default
 * @return {string} - the html string 
 */
function nvArrayToOptions(nv, selected){
	var html = "";
	for(i in nv){
	    var selhtml = "";
	    if (typeof selected == "string" && i == selected) selhtml = " selected"; 
	    opthtml = "<option value='" + i + "'" + selhtml + ">" + nv[i] + "</option>";
	    if(i == ""){
	    	html = opthtml + html;
	    }
	    else {
	    	html += opthtml;
	    }
	}
	return html;
}

/**
 * @function jpr
 * @summary a short cut to alerting a json stringified version of a javascript object - basic debugging 
 * @param obj - the object to be show in the alert box
 */
function jpr(obj){
	alert(JSON.stringify(obj));
}
