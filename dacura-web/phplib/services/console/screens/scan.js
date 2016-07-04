var seshatscraper = {
	varnames: {},
	locators: {}
};

var page = $('#bodyContent').html();

seshatscraper.grabFacts = function(){
	this.originalpage = page;
	this.pagecontexts = this.calculatePageContexts(page);
	var regex = /(♠([^♠♥]*)♣([^♠♥]*)♥)([^♠]*)/gm;
	var i = 0;
	var facts = [];
	while(matches = regex.exec(page)){
		factParts = {
			"id": (i+1),
			"location": matches.index, 
			"full": matches[1],
			"length" : matches[1].length, 
			"varname": matches[2].trim(),
			"contents": matches[3].trim(),
			"notes": matches[4].trim().substring(4).trim()
		};
		factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
		if(factParts.notes.substring(factParts.notes.length - 3) == "<b>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 3) == "<p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 4) == "</p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 4).trim();
		}
		//factParts.notes = factParts.notes.substring(0, factParts.notes.length - 6);
		if(factParts.varname.length == 0){
			factParts.parsed = { "result_code" : "error", "result_message" : "Variable name is missing"}				
		}
		else {
			if(factParts.contents.length == 0){
				factParts.parsed = { "result_code" : "empty", "result_message" : "No value entered yet"}				
			}
			if(typeof this.varnames[factParts.varname] == "undefined"){
				this.varnames[factParts.varname] = {};
			}
			this.varnames[factParts.varname][factParts.id] = factParts;
		}
		var locator = this.getFactContextLocator(factParts);
		if(locator){
			locid = locator.id;
			var flocid = locid + factParts.varname;
			if(typeof this.locators[flocid] != "undefined"){
				locid += "#" + (++this.locators[flocid]);
			}
			else {
				this.locators[flocid] = 1;
			}
			factParts.pattern = locid;
		}
		else {
			factParts.pattern = "";
		}
		facts[i++] = factParts;
	}
	return facts;
}

seshatscraper.uniqifyFacts = function(){
	for(var i = 0; i< this.pageFacts.length; i++){
		var factoid = this.pageFacts[i];
		if(size(this.varnames[factoid.varname]) == 1){
			this.pageFacts[i].uniqid = factoid.varname;
		}
		else {
			this.pageFacts[i].uniqid = factoid.varname + "_" + "repeated"; 
			var seq = 1;
			for(var id in this.varnames[factoid.varname]){
				if(this.varnames[factoid.varname][id].id == factoid.id){
					this.pageFacts[i].uniqid = factoid.varname + "_" + seq;	
					break;
				}
				seq++;	
			}
		}
	}
}

seshatscraper.getFactContextLocator = function(factoid){
	//most recent header text....
	//locate the nearest header
	var hloc = 0;
	for(var loc in this.pagecontexts){
		if(loc < factoid.location && loc > hloc){
			hloc = loc;
		}
	}
	return this.pagecontexts[hloc];
}

seshatscraper.calculatePageContexts = function(page){
	var headerids = {};
	var pagecontexts = {};
	var pheaders = $(':header span.mw-headline');
	for(var j = 0; j< pheaders.length; j++){
		var hid = pheaders[j].id;
		if(hid.length){
			var htext = $(pheaders[j]).text();
			if(htext.substring(htext.length - 6) == "[edit]"){
				htext = htext.substring(htext.length - 6); 
			}
			var regexstr = "id\\s*=\\s*['\"]" + escapeRegExp(hid) + "['\"]";
			var re = new RegExp(regexstr, "gmi");
			var hids = 0;
			var hmatch;
			while(heads = re.exec(page)){
				hmatch = heads.index;
				hids++;
			}
			if(hids != 1){
				alert("failed to find unique header id for header " + hid + " " + hids);
			}
			else {
				pagecontexts[hmatch] = {id: hid, text: htext};
			}
		}
	}
	return pagecontexts;
}

seshatscraper.displayPageStats = function(stats){
	var html = "<div class='page-stats'><dl><dt class='seshatCorrect'>Variables</dt><dd class='seshatCorrect'>" + (stats.empty + stats.complex + stats.simple) + "</dd>" + 
		"<dt class='seshatEmpty'>Filled Correctly</dt><dd class='seshatEmpty'>" + (stats.complex + stats.simple)  + "</dd>" + 
		"<dt class='seshatError'>Problems</dt><dd class='seshatError'>" + (stats.error + stats.warning) + "</dd></dl></div>";
	dconsole.loadExtra(html);
}


