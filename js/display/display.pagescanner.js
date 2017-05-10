
DacuraPageScanner.prototype.displayFactoids = function(showContextFunc, showFactoidPartsFunc){
	var npage = "";//we build up the new page body from scratch by stitching the updates into the page text and doing a full text update
	var npage_offset = 0;
	//jQuery(this.jquery_body_selector).hide();
	for(var fid in this.factoids){
		var foid = this.factoids[fid];
		npage += this.originalBody.substring(npage_offset, foid.original.location); 
		npage += foid.decorate(this.factoid_css_class, this.factoid_id_prefix, showFactoidPartsFunc);
		npage += showContextFunc(foid);
		npage_offset = foid.original.location + (foid.original.full.length);
	}
	npage += this.originalBody.substring(npage_offset);
	jQuery(this.jquery_body_selector).html(npage).show("pulsate", {}, 800);
} 

DacuraPageScanner.prototype.displayFrameFactoids = function(frameviewer, entity_class, candidate, frames, settings){
	var selhtml = false;
	var vtype = (settings && settings.view_style ? settings.view_style : "replace");
	for(var fid in this.factoids){
		var foid = this.factoids[fid];
		if(frameviewer && frames){
			foid.setRelevantHarvestFrames(frames);
			//get frames specifically for this factoid by checking harvests
		}
		var decorated = foid.decorate(this.factoid_css_class, this.factoid_id_prefix, frameviewer, entity_class, candidate, frames);
		if(vtype == "simple"){
			npage += this.originalBody.substring(npage_offset, foid.original.location) + decorated + foid.original.full;
		}
		else if(vtype == "replace"){
			npage += this.originalBody.substring(npage_offset, foid.original.location) + decorated + foid.original.after;
		}
		npage_offset = foid.original.location + foid.original.full.length;
	}
	jQuery(this.jquery_body_selector).html(npage + this.originalBody.substring(npage_offset));
	if(frameviewer && frames){
		for(var fid in this.factoids){
			var foid = this.factoids[fid];
			var vmode = "create";
			if(foid.frames && foid.frames.length){
				var htmlid = this.factoid_id_prefix + foid.uniqid;
				var frmid = htmlid + "-frameviewer";
				frameviewer.draw(foid.frames, vmode, frmid);
			}
		}
		
	}
}

//analysis stuff over - now just display
DacuraPageScanner.prototype.undisplay = function(){
	jQuery(this.jquery_body_selector).html(this.originalBody);
	
}

//called when a factoid is clicked upon on the page...
//default is to call show factoid - can be overriden by setting a loadFactoidHandler(fid) function
DacuraPageScanner.prototype.loadFactoid = function(fid, viewcallback, updcallback){
	this.current_factoid = fid;
	if(typeof viewcallback == "function"){
		viewcallback(fid, updcallback);
	}
	else {
		this.showFactoid(fid, updcallback);
	}
}

//default behaviour when factoid on page is clicked - popup
DacuraPageScanner.prototype.showFactoid = function(fid, updcallback){
	alert(fid + " called to view factoid - empty function");
	//jpr(this.factoids[fid]);
}

//generates a chunk of html to represent a summary of the statistics from a page scan
//called by the complete function to show summary of scan results on jobby
DacuraPageScanner.prototype.getScanSummaryHTML = function(){
	var html = "<div class='page-scan-summary'>Summary: <dl>";
	for(var i in this.stats){
		html += "<dt class='" + this.stats[i].css + "'>" + this.stats[i].label + "</dt><dd class='" + this.stats[i].css + "'>" + this.stats[i].value + "</dd>";
	}
	html += "</dl></div>";
	return html;
}


DacuraPageScanner.prototype.scrollTo = function(uid, jq){
	if(jq){
		var faketop = jQuery(jq).height();		
		$('html, body').animate({
			scrollTop: jQuery("#" + this.factoid_id_prefix + uid).offset().top - (faketop + 20)
		}, 2000);
	}
}
