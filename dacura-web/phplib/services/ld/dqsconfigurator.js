
function DQSConfigurator(saved, def, avs, mode, ucallback){
	this.updatecallback = ucallback;
	this.mode = typeof mode == "undefined" ? "view" : mode;
	this.def = [];
	if(typeof def == 'object'){
		for(var i in def){
			this.def.push(i);
		}
	}
	else {
		this.def = def;
	}
	this.dqs = avs;
	if(mode == "graph"){
		this.ldtype = "graph";
		this.mode = "view";
	}
	else {
		this.ldtype = "ontology";
	}
	this.saved = typeof saved == "object" ? saved : false;
	if(this.saved){
		this.original = this.saved;
		if(typeof this.saved == 'object'){
			this.current = $.extend(true, [], this.saved) 
		}
		else {
			this.current = this.saved;
		}
	}
	else {
		this.original = this.def;
		if(typeof this.def == 'object'){
			this.current = $.extend(true, [], this.def) 
		}
		else {
			this.current = this.def;
		}
	}
	this.isauto = this.saved ? false : true;
	if(this.isauto){
		this.original_isauto = true;
	}
	else {
		this.original_isauto = false;
	}
}

DQSConfigurator.prototype.testUpdate = function(){
	if(this.hasChanged()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, true);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes");
	}
}

DQSConfigurator.prototype.Update = function(){
	if(this.hasChanged()){
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


DQSConfigurator.prototype.hasChanged = function(){
	if(this.isauto != this.original_isauto) return true;
	if(typeof this.current != typeof this.original) return true;
	if(this.current == "all" && this.original == "all") return false;
	for(var i = 0; i < this.current.length; i++){
		if(this.original.indexOf(this.current[i]) == -1) return true;
	}
	for(var i = 0; i < this.original.length; i++){
		if(this.current.indexOf(this.original[i]) == -1) return true;
	}
	return false;
}

DQSConfigurator.prototype.setEditMode = function(){
	if(this.mode != "edit"){
		$('#enable-update-tests').prop("checked", true);
	    $('#enable-update-tests').button('option', 'label', 'Cancel Update');
		$('#enable-update-tests').button("refresh");			    
		this.mode = "edit";
		$('#tmaa').buttonset("enable");
		if(this.isauto){
			this.isauto = false;
			$('#tests-set-manual').prop("checked", true);
			$('#tmaa').buttonset("refresh");			
		}
		this.refresh();
	}
};

DQSConfigurator.prototype.setViewMode = function(){
	$('#enable-update-tests').prop("checked", false);
	if(this.mode == "edit"){
		$('#enable-update-tests').button('option', 'label', 'Update Configuration');
		$('#enable-update-tests').button("refresh");
		$('#tmaa').buttonset("disable");
		if(this.ldtype == "graph"){
			$('#graph-tests-update').show();			
		}
		this.mode = "view";
		this.refresh();
	}
};

DQSConfigurator.prototype.setAuto = function(){
	if(!this.isauto){
		this.isauto = true;
		if(typeof this.def == 'object'){
			this.current = $.extend(true, [], this.def) 
		}
		else {
			this.current = this.def;
		}
		this.refresh();
	}
};

DQSConfigurator.prototype.setManual = function(){
	if(this.isauto){
		this.isauto = false;
		this.refresh();
	}
};

DQSConfigurator.prototype.getEmptyHTML = function(){
	return "<div class='empty-dqs'>No Tests Selected</div>";
};

DQSConfigurator.prototype.getTestsSummary = function(){
	var html = "<span class='dqs-summary'>";
	if(this.current == 'all'){
		html += "all</span><span class='implicit-dqs'>";
		for(var i in this.dqs){
			html += dacura.ld.getDQSHTML(i, this.dqs[i], "view");
		}
	}
	else if(isEmpty(this.current)){
		html += this.getEmptyHTML();
	}
	else {
		for(var i = 0; i<this.current.length; i++){
			html += dacura.ld.getDQSHTML(this.current[i], this.dqs[this.current[i]], "view");	
		}
	}
	html += "</span>";
	return html;
};

DQSConfigurator.prototype.getChooseAllHTML = function(){
	var html = "<input type='radio' id='dqs-radio-all' name='dqsall' value='all'";
	if(this.current == "all"){
		html += " checked";
	}
	html += "><label for='dqs-radio-all'>All Tests</label>";
	html += "<input type='radio' id='dqs-radio-none' name='dqsall' value='none'";
	if(this.current.length == 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-none'>No Tests</label>";
	html += "<input type='radio' id='dqs-radio-notall' name='dqsall' value='notall' ";
	if(typeof this.current == 'object' && this.current.length > 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-notall'>Choose Tests</label>";
	return html;
};

DQSConfigurator.prototype.refresh = function(){
	var includes = [];
	var available = [];
	if(this.current == "all"){
		for(var i in this.dqs){
			includes.push(dacura.ld.getDQSHTML(i, this.dqs[i], "implicit"));
		}
	}
	else if(this.current.length == 0){
		var x = (this.mode == "edit" && !this.isauto ? "add": "tile");
		for(var i in this.dqs){
			available.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));
		}
	}
	else {
		for(var i in this.dqs){
			if(this.current.indexOf(i) == -1){
				var x = (this.mode == "edit" && !this.isauto ? "add": "tile");
				available.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));				
			}
			else {
				var x = (this.mode == "edit" && !this.isauto ? "remove": "tile");
				includes.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));				
			}
		}	
	}
	if(includes.length > 0){
		$('.dqs-includes').html(includes.join(" "));
	}
	else {
		$('.dqs-includes').html(this.getEmptyTestsHTML());
	}
	if(available.length > 0){
		$('.dqs-available').html(available.join(" "));
	}
	else {
		$('.dqs-available').html(this.getEmptyTestsHTML());
	}
	$('.dqs-all-config-element').html(this.getChooseAllHTML(this.mode));	
	var self = this;
	$('.dqs-all-config-element').buttonset().click(function(){
		if($('.dqs-all-config-element input:checked').val() == "all"){
			if(self.current != "all"){
				self.current = "all";
				self.refresh();
			}			
		}
		else if($('.dqs-all-config-element input:checked').val() == "none"){
			if(typeof self.current != "object" || self.current.length > 0){
				self.current = [];
				self.refresh();
			}					
		}
		else {
			if(typeof self.current != "object" && self.current == "all"){
				self.current = self.getAllTestsArray();
				self.refresh();
			}
		}	
			
	});
	if(this.mode == "view" || this.isauto){
		$('.dqs-all-config-element').buttonset("disable");
	}
	else {
		$('.dqstile-add').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.add(this.id.substring(8));
		});
		$('.remove-dqs').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.remove(this.id.substring(11));
		});
	}
	if(this.mode == "edit" && this.hasChanged()){
		$('#dqs-buttons').show();
		$('#graph-tests-update').hide();
	}
	else {
		$('#dqs-buttons').hide();		
		$('#graph-tests-update').show();
	}
};

