dacura_widget = {
	current_record:  null,
	ajax_url: "http://localhost/fame/dacura/ajaxapi.php", 
	mode: "remote",
	debug: false,
	html_naming_prefix: "dacura-widget",
    login_title: "Log in to DaCura", 
    tool_options: {
    	width: 450,
    	title: "Political Violence Event Report"
    },
    candidate_id: 0,
    chunk_id: 0,
    append_selector: "body"
};


dacura_widget.getAjaxSettings = function(action){
	var x ={
    	url: this.ajax_url,
		type: 'POST',
		xhrFields: {
	       withCredentials: true
	    },
    	data: { "action": action }
	};
	if(this.mode == "internal"){
		x.data.access = "internal";
	}
	return x;
};

dacura_widget.openTool = function(type){
	var self = this;
	if(typeof type == "undefined"){
		$('#'+this.html_naming_prefix + "-tool").dialog("open");
	}
	else if(type == "update"){
		$('#'+this.html_naming_prefix + "-tool").dialog("open");
		alert("update tool");
		$('#'+this.html_naming_prefix + "-tool").dialog('option', 'buttons', {
			'Update Report': function() { self.userSubmit("update"); }, 
			'Cancel': function() { $( this ).dialog( 'close' ); } 
		});	
	}
};

dacura_widget.removeTool = function(){
	$('#'+this.html_naming_prefix + "-tool").dialog("close");
	$('#'+this.html_naming_prefix + "-tool").remove();
	$('#'+this.html_naming_prefix + "-tool-confirm").remove();
};

dacura_widget.closeTool = function(){
	$('#'+this.html_naming_prefix + "-tool").dialog("close");
	$('#'+this.html_naming_prefix + "-tool-confirm").dialog("close");
};

dacura_widget.loadTool = function(auto, ajs){
	var self = this;
	if(typeof ajs == "undefined") ajs = this.getAjaxSettings("get_widget");
	$.ajax(ajs)
	.done(function(retdata) {
		self.createTool(retdata, auto);
	})
	.fail(function (jqXHR, textStatus) {
		alert("Failed to load tool: " + textStatus + " [" + jqXHR.status + "] " + jqXHR.responseText, "error");
	});
};

dacura_widget.toolselector = function(){
	return "#" + this.html_naming_prefix + "-tool";
};


dacura_widget.createTool = function(html, autoopen){
	var wrapper = "<div class='" + this.html_naming_prefix + "' id='" + this.html_naming_prefix + "-tool'>";
	wrapper += "<div class='dc-widget-busy " + this.html_naming_prefix + "-busy' id='" + this.html_naming_prefix + "-tool-busy'>";
	wrapper += "<img src='media/images/ajax-loader.gif'><div class='dc-widget-busy-message' id='dc-widget-busy-message-form'></div></div>";
	wrapper += "<div class='" + this.html_naming_prefix + "-message' id='" + this.html_naming_prefix + "-tool-message'></div>";
	wrapper += "<div class='" + this.html_naming_prefix + "-form' id='" + this.html_naming_prefix + "-tool-form'>" + html + "</div>";
	wrapper += "</div>";
	wrapper += "<div class='" + this.html_naming_prefix + "' id='" + this.html_naming_prefix + "-tool-confirm'>";
	wrapper += "<div class='dc-widget-busy " + this.html_naming_prefix + "-busy' id='" + this.html_naming_prefix + "-tool-confirm-busy'>";
	wrapper += "<img src='media/images/ajax-loader.gif'><div class='dc-widget-busy-message' id='dc-widget-busy-message-confirm'></div></div>";
	wrapper += "<div class='" + this.html_naming_prefix + "-message' id='" + this.html_naming_prefix + "-tool-confirm-message'></div>";
	wrapper += "<div class='" + this.html_naming_prefix + "' id='" + this.html_naming_prefix + "-tool-confirm-contents'>";
	wrapper += "</div>";
	$(this.append_selector).append(wrapper);
	var j = this.tool_options;
	j["autoOpen"] = autoopen;
	$('#' + this.html_naming_prefix + "-tool").dialog(j);
	j["autoOpen"] = false;
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog(j);
	this.setToolButtons();
};

