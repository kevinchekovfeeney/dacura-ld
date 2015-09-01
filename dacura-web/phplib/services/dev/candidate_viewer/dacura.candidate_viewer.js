dacura.candidate_viewer = {}
dacura.candidate_viewer.apiurl = "<?=$service->get_service_url('candidate_viewer', array(), true)?>";
dacura.candidate_viewer.api = {};

dacura.candidate_viewer.api.start_session = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
	}
	xhr.url = dacura.candidate_viewer.apiurl + "/session";
	xhr.type = "PUT";
	return xhr;
}

dacura.candidate_viewer.api.pause_session = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={
				action: "pause"
		};
	}
	xhr.url = dacura.candidate_viewer.apiurl + "/session";
	xhr.type = "POST";
	return xhr;
};

dacura.candidate_viewer.api.end_session = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate_viewer.apiurl + "/session";
	xhr.type = "DELETE";
	return xhr;
};

dacura.candidate_viewer.api.continue_session = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={
				action: "continue"
		};
	}
	xhr.url = dacura.candidate_viewer.apiurl + "/session";
	xhr.type = "POST";
	return xhr;
};

dacura.candidate_viewer.api.resume_session = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={
				action: "resume"
		};
	}
	xhr.url = dacura.candidate_viewer.apiurl + "/session";
	xhr.type = "POST";
	return xhr;
};



dacura.candidate_viewer.api.get_next_candidate = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
	}
	xhr.url = dacura.candidate_viewer.apiurl;
	xhr.type = "GET";
	return xhr;
};

dacura.candidate_viewer.api.get_widget = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
	}
	xhr.url = dacura.candidate_viewer.apiurl+"/widget";
	xhr.type = "GET";
	return xhr;
};

dacura.candidate_viewer.api.send_decision = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data = {};
	}
	xhr.url = dacura.candidate_viewer.apiurl+"/widget";
	xhr.type = "POST";
	return xhr;
};


/*
 * continue_session
 * resume_session
 * pause_session
 * get_widget
 * get_next_candidate
 * update_candidate
 * 
 */


dacura.candidate_viewer.resetButtons = function(){
	$('#candidate-pause').show();
	$('#candidate-resume').hide();
	$('#work-controls').show();
}

dacura.candidate_viewer.init = function(){
	dacura.candidate_viewer.clock = setInterval('clockTick();', 1000 );
	$('#candidate-resume').hide();
	$('#work-pause').hide();
	$('#work-fetching').hide();
	$('#work-session-candidate').hide();
	$('#dc-state-box').hide();
	$('#reportsbox').hide();
	$('#dc-work').hide();
}


 dacura.candidate_viewer.doWork = function(){
	var self = this;
	$('body').append("<div id='session-start'><p>Once you start your session, you will be presented with a series of articles from the (London) Times Newspaper.</p>" +
			"		<p> For each one, you need to identify whether it contains a report of a political violence event and whether there were any fatalities</p>" +
			"		<p> <b>Political violence</b> includes all violent events (riots, lynchings, massacres etc..) which are not domestic and not ordinary economic crime." +
			"		<p> We are <b>only</b> interested in events that took place in Britain and Ireland where fatalities may have taken place.</div>");
	$('#session-start').dialog({title: "Ready to work?", modal: true, width: 600,
		buttons: {
		"Start Session": function() {
			self.startDacuraSession();
			$( this ).dialog( "close" );
			$('#session-start').remove();
		},
		Cancel: function() {
			$('#dc-work').hide();
			$('#dc-content').show();
			$( this ).dialog( "close" );
			$('#session-start').remove();
		}
	}});
};

