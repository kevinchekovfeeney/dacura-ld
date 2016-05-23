function OntologyImporter(orig, available, autos, mode, ucallback){
	this.available_imports = available;
	this.automatic_imports = autos;
	this.updatecallback = ucallback;
	if(mode != "create" && (typeof orig != "object" || this.importListsMatch(orig, autos))){
		this.orig = autos;
		this.isauto = true; 
		this.orig_isauto = true;
	}
	else {
		this.orig = orig;
		this.orig_isauto = false;
		this.isauto = false;
	}
	this.current = $.extend(true, {}, this.orig); 
	this.mode = typeof mode == "undefined" ? "view" : mode;
	if(this.mode == "graph"){
		this.mode = 'edit';
		this.ldtype = 'graph';
		this.isauto = false;
		this.orig_isauto = false;		
	}
	else {
		this.ldtype = "ontology";
	}
	this.show_buttons = true;
}

OntologyImporter.prototype.setImports = function(explicit, automatic){
	var changed = false;
	if(!this.importListsMatch(this.automatic_imports, automatic)){
		this.automatic_imports = automatic;
		changed = true;
	}
	if(!this.importListsMatch(this.current, explicit)){
		this.current = explicit;
		changed = true;
	}
	if(changed){
		this.refresh();
	}
	return changed;
}

OntologyImporter.prototype.setManual = function(){
	if(this.isauto){
		this.isauto = false;
		this.registerImportEvent();
		this.refresh();
	}
}

OntologyImporter.prototype.setAuto = function(){
	if(!this.isauto){
		this.isauto = true;
		this.registerImportEvent();
		this.current = $.extend(true, {}, this.automatic_imports ) 
		this.refresh();
	}
}

OntologyImporter.prototype.changeOntologyVersion = function(ontid, newv){
	this.current[ontid].version = newv;
	this.registerImportEvent();	
}


OntologyImporter.prototype.testUpdate = function(){
	if(this.hasStateChange()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, true);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes - test");
	}
}

OntologyImporter.prototype.Update = function(){
	if(this.hasStateChange()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, false);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes");
	}
}


OntologyImporter.prototype.hasStateChange = function(){
	if(this.isauto != this.orig_isauto) return true;
	return !this.importListsMatch(this.orig, this.current);
}

OntologyImporter.prototype.registerImportEvent = function(){
	if(this.hasStateChange()){
		$('#update-imports').show();
		$('#enable-update-imports').hide();
		if(this.ldtype == "graph"){
			$('#graph-update').hide();
		}
	}
	else {
		$('#update-imports').hide();
		$('#enable-update-imports').show();
	}
}

OntologyImporter.prototype.importListsMatch = function(a, b){
	for(var k in a){
		if(typeof b[k] != "object"){
			return false;
		}
		for(var j in a[k]){
			if(typeof b[k][j] == "undefined" || b[k][j] != a[k][j]){
				return false;
			}
		} 
	}
	for(var k in b){
		if(typeof a[k] != "object"){
			return false;
		}
		for(var j in b[k]){
			if(typeof a[k][j] == "undefined"){
				return false;
			}
		} 
	}
	return true;	
}

OntologyImporter.prototype.reset = function(){
	if(this.hasStateChange()){
		this.current = $.extend(true, {}, this.orig); 
		this.is_auto = this.orig_isauto;
		this.mode = "edit";
		this.setViewMode();
	}
	else if(this.mode != "view"){
		this.setViewMode();
	}
}

