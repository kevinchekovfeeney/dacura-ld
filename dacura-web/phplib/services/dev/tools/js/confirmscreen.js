dacura_widget.showConfirm = function(data){
	var questions = [];
	questions.push({"msg": "The article contains a report of a political violence event", "field" : 'report', "check" : "ok", "field" : 'report', "confidence": true});
	questions.push(this.getDateConfirm(data.date));
	questions.push(this.getPlaceConfirm(data));
	questions.push({"msg": "The event type is: <span class='confirm-user-data'>" + data.type + "</span>", "check" : "ok", "field" : 'type', "confidence" : true});
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
		questions.push({"msg": "The event description is: <span class='confirm-user-data'>" + data.description + "</span>", "check" : "ok", "field": 'description', "confidence" : false});
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
	var detailshtml = "<table class='dacura-confirm-form dacura-confirm-ok'><th class='dc-confirm-details'>Report Details</th><th>Dubious?</th>";
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


dacura_widget.getDateConfirm = function(date){
	var first_date = "";
	var msg = {};
	if(!('to' in date) && "day" in date.from && date.from.day != ""){
		first_date = "on " + date.from.day + "/";
	}
	else if(('to' in date) && "day" in date.from && date.from.day != ""){
		first_date = date.from.day + "/";
	}
	else if(!('to' in date)){
		first_date = "during ";
	}
	if("month" in date.from && date.from.month != ""){
		first_date += date.from.month + "/";
	}
	if('year' in date.from){
		first_date += date.from.year;
	}
	else {
		msg= {"msg": "No year specified for event date", 'check':  "broken", "confidence": false};
	}
	if('to' in date){
		var second_date = "";
		if("day" in date.to && date.to.day != ""){
			second_date = date.to.day + "/";
		}
		if("month" in date.to && date.to.month != ""){
			second_date += date.to.month + "/";
		}
		second_date += date.to.year;
		msg= {"msg": "The event took place <span class='confirm-user-data'>between " + first_date + " and " + second_date + "</span>", 'check':  "ok", "field" : "date", "confidence": true};
	}
	else {
		msg= {"msg": "The event took place <span class='confirm-user-data'>" + first_date + "</span>", 'check':  "ok", "field" : "date", "confidence": true};
	}
	return msg;
};

dacura_widget.getPlaceConfirm = function(data){
	var locs = {
			eng: "England",
			sco: "Scotland", 
			wal: "Wales", 
			irl: "Ireland"
	};
	var locstr = "";
	var msg = {};
	if("location" in data){
		if('place' in data.location && data.location.place != ""){
			locstr = data.location.place;
		}
		if('county' in data.location && data.location.county != ""){
			if(locstr != "") locstr += ", ";
			locstr += data.location.county;
		}
		if('country' in data.location && data.location.country != ""){
			if(locstr != "") locstr += ", ";
			locstr += locs[data.location.country];
		}
	}
	if(locstr == ""){
		msg= {"msg": "No location specified", 'check':  "warning", "confidence": false};
	}
	else {
		msg= {"msg": "The event took place in <span class='confirm-user-data'>" + locstr + "</span>", 'check':  "ok", "field" : "location", "confidence": true};
	}
	return msg;
};


dacura_widget.getFatalitiesConfirm = function(fatalities){
	if(fatalities.type == "unknown"){
		msg = {"msg": "There were an unknown number of fatalities", 'check':  "ok", "field" : "fatalities", "confidence": true};
	}
	else if(fatalities.type == "number"){
		if(fatalities.number == "" || fatalities.number == "0"){
			msg = {"msg": "No Fatalities indicated - unknown", 'check':  "warning", "confidence": false};
		}
		else {
			if(fatalities.number == 1){
				msg = {"msg": "There was <span class='confirm-user-data'>1 fatality</span>", 'check':  "ok", "field" : "fatalities", "confidence": true};
			}
			else {
				msg = {"msg": "There were <span class='confirm-user-data'>" + fatalities.number + " fatalities</span>", 'check':  "ok", "field" : "fatalities", "confidence": true};
			}
		}
	}
	else if(fatalities.type == 'range'){
		var tmsg = "";
		var warning = false;
		if((fatalities.from == "" || fatalities.from == "0") && (fatalities.to == "" || fatalities.to == "0") ){
			warning = true;
			tmsg = "No range limits of fatalities specified";
		}
		else if(fatalities.from == "" || fatalities.from == "0"){
			tmsg = "Fatalities were <span class='confirm-user-data'>less than " + fatalities.to + "</span>";
		}
		else if(fatalities.to == "" || fatalities.to == "0"){
			tmsg = "Fatalities were <span class='confirm-user-data'>more than " + fatalities.from + "</span>";
		}
		else {
			tmsg = "Fatilities were <span class='confirm-user-data'>between " + fatalities.from + " and " + fatalities.to + "</span>"; 
		}
		if(warning){
			msg= {"msg": tmsg, 'check':  "warning", "confidence": false};
		}
		else {
			msg= {"msg": tmsg, 'check':  "ok", "field" : "fatalities", "confidence": true};
		}
	}
	else {
		msg= {"msg": "No fatalities field!", 'check':  "warning", "confidence": false};		
	}
	return msg;
};

dacura_widget.getMotivationConfirm = function(motivation){
	if(motivation.length == 0){
		msg= {"msg": "No motivations were specified", 'check':  "warning", "confidence": false};
	}
	else if(motivation.length == 1){
		msg= {"msg": "The motivation was <span class='confirm-user-data'>" + motivation[0] + "</span>", 'check':  "ok", "field" : "motivation", "confidence": true};
	}
	else {
		msg= {"msg": motivation.length + " motivations were involved: <span class='confirm-user-data'>" + motivation.join(", ") + "</span>", 'check':  "ok", "field" : "motivation",  "confidence": true};
	}
	return msg;
};

dacura_widget.getActorsConfirm = function(actors){
	var msgs = [];
	for(var i = 0; i <= 3; i++){
		actor = actors[i];
		var amsg = "Actor " + (i + 1) + " ";
		if(actor.actortype == ""){
			amsg += " not defined";
			if(i < 2){
				msgs.push({"msg": amsg, "check" : "warning", "confidence": false});			
			}
			else {
				msgs.push({"msg": amsg, "check" : "empty", "field" : "actor" + i, "confidence": false});			
			}
		}
		else {
			//get numbers 
			var nums;
			if(actor.counttype == 'number'){
				nums = (actor.countnumber) ? actor.countnumber : "unknown number";
			}
			else {
				if(actor.countfrom == "" && actor.countto == ""){
					nums = "unknown number";
				}
				else if(actor.countfrom == "" ){
					nums = "less than " + actor.countto;
				}
				else if(actor.countro == ""){
					nums = "more than" + actor.countfrom;
				}
				else {
					nums = "between " + actor.countfrom + " and " + actor.countto;
				}
			}
			var gname = "";
			if(actor.actorgroup != ""){
				gname = ", named <span class='confirm-user-data'>" +  actor.actorgroup + "</span>, ";
			}

			if(actor.actortype == 'individual'){
				if(nums == "1"){
					amsg += "<span class='confirm-user-data'>An individual</span> " + gname; 
				}
				else {
					amsg += "<span class='confirm-user-data'>" + nums + " Individuals</span> " + gname;					
				}
			}
			else if(actor.actortype == 'broadgroup'){
				amsg += "<span class='confirm-user-data'>A broad group</span>" + gname + " of <span class='confirm-user-data'>" + nums + " people</span> ";									
			}
			else {
				amsg += "<span class='confirm-user-data'>An organised group</span>" + gname + " of <span class='confirm-user-data'>" + nums + " people</span> ";													
			}
			if(actor.fatalitytype == "number" && actor.fatalitynumber == ""){
				amsg += " sustained unknown casualties ";
			}
			else if(actor.fatalitytype == "number") {
				if(parseInt(actor.fatalitynumber) == 1){
					amsg += " sustained <span class='confirm-user-data'>1 fatality</span> ";
				}
				else if(parseInt(actor.fatalitynumber)){ 
					amsg += " sustained <span class='confirm-user-data'>" + actor.fatalitynumber + " fatalities</span> ";
				}
				else {
					amsg += " sustained <span class='confirm-user-data'>0 casualties</span> ";
				}
			}
			else if(actor.fatalitytype == "range") {
				if(parseInt(actor.fatalityfrom) && parseInt(actor.fatalityto)){
					amsg += " sustained <span class='confirm-user-data'>between " + actor.fatalityfrom + " and " + actor.fatalityto + " fatalities</span> ";
				}
				else if(parseInt(actor.fatalityfrom)){
					amsg += " sustained <span class='confirm-user-data'>more than "  + actor.fatalityfrom + " fatalities</span> ";
				}
				else if(parseInt(actor.fatalityto)){
					amsg += " sustained <span class='confirm-user-data'>less than "  + actor.fatalityto + " fatalities</span> ";
				}	
				else {
					amsg += " <span class='confirm-user-data-error'>invalid range of fatalities sustained</span> ";
				}
		    }
			if(actor.represents == ""){
				amsg += "(representation not specified)";
			}
			else {
				amsg += "representing ";
				if(actor.represents == 'state'){
					amsg += "<span class='confirm-user-data'>the state</span> ";
				}
				else if(actor.represents == 'broadgroup'){
					amsg += "<span class='confirm-user-data'>a broad social group</span> ";
				}
				else {
					amsg += "<span class='confirm-user-data'>an organised group</span> ";
				}
				if("representsgroup" in actor && actor.representsgroup != ""){
					amsg += "named <span class='confirm-user-data'>" + actor.representsgroup + "</span>";
				}
			}
			msgs.push({"msg": amsg, "check" : "ok", "field" : "actor" + i,"confidence": true});			
		}
	}
	return msgs;
};

dacura_widget.showURLForConfirm = function(url){
	var x = "<a class='confirm-user-data' href='" + url + "'>";
	if(url.length > 40){
		x += url.substring(0, 39);
	}
	else {
		x += url;
	}
	x += "</a>";
	return x;
};

dacura_widget.getCitationConfirm = function(citation){
	var msgs = [];
	if(citation.publicationtitle != "") {
		msgs.push({"msg": "Publication title <span class='confirm-user-data'>" + citation.publicationtitle + "</span>", "check" : "ok", "field" : "citation.publicationtitle", "confidence": false});
	}
	else {
		msgs.push({"msg": "Publication title not specified", "check" : "warning", "confidence": false});
	}
	if(citation.publicationurl != "") {
		msgs.push({"msg": "Publication url " + this.showURLForConfirm(citation.publicationurl), "check" : "ok", "field" : "citation.publicationurl", "confidence": false});
	}
	else {
		msgs.push({"msg": "Publication url not specified", "check" : "warning", "confidence": false});
	}
	if(citation.issuetitle != "") {
		msgs.push({"msg": "Issue title <span class='confirm-user-data'>" + citation.issuetitle + "</span>", "check" : "ok", "field" : "citation.issuetitle", "confidence": false});
	}
	else {
		msgs.push({"msg": "Issue title not specified", "check" : "empty", "confidence": false});
	}
	if(citation.issueurl != "") {
		msgs.push({"msg": "Issue URL " + this.showURLForConfirm(citation.issueurl), "check" : "ok",  "field" : "citation.issueurl", "confidence": false});
	}
	else {
		msgs.push({"msg": "Issue URL not specified", "check" : "empty", "confidence": false});
	}
	if("issuedate" in citation) {
		if(citation.issuedate.year == "" || citation.issuedate.year == 0 || ((citation.issuedate.month == "" || citation.issuedate.month == 0) 
				&& (citation.issuedate.day != "" && citation.issuedate.day != 0))){
			msgs.push({"msg": "Issue date not specified", "check" : "empty", "confidence": false});
		}
		else {
			var ds = "";
			if(citation.issuedate.day != "" && citation.issuedate.day != 0){
				ds += citation.issuedate.day + "/";
			}
			if(citation.issuedate.month != "" && citation.issuedate.month != 0){
				ds += citation.issuedate.month + "/";
			}
			ds += citation.issuedate.year;
			msgs.push({"msg": "Issue date <span class='confirm-user-data'>" + ds + "</span>", "check" : "ok", "field" : "citation.issuedate","confidence": false});
		}
	}
	else {
		msgs.push({"msg": "Issue date not specified", "check" : "warning", "confidence": false});
	}
	if(citation.sectiontitle != "") {
		msgs.push({"msg": "Section title <span class='confirm-user-data'>" + citation.sectiontitle + "</span>", "check" : "ok", "field" : "citation.sectiontitle", "confidence": false});
	}
	else {
		msgs.push({"msg": "Section title not specified", "check" : "empty", "confidence": false});
	}
	if(citation.sectionurl != "") {
		msgs.push({"msg": "Section url " + this.showURLForConfirm(citation.sectionurl), "check" : "ok",  "field" : "citation.sectionurl", "confidence": false});
	}
	else {
		msgs.push({"msg": "Section url not specified", "check" : "empty", "confidence": false});
	}
	if(citation.articletitle != "") {
		msgs.push({"msg": "Article title <span class='confirm-user-data'>" + citation.articletitle + "</span>", "check" : "ok", "field" : "citation.articletitle", "confidence": false});
	}
	else {
		msgs.push({"msg": "Article title not specified", "check" : "warning", "confidence": false});
	}
	if(citation.articleurl != "") {
		msgs.push({"msg": "Article url " + this.showURLForConfirm(citation.articleurl), "check" : "ok", "field" : "citation.articleurl", "confidence": false});
	}
	else {
		msgs.push({"msg": "Article url not specified", "check" : "warning", "confidence": false});
	}
	if(citation.articleid != "") {
		msgs.push({"msg": "Article ID <span class='confirm-user-data'>" + citation.articleid + "</span>", "check" : "ok", "field" : "citation.articleid", "confidence": false});
	}
	else {
		msgs.push({"msg": "Article ID  not specified", "check" : "warning", "confidence": false});
	}
	if(citation.articleimage != "") {
		msgs.push({"msg": "Article image " + this.showURLForConfirm(citation.articleimage), "check" : "ok", "field" : "citation.articleimage", "confidence": false});
	}
	else {
		msgs.push({"msg": "Article image not specified", "check" : "warning", "confidence": false});
	}
	if(citation.articlepagefrom != "") {
		if(citation.articlepageto != ""){
			msgs.push({"msg": "Pages: <span class='confirm-user-data'>" + citation.articlepagefrom + " to " + citation.articlepageto + "</span>", "check" : "ok", "field" : "citation.pages", "confidence": false});
		}
		else {
			msgs.push({"msg": "Page: <span class='confirm-user-data'>" + citation.articlepagefrom + "</span>", "check" : "ok", "field" : "citation.articlepages", "confidence": false});
		}
	}	
	else {
		msgs.push({"msg": "Article pages not specified", "check" : "empty", "confidence": false});
	}
	return msgs;
};

dacura_widget.validateNewRecord = function(data){
	/*
	 * Rules: 
	 * 1. We need a citation -> id, url
	 * 2. we need a date year at least
	 * 3. we need an event type
	 * 4. we need to check all ranges to make sure that there is something there
	 */
	var missing = [];
	if('date' in data && "from" in data.date && "year" in data.date.from){
		if(data.date.from.year == ""){
			missing.push({name: "event-datetime-from-yy", "msg": "You must specify at least the year of the event"});
		}
		else if(!parseInt(data.date.from.year) || data.date.from.year > 2013 || parseInt(data.date.from.year) < 1780){
			missing.push({name: "event-datetime-from-yy", "msg": "The event year must be between 1780 and 2013"});
		}
		if("month" in data.date.from && data.date.from.month != "" && (data.date.from.month > 12 || data.date.from.month < 1)){
			missing.push({name: "event-datetime-from-mm", "msg": "The event month must be 1 and 12"});
		}
		if("day" in data.date.from && data.date.from.day != "" && (!("month" in data.date.from) || data.date.from.month == "")){
			missing.push({name: "event-datetime-from-mm", "msg": "You cannot specify a day without a month"});			
		}
		if("day" in data.date.from && data.date.from.day != "" && (data.date.from.day > 31 || data.date.from.day < 1)){
			missing.push({name: "event-datetime-from-dd", "msg": "The event day must be 1 and 31"});
		}
		if("to" in data.date){
			if("day" in data.date.to && data.date.to.day != "" && (!("month" in data.date.to) || data.date.to.month == "")){
				missing.push({name: "event-datetime-to-mm", "msg": "You cannot specify a day without a month"});			
			}
			if(data.date.to.year == ""){
				missing.push({name: "event-datetime-to-yy", "msg": "You must specify at least the year of the date range end"});				
			}
			else if(parseInt(data.date.to.year) > 2013 || parseInt(data.date.to.year) < 1780){
				missing.push({name: "event-datetime-to-yy", "msg": "The event range end year must be between 1780 and 2013"});
			}
			else if(parseInt(data.date.to.year) < parseInt(data.date.from.year)){
				missing.push({name: "event-datetime-to-yy", "msg": "The event range end year is before the start year"});				
			}
			if("month" in data.date.to && data.date.to.month != "" && (data.date.to.month > 12 || data.date.to.month < 1)){
				missing.push({name: "event-datetime-to-mm", "msg": "The event month must be 1 and 12"});
			}
			else if("month" in data.date.to && "month" in data.date.from && data.date.from.month != "" && (data.date.to.month < data.date.from.month && data.date.to.year == data.date.from.year)){
				missing.push({name: "event-datetime-to-mm", "msg": "The event range end month is before the start month"});								
			}
			else if("day" in data.date.to && "day" in data.date.from && data.date.from.day != "" && (data.date.to.day < data.date.from.day && data.date.to.year == data.date.from.year && data.date.to.month == data.date.from.month)){
				missing.push({name: "event-datetime-to-dd", "msg": "The event range end day is before the start day"});												
			}
			if("day" in data.date.to && data.date.to.day != "" && (data.date.to.day > 31 || data.date.to.day < 1)){
				missing.push({name: "event-datetime-to-dd", "msg": "The event day must be between 1 and 31"});
			}
		}
	}
	else {
		missing.push({name: "event-category", "msg": "date field not correctly passed to check function!"});
	}
	if('type' in data && data.type == ""){
		missing.push({name: "event-category", "msg": "You must specify an event type field"});
	}
	if("fatalities" in data && "type" in data.fatalities){
		if(data.fatalities.type == "range"){
			var min = data.fatalities.from == "" ? 0 : parseInt(data.fatalities.from);
			var max = data.fatalities.to == "" ? 0 : parseInt(data.fatalities.to);
			if(isNaN(min)){
				missing.push({name: "event-fatalities-from", "msg": "The minimum fatalities must be a number"});						
			}
			else if(isNaN(max)){
				missing.push({name: "event-fatalities-to", "msg": "The maximum fatalities must be a number"});						
			}
			else if(min== 0 && max == 0){
				missing.push({name: "event-fatalities-from", "msg": "You must specify at least a maximum or a minimum value of the fatality range"});						
			}
			else if((min > 0) && (max  > 0) && (max <= min)){
				missing.push({name: "event-fatalities-from", "msg": "The minimum of the fatality range must be less than the maximum"});			
			}
		}
		else {
			var num = data.fatalities.number == "" ? 0 : parseInt(data.fatalities.number);
			if(isNaN(num)){
				missing.push({name: "event-fatalities-number", "msg": "The fatalities must be a number"});						
			}
		}
	}
	else {
		missing.push({name: "event-fatalities-number", "msg": "fatalities field not correctly passed to check function!"});
	}
	var all_actor_min_fatalities = 0;
	var all_actor_max_fatalities = 0;
	var maxed_out = 0;
 	for(var i in data.actors){
 		var actor = data.actors[i];
 		var j = 1 + parseInt(i);
 		if(actor.actortype != ""){
	 		if(actor.counttype == 'range'){
				var acmin = actor.countfrom == "" ? 0 : parseInt(actor.countfrom);
				var acmax = actor.countto == "" ? 0 : parseInt(actor.countto);
				if(isNaN(acmin)){
					missing.push({name: "actor-"+j+"-count-from", "msg": "The minimum count of actor " + j + " must be a number"});						
				}
				else if(isNaN(acmax)){
					missing.push({name: "actor-"+j+"-count-to", "msg": "The maximum count of actor " + j + " must be a number"});						
				}
				else if(acmin== 0 && acmax == 0){
					missing.push({name: "actor-"+j+"-count-from", "msg": "You must specify at least a maximum or a minimum value of the count range of actor " + j});						
				}
				else if((acmin > 0) && (acmax  > 0) && (acmin >= acmax)){
					missing.push({name: "actor-"+j+"-count-to", "msg": "The minimum of the count range of actor " + j + " must be less than the maximum"});			
				}			
	 		}
	 		else {
				var anum = actor.countnumber == "" ? 0 : parseInt(actor.countnumber);
				if(isNaN(anum)){
					missing.push({name: "actor-"+j+"-count-number", "msg": "The count of actor " + j + " must be a number"});						
				}
			}
	 		if(actor.fatalitytype == 'range'){
				var afmin = actor.fatalityfrom == "" ? 0 : parseInt(actor.fatalityfrom);
				var afmax = actor.fatalityto == "" ? 0 : parseInt(actor.fatalityto);
				if(isNaN(afmin)){
					missing.push({name: "actor-"+j+"-fatalities-from", "msg": "The minimum fatalities of actor " + j + " must be a number"});						
				}
				else if(isNaN(afmax)){
					missing.push({name: "actor-"+j+"-fatalities-to", "msg": "The maximum fatalities of actor " + j + " must be a number"});						
				}
				else if(afmin== 0 && afmax == 0){
					missing.push({name: "actor-"+j+"-fatalities-from", "msg": "You must specify at least a maximum or a minimum value of the fatalities range of actor " + j});						
				}
				else if((afmin > 0) && (afmax  > 0) && (afmin >= afmax)){
					missing.push({name: "actor-"+j+"-fatalities-to", "msg": "The minimum of the fatalities range of actor " + j + " must be less than the maximum"});			
				}			
	 		}
	 		else {
				var fnum = actor.fatalitynumber == "" ? 0 : parseInt(actor.fatalitynumber);
				if(isNaN(fnum)){
					missing.push({name: "actor-"+j+"-fatalities-number", "msg": "The fatalities of actor " + j + " must be a number"});						
				}
			}
 		}
 	}
	return missing;
};