dacura.candidate_viewer.startDacuraSession = function(){
	this.resetButtons();
	this.disableSubmissionButtons();
	this.disableSessionButtons();
	//var ajs = this.getAjaxSettings("start_session", "work", "Starting Session");
	var ajs = dacura.candidate_viewer.api.start_session();
	var self = this;
	$('#dc-content').hide();
	$('#dc-work').show();
	ajs.beforeSend = function(){
		self.showWorkBusy("Session Initialised");
	};
	ajs.complete = function(){
		self.clearWorkBusy();    		
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR){
		//self.showWorkMessage("Session Started", "success");
		var gs = JSON.parse(data);
		self.updateGameState(gs);
		var wajs = dacura.candidate_viewer.api.get_next_candidate();
		wajs.beforeSend = function(){
			self.showWorkBusy("Fetching next page");
		};
		wajs.complete = function(){
			self.clearWorkBusy();    		
		};
		$.ajax(wajs)
		.done(function(data, textStatus, jqXHR){
			var gs = JSON.parse(data);
			self.displayCandidate(gs);
			self.enableSubmissionButtons();
			self.enableSessionButtons();
		})
		.fail(function (jqXHR, textStatus){
			if(jqXHR.status == 410){
				dacura.candidate_viewer.shutDownWorkMode();
				dacura.candidate_viewer.showAlertMsg("No Candidates Available", "There are no further articles to be scanned");
				$('#dc-content').show();			
			}
			else {
				dacura.candidate_viewer.showAlertMsg("Error", jqXHR.responseText + " [" + jqXHR.status + "]");
			}		
		});		
	})
	.fail(function (jqXHR, textStatus){
		self.showAlertMsg("Error",  jqXHR.responseText + " [" + jqXHR.status + "]");
	});
};

/**
 * Loads the tool first - then loads the session - this is to ensure that the tool is fully loaded 
 * before it is called. 
 */
dacura.candidate_viewer.continueDacuraSession = function(){
	//this.resetButtons();
	this.disableSubmissionButtons();
	this.showWorkBusy("Session Resumed");
	var self =this;
	var wajs = dacura.candidate_viewer.api.get_widget();
	wajs.beforeSend = function(){
		self.showWorkBusy("Session Initialised");
	};
	dacura_widget.mode = "internal";
	
	wajs.complete = function (){
		var ajs = dacura.candidate_viewer.api.continue_session();//dacura.candidate_viewer.getAjaxSettings("continue_session", "control", "Loading User Session");
		$.ajax(ajs)
		.done(function(data, textStatus, jqXHR){
			var gs = JSON.parse(data);
			$('#dc-content').hide();
			$('#dc-work').show();
			self.updateGameState(gs);
			var xajs = dacura.candidate_viewer.api.get_next_candidate();
			xajs.beforeSend = function(){
				self.showWorkBusy("Fetching next page");
			};
			xajs.complete = function(){};
			$.ajax(xajs)
			.done(function(data, textStatus, jqXHR){
				var xs = JSON.parse(data);
				self.displayCandidate(xs);
				self.enableSessionButtons();
				self.enableSubmissionButtons();
			})
			.fail(function (jqXHR, textStatus){
				self.enableSessionButtons();
				if(jqXHR.status == 410){
					dacura.candidate_viewer.shutDownWorkMode();
					dacura.candidate_viewer.showAlertMsg("No Candidates Available", "There are no further articles to be scanned");
					$('#dc-content').show();
							
				}
				else {
					dacura.candidate_viewer.showAlertMsg("Error", jqXHR.responseText + " [" + jqXHR.status + "]");
				}		
			});
		})
		.fail(function (jqXHR, textStatus){
			self.showWorkMessage("Error: " + jqXHR.responseText, "error");
		});		
	};
	dacura_widget.loadTool(false, wajs);
}



