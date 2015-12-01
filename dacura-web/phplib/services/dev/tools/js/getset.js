dacura_widget.gatherInput = function(){
	var needs_toggle = false;
	var data = new Object();
	if(!$('#dc-details-section-contents').is(":visible")){
		needs_toggle = true;
		$('#dc-details-section-contents').show();
	}
	data["date"] = {
		"from": {
			"day": 	$('#event-datetime-from-dd').focus().val(),
			"month": 	$('#event-datetime-from-mm').focus().val(),
			"year": 	$('#event-datetime-from-yy').focus().val()
		}
	};
	if($('#event-datetime-precision').val() == 'between'){
		data["date"].to = {
			"day": 	$('#event-datetime-to-dd').focus().val(),
			"month": 	$('#event-datetime-to-mm').focus().val(),
			"year": 	$('#event-datetime-to-yy').focus().val()
		};
	}
	data['location'] = {
		"country": $('#event-location-country').val(),
		"place": $('#event-location-place').focus().val()
	};
	if(data['location']['country'] == 'irl'){
		data['location']['county'] = $('#event-location-irishcounty').val();
	}
	if($('#event-fatalities-number').is(":visible")){
		data['fatalities'] = {
			type: 'number',
			number: $('#event-fatalities-number').focus().val()
		};
	}
	else {
		data['fatalities'] = {
			type: "range", 
			from: $('#event-fatalities-from').focus().val(),
			to: $('#event-fatalities-to').focus().val()
		};		
	}
	data['type'] = $('#event-category').val();

	if(needs_toggle){
		$('#dc-details-section-contents').hide();
		needs_toggle = false;
	}
	data['description'] = $('#event-description-input').val();
	if(!$('#dc-actors-section-contents').is(":visible")){
		needs_toggle = true;
		$('#dc-actors-section-contents').show();
	}
	//needs work as some of the bits are hidden by default//
	data['actors'] = [];
	for(var i = 1; i <= 4; i++){
		var actordata = {
		   actortype:  $('#event-actor-type-'+i).val(),
		   represents: $('#event-actor-represents-'+i).focus().val()
		};
		if($('#event-actor-groupname-'+i).is(":visible")){
			actordata['actorgroup'] = $('#event-actor-groupname-' + i).focus().val();
		}
		if($('#event-actor-represents-groupname-'+i).is(":visible")){
			actordata['representsgroup'] = $('#event-actor-represents-groupname-'+i).focus().val();
		}
		if($('#actor-'+i+'-fatalities-number').is(":visible")){
			actordata['fatalitytype'] = 'number';
			actordata['fatalitynumber'] = $('#actor-'+i+'-fatalities-number').focus().val();
		}
		else {
			actordata['fatalitytype'] = 'range';
			actordata['fatalityfrom'] = $('#actor-'+i+'-fatalities-from').focus().val();
			actordata['fatalityto'] = $('#actor-'+i+'-fatalities-to').focus().val();
		}
		if($('#actor-'+i+'-count-number').is(":visible")){
			actordata['counttype'] = 'number';
			actordata['countnumber'] = $('#actor-'+i+'-count-number').focus().val();
		}
		else {
			actordata['counttype'] = 'range';
			actordata['countfrom'] = $('#actor-'+i+'-count-from').focus().val();
			actordata['countto'] = $('#actor-'+i+'-count-to').focus().val();
		}
		data.actors.push(actordata);
/*		data.actors.push({ 
		   actortype:  $('#event-actor-type-'+i).val(),
		   actormin:  $('#event-actor-number-min-'+i).focus().val(),
		   actormax:  $('#event-actor-number-max-'+i).focus().val(),
		   actorgroup: $('#event-actor-groupname-' + i).focus().val(),
		   fatalitytype: $('#event-actor-fatalities-precision-'+i).val(),
		   fatalityfrom: $('#event-actor-fatalities-from-'+i).focus().val(),
		   fatalityto: $('#event-actor-fatalities-to-'+i).focus().val(),
		   represents: $('#event-actor-represents-'+i).focus().val(),
		   representsgroup: $('#event-actor-represents-groupname-'+i).focus().val(),
	   });*/
	}
	if(needs_toggle){
		$('#dc-actors-section-contents').hide();
		needs_toggle = false;
	}
	data['motivation'] = [];
	$('#event-motivation input.dc-motivations-checkboxes:checked').each(function( index ){
		data.motivation.push($(this).val());
	});
	
	if(!$('#dc-citation-section-contents').is(":visible")){
		needs_toggle = true;
		$('#dc-citation-section-contents').show();
	}
	data['citation'] = {
		publicationtitle: $('#event-citation-publication-title').focus().val(), 
		publicationurl: $('#event-citation-publication-url').focus().val(), 
		issuetitle: $('#event-citation-issue-title').focus().val(),
		issueurl: $('#event-citation-issue-url').focus().val(),
		issuedate: {
			day: $('#event-citation-issue-date-dd').focus().val(),
			month: $('#event-citation-issue-date-mm').focus().val(),
			year: $('#event-citation-issue-date-yy').focus().val(),
		}, 
		sectiontitle: $('#event-citation-section-title').focus().val(),
		sectionurl: $('#event-citation-section-url').focus().val(),
		articletitle: $('#event-citation-article-title').focus().val(),
		articleurl: $('#event-citation-article-url').focus().val(),
		articleid: $('#event-citation-article-id').focus().val(),
		articleimage: $('#event-citation-article-image-url').focus().val(),
		articlepagefrom: $('#event-citation-article-pagesfrom').focus().val(),
		articlepageto: $('#event-citation-article-pagesto').focus().val()
	};
	if(needs_toggle){
		$('#dc-citation-section-contents').hide();
	}
	return data;
};