OntologyImporter.prototype.refresh = function(){
	$('.manual-imports').html("");
	$('.auto-imports').html("");
	$('.system-ontologies-contents').html("");
	$('.local-ontologies-contents').html("");
	if(size(this.current) > 0){
		for(var k in this.current){
			var tit = this.getOntHTMLTitle(k, this.current[k].version);
			if(typeof this.available_imports[k] != "undefined"){
				if(this.mode == "edit"){
					$('.manual-imports').append(dacura.ld.getOntologySelectHTML(k, tit, this.current[k].version, this.available_imports[k].version));
				}
				else {
					$('.manual-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.current[k].version));			
				} 						
			}
			else {
				alert(k + " ontology is imported but unknown");
				$('.manual-imports').append(dacura.ld.getOntologySelectHTML(k, tit, this.current[k].version)); 				
			}
		}
	}
	else {
		$('.manual-imports').html(this.getEmptyImportsHTML());	
	}
	if(size(this.automatic_imports) > 0){
		for(var k in this.automatic_imports){
			var tit = this.getOntHTMLTitle(k, this.automatic_imports[k].version);
			if(typeof this.available_imports[k] != "undefined"){
				$('.auto-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.available_imports[k].version)); 						
			}		
			else {
				alert(k + " ontology is imported but unknown");
				$('.auto-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.available_imports[k].version)); 				
			}
		}
	}
	else {
		$('.auto-imports').html(this.getEmptyImportsHTML());
	}
	var lonts = {};
	var sonts = {};
	for(var k in this.available_imports){
		if(typeof this.current[k] == "undefined"){
			if(this.available_imports[k].collection == "all"){
				sonts[k] = this.available_imports[k];
			}
			else {
				lonts[k] = this.available_imports[k];
			}
		}		
	}
	for(var k in lonts){
		if(this.mode == 'edit' && !this.isauto){
			$('.local-ontologies-contents').append(dacura.ld.getOntologySelectHTML(k, this.getOntHTMLTitle(k)));
		}
		else {
			$('.local-ontologies-contents').append(dacura.ld.getOntologyViewHTML(k, this.getOntHTMLTitle(k), null, lonts[k].version));
		}
	}
	for(var k in sonts){
		if(this.mode == 'edit' && !this.isauto){
			$('.system-ontologies-contents').append(dacura.ld.getOntologySelectHTML(k, this.getOntHTMLTitle(k)));
		}
		else {
			$('.system-ontologies-contents').append(dacura.ld.getOntologyViewHTML(k, this.getOntHTMLTitle(k), null, sonts[k].version));
		}
	}
	if(size(lonts) > 0 && this.mode == "edit"){
		$('.local-ontologies').show();	
	}
	else {
		$('.local-ontologies').hide();
	}
	if(this.mode != "edit"){
		$('#update-imports').hide();
	}
	else if(this.hasStateChange()){
		$('#update-imports').show();
	}
	if(size(sonts) > 0 && this.mode == "edit"){
		$('.system-ontologies').show();		
	}
	else {
		$('.system-ontologies').hide();
	}
	this.setImportsVisibility();
	var self = this;
	if(!this.isauto && this.mode == "edit"){
		$('span.remove-ont').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		});
		$('span.ontlabeladd').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		});
		$('span.ontlabeladd').click(function(){
			self.add(this.id.substring(13));
		});
		$('span.remove-ont').click(function(){
			self.remove(this.id.substring(16));
		});
		$('select.imported_ontology_version').selectmenu({
			width: 100,
			change: function( event, ui ) {
				self.changeOntologyVersion(this.id.substring(26), this.value);
			}
		});
	}
}

OntologyImporter.prototype.setImportsVisibility = function(){
	if(this.isauto){
		$('.auto-imports').show();
		$('.auto-imports-header').show();
		$('.manual-imports').hide();
		$('.manual-imports-header').hide();
	}
	else if(this.ldtype == "graph"){
		if(size(this.automatic_imports) > 0){
			$('.auto-imports').show();
			$('.auto-imports-header').show();
		}
		else {
			$('.auto-imports-header').hide();
			$('.auto-imports').hide();		
		}
		$('.manual-imports').show();
		$('.manual-imports-header').show();
		//$('#update-imports').show();		
	}
	else {
		$('.auto-imports-header').hide();
		$('.manual-imports-header').show();
		$('.manual-imports').show();
		$('.auto-imports').hide();	
	}
}

OntologyImporter.prototype.getEmptyImportsHTML = function(){
	var html = "<div class='empty-imports'>No ontologies imported</div>";
	return html;
}