dacura_widget.switchToInputForm = function(){
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog("close");
	$('#' + this.html_naming_prefix + "-tool").dialog("open");
	$('#' + this.html_naming_prefix + "-tool-confirm-contents").html("");
	$('#' + this.html_naming_prefix + "-tool-confirm-message").html("");
};

dacura_widget.switchToConfirmForm = function(html, data){
	$('#' + this.html_naming_prefix + "-tool").dialog("close");
	this.setConfirmButtons(data);
	$('#' + this.html_naming_prefix + "-tool-confirm-contents").html(html);
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog("open");
};

dacura_widget.setToolButtons = function(){
	var self = this;
	$('#' + this.html_naming_prefix + "-tool").dialog('option', 'buttons', {
		'Add Report': function() { self.userSubmit(); }, 
		'Cancel': function() { $( this ).dialog( 'close' ); } 
	});	
};

dacura_widget.setConfirmButtons = function(data){
	var self = this;
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog('option', 'buttons', {
		'Modify': function() { self.switchToInputForm();}, 
		'Confirm': function() { self.submitConfirmedForm(data); }, 
		'Cancel': function() { $( this ).dialog( 'close' ); } 
	});	
};


dacura_widget.removeAll = function(include_rs){
	$('.' + this.html_naming_prefix).remove();
};

dacura_widget.removeRemoteSessionPanel = function(){
	$('#dc-remote-session').remove();
};


dacura_widget.removeTools = function(){
	$('#' + this.html_naming_prefix + "-tool").dialog("close").remove();
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog("close").remove();
};


dacura_widget.drawLoginBox = function(){
	$('body').append("<div id='dacura-login-div'></div>");
	$('#dacura-login-div').append("<iframe src='" + this.ajax_url + "?action=get_loginbox'></iframe>").dialog({title: this.login_title, width: 300});
};

dacura_widget.userSubmit = function(type){
	this.clearMessages();
	$('.dacura-input-missing').removeClass('dacura-input-missing');
	var data = this.gatherInput();
	if(this.debug){
		this.debugDump(data);
	}
	if(typeof type == "undefined" || type == "new"){
		var missing = this.validateNewRecord(data);
		if(missing.length > 0){
			var items = new Array();
			for(var i in missing){
				items.push(missing[i]['msg']);
				$('#' + missing[i]['name']).addClass('dacura-input-missing');
				if(i == 0){$('#' + missing[i]['name']).focus();}
			}
			this.setMessage("Data Validation Error", items, "error");
			//$('#' + this.html_naming_prefix + '-tool-message').focus();
			$(window).scrollTop($('#' + this.html_naming_prefix + '-tool-message').offset().top);
		}
		else {
			this.preApprove(data);//sends it off to the server for approval...
		}
	}
	else if(type == "update"){
		var missing = this.validateUpdateRecord(data);
		if(missing.length > 0){
			var items = new Array();
			for(var i in missing){
				items.push(missing[i]['msg']);
				$('#' + missing[i]['name']).addClass('dacura-input-missing');
				if(i == 0){$('#' + missing[i]['name']).focus();}
			}
			this.setMessage("Data Validation Error", items, "error");
			//$('#' + this.html_naming_prefix + '-tool-message').focus();
			$(window).scrollTop($('#' + this.html_naming_prefix + '-tool-message').offset().top);
		}
		else {
			this.preApprove(data);//sends it off to the server for approval...
		}
		
		alert("update record going on");
	}
};

dacura_widget.validateUpdateRecord = dacura_widget.validateNewRecord;

