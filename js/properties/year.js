function YearViewer(config) {
	if(config){
		this.init(config);
	}
	this.default_year = (config && config.default_year ? config.default_year : 0); 
}

YearViewer.prototype.init = function(config){
	this.default_year = (config.default_year ? config.default_year : 0); 
}

YearViewer.prototype.hasDisplay = function(mode){
	//alert(mode);
	return true;
	
}

YearViewer.prototype.intFromString = function(str){
	var x = parseInt(str);
	x = this.formatYear(x);
	//alert(x);
	if(str.indexOf("b") !== -1 || str.indexOf("B") !== -1){
		return "-" + x;
	}
	return x;
}


YearViewer.prototype.getHTMLInput = function(value, onblur, frame){
	var ta = document.createElement("span");
	var yip = document.createElement("input");
	yip.setAttribute('type', "text");
	yip.setAttribute("value", value);
	var sip = this.getSuffixInput();
	if(value > 0){
		yip.value = value;
		sip.value = "CE";
	}
	else if(value < 0) {
		yip.value = -value;
		sip.value = "BCE";
	}
	if(typeof frameContentsUpdateCheck == "function"){
    	yip.onblur = frameContentsUpdateCheck;
    	sip.onblur = frameContentsUpdateCheck;
    }	
	ta.appendChild(yip);
	ta.appendChild(sip);
	var self = this;
	ta.contents = function(val){
		if(typeof val != "undefined"){
			self.setValue(val, yip, sip);
		}
		return self.getValue(yip, sip);
	}
	yip.setAttribute('data-property', frame.property);        
	yip.setAttribute('data-class', frame.range);        
	sip.setAttribute('data-property', frame.property);        
	sip.setAttribute('data-class', frame.range);        
	return ta;
}

YearViewer.prototype.asString = function(data){
	var value = parseInt(data);
	var suffix = "";
	if(value > 0){
		suffix = "CE";
	}
	else if(value < 0) {
		suffix = "BCE";
		value = -value;
	}
	var txt = (value == 0 ? "Year 0" : value + " " + suffix);
	return txt;
}

YearViewer.prototype.display = function(frame, mode, frameContentsUpdateCheck, dontbind){
	if(frame.rangeValue && frame.rangeValue.data){
		var value = parseInt(elt.rangeValue.data);
	}
	else {
		var value = "";		
	}
	if(mode == "view"){
		var ta = document.createElement("span");
		var txt = this.asString(value);
		var value = document.createTextNode(txt);
		ta.appendChild(value);
	}
	else {
		var ta = document.createElement("span");
		var yip = document.createElement("input");
		yip.setAttribute('type', "text");
		yip.setAttribute("value", value);
		var sip = this.getSuffixInput(suffix);
		ta.appendChild(yip);
		ta.appendChild(sip);
		//yip.setAttribute('data-value', labelValue);
		if(typeof frameContentsUpdateCheck == "function"){
	    	yip.onblur = frameContentsUpdateCheck;
	    	sip.onblur = frameContentsUpdateCheck;
	    }
		this.bind(frame, "contents", yip, sip);
	}
	//input.setAttribute('data-value', labelValue);
	//input.appendChild(value);
	//var ta = document.createElement("textarea");
    return ta;
}

YearViewer.prototype.getSuffixInput = function(val){
	var selDiv = document.createElement("select");
	selDiv.setAttribute('class', "dacura-entityref-picker");        
	var opta = document.createElement("option");
	opta.value="CE";
	opta.innerHTML = "CE"; // whatever property it has
	selDiv.appendChild(opta);
	var optb = document.createElement("option");
	optb.innerHTML = "BCE"; // whatever property it has
	optb.value="BCE";
	selDiv.appendChild(optb);
	return selDiv;
}

YearViewer.prototype.setValue = function(val, yip, sip){
	if(val === ""){
		yip.value = val;
		sip.value = val;
	}
	else {
		var yr = parseInt(val);
		if(yr > 0){
			sip.value = "CE";
			yip.value = yr;
		}
		if(yr < 0){
			sip.value = "BCE";
			yip.value = -yr;
		}
	}
}

YearViewer.prototype.getValue = function(yip, sip){
	var yr = yip.value;
	if(yr === "") return "";
	else if(yr === 0) yr = "0000";
	else if(yr < 10) yr = "000" + yr;
	else if(yr < 100) yr = "00" + yr;
	else if(yr < 1000) yr = "0" + yr;
	if(sip.value == "BCE"){
		yr = "-"+yr;
	}
	else {
		yr = "" + yr;
	}
	return yr;
	
}
YearViewer.prototype.formatYear = function(yr){
	if(yr === "") return "";
	else if(yr < 0){
		if(yr < -999) return "" + yr;
		else if(yr < -99) yr = "-0" + -yr;
		else if(yr < -9) yr = "-00" + -yr;
		else yr = "-000" + -yr;
	}
	else if(yr === 0) yr = "0000";
	else if(yr < 10) yr = "000" + yr;
	else if(yr < 100) yr = "00" + yr;
	else if(yr < 1000) yr = "0" + yr;
	return yr;
	
}

YearViewer.prototype.bind = function(obj, prop, yip, sip){
	if(typeof obj[prop] == "undefined"){
	    Object.defineProperty(obj, prop, {
	    	get: function(){
	    		var yr = yip.value;
	    		if(yr == "") return "";
	    		if(yr < 10) yr = "000" + yr;
	    		else if(yr < 100) yr = "00" + yr;
	    		else if(yr < 1000) yr = "0" + yr;
	    		if(sip.value == "BCE"){
	    			yr = "-"+yr;
	    		}
	    		else {
	    			yr = "" + yr;
	    		}
	    		return yr;
	    	}, 
	    	set: function(newValue){elt.value = newValue;},
	    	configurable: true
	    });
	}
	else {
		//alert(prop + " is already defined as: " + obj[prop]);
	}
}