dacura.candidate_viewer.displayCandidate = function(cand){
	//this.updateGameState(cand.session);
	//dacura_widget.clearTool();
	this.candidate = cand;
	var url = "";
	var purl = false;
	var imghtml = "<img id='current-candidate-image' src='";
	if("image" in this.candidate){
		if("full" in this.candidate.image){
			url = this.candidate.image.full.url;
			imghtml +=  url + "'>";
		}
		else{
			alert("malformated candidate " + cand.id);
		}	
		if("preview" in this.candidate.image){
			purl = this.candidate.image.preview.url;
			imghtml += "<img id='current-candidate-preview' width=" + this.candidate.image.full.width + "height=" + this.candidate.image.full.height + " src='" +  purl + "'>";
		}
	}
	else {
		url = this.candidate.contents.citation.articleimage;
		imghtml += url + "'>";
	}
	var details = "";
	if("articletitle" in this.candidate.contents.citation){
		details = "Article Title: " + this.candidate.contents.citation.articletitle.substring(0, 100) + ". ";
	}
	if("sectiontitle" in this.candidate.contents.citation){
		details += "Published in the " + this.candidate.contents.citation.sectiontitle + " section of The Times ";
	}
	else {
		details += "Published in The Times ";
	}
	if("issuedate" in this.candidate.contents.citation){
		details += this.candidate.contents.citation.issuedate.day + "/" + this.candidate.contents.citation.issuedate.month + "/" + this.candidate.contents.citation.issuedate.year;
	}
	$('#work-candidate-banner').html(details);
	$('#work-show-img').html(imghtml);
	$('#work-show-img').panzoom({ 
		increment: 0.1,
		minScale: 0.1,
		maxScale: 1,
        transition: false
	});
	if(purl){
		var h = this.candidate.image.full.height / 2;
		dacura.candidate_viewer.setUpPreview(purl, h);
		dacura.candidate_viewer.setUpImageLoad(url, purl);
	}
	else{
		dacura.candidate_viewer.setUpImageLoad(url);
	}
	var self = this;
	//dacura.candidate_viewer.centerCandidateImage(url);
	$('#candidate-slider').slider({
		min: 1,
		max: 10,
		value: 5,
		change: function( event, ui ) {
			var val = $(this).slider( "value" ) / 10; 
			$('#work-show-img').panzoom("zoom", val);
		},
		disabled: true
	});
	$('#work-show-img').panzoom("zoom", 0.5);
	this.clearWorkBusy();    		
}

dacura.candidate_viewer.setUpPreview = function(src, h){
	var newImg = new Image();
	newImg.onload = function() {
		dacura.candidate_viewer.clearWorkBusy();
		$(window).scrollTop(h);
		var diff = $('#work-show-img').width() - $('#current-candidate-image').width();
		if(diff > 1){
			diff = diff / 2;
			$('#current-candidate-preview').css("left", diff + "px");
			//alert(h + " " + diff);
		} 
	};
    newImg.src = src; // this must be done AFTER setting onload
};

dacura.candidate_viewer.setUpImageLoad = function(src, x){
	var newImg = new Image();
	newImg.onload = function() {
		dacura.candidate_viewer.clearWorkBusy(); 
		$('#candidate-slider').slider("enable");
		if(!x){
	    	var height = newImg.height;
	    	$(window).scrollTop(height/2);
		}
		else {
			var diff = $('#work-show-img').width() - $('#current-candidate-image').width();
			if(diff > 1){
				diff = diff / 2;
				$('#current-candidate-preview').css("left", diff + "px");
				//alert(h + " " + diff);
			} 
		}
	}
    newImg.src = src; // this must be done AFTER setting onload
}



dacura.candidate_viewer.centerCandidateImage = function(src){
	var newImg = new Image();
	newImg.onload = function() {
    	var height = newImg.height;
    	var width = newImg.width;
    	$(window).scrollTop(height/2);
    }
    newImg.src = src; // this must be done AFTER setting onload
}

dacura.candidate_viewer.updateGameState = function(sess){
	$('.dc-candidates-time').html(sess.duration);
	$('#work-session-viewed').html(sess.assigned);
	$('#work-session-accepted').html(sess.accepted);
	$('#work-session-rejected').html(sess.rejected);
}

dacura.candidate_viewer.showWorkMessage = function(msg, type){
	//$('#work-message').html("<div id='work-message-contents' class='work-message work-message-" + type + "'>" + msg + "</div>").hide().show({effect: 'fade',
     //   duration: 500}).delay(4000).hide({
       //     effect: 'fade',
        //    duration: 3000
        //});
    //alert(type + " - " + msg);
	dacura.candidate_viewer.showWorkBusy(msg);
}

