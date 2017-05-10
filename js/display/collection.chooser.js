DacuraChooser = function(elts, cls, onChange, pickonetext, picked){
	this.elements = elts;
	this.cls = cls;
	this.onChange = onChange;
	this.picked = (picked ? picked : "");
	this.pickonetext = pickonetext;
	this.init();
}

DacuraChooser.prototype.init = function(){
	var self = this;
	this.select2config = {
		  placeholder: this.pickonetext, 
		  allowClear: true,
		  minimumResultsForSearch: 10,
		  templateResult: function(state){
			  if (!state.id) { return state.text; }
			  if (typeof self.elements[state.id].icon == "string"){
				  return jQuery("<span><img class='dacura-chooser-icon' src='" + self.elements[state.id].icon + "'> " + self.elements[state.id].title + "</span>");				  
			  }
			  return self.elements[state.id].title;
		  }
	}	
}

DacuraChooser.prototype.getAsExtendedDOM = function(){
	var sdom = document.createElement("div");
	sdom.setAttribute("class", "dacura-chooser " + this.cls);
	
	var pholderdom = document.createElement("span");
	pholderdom.setAttribute("class", "dacura-picked " + this.cls);
	
	var ispan = document.createElement("span");
	ispan.setAttribute("class", "chooser-title-icon");
	var icon = document.createElement("img");
	if(this.picked && typeof this.elements[this.picked].icon == "string"){
		icon.setAttribute("src", this.elements[this.picked].icon);
	}
	ispan.appendChild(icon);
	
	var tspan = document.createElement("span");
	tspan.setAttribute("class", "chooser-title-text");
	if(this.picked && typeof this.elements[this.picked].title == "string"){
		var tnode = document.createTextNode(this.elements[this.picked].title);
		tspan.appendChild(tnode);
	}
	var cspan = document.createElement("span");
	cspan.setAttribute("class", "chooser-title-changer");
	cspan.setAttribute("title", "Change selection");
	var icd = document.createElement("span");
	icd.setAttribute("class", "chooser-changer-icon fa fa-angle-down fa");	
	icd.addEventListener("click", function(){
		jQuery(pholderdom).hide();
		jQuery(sholderdom).show();
	});
	cspan.appendChild(icd);
	pholderdom.appendChild(ispan);
	pholderdom.appendChild(tspan);
	pholderdom.appendChild(cspan);
	
	var sholderdom = document.createElement("span");
	sholderdom.setAttribute("class", "dacura-picker-holder " + this.cls);
	var seldom = this.getAsDOM();
	sholderdom.appendChild(seldom);
	sdom.appendChild(sholderdom);
	sdom.appendChild(pholderdom);
	
	if(this.picked){
		jQuery(sholderdom).hide();
	}
	else {
		jQuery(pholderdom).hide();		
	}
	if(size(this.elements) == 1){
		jQuery(cspan).hide();		
	}
	var onc = this.onChange;
	var self = this;
	this.onChange = function(val){
		self.picked = val;
		if(self.picked){
			jQuery(tspan).html(self.elements[self.picked].title);					
			if(typeof self.elements[self.picked].icon){
				icon.setAttribute("src", self.elements[self.picked].icon);
			}
			jQuery(sholderdom).hide();
			jQuery(pholderdom).show();		
		}
	}
	return sdom;
}

DacuraChooser.prototype.getAsDOM = function(){
	var selDiv = document.createElement("select");
	selDiv.setAttribute("class", "dacura-picker");
	var option = document.createElement("option");
	option.text = this.pickonetext;
	option.value = "";
	selDiv.appendChild(option);
	var i = 0; var index = 0;
	for(var eltid in this.elements){
		if(eltid == this.picked){
			index = i;
		}
		var noption = document.createElement("option");
		noption.text = this.elements[eltid].title;
		noption.value = eltid;
		selDiv.appendChild(noption);
		i++;
	}
	selDiv.selectedIndex = i;
	jQuery(selDiv).select2(this.select2config).on('change.select2', function(){
		onChange(this.value);
	});	
}


DacuraCollectionChooser = function(cols, picked, onChange){
	this.elements = cols;
	this.cls = "dacura-collection-chooser";
	this.onChange = onChange;
	this.picked = picked;
	this.pickonetext = "Select a collection";
	this.init();
}

DacuraCollectionChooser.prototype = DacuraChooser.prototype;


DacuraClassChooser = function(){}
DacuraPropertyChooser = function(){}
DacuraInstanceChooser = function(){}
DacuraServiceChooser = function(){}
