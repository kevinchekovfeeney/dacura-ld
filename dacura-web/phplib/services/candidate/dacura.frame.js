
dacura.frame = {};
dacura.frame.api = {};
dacura.frame.apiurl = dacura.system.apiURL();
	
dacura.frame.api.getFrame = function (cls){
	var xhr = {};
	xhr.type = "POST";
	xhr.url = dacura.frame.apiurl + "/frame";
	xhr.data = {'class' : cls};
	xhr.dataType = "json";
	return xhr;
};


dacura.frame.draw = function(resultobj, target){
	var framestr = resultobj.result;
	var frame = JSON.parse(framestr);
	var obj = document.createElement("div");
	
	gensym = dacura.frame.Gensym("query");
	res = dacura.frame.frameGenerator(frame, obj, gensym);
	
	var elt = document.getElementById(target);
	elt.appendChild(res);
	
	dacura.frame.initInteractors();
	
};

dacura.frame.initInteractors = function (){
	$('.queryInteractor').
		
}

dacura.frame.Gensym = function(pref){
	var prefix = pref;
	var count = 0;
	return {
		next: function(){
			var result = prefix + count;
			count += 1;
			return result;
		}
	};
};

dacura.frame.frameGenerator = function(frame, obj, gensym){

	/* list of frame options */ 
	if(frame.constructor == Array){
		// We are a property 
		for (var i = 0 ; i < frame.length ; i++){
			var property_elt = frame[i];
			
			var propdiv = document.createElement("div");
			// propdiv.setAttribute('style', 'display: inline-block;');
			var labelnode = document.createElement("label");
			
			if (property_elt.label){
				var label = property_elt.label.data;
				var textnode = document.createTextNode(label + ':');
			}else{
				var textnode = document.createTextNode(property_elt.property + ':');			
			}
			labelnode.appendChild(textnode);
			propdiv.appendChild(labelnode);
			
			if( property_elt.type == 'objectProperty' ){
				var subframe = property_elt.frame;
				var framediv = document.createElement("div");
				framediv.setAttribute('style', 'padding-left: 5px; display: inline-block;');

				dacura.frame.frameGenerator(subframe, framediv, gensym);
				labelnode.appendChild(framediv);
			}else if(property_elt.type == 'datatypeProperty'){
				var input = document.createElement("input");
				var ty = dacura.frame.typeConvert(property_elt.range);
				input.setAttribute('type', ty);
				
				labelnode.appendChild(input);
			}
			propdiv.appendChild(labelnode);
			
			obj.appendChild(propdiv);
		}
	}else if(frame.constructor == Object && frame.type == 'entity'){
		// we are an entity
		
		var input = document.createElement("input");
		input.setAttribute('class', 'queryInteractor');
		input.setAttribute('id', gensym.next());
		// This should really be a specialised search box.
		input.setAttribute('type', 'text');
		obj.appendChild(input);
	}
	
	return obj; 
};

dacura.frame.typeConvert = function(ty){
	// This needs to be extended at each XSD type. 
	switch (ty) { 
	case "http://www.w3.org/2001/XMLSchema#boolean" :
		return 'checkbox';
	case "http://www.w3.org/2000/01/rdf-schema#Literal" :
		return 'text';
	}
};

dacura.frame.fill = function(frameid, candidate){
	//
};

dacura.frame.init = function(entity){
	// frame is in triple store. 
	if(entity.latest_status == 'accepted'){
	}else{
		// frame is not in triple store and we have to get a class based frame.

		// DDD
		// This url should be found in a different manner - it seems to be hard coded.

		// entity qualified name [ dacura.system/ld that has URL ]
		var eqn = 'http://localhost/dacura/' +
			entity.meta.cid + '/' + entity.ldtype + '/' + entity.id;
		// Should be in meta data ?/!
		var cls = entity.contents.main[eqn]['rdf:type'];
		var ajs = dacura.frame.api.getFrame(cls);
		ajs.handleResult = dacura.frame.draw;
		dacura.system.invoke(ajs, "", "ldo-contents-contents");

		
		//
		
	}		
};