dacura.candidate_viewer.clearWorkMessage = function(){
	$('#work-message').html("");
}

dacura.candidate_viewer.showWorkBusy = function(msg){
	$('#work-show').hide();
	$("#dc-work-fetching-header").html(msg);
	$('#work-fetching').show();
	//$(window).scrollTop(0);
}


dacura.candidate_viewer.clearWorkBusy = function(){
	$('#work-show').show();
	$('#work-fetching').hide();
}


dacura.candidate_viewer.disableSessionButtons = function (){
	$('#candidate-pause').button( "disable");	
	$('#candidate-resume').button( "disable");	
	$('#candidate-end').button( "disable");	
};

dacura.candidate_viewer.enableSessionButtons = function (){
	$('#candidate-pause').button( "enable");	
	$('#candidate-resume').button( "enable");	
	$('#candidate-end').button( "enable");	
};



dacura.candidate_viewer.disableSubmissionButtons = function (){
	$('#candidate-accept').button( "disable");	
	$('#candidate-reject').button( "disable");	
	$('#candidate-skip').button( "disable");	
};

dacura.candidate_viewer.enableSubmissionButtons = function (){
	$('#candidate-accept').button( "enable");	
	$('#candidate-reject').button( "enable");	
	$('#candidate-skip').button( "enable");	
};

dacura.candidate_viewer.updateSessionState = function(act, msg){
	//this.clearWorkMessage();
	//this.showWorkBusy(msg);
	var ajs;
	if(act == "pause"){
		this.disableSubmissionButtons();
		ajs = dacura.candidate_viewer.api.pause_session();
	}
	else if(act == 'unpause'){
		ajs = dacura.candidate_viewer.api.resume_session();
	}
	else if(act == 'end'){
		ajs = dacura.candidate_viewer.api.end_session();
	}
	var self = this;
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		var gs = JSON.parse(data);
		self.updateGameState(gs);
		if(act != 'pause'){
			self.enableSubmissionButtons();
		}
	})	
	.fail(function (jqXHR, textStatus){
		self.enableSessionButtons();	
		self.enableSubmissionButtons();
		self.showWorkMessage("Error: " + jqXHR.responseText, "error");
	});
};

dacura.candidate_viewer.processCandidate = function(act, msg){
	this.clearWorkMessage();
	this.disableSubmissionButtons();
	this.showWorkBusy(msg);
	this.disableSessionButtons();
	var ajs = dacura.candidate_viewer.api.send_decision();
	ajs.beforeSend = function(){
		self.showWorkBusy("Submitting Decision");
	};
	ajs.complete = function (){};
	ajs.data.id = this.candidate.id;
	ajs.data.decision = act;
	var self = this;
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		var gs = JSON.parse(data);
		self.updateGameState(gs);
		var xajs = dacura.candidate_viewer.api.get_next_candidate();
		xajs.beforeSend = function(){
			self.showWorkBusy("Fetching next page");
		};
		xajs.complete = function(){};
		$.ajax(xajs)
		.done(function(data, textStatus, jqXHR){
			var xs = JSON.parse(data);
			dacura.candidate_viewer.displayCandidate(xs);
			dacura.candidate_viewer.enableSessionButtons();	
			dacura.candidate_viewer.enableSubmissionButtons();
		})
		.fail(function (jqXHR, textStatus){
			dacura.candidate_viewer.enableSessionButtons();	
			if(jqXHR.status == 410){
				dacura.candidate_viewer.shutDownWorkMode();
				dacura.candidate_viewer.showAlertMsg("No Candidates Available", "There are no pages left to view");
				$('#dc-content').show();
			}
			else {
				dacura.candidate_viewer.showAlertMsg("Error", jqXHR.responseText + " [" + jqXHR.status + "]");
			}
		});
	})
	.fail(function (jqXHR, textStatus){
		//self.clearWorkBusy();
		self.enableSessionButtons();	
		self.enableSubmissionButtons();
		self.showWorkMessage("Error: " + jqXHR.responseText, "error");
	});
}

