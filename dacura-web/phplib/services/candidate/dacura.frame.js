
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
}


dacura.frame.draw = function(resultobj, targets){
	var framestr = resultobj.result;
	var frame = JSON.parse(framestr);

	for (var i = 0 ; i < frame.length ; i++){
		
	}
}

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
}