OntologyImporter.prototype.setViewMode = function(){
	$('#enable-update-imports').prop("checked", false);
	if(this.mode != "view"){
		$('#enable-update-imports').button('option', 'label', 'Update Configuration');
		$('#enable-update-imports').button("refresh");		
		if(this.ldtype == "ontology"){
			$('#imaa').buttonset("disable");
		}
		else {
			$('#graph-update').show();			
		}
		this.mode = "view";
		this.refresh();
	}
}

OntologyImporter.prototype.setEditMode = function(){
	$('#enable-update-imports').prop("checked", true);
	if(this.mode != "edit"){
	    $('#enable-update-imports').button('option', 'label', 'Cancel Update');
		$('#enable-update-imports').button("refresh");			    
		if(this.ldtype != "graph"){
			$('#imaa').buttonset("enable");
		}
		this.mode = "edit";
		if(this.isauto){
			this.isauto = false;
			$('#imports-set-manual').prop("checked", true);
			$('#imaa').buttonset("refresh");			
		}		
		this.refresh();
	}
}

OntologyImporter.prototype.getButtonsHTML = function(){
	var html = "<div id='update-imports' class='subscreen-buttons dch'>";
	html += "<button id='cancelupdateimport' class='dacura-update-cancel subscreen-button'>Cancel Changes</button>";		
	html += "<button id='testupdateimport' class='dacura-test-update subscreen-button'>Test New Import Configuration</button>";		
	html += "<button id='updateimport' class='dacura-update subscreen-button'>Save New Import Configuration</button>";
	html += "</div>";	
	if(this.ldtype == "graph"){
		html += "<div id='graph-update' class='subscreen-buttons'>";
		html += "<input type='checkbox' class='enable-update imports-enable-update' id='enable-update-imports'>";
		html += "<label for='enable-update-imports'>Update Configuration</label>";
		html += "</div>";
	}
	return html;
}

OntologyImporter.prototype.draw = function(target){
	var html = "<div class='manual-imports-header'>Imported Ontologies (selected by user)</div>";
	html += "<div class='manual-imports'></div>";
	html += "<div class='auto-imports-header'>Imported Ontologies (automatically calculated by Dacura)</div>";
	html += "<div class='auto-imports'></div>";
	if(this.show_buttons){
		html += this.getButtonsHTML();
	}
	html += "<div class='local-ontologies'>";
	html += "<div class='local-ontologies-header'>Available Local Ontologies</div>";
	html += "<div class='local-ontologies-contents'>";
	html += "</div></div>";
	html += "<div class='system-ontologies'>";
	html += "<div class='system-ontologies-header'>Available System Ontologies</div>";
	html += "<div class='system-ontologies-contents'>";
	html += "</div></div>";
	html += "</div>";
	$(target).html(html);
	var self = this;
	$('#cancelupdateimport').button().click( function(){
		self.cancelUpdate();
	});
	$('#testupdateimport').button().click( function(){
		self.testUpdate();
	});	
	$('#updateimport').button().click( function(){
		self.Update();
	});	
	if(this.ldtype == "ontology"){	
		if(this.isauto){
			$('#imports-set-automatic').prop("checked", true);
		}
		else {
			$('#imports-set-manual').prop("checked", true);
		}
	}
	this.initUpdateButton();
	this.refresh();	
}

OntologyImporter.prototype.initUpdateButton = function(){
	var txt = "Update Configuration";
	if(this.mode == 'edit'){
		txt = "Cancel Update";
		$('#enable-update-imports').prop("checked", true);
	}
	else {
		$('#enable-update-imports').prop("checked", false);
	}
	var self = this;
	$('#enable-update-imports').button({
		label: txt
	}).click(function(){
		if($('#enable-update-imports').is(':checked')){
			self.setEditMode();
		}
		else {
			self.setViewMode();		
		}
	});
}