dacura_widget.load = function(data){
	var has_details = false;
	if(this.mode == "debug"){
		this.debugDump(data);
	}
	if("date" in data){
		has_details = true;
		if("from" in data.date){
			$('#event-datetime-from-dd').val(data.date.from.day).focusout();
			$('#event-datetime-from-mm').val(data.date.from.month).focusout();
			$('#event-datetime-from-yy').val(data.date.from.year).focusout().change();
			$('#event-datetime-precision').val("on").change(); 
		}
		if("to" in data.date){
			$('#event-datetime-to-dd').val(data.date.to.day).focusout();
			$('#event-datetime-to-mm').val(data.date.to.month).focusout();
			$('#event-datetime-to-yy').val(data.date.to.year).change().focusout();
			$('#event-datetime-precision').val("between").change();
		}
	}
	if("location" in data){
		has_details = true;
		if("country" in data.location) {
			$('#event-location-country').val(data.location.country).change();
		}
		if("place" in data.location) {
			$('#event-location-place').val(data.location.place).focusout();;
		}
		if("county" in data.location) {
			$('#event-location-irishcounty').val(data.location.county).focusout();;
		}
	}
	if("fatalities" in data){
		has_details = true;
		if("type" in data.fatalities) {
			if(data.fatalities.type == 'range'){
				showFatalitiesRange();
			}
			else {
				showFatalitiesNumber();
			}
		}
		if('number' in data.fatalities){
			$('#event-fatalities-number').val(data.fatalities.number).focusout();
		}
		if("from" in data.fatalities) {
			$('#event-fatalities-from').val(data.fatalities.from).focusout(); 
		}
		if("to" in data.fatalities) {
			$('#event-fatalities-to').val(data.fatalities.to).focusout();; 
		}
	}
	if('type' in data){
		$('#event-category').val(data.type).focusout();;
		has_details = true;
	}
	
	if(has_details){
		$('#dc-details-section-contents').show();
	}
	else {
		$('#dc-details-section-contents').hide();
	}
	if('description' in data){
		 $('#event-description-input').val(	data.description ).focusout();;
		 $('#dc-description-section-contents').show();
	}
	else {
		 $('#dc-description-section-contents').hide();
	}
	if("actors" in data){
		 $('#dc-actors-section-contents').show();	
		 	for(var i in data.actors){
		 		var actor = data.actors[i];
		 		var j = 1 + parseInt(i);
		 		if("actortype" in actor){
					$('#event-actor-type-'+j).val(actor.actortype).change();
				}
		 		if('countfrom' in actor){
		 			$('#actor-'+j+'-count-from').val(actor.countfrom).focusout();						
				}
				if('countto' in actor){
					$('#actor-'+j+'-count-to').val(actor.countto).focusout();
				}
				if('countnumber' in actor){
		 			$('#actor-'+j+'-count-number').val(actor.countnumber).focusout();						
				}	 			
		 		if("counttype" in actor && actor.counttype == 'range'){
					showActorCountRange(j);
		 		}
		 		else {
		 			showActorCountNumber(j);		 			
		 		}
		 		if('fatalityfrom' in actor){
		 			$('#actor-'+j+'-fatalities-from').val(actor.fatalityfrom).focusout();						
				}
				if('fatalityto' in actor){
					$('#actor-'+j+'-fatalities-to').val(actor.fatalityto).focusout();
				}
				if('fatalitynumber' in actor){
		 			$('#actor-'+j+'-fatalities-number').val(actor.fatalitynumber).focusout();						
				}
				if("fatalitytype" in actor && actor.counttype == 'range'){
					showActorFatalitiesRange(j);
		 		}
		 		else {
					showActorFatalitiesNumber(j);		 			
		 		}
				if("actorgroup" in actor){
					$('#event-actor-groupname-'+j).val(actor.actorgroup).focusout();;
				}
				if("represents" in actor){
					$('#event-actor-represents-'+j).val(actor.represents).change().focusout();;
				}
				if("representsgroup" in actor){
					$('#event-actor-represents-groupname-'+j).val(actor.representsgroup).focusout();;
				}
			}
	}
	else {
		 $('#dc-actors-section-contents').hide();		
	}
	if("motivation" in data){
		if('motivation' in data){
			for (var key in data.motivation){
				$('#event-motivation-' + data.motivation[key]).prop("checked", true).change();
			}
		}
	}
	else {
		 $('#dc-motivation-section-contents').hide();		
	}
	if("citation" in data){
		if("publicationtitle" in data.citation){
			$('#event-citation-publication-title').val(data.citation.publicationtitle).focusout();
		}
		if("publicationurl" in data.citation){
			$('#event-citation-publication-url').val(data.citation.publicationurl).focusout();
		}
		if("issuetitle" in data.citation){
			$('#event-citation-issue-title').val(data.citation.issuetitle).focusout(); 
		}
		if("issueurl" in data.citation){
			$('#event-citation-issue-url').val(data.citation.issueurl).focusout();
		}
		if("issuedate" in data.citation){
			if("day" in data.citation.issuedate){
				 $('#event-citation-issue-date-dd').val(data.citation.issuedate.day).focusout();
			}
			if("month" in data.citation.issuedate){
				 $('#event-citation-issue-date-mm').val(data.citation.issuedate.month).focusout();
			}
			if("year" in data.citation.issuedate){
				 $('#event-citation-issue-date-yy').val(data.citation.issuedate.year).focusout();
			}
			if(data.citation.issuedate.year){
				var hm = data.citation.issuedate.month ? parseInt(data.citation.issuedate.month) : 12;
				var hd = data.citation.issuedate.day ? parseInt(data.citation.issuedate.day) : 31;
				var hy = parseInt(data.citation.issuedate.year);
				var maxd = new Date(hy, hm-1, hd);
				$('#event-dmy-from-hidden').datepicker("option", "maxDate", maxd);
				$('#event-dmy-to-hidden').datepicker("option", "maxDate", maxd);
			}
		}
		if("sectiontitle" in data.citation){
			$('#event-citation-section-title').val(data.citation.sectiontitle).focusout(); 
		}
		if("sectionurl" in data.citation){
			$('#event-citation-section-url').val(data.citation.sectionurl).focusout(); 
		}
		if("articletitle" in data.citation){
			$('#event-citation-article-title').val(data.citation.articletitle).focusout(); 
		}
		if("articleurl" in data.citation){
			$('#event-citation-article-url').val(data.citation.articleurl).focusout(); 
		}
		if("articleid" in data.citation){
			$('#event-citation-article-id').val(data.citation.articleid).focusout(); 
		}
		if("articleimage" in data.citation){
			$('#event-citation-article-image-url').val(data.citation.articleimage).focusout(); 
		}
		if("articlepagefrom" in data.citation){
			$('#event-citation-article-pagesfrom').val(data.citation.articlepagefrom).focusout(); 
		}
		if("articlepageto" in data.citation){
			$('#event-citation-article-pagesto').val(data.citation.articlepageto).focusout(); 
		}
		$('#dc-citation-section-contents').show();		
	}
	else {
		 $('#dc-citation-section-contents').hide();		
	}

};

