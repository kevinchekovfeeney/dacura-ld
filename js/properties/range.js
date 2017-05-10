function RangeViewer(config) {
	if(config){	
		this.init(config);
	}
}

RangeViewer.prototype.init = function(config){
	this.type = config.type;
}

RangeViewer.prototype.hasDisplay = function(mode){
	return true;
}

RangeViewer.prototype.import = function(val, frame){
	var vals = this.parseValue(val, "-");
	if(this.type == "Year"){
		var yv = new YearViewer();
		var nval = yv.intFromString(vals[0]);
		if(vals[1]){
			var fdata = "[" + nval + "," + yv.intFromString(vals[1]) + "]";
		}
		else {
			fdata = nval;
		}
	}
	else {
		if(vals.length == 2){
			var fdata = "[" + vals[0] + "," + vals[1]+"]";
		}
		else {
			var fdata = vals[0];
		}
	}
	var vstruct = { data: fdata, type: frame.range};
	frame.rangeValue = vstruct;
	return frame.rangeValue;
}




RangeViewer.prototype.getIP = function(data, onChange, frame){
	if(this.type == "Integer" || this.type == "Decimal"){
		var firstip = document.createElement("input");
		firstip.setAttribute('type', "text");
		if(data){
			firstip.value = data;
		}
		firstip.onblur = onChange;
		firstip.setAttribute('data-property', frame.property);        
		firstip.setAttribute('data-class', frame.range);        

	}
	if(this.type == "Year"){
		var yv = new YearViewer();
		var x = parseInt(data);
		var idata = (x ? x : "");
		var firstip = yv.getHTMLInput(idata, onChange, frame);
	}
	return firstip;
}

RangeViewer.prototype.parseValue = function(val, dividor){
	dividor = (dividor ? dividor : ",");
	vals = [];
	if(typeof val == "object" && val.length){
		vals = val;
	}
	else {
		if(typeof val != "string"){
			val = "" + val;
		}
		if(val.length && (val.charAt(0) == "[") && val.charAt(val.length -1) == "]"){
			vals.push(val.substring(1, val.indexOf(dividor)));
			vals.push(val.substring(val.indexOf(dividor) + 1, val.length -1));
		}
		else {
			vals.push(val);
		}
	}
	return vals;
}

RangeViewer.prototype.valueToString = function(val){
	if(this.type == "Year"){
		var yv = new YearViewer();
		return yv.asString(val);
	}
	else if(this.type == "Integer" || this.type == "Decimal"){
		if(isNumber(val)){
			val = numberWithCommas(val);
		}
		return val;
	}
}


RangeViewer.prototype.display = function(frame, mode, onChange){
	var showing = false;
	if(frame.rangeValue && frame.rangeValue.data){
		var vals = this.parseValue(frame.rangeValue.data);
	}
	else {
		var vals =[];
	}
	var d = document.createElement("span");
	var rvals = document.createElement("span");
	var svals = document.createElement("span");
	if(mode == "view"){
		var tnode = document.createTextNode(this.valueToString(vals[0]));
		rvals.appendChild(tnode);
		d.appendChild(rvals);	
		if(vals.length == 2){
			msg = "The exact value is uncertain, it lies somewhere within this range"
			attachRangeIcon(rvals, msg);
			var t2node = document.createTextNode(this.valueToString(vals[1]));
			svals.appendChild(t2node);
			d.appendChild(svals);	
		}
		return d;
	}
	
	var data1 = (vals.length > 0 ? vals[0] : "");
	var data2 = (vals.length > 1 ? vals[1] : "");
	
	var firstip = this.getIP(data1, onChange, frame);
	var secondip = this.getIP(data2, onChange, frame);

	var self = this;
	var showRange = function(){
		if(showing){
			showing = false;
			if(self.type != "Year"){
				secondip.value = "";				
			}
			else {
				secondip.contents("");
			}
			rvals.removeChild(svals);
		}
		else {
			showing = true;
			rvals.appendChild(svals);			
		}
	}
	svals.appendChild(secondip);
	//attachRangeIcon(d, msg, showRange);
	rvals.appendChild(firstip);
	if(showing){
		msg = "Click to input a definite value rather than an uncertain range";
	}
	else {
		msg = "If the exact value is uncertain, click to input the value as a range"
	}
	attachRangeIcon(rvals, msg, showRange);
	if(vals.length > 1){
		showRange();
	}
    this.bind(frame, "contents", firstip, secondip);
	d.appendChild(rvals);
	//if(elt.rangeValue && elt.rangeValue.data){
	//	firstip.value = elt.rangeValue.data;
	//}
	
	//attachRangeIcon(d, msg, showRange);
    return d;
}

RangeViewer.prototype.bind = function(obj, prop, ip1, ip2){
	var self = this;
    //if(typeof obj[prop] == "undefined"){
	Object.defineProperty(obj, prop, {
    	get: function(){
    		if(self.type != "Year"){
	    		if(ip2.value && ip1.value){
	    			return "[" + ip1.value + "," + ip2.value + "]"; 
	    		}
	    		else if(ip1.value){
	    			return ip1.value;
	    		}
	    		return ip2.value;
    		}
    		else {
    			var oval = ip1.contents()
    			var tval = ip2.contents();
    			if(oval && tval){
    				return "[" + oval + "," + tval + "]"; 
    			}
    			else if(oval){
    				return oval;
    			}
    			else {
    				return tval;
    			}
    		}
    	}, 
    	set: function(newValue){ip1.value = newValue;},
    	configurable: true
    });
	//}
	//else {
		//alert(prop + " is already defined as: " + obj[prop]);
	//}
}


function attachRangeIcon(rDiv, msg, func){
	attachIcon(rDiv, "range-value", "fa-ellipsis-h", msg, func);
}

