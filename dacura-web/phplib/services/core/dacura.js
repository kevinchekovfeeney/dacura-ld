dacura = {
	system: {
		ajax_url: "",
		mode: "void"
	}, 		
};

dacura.toolbox = {};


/*
 * Function for calling slow ajax functions which print status updates before returning a result 
 * ajs: the ajax setting object that will be sent
 * oncomplete: the function that will be executed on completion
 * onmessage: the function that will be executed on receipt of a message
 * onerror: the function that will be called on 
 */
dacura.toolbox.modalSlowAjax = function(url, method, args, initmsg){
	dacura.toolbox.showModal(initmsg, "info");
	var onc = function(res){
		alert(res + " is the result");
	}
	var onm = function(msgs){
		for(var i = 0; i < msgs.length; i++){
			dacura.toolbox.updateModal(msgs[i]);
		}
	}
	this.slowAjax(url, method, args, onc, onm);
}

dacura.toolbox.updateModal = function(msg, msgclass){
	if(!$('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog( "open");
	}
	$('#dacura-modal').html(msg);
} 

dacura.toolbox.showModal = function(msg, msgclass){
		$('#dacura-modal').dialog({
			 dialogClass: "modal-message",
			 modal: true
	    } ).html(msg);		
}

dacura.toolbox.removeModal = function(){
	if($('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog("close").html("This should be invisible");
	}
}


dacura.toolbox.slowAjax = function (url, method, args, oncomplete, onmessage, onerror){
    var msgcounter = 0;
    var xhr = $.ajaxSettings.xhr();
    if(method == "POST"){
    	args = $.param( args);
    	//xhr.setRequestHeader("Content-length", params.length);
    	/*var out = new Array();

    	for (key in args) {
    	    out.push(key + '=' + encodeURIComponent(args[key]));
    	}
    	args = out.join('&');*/
    }
	xhr.multipart = true; 
	xhr.open(method, url, true); 
	xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	//xhr._cachedOnreadystatechange = xhr.onreadystatechange;
	xhr.onreadystatechange = function() {
		//xhr._cachedOnreadystatechange();
		if (xhr.readyState === 3) {
			//parse 
			//var newmsgs = dacura.toolbox.getNewResultMsgs(msgcounter, xhr.responseText);
			  var msgs = xhr.responseText.split("}\n{");
			  msgs.splice(0, msgcounter);
			  var len=msgs.length;
			  if(!(len == 1 && msgcounter == 0))
		      {
				  for(var i=0; i<len; i++) {
					  if(i == 0 && msgcounter == 0) msgs[i] = msgs[i] + "}";
					  else if(i == len-1) msgs[i] = '{' + msgs[i];
					  else msgs[i] = '{' + msgs[i] + '}';
				  }
		      }
			  onmessage(msgs);
		      msgcounter += msgs.length;
		}
		else if(xhr.readyState === 4){
			var msgs = xhr.responseText.split("}\n{");
			oncomplete(msgs[msgs.length-1]);  
		}
	};
	xhr.send(args);
};

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
	if(api != "") url += api + "/";
	url += cid + "/" + did + "/" + sname;
	if(args.length > 0) url += "/args";
	return url;
}

