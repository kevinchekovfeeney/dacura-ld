function dacura_widget(){
	this.current_record = null;
	this.ajax_url = "http://tcdfame.cs.tcd.ie/dacura/ajaxapi.php";
	this.ajax_user = "rob";
	this.ajax_pass = "rob";
	
}



dacura_widget.prototype.drawEmpty = function(woptions){
	var self = this;
	$.post(this.ajax_url, { action: "get_widget", options: JSON.stringify(woptions)})
	.done(function(data) {
		$('body').append(data);
		self.drawInputOptions();
	});	
}





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
	
}


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
}

dacura_widget.prototype.checkNewRecord = function(){
	alert($('#event-motivation').val());
	
}


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
}

dacura_widget.prototype.getRecordFromServer = function(xid){
	var self = this;
	$.post(this.ajax_url, { id: xid, action: "get_record"})
	.done(function(data) {
		var bits = JSON.parse(data);
		self.populateWidgetFromRecord(xid, bits);
	    $('.dump_result').html(prettyPrint(bits)) ;
	});
}
dacura_widget.prototype.populateCategory = function(id, details){
	$('#event-category').val(details.label);
}

dacura_widget.prototype.populateMotivation = function(mot){	
	for (var key in mot.values){
		$('#event-motivation-' + mot.values[key].label).prop("selected", true);
	}	
}

dacura_widget.prototype.populateSource = function (src){
	for (var key in src.values){
		$('#event-source').val(src.values[key].unstructured);		
	}	
}

dacura_widget.prototype.populateDescription = function (desc){
	for (var key in desc.values){
		$('#event-description').val(desc.values[key]);		
	}	

	
}
dacura_widget.prototype.populateLocation = function (loc){
	for (var key in loc.values){
		$('#event-location').val(loc.values[key].unstructured);		
	}	
}

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
}

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
}