dacura_widget.preApprove = function(data){
	var self = this;
	$.ajax( this.getAjaxSettings('suggest_report') )
	.done(function(ret) {
		ret = JSON.parse(ret);
		if(ret.status == "ok"){
			self.showConfirm(data);
		}
		else if(ret.status == "warning"){
			self.showWarning(ret, data);
		}
		else {
			self.setMessage("Data Validation Error (server) ", ret.items, "error");
		}
	})
	.fail(function (jqXHR, textStatus) {
		this.setMessage("Data Validation Error on server", [], "error");
	});
};

dacura_widget.showSuccess = function(msg){
	var bid = this.html_naming_prefix + "-submit-result";
	$('body').append("<div id='" + bid + "'>" + msg + "</div>");
	$('#' + bid).dialog({
		title: "Report Submitted",
		modal: true,
		show: {
            effect: 'fade',
            duration: 500
        },
        hide: {
            effect: 'fade',
            duration: 3000
        }
	});
	window.setTimeout(function(){$('#' + bid).dialog('close');}, 2000);
	window.setTimeout(function(){$('#' + bid).remove();}, 3000);
};

dacura_widget.setWidgetBusy = function(msg){
	$('#' + this.html_naming_prefix + "-tool-confirm-busy").show();
	$('#dc-widget-busy-message-confirm').html(msg);
	$('.ui-dialog button:nth-child(1)').button('disable');
	$('.ui-dialog button:nth-child(2)').button('disable');
	$('.ui-dialog button:nth-child(3)').button('disable');
};

dacura_widget.setWidgetUnbusy = function(){
	$('.ui-dialog button:nth-child(1)').button('enable');
	$('.ui-dialog button:nth-child(2)').button('enable');
	$('.ui-dialog button:nth-child(3)').button('enable');
	$('#dc-widget-busy-message-confirm').html("");
	$('#' + this.html_naming_prefix + "-tool-confirm-busy").hide();
};


dacura_widget.submitConfirmedForm = function(data){
	this.setWidgetBusy("Submitting report to DaCura");
	data = this.addConfirmInput(data);
	if(this.debug){
		this.debugDump(data);
	}
	//need to show that the confirm form is busy...
	var self = this;
	var ajs;
	ajs = this.getAjaxSettings('candidate_decision');
	ajs.data.decision = 'accept';
	ajs.data.id = this.candidate_id;
	ajs.complete = function(){
        // hide gif here, eg:
    	dacura_widget.setWidgetUnbusy();
    };
	ajs.data["dcpayload"] = JSON.stringify(data);
	$.ajax(ajs)
	.done(function(retdata) {
			dacura.candidate_viewer.processSuccess(retdata);
	})
	.fail(function (jqXHR, textStatus) {
		self.setConfirmMessage(textStatus + " [" + jqXHR.status + "] " + jqXHR.responseText, "error");
	});
};



dacura_widget.loadToolFromContext = function(){
	alert("load tool from context needs to be overwritten");
};
dacura_widget.clearTool = function(){
	alert("clear needs to be overwritten");
};

dacura_widget.removeTool = function(){
	alert("remove needs to be overwritten");
};


dacura_widget.load = function(data){
	//This needs to be overwritten
	alert("Load needs to be overwritten");
};

dacura_widget.clearMessages = function(){
	$('#' + this.html_naming_prefix + "-tool-message").html("");
};

dacura_widget.clearConfirmMessages = function(){
	$('#' + this.html_naming_prefix + "-tool-confirm-message").html("");
};

dacura_widget.setConfirmMessage = function(msg, type){
	var html = "<p class='" + this.html_naming_prefix + "-message " + this.html_naming_prefix + "-message-" + type + "' id='" + this.html_naming_prefix + "confirm-message-body'>" + msg + "</p>";
	$('#' + this.html_naming_prefix + '-tool-confirm-message').html(html);
};

