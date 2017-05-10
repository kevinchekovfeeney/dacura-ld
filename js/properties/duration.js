function DurationViewer(config) {
	this.init(config);
}

DurationViewer.prototype.init = function(config){
	this.separator = (config && config.separator ? config.separator  : " - ");
}

DurationViewer.prototype.hasDisplay = function(mode){
	return mode == "view";
}

DurationViewer.prototype.display = function(frame, mode, onChange){
	var d = document.createElement("span");
	d.setAttribute("class", "dacura-duration-display");
	if(typeof frame.frame[firstKey(frame.frame)] == "object" && frame.frame[firstKey(frame.frame)].length){
		var fms = frame.frame[firstKey(frame.frame)];
		var eone = false, etwo = false;
		if(fms[0].rangeValue && fms[0].rangeValue.data){
			eone = fms[0].rangeValue.data
		}
		if(fms[1] && fms[1].rangeValue && fms[1].rangeValue.data){
			etwo = fms[1].rangeValue.data
		}
		if(eone === false && etwo === false){
			d.appendChild(document.createTextNode("empty duration"));
		}
		else {
			var yv = new YearViewer();
			if(eone === false){
				d.appendChild(document.createTextNode(yv.asString(etwo)));
			}
			else if(etwo === false){
				d.appendChild(document.createTextNode(yv.asString(eone)));				
			}
			else {
				if(parseInt(eone) > parseInt(etwo)){
					d.appendChild(document.createTextNode(yv.asString(etwo)));					
				}
				else {
					d.appendChild(document.createTextNode(yv.asString(eone)));					
				}
				if(parseInt(eone) != parseInt(etwo)){
					d.appendChild(document.createTextNode(this.separator));					
				}
				if(parseInt(eone) > parseInt(etwo)){
					d.appendChild(document.createTextNode(yv.asString(eone)));					
				}
				else if(parseInt(eone) != parseInt(etwo)){
					d.appendChild(document.createTextNode(yv.asString(etwo)));					
				}
			}
		}
	}
	return d;
}


DurationViewer.prototype.import = function(val, frame){
	//alert(val);
	vals = val.split("-");
	signs = [];
	if(vals.length == 2){
		for(var i = 0; i<2; i++){
			if(vals[i].indexOf("b") !== -1 || vals[i].indexOf("B") !== -1){
				signs[i] = "negative";
			}
			else if(vals[i].indexOf("c") !== -1 || vals[i].indexOf("C") !== -1){
				signs[i] = "positive";
			}
			else {
				signs[i] = "unknown";			
			}
		}
		if(signs[0] == "unknown") signs[0] = signs[1];
		if(signs[0] == "unknown") {
			signs[1] = "positive";
			signs[0] = "positive";
		}
		var x = parseInt(vals[0]);
		if(signs[0] == "negative" && x > 0){
			x = -x;
		}
		var y = parseInt(vals[1]);
		if(signs[1] == "negative" && y > 0){
			y = -y;
		}
		var yv1 = new YearViewer();
		var nvals = [yv1.formatYear(x)];
		nvals.push(yv1.formatYear(y));
		var dval = 0;
		var imported = {"rdf:type": frame.range};
		for(var k in frame.frame){
			for(var j = 0; (j<frame.frame[k].length && j<nvals.length); j++){
				var vstruct = { data: nvals[j], type: frame.frame[k][j].range};
				if(!imported[k]) imported[k] = [];
				imported[k].push(vstruct);
				frame.frame[k][j].rangeValue = vstruct;
				if(!dval){
					dval = frame.frame[k][j].domainValue;
				}
				else {
					frame.frame[k][j].domainValue = dval;
				}
			}
		}
		return imported;
	}
	else {
		alert("improper duration string for import");
	}
}