dacura.candidate_viewer.processSuccess = function(retdata){
	var gs = JSON.parse(retdata);
	this.updateGameState(gs);
	dacura_widget.closeTool();
	$('#another-report').remove();
	$('body').append("<div id='another-report'>Is there another political violence event in this page? If so, please stay on this page and fill in another report.</div>");
	$('#another-report').dialog({
		resizable: false,
		title: "Report successfully submitted", 
		width: 500,
		modal: true,
		buttons: {
			"Stay on this page": function() {
			$( this ).dialog( "close" );
			dacura.candidate_viewer.clearWorkBusy();
			dacura_widget.clearTool();
			//dacura.candidate_viewer.fireUpTool();
		},
		"Continue to next page": function() {
			dacura.candidate_viewer.disableSubmissionButtons();
			$( this ).dialog( "close" );
			var xajs = dacura.candidate_viewer.api.get_next_candidate();
			xajs.complete = function(){};
			$.ajax(xajs)
			.done(function(data, textStatus, jqXHR){
				var xs = JSON.parse(data);
				dacura.candidate_viewer.displayCandidate(xs);
				dacura.candidate_viewer.enableSessionButtons();	
				dacura.candidate_viewer.enableSubmissionButtons();
			})
			.fail(function (jqXHR, textStatus){
				if(jqXHR.status == 410){
					dacura.candidate_viewer.shutDownWorkMode();
					msg = "There are no outstanding candidates in any years that have been allocated to you.  You need to get an administrator to assign you more years.";
					dacura.candidate_viewer.showAlertMsg("No Candidates Available", msg);
					$('#dc-content').show();
				}
				else {
					dacura.candidate_viewer.showAlertMsg("Error", jqXHR.responseText + " [" + jqXHR.status + "]");
				}
			});
		}
	}
	});			 	
	//this.updateGameState(cand.session);
};

$('#do-work').button().click(function(e){
	e.preventDefault();
 	dacura.candidate_viewer.clearControlMessage();
	dacura.candidate_viewer.doWork(0);
});


dacura.candidate_viewer.shutDownWorkMode = function(){
	dacura.candidate_viewer.clearWorkBusy();    
	dacura.candidate_viewer.resetButtons();		
	dacura.candidate_viewer.turnSessionHoverOn();
	$('#work-session').click();
	dacura.candidate_viewer.hideSessionState();
	$('#work-pause').hide();
	$('#dc-work').hide();
	$('#work-candidate-banner').html("");
	$('#work-show-img').html("");
	dacura_widget.removeAll();
};

dacura.candidate_viewer.showAlertMsg = function(tit, msg){
	$('#dacura-alert-message').remove();
	$('body').append("<div id='dacura-alert-message'>" + msg + "</div>");
	$('#dacura-alert-message').dialog(
	{
		resizable: false,
		title: tit, 
		width: 300,
		modal: true,
		buttons: {
			"OK": function() {
				$( this ).dialog( "close" );
			}
		}
	});
	//probably want to fire up a dialog to show a summary of the session. 
	$('#dc-content').show();
}

$('#candidate-end').button().click(function(e){
	e.preventDefault();
	dacura.candidate_viewer.updateSessionState("end", "Session terminated");
	dacura.candidate_viewer.shutDownWorkMode();
	var html = "Duration: " + $('.dc-candidates-time').html() + " Viewed: " + $('#work-session-viewed').html() + " Accepted: " + $('#work-session-accepted').html() + " Rejected: " + $('#work-session-rejected').html();
	dacura.candidate_viewer.showAlertMsg("Session Terminated - Details", html );
	$('#dc-content').show();
});

dacura.candidate_viewer.fireUpTool = function(){
	$(dacura_widget.toolselector()).bind('dialogclose', function(event) {
		dacura.candidate_viewer.enableSubmissionButtons();
	});
	$(dacura_widget.toolselector()).bind('dialogopen', function(event) {
		dacura.candidate_viewer.disableSubmissionButtons();
	});
	$(dacura_widget.toolselector()+ "-confirm").bind('dialogclose', function(event) {
		dacura.candidate_viewer.enableSubmissionButtons();
	});
	$(dacura_widget.toolselector()+ "-confirm").bind('dialogopen', function(event) {
		dacura.candidate_viewer.disableSubmissionButtons();
	});
	dacura_widget.openTool();
	dacura_widget.loadCandidate(dacura.candidate_viewer.candidate);
}