dacura_widget.setMessage = function(msg, items, type){
	var html = "<p class='" + this.html_naming_prefix + "-message " + this.html_naming_prefix + "-message-" + type + "' id='" + this.html_naming_prefix + "-message-body'>" + msg + "</p>";
	if(items.length > 0){
		html += "<ul class='" + this.html_naming_prefix + "-message-items " + this.html_naming_prefix + "-message-items-" + type + "' id='" + this.html_naming_prefix + "-message-items'>";
		for(var i in items){
			html += "<li class='" + this.html_naming_prefix + "-message-item " + this.html_naming_prefix + "-message-item-" + type + "' id='" + this.html_naming_prefix + "-message-item" + i +"'>" + items[i] + "</li>";
		}
		html += "</ul>";
	}
	$('#' + this.html_naming_prefix + '-tool-message').html(html);
};

dacura_widget.showWarning = function(ret, data){
	this.switchToConfirmForm(ret.msg, data);
	$('#' + this.html_naming_prefix + "-tool-confirm").dialog('option', 'buttons', {
		'Modify': function() { self.switchToInputForm();}, 
		'Confirm': function() { self.showConfirm(data); }, 
		'Cancel': function() { $( this ).dialog( 'close' ); } 
	});	
};

dacura_widget.gatherInput = function(){
	//This needs to be overwritten
	alert("Gather input needs to be overwritten");
};


dacura_widget.debugDump = function(data){
	$('body').prepend("<div id='dacura-debug-print'></div>");
	$('#dacura-debug-print').html(prettyPrint( data )).dialog();
};

dacura_widget.addConfirmInput = function(data){
	data.dubious = new Array();
	$('input.dacura-details-dubious:checked').each(function( index ){
		data.dubious.push($(this).val());
	});
	return data;
};

dacura_widget.showConfirm = function(data){
	var questions = this.getConfirmMessages(data);
	var detailshtml = "<table class='dacura-confirm-form dacura-confirm-ok'><th>Report Details</th><th>Dubious?</th>";
	var warninghtml = "<ul class='dacura-confirm-warnings'>";
	var emptyhtml = "<ul class='dacura-confirm-empty'>";
	for(var key in questions){
		if(questions[key].check == 'ok'){
			detailshtml += "<tr><td class='dacura-confirm-details dacura-confirm-details-"+questions[key].check +"'>" + questions[key].msg +  "</td><td class='dacura-confirm-details-dubious'>";
			if(questions[key].confidence){
				detailshtml += "<input class='dacura-details-dubious' id='dacura-details-dubious-" + key + "' value='"+  questions[key].field + "' type='checkbox'>";
			}
			detailshtml += "</td></tr>";
		}
		else if(questions[key].check == 'warning'){
			warninghtml += "<li>" + questions[key].msg + "</li>";
		}
		else if(questions[key].check == 'empty'){
			emptyhtml += "<li>" + questions[key].msg + "</li>";
		}
	} 
	detailshtml += "</table>";
	warninghtml += "</ul>";
	emptyhtml += "</ul>";
	html = detailshtml + "<table class='dacura-confirm-form dacura-confirm-sundries'><tr><th>Warnings</th><th>Empty Fields</th></tr>";
	html += "<tr><td class='dacura-confirm-details-warning'>" + warninghtml + "</td>";
	html += "<td class='dacura-confirm-details-empty'>" + emptyhtml + "</td></tr></table>";
	this.switchToConfirmForm(html, data);
	//return questions;
};

dacura_widget.getConfirmMessages = function(data){
	//overwrite
	var questions = new Array();
	return questions;
};

dacura_widget.validateNewRecord = function(data){
	var missing = [];
	alert("Validate new record needs to be over-written");
	return missing;
};


