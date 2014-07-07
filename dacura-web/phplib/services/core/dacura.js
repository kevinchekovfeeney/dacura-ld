dacura = {
	system: {
		ajax_url: "",
		mode: "void"
	}, 		
};

dacura.toolbox = {};

dacura.toolbox.writeErrorMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-error'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeInfoMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-info'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeWarningMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-warning'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeBusyMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-info'><div class='dacura-busy-small'></div>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.clearBusyMessage = function(jqueryid){
	$(jqueryid).html("");
	$(jqueryid).hide();
};


dacura.toolbox.writeSuccessMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-success'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeWarningMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-warning'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.showSuccessPage = function(jqueryid, msg){
	$(jqueryid).prepend("<div id='pagecontent-container'><div id='pagecontent' class='pagecontent-success'>" + msg + "</div></div>");
};

dacura.toolbox.getServiceURL = function(base, api, cid, did, sname, args){
	url = base;
	if(api == "api") url += "api/";
	url += cid + "/" + did + "/" + sname;
	if(args.length > 0) url += "/args";
	return url;
}