$('#candidate-accept').button().click(function(e){
	e.preventDefault();
	dacura.candidate_viewer.fireUpTool();
});

$('#candidate-reject').button().click(function(e){
	e.preventDefault();
	//dacura.candidate_viewer.showNewCandidateLoading("");
	dacura.candidate_viewer.processCandidate("reject", "Candidate Rejected");
});

$('#candidate-resume').button().click(function(e){
	e.preventDefault();
	dacura.candidate_viewer.clock = setInterval('clockTick();', 1000 )
	dacura.candidate_viewer.turnSessionHoverOn();
	$('#work-session').click();
	$('#work-show').show();
	$('#work-pause').hide();
	dacura.candidate_viewer.centerCandidateImage($('#current-candidate-image').attr("src"));
	$('#candidate-resume').hide();
	$('#candidate-pause').show();
	//$('#work-controls').show({"slide": {direction: "left"}, duration: 2000});
	dacura.candidate_viewer.updateSessionState("unpause", "Work session resumed");
});

dacura.candidate_viewer.pauseSession = function(){
	clearInterval(dacura.candidate_viewer.clock);
	$('#work-show').hide();
	$('#work-pause').show();
	$('#candidate-resume').show();
	$('#candidate-pause').hide();
	dacura_widget.closeTool();
	dacura.candidate_viewer.showSessionState(); 
	dacura.candidate_viewer.turnSessionHoverOff();
	$('#work-session').unbind('click');
};


$('#candidate-pause').button().click(function(e){
	e.preventDefault();
	dacura.candidate_viewer.pauseSession();
	//$('#work-controls').hide({"slide": {direction: "right"}, duration: 2000});
	dacura.candidate_viewer.updateSessionState("pause", "Work session paused");
});

$('#candidate-skip').button().click(function(e){
	e.preventDefault();
	$('#work-show').hide();
	dacura.candidate_viewer.processCandidate("skip", "Candidate Skipped");
});

/*
 * Submit buttons
 */

function clockTick(hms){
	hms = $('.dc-candidates-time').text();
	bits = hms.split(":");
	var h = bits[0];
	var m = bits[1];
	var s = bits[2];
	if(s == "59"){
		s = "00";
		if(m == "59"){
			m = "00";
			h = parseInt(h) + 1;
		}
		else {
			m = parseInt(m) + 1;
		}
	}
	else {
		s = parseInt(s) + 1;	
	}
	if(parseInt(m) < 10) m = "0" + parseInt(m);
	if(parseInt(s) < 10) s = "0" + parseInt(s);
	$('.dc-candidates-time').html(h + ":" + m + ":" + s);	
}

dacura.candidate_viewer.showSessionState = function(){
	$('#dc-state-box').show("slide");
	$('#work-session').css("background-color", "#ffffcc");
};

dacura.candidate_viewer.hideSessionState = function(){
	$('#dc-state-box').hide("slide");
	$('#work-session').css("background-color", "#ffffff");
};

dacura.candidate_viewer.turnSessionHoverOff = function(){
	$('.work-session-hover').unbind('mouseenter mouseleave');
	$('#work-session').click(dacura.candidate_viewer.turnSessionHoverOn);
};

dacura.candidate_viewer.turnSessionHoverOn = function(){
	$('.work-session-hover').hover(dacura.candidate_viewer.showSessionState, dacura.candidate_viewer.hideSessionState);		
	$('#work-session').click(dacura.candidate_viewer.turnSessionHoverOff);
}

dacura.candidate_viewer.loadTool = function() {
	var wajs = dacura.candidate_viewer.api.get_widget();
	dacura_widget.loadTool(false, wajs);
};