DQSConfigurator.prototype.cancelUpdate = function(){
	if(typeof this.original == 'object'){
		this.current = $.extend(true, [], this.original); 
	}
	else {
		this.current = this.original;
	}
	this.isauto = this.original_isauto 
	this.setViewMode();
};

DQSConfigurator.prototype.add = function(id){
	if(typeof this.current == "object" && this.current.indexOf(id) == -1){
		this.current.push(id);
		this.refresh();
	}
};

DQSConfigurator.prototype.remove = function(id){
	if(typeof this.current == "object" && this.current.indexOf(id) != -1){
		this.current.splice(this.current.indexOf(id), 1);
		this.refresh();
	}
	else {
		alert("failed with " + id);
	}
};

DQSConfigurator.prototype.getEmptyTestsHTML = function(){
	var html = "<div class='empty-tests'>No DQS tests configured</div>";
	return html;
};

DQSConfigurator.prototype.getAllTestsArray = function(){
	var allt = [];
	for(var k in this.dqs){
		allt.push(k);
	}
	return allt;
};

DQSConfigurator.prototype.getButtonsHTML = function (){
	var html = "<div id='dqs-buttons' class='subscreen-buttons dch'>";
	html += "<button id='cancelupdatetests' class='dacura-update-cancel subscreen-button'>Cancel Changes</button>";		
	html += "<button id='testupdatetests' class='dacura-test-update subscreen-button'>Test DQS Configuration</button>";		
	html += "<button id='updatetests' class='dacura-update subscreen-button'>Save DQS Configuration</button>";
	html += "</div>";	
	if(this.ldtype == "graph"){
		html += "<span id='tmaa' class='manauto testsmanauto'>";
		html +=	"<input type='radio' name='tma' id='tests-set-manual' value='manual'><label for='tests-set-manual'>Manual</label>";
		html += "<input type='radio' name='tma' id='tests-set-automatic' value='automatic'><label for='tests-set-automatic'>Automatic</label>";
		html += "</span>";		
		html += "<div id='graph-tests-update' class='subscreen-buttons'>";
		html += "<input type='checkbox' class='enable-update tests-enable-update' id='enable-update-tests'>";
		html += "<label for='enable-update-tests'>Update Configuration</label>";
		html += "</div>";		
	}
	return html;
};


DQSConfigurator.prototype.draw = function(jq, mode, dontfill){
	if(typeof mode != "undefined"){
		this.mode = mode; 
	}
	var html = "<div class='dqsconfig'>";
	html += "<div class='dqs-all-config-element'></div>";
	html += "<div class='dqs-includes-title'>Currently Included Tests</div>";
	html += "<div class='dqs-includes'></div>";
	html += this.getButtonsHTML();
	html += "<div class='dqs-available-title'>Available Tests</div>";
	html += "<div class='dqs-available'></div>";
	html += "</div>";
	$(jq).html(html);
	var self = this;
	$('#cancelupdatetests').button().click( function(){
		self.cancelUpdate();
	});
	$('#testupdatetests').button().click( function(){
		self.testUpdate();
	});	
	$('#updatetests').button().click( function(){
		self.Update();
	});		
	if(this.isauto){
		$('#tests-set-automatic').prop("checked", true);
	}
	else {
		$('#tests-set-manual').prop("checked", true);
	}
	if(this.mode == 'edit'){
		$('#enable-update-tests').prop("checked", true);
	}
	else {
		$('#enable-update-tests').prop("checked", false);
	}
	this.initUpdateButton();
	if(typeof dontfill == "undefined"){	
		this.refresh();
	}
};

DQSConfigurator.prototype.initUpdateButton = function(){
	var txt = "Update Configuration";
	if(this.mode == 'edit'){
		txt = "Cancel Update";
		$('#enable-update-tests').prop("checked", true);
	}
	else {
		$('#enable-update-tests').prop("checked", false);
	}
	var self = this;
	$('#enable-update-tests').button({
		label: txt
	}).click(function(){
		if($('#enable-update-tests').is(':checked')){
			self.setEditMode();
		}
		else {
			self.setViewMode();		
		}
	});
	$('#tmaa').buttonset().click(function(){
		if($('input[name=tma]:checked').val() == "manual"){
			self.setManual();
		}
		else {
			self.setAuto();		
		}
	});	
	$('#tmaa').buttonset("disable");	
};


