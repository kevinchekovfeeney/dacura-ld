function TextareaViewer(config) {
	this.init(config);
}

TextareaViewer.prototype.init = function(config){
	this.width = ((config && config.width) ? config.width : '80%'); 
	this.height = ((config && config.height ) ? config.height : '40px'); 
}

TextareaViewer.prototype.hasDisplay = function(mode){
	return true;
}

function htmlDecode(input){
	 var e = document.createElement('span');
	 e.innerHTML = input;
	 return e.childNodes.length === 0 ? "" : e.childNodes;
}

TextareaViewer.prototype.display = function(elt, mode, frameContentsUpdateCheck){
	if(mode == "view"){
		var ta = document.createElement("span");
		var doms = htmlDecode(elt.rangeValue.data);
		while(doms.length){
			ta.appendChild(doms[0]);
		}
		return ta;
	}
	var ta = document.createElement("textarea");
	ta.setAttribute('class', "dacura-big-input");        
	if(elt.rangeValue && elt.rangeValue.data){
		ta.value = elt.rangeValue.data;
	}
    this.bind(elt, "contents", ta);
    if(typeof frameContentsUpdateCheck == "function"){
    	ta.onblur = frameContentsUpdateCheck;
    }
	ta.setAttribute('data-property', elt.property);        
	ta.setAttribute('data-class', elt.range);        
    return ta;
}

TextareaViewer.prototype.bind = function(obj, prop, elt){
	if(typeof obj[prop] == "undefined"){
	    Object.defineProperty(obj, prop, {
	    	get: function(){return elt.value;}, 
	    	set: function(newValue){elt.value = newValue;},
	    	configurable: true
	    });
	}

}