dacura_widget.removeTool = function(){
}

dacura_widget.clearTool = function(){
	this.clearInput();
	//$('.dc-default-value').removeClass('dc-default-value');

};


dacura_widget.clearInput = function(){
	$('#dc-details-section-contents').show();
	$('#dc-motivations-section-contents').show();
	$('#dc-description-section-contents').show();
	$('#dc-citation-section-contents').show();
	$('#dc-actors-section-contents').show();

	$('#event-datetime-from-dd').val("").focusout();
	$('#event-datetime-from-mm').val("").focusout();
	$('#event-datetime-from-yy').val("").focusout();
	$('#event-dmy-from-hidden').val("");
	$('#event-datetime-to-dd').val("").focusout();
	$('#event-datetime-to-mm').val("").focusout();
	$('#event-datetime-to-yy').val("").focusout();
	$('#event-dmy-to-hidden').val("");
	$('#event-datetime-precision').val("on").change();
	$('#event-location-country').val("").change();
	$('#event-location-irishcounty').val("");
	$('#event-location-place').val("").focusout();
	$('#event-description-input').val("");
	$('#event-category').val("").change();
	$('#event-fatalities-from').val("").focusout();
	$('#event-fatalities-to').val("").focusout();
	$('#event-fatalities-number').val("").focusout();
	for(var i = 1; i <= 4; i++){
		$('#actor-'+i+'-count-from').val("").focusout();
		$('#actor-'+i+'-count-to').val("").focusout();
		$('#actor-'+i+'-count-number').val("").focusout();
		$('#actor-'+i+'-fatalities-from').val("").focusout();
		$('#actor-'+i+'-fatalities-to').val("").focusout();
		$('#actor-'+i+'-fatalities-number').val("").focusout();
		$('#event-actor-type-'+i).val("").change();
	    $('#event-actor-groupname-'+i).val("").focusout();
	    $('#event-actor-represents-'+i).val("").focusout();
	    $('#event-actor-represents-groupname-'+i).val("").focusout();
		showActorFatalitiesNumber(i);	
		showActorCountNumber(i);		 			
	}
	$('#event-motivation input.dc-motivations-checkboxes:checked').each(function( index ){
		$(this).prop("checked", false).change();
	});
	$('#event-citation-publication-title').val("").focusout();  
	$('#event-citation-publication-url').val("").focusout();  
	$('#event-citation-issue-title').val("").focusout();  
	$('#event-citation-issue-url').val("").focusout();  

	
	$('#event-citation-section-title').val("").focusout(); 
	$('#event-citation-section-url').val("").focusout();  
	$('#event-citation-article-title').val("").focusout();  
	$('#event-citation-article-url').val("").focusout();  
	$('#event-citation-article-id').val("").focusout();  
	$('#event-citation-article-image-url').val("").focusout();  
	$('#event-citation-article-pagesfrom').val("").focusout();  
	$('#event-citation-article-pagesto').val("").focusout(); 
	$('#event-citation-issue-date-dd').val("").focusout();
	$('#event-citation-issue-date-mm').val("").focusout();
	$('#event-citation-issue-date-yy').val("").focusout();
};



//this should be moved to a place of its own
dacura_widget.loadToolFromContext = function(){
	if(this.debug) {
		alert("need to overwrite parse page");
	}
	return {};
};

dacura_widget.parsePage = function(){
	if(this.debug) {
		alert("need to overwrite parse page");
	}
	return {};
};