/*
dacura_widget.getConfirmMessages = function(data){
	questions.push({"msg": "The article contains a report of a political violence event", "field" : 'report', "check" : "ok", "field" : 'report', "confidence": true});
	questions.push(this.getDateConfirm(data.date));
	questions.push(this.getPlaceConfirm(data));
	questions.push({"msg": "The event type is: " + data.type, "check" : "ok", "field" : 'type', "confidence" : true});
	if("fatalities" in data){
		questions.push(this.getFatalitiesConfirm(data.fatalities));
	}
	else {
		questions.push({"msg": "No fatalities information", "check" : "warning", "confidence": false});
	}
	if("motivation" in data){
		questions.push(this.getMotivationConfirm(data.motivation));
	}
	else {
		questions.push({"msg": "No motivation information", "check" : "warning", "confidence": false});
	}
	if("actors" in data){
		var acts = this.getActorsConfirm(data.actors);
		for(var key in acts){
			questions.push(acts[key]);
		}
	}
	else {
		questions.push({"msg": "No actors information", "check" : "warning", "confidence": false});
	}
	if("description" in data && data.description != ""){
		questions.push({"msg": "The event description is: " + data.description, "check" : "ok", "field": 'description', "confidence" : false});
	}
	else {
		questions.push({"msg": "No event description", "check" : "warning", "confidence" : false});
	}
	if("citation" in data){
		var cits = this.getCitationConfirm(data.citation);
		for(var key in cits){
			questions.push(cits[key]);
		}
	}
	else {
		questions.push({"msg": "No citation information", "check" : "warning", "confidence": false});
	}
	return questions;
};
*/