OntologyImporter.prototype.cancelUpdate = function(){
	this.current = $.extend(true, {}, this.orig); 
	this.setViewMode();
	$('#imports-set-manual').prop("checked", true);
	
}

OntologyImporter.prototype.remove = function(ontid){
	//this.current.splice(ontid, 1);
	delete(this.current[ontid]);
	if(isEmpty(this.current)){
		$('.manual-imports').html(this.getEmptyImportsHTML());		
	}
	this.registerImportEvent();	
	$("#imported_ontology_" + ontid).remove();
	
	if(typeof this.available_imports[ontid] != "undefined"){
		var avont = this.available_imports[ontid];
		if(avont.collection == "all"){
			$('div.system-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, this.getOntHTMLTitle(ontid)));
			$('div.system-ontologies').show();
		}
		else {
			$('div.local-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, this.getOntHTMLTitle(ontid)));		
			$('div.local-ontologies').show();
		}
		var self = this;
		$("#add_ontology_" + ontid).hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.add(this.id.substring(13));
		});
	}	
}


OntologyImporter.prototype.add = function(ontid){
	var avont = this.available_imports[ontid];
	if(isEmpty(this.current)){
		$('.manual-imports').html("");	
		$('.auto-imports').html("");	
	}
	this.current[ontid] = {id: ontid, version: 0, collection: avont.collection};
	this.registerImportEvent();
	var tit = this.getOntHTMLTitle(ontid, 0);
	$("div.manual-imports").append(dacura.ld.getOntologySelectHTML(ontid, tit, 0, avont.version));
	var self = this;
	$("#remove_ontology_" + ontid).hover(function(){
		$(this).addClass('uhover');
	}, function() {
	    $(this).removeClass('uhover');
	}).click(function(){
		self.remove(this.id.substring(16));
	});
	$('#imported_ontology_version_'+ontid).selectmenu({
		width: 100,
		change: function( event, ui ) {
			self.changeOntologyVersion(ontid, this.value);
		}
	});
	$("#add_ontology_" + ontid).remove();
	if(!$("div.system-ontologies-contents span").length){
		$("div.system-ontologies").hide();
	}
	if(!$("div.local-ontologies-contents span").length){
		$("div.local-ontologies").hide();
	}
}

OntologyImporter.prototype.getOntHTMLTitle = function(ontid, overridev){
	var ontv = this.available_imports[ontid];
	if(typeof ontv == "undefined"){
		alert("unidentified ontology " + ontid);
		return "Unknown ontology " + ontid;
	}
	var tit = ontv.id;
	if(typeof ontv.title == "string" && ontv.title){
		tit += ": " + ontv.title;
	}
	var html = "<div class='tooltiph'>" + tit + "</div>";
	var v = typeof overridev == "undefined" ? ontv.version : overridev;
	
	html += "<span class='oht'>" + (v == 0 ? "Latest Version (" + ontv.version + ")" : "Version: " + v) + "</span>"; 
	html += " <span class='oht'>Collection: " + ontv.collection + "</span>"; 
	html += " <span class='oht'>URL: " + ontv.url + "</span>"; 
	return escapeHtml(html);
}

function importToURL(conf){
	var url = dacura.system.install_url;
	url += (conf.collection == "all" ? "" : conf.collection + "/");
	url += "ontology/" + conf.id;
	if(conf.version > 0){
		url += "?version=" + conf.version;
	}
	return url;
}

function urlToImport(url){
	var imp = {};
	if(url.substring(0, dacura.system.install_url.length) != dacura.system.install_url){
		return false;
	}
	var meat = url.substring(dacura.system.install_url.length);
	if(meat.substring(0,8) == "ontology"){
		imp.collection = "all";
	}
	else {
		imp.collection = meat.substring(0, meat.indexOf("/") + 1);
	}
	meat = meat.substring(meat.lastIndexOf("/")+1);
	var bits = meat.split("?version=");
	imp.id = bits[0];
	if(bits.length == 2){
		imp.version = bits[1];
	}
	else {
		imp.version = 0;
	}
	return imp;
}