seshatscraper.sendFactsToParser = function(){
	var pfacts = [];
	var fact_ids = [];
	if(this.pageFacts.length == 0){
		//return dconsole.writeResultMessage("error", "No Seshat facts found in the page", "Seshat facts are encoded as ♠ VAR ♠ VALUE ♥");	
	}
	for(i in this.pageFacts){
		if(typeof this.pageFacts[i].parsed != "object"){
			pfacts[pfacts.length] = this.pageFacts[i].contents;
			fact_ids[fact_ids.length] = i;			
		}
	}
	if(pfacts.length == 0){
		//whole page parsed already
		seshatscraper.displayFacts();
		return;
	}
	xhr = {};
	//xhr.xhrFields = {
	 // withCredentials: true
	//};
	xhr.data = { "data" : JSON.stringify(pfacts)};
    //xhr.dataType = "json";
    //xhr.data = JSON.stringify(pfacts);
	xhr.url = "http://localhost/dacura/rest/scraper/validate";
	xhr.type = "POST";
	xhr.beforeSend = function(){
		var msg = fact_ids.length + " Variables being analysed";
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				seshatscraper.pageFacts[fact_ids[i]].parsed = results[i];
			}
			seshatscraper.displayFacts();
		}
		catch(e){
			dconsole.writeResultMessage("error", "Failed to contact server to parse variables: " ,  e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dconsole.writeResultMessage("error", "Failed to contact server to parse variables: ",  jqXHR.responseText);
		alert(JSON.stringify(jqXHR));
	});
};

seshatscraper.displayFacts = function (){
	var stats = {"error": 0, "warning": 0, "complex": 0, "simple" : 0, "empty": 0};
	error_sequence = [];
	var json = this.pageFacts;
	var npage = "";
	var npage_offset = 0;
	for(var i = 0;i < json.length;i++){
		stats[json[i]["parsed"]["result_code"]]++;
		if(typeof json[i].parsed != "object" || json[i].parsed.result_code == "error" || json[i].parsed.result_code == "warning"){
			error_sequence[error_sequence.length] = json[i].id;
		}
		parsed = (typeof json[i].parsed == "object" ? json[i].parsed : {result_code: "error"});
		var cstr = "seshatFact";
		var imgstr = "";
		if(parsed.result_code == "error"){
			cstr += " seshatError";
			imgstr = "<img class='seshat_fact_img seshat_error' src='<?=$service->get_service_file_url('error.png')?>' alt='error' title='error parsing variable'> ";
		}
		else if(parsed.result_code == "warning"){
			cstr += " seshatWarning";
			imgstr = "<img class='seshat_fact_img seshat_warning' src='<?=$service->get_service_file_url('error.png')?>' alt='error' title='variable warning'> ";
		}
		else if(parsed.result_code == "empty"){
			cstr += " seshatEmpty";
			imgstr = "<img class='seshat_fact_img seshat_empty' src='<?=$service->get_service_file_url('empty.png')?>' alt='error' title='variable empty'> ";
		}
		else {
			cstr += " seshatCorrect";
			imgstr = "<img class='seshat_fact_img seshat_correct' src='<?=$service->get_service_file_url('correct.png')?>' alt='error' title='variable parsed correctly'> ";
		}

		var sd = "<div class='" + cstr + "' id='fact_" + json[i]["id"] + "'>" + imgstr + json[i]["full"] + "</div>";
		//now update the page....
		
		npage += this.originalpage.substring(npage_offset, json[i]["location"]) + sd;
		npage_offset = json[i]["location"] + json[i]["length"];
	}

	$('#bodyContent').html(npage + this.originalpage.substring(npage_offset));
	$('.seshatFact').click(function(){
		var fid = $(this).attr("id").substring(5);
		seshatscraper.loadFact(fid);
	});
	$('.seshatFact').hover(function(){
		$(this).addClass("seshatFactSelected");
	},function() {
		$( this ).removeClass( "seshatFactSelected" );
	});
	//write into the results pane..
	seshatscraper.error_ids = error_sequence;
};

seshatscraper.loadFact = function(fid){
	seshatscraper.displayPageStats(stats);
	jpr(this.pageFacts(fid));
} 

if(page){
	seshatscraper.pageFacts = seshatscraper.grabFacts();
	seshatscraper.uniqifyFacts();
	seshatscraper.displayFacts();
	//seshatscraper.sendFactsToParser();	
}
