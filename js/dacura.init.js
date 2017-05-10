dacura = {};
dacura.sparams = <?=json_encode($params)?>;

dacura.system = {
		mode: "void",
		xhraborted: false,
		lasttoggleid: 1,
		xhr: {"abort": function(){alert("abort");}},
		busyclass: "medium",
		modalConfig: {
			 dialogClass: "modal-message",
			 modal: true
		},
		targets: {
			"resultbox": '.tool-info', 
			"busybox": '.tool-body',
			"scrollto": false
		},
		msgs: {
			"busy" : "Submitting request to Dacura Server",
			"fail" : "Service call failed",
			"info" : "Service call completed",
			"warning" : "Service call completed with warnings",
			"success" : "Service call successfully completed",
			"nodata" : "Server response was empty",
			"notjson" : "Failed to parse server response"
		},
};	
	
dacura.system.rest_path = dacura.sparams.rest;
dacura.system.install_url = dacura.sparams.url
dacura.system.pagecontext = {
		"collection_id": dacura.sparams.cid,
		"service" : dacura.sparams.sname
};

dacura.cid = function(){
	return dacura.system.pagecontext.collection_id;
}

dacura.url = function(isajax){
	return dacura.system.install_url + (isajax ? dacura.system.rest_path : "");
}

dacura.rest_path = function(){
	return dacura.system.rest_path;
}


dacura.sname = function(){
	return dacura.system.pagecontext.service;
}


if(typeof jQuery == "undefined"){
	eval(dacura.sparams.jQuery);
}

if(dacura.sparams.jslibs){
	for(var i = 0; i<dacura.sparams.jslibs.length; i++){
		jQuery.getScript(dacura.sparams.jslibs[i]);
	}
}