/*
dacura_widget.prototype.populateWidgetFromRecord = function(xid, record){
		this.clearWidget();
		$('#dacura-submit-add').hide();
		$('#dacura-submit-delete').hide();
		$('#dacura-submit-update').show();
		$("div.dacura-widget").dialog("option", "title", "PV Event Record: " + xid);
		this.current_record = record;
		for (var key in record.category.values){
			this.populateCategory(key, record.category.values[key]);	
		}
		this.populateMotivation(record.motivation);	
		this.populateSource(record.source);	
		this.populateDescription(record.description);	
		this.populateLocation(record.location);	
		this.populateFatalities(record.fatalities);	
		this.populateDate(record.edate);	
};


dacura_widget.prototype.populateFromCandidate = function(cand){
	$('#dacura-submit-add').show();
	$('#dacura-submit-delete').hide();
	$('#dacura-submit-update').hide();
	this.populateDate(cand.contents.date);	
	this.populateSource(cand.contents.citation);
};

//input: {from : { day: x, month: m, year: } to : { day: x, month: m, year: } 
dacura_widget.prototype.populatewDate = function(dat){
	if('from' in dat){
		$('#event-datetime-from-dd').val(dat['from'].day).change();
		$('#event-datetime-from-mm').val(dat['from'].month).change();
		$('#event-datetime-from-yy').val(dat['from'].year).change();
	}
	var isrange = false;
	if('to' in dat){
		$('#event-datetime-to-dd').val(dat['to'].day).change();
		$('#event-datetime-to-mm').val(dat['to'].month).change();
		$('#event-datetime-to-yy').val(dat['to'].year).change();
		isrange = true;
	}
	if(isrange){
		$('event-datetime-precision').val("between").change();
	}
	else {
		$('event-datetime-precision').val("on").change();
	}
};


dacura_widget.prototype.populateDate = function (dat){
	for (var key in dat.values){
		var datv = dat.values[key];
		$('#event-date-types').val(datv.type);
		$('#event-date-iptypes').val(datv.iptype);
		$('#event-date-types').change();
		$('#event-date-iptypes').change();
		if('day' in datv){
			$('#event-date-from-date-day').val(datv.day);
		}
		if('month' in datv){
			$('#event-date-from-date-month').val(datv.month);
		}
		if('year' in datv){
			$('#event-date-from-date-year').val(datv.year);
		}
	}
};

dacura_widget.prototype.getRecordFromServer = function(xid){
	var self = this;
	$.post(this.ajax_url, { id: xid, action: "get_record"})
	.done(function(data) {
		var bits = JSON.parse(data);
		self.populateWidgetFromRecord(xid, bits);
	    $('.dump_result').html(prettyPrint(bits)) ;
	});
};

dacura_widget.prototype.populateCategory = function(id, details){
	$('#event-category').val(details.label);
};

dacura_widget.prototype.populateMotivation = function(mot){	
	for (var key in mot.values){
		$('#event-motivation-' + mot.values[key].label).prop("selected", true);
	}	
};

dacura_widget.prototype.populateSource = function (src){
	for (var key in src.values){
		$('#event-source').val(src.values[key].unstructured);		
	}	
};

dacura_widget.prototype.populateDescription = function (desc){
	for (var key in desc.values){
		$('#event-description').val(desc.values[key]);		
	}	
};

dacura_widget.prototype.populateLocation = function (loc){
	for (var key in loc.values){
		$('#event-location').val(loc.values[key].unstructured);		
	}	
};

dacura_widget.prototype.populateFatalities = function (fat){
	for (var key in fat.values){
		var fatv = fat.values[key];
		if("min" in fatv ){
			$('#event-fatalities-types').val('Range');
			$('.fatalities-min').val(fatv.min);
			$('.fatalities-max').val(fatv.max);
		}
		else if("value" in fatv){
			$('#event-fatalities-types').val('Number');
			$('.fatalities-num').val(fatv.value);
		}
		else {
			$('#event-fatalities-types').val('Unknown');	
		}
	}
	$('#event-fatalities-types').change();
};

dacura_widget.prototype.clearWidget = function (){
	$('#event-category option').removeProp('selected');
	$('#event-motivation option').removeProp('selected');
	$('#event-source').val("");
	$('#event-description').val("");
	$('#event-location').val("");
	$('#dacura-submit-add').show();
	$('#dacura-submit-delete').show();
	$('#dacura-submit-update').hide();
	this.current_record = null;
};

dacura_widget.prototype.drawEmpty = function(woptions){
	var self = this;
	$.post(this.ajax_url, { action: "get_widget", mode: "capture", options: JSON.stringify(woptions)})
	.done(function(data) {
		$('body').append(data);
		self.drawInputOptions();
	});	
};


dacura_widget.prototype.drawInputOptions = function(){
	var self = this;
	$('#dacura-submit-add').button().click(function(e){
		e.preventDefault();
		self.checkNewRecord();
	});
	$('#dacura-submit-delete').button().click(function(e){
		e.preventDefault();
		alert("Deleted");
	});
	$('#dacura-submit-update').button().click(function(e){
		e.preventDefault();
		alert("Updated");
	}).hide();

	$('.dacura-date-to').hide();
	$('.dacura-date-header').hide();
	$('.date-unstructured').hide();
	$('.date-partial').hide();
	$('#event-date-types').change(function(){
		if($(this).val() == 'Range'){
			$('.dacura-date-to').show();
			$('.dacura-date-header').show();
		}
		else {
			$('.dacura-date-to').hide();
			$('.dacura-date-header').hide();
		}
	});
	$('#event-date-iptypes').change(function(){
		if($(this).val() == 'Partial'){
			$('.date-full').hide();
			$('.date-unstructured').hide();
			$('.date-partial').show();
		}
		else if($(this).val() == 'Unstructured'){
			$('.date-full').hide();
			$('.date-unstructured').show();
			$('.date-partial').hide();
		}
		else {
			$('.date-full').show();
			$('.date-unstructured').hide();
			$('.date-partial').hide();				
		}
		//alert($(this).val());						
	});
	$('.fatalities-range').hide();
	$('#event-fatalities-types').change(function(){
		if($(this).val() == 'Range'){
			$('.fatalities-range').show();
			$('.fatalities-num').hide();
		}
		else if($(this).val() == 'Number'){
			$('.fatalities-range').hide();
			$('.fatalities-num').show();
		}
		else {
			$('.fatalities-range').hide();
			$('.fatalities-num').hide();
		}
	});
	
};

*
*/

