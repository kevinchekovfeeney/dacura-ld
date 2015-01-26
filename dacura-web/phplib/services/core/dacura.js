dacura = {
	system: {
		ajax_url: "",
		mode: "void"
	}, 		
};

dacura.toolbox = {};
dacura.toolbox.xhr = {
		"abort": function(){alert("abort");}
};

dacura.toolbox.xhraborted = false;

/*
 * Function for calling slow ajax functions which print status updates before returning a result 
 * ajs: the ajax setting object that will be sent
 * oncomplete: the function that will be executed on completion
 * onmessage: the function that will be executed on receipt of a message
 * onerror: the function that will be called on 
 */

dacura.toolbox.modalConfig = {
	 dialogClass: "modal-message",
	 modal: true
};

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
};

dacura.toolbox.setModalProperties = function(args){
	for (var key in args) {
		if (args.hasOwnProperty(key)) {
		    dacura.toolbox.modalConfig[key] = args[key];
	    }
	}
};

dacura.toolbox.updateModal = function(msg, msgclass){
	if(!$('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog( "open");
	}
	$('#dacura-modal').html(msg);
} 

dacura.toolbox.showModal = function(msg, msgclass){
		$('#dacura-modal').dialog(dacura.toolbox.modalConfig).html(msg);		
}

dacura.toolbox.removeModal = function(){
	if($('#dacura-modal').dialog( "isOpen" )){
		$('#dacura-modal').dialog("close").html("This should be invisible");
	}
}

dacura.toolbox.abortSlowAjax = function(){
	dacura.toolbox.xhraborted = true;
	dacura.toolbox.xhr.abort();
}

dacura.toolbox.slowAjax = function (url, method, args, oncomplete, onmessage, onerror){
	dacura.toolbox.xhraborted = false;
	var msgcounter = 0;
    var xhr = $.ajaxSettings.xhr();
    if(method == "POST"){
    	args = $.param( args);
    }
	xhr.multipart = true; 
	xhr.open(method, url, true); 
	var msgcounter = 0;
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
			if(!dacura.toolbox.xhraborted){
				var msgs = xhr.responseText.split("}\n{");
				if(msgs.length > 1){
					oncomplete("{" + msgs[msgs.length-1]);  					
				}
				else {
					oncomplete(msgs[0]);  					
				}
			}
		}
	};
	xhr.send(args);
	dacura.toolbox.xhr = xhr;
};

dacura.toolbox.writeErrorMessage = function(jqueryid, msg, fade){
	$(jqueryid).html("<div class='dacura-user-message-box dacura-error'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeInfoMessage = function(jqueryid, msg, fade){
	$(jqueryid).html("<div class='dacura-user-message-box dacura-info'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeWarningMessage = function(jqueryid, msg, fade){
	$(jqueryid).html("<div class='dacura-user-message-box dacura-warning'>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.writeSuccessMessage = function(jqueryid, msg, fade){
	$(jqueryid).html("<div class='dacura-user-message-box dacura-success'>" + msg + "</div>");
	$(jqueryid).show();
};


dacura.toolbox.writeBusyOverlay = function(jqueryid, msg){
	$('#busy-overlay').remove();
	$("<div class='busy-overlay' id='busy-overlay'/>").css({
	    position: "absolute",
	    width: "100%",
	    height: "100%",
	    left: 0,
	    top: 0,
	    zIndex: 1000000,  // to be on the safe side
	})
	.appendTo($(jqueryid).css("position", "relative"));
	$('#busy-overlay').html("<div class='dacura-user-message-box dacura-info'><div class='dacura-busy-small'>&nbsp;</div>" + msg + "</div>");
};

dacura.toolbox.updateBusyOverlay = function(msg){
	$('#busy-overlay').html(msg);
}

dacura.toolbox.removeBusyOverlay = function(msg, secs){
	$('#busy-overlay').html(msg);
	setTimeout(function(){ 
		$('#busy-overlay').remove();			
	}, secs);
};


dacura.toolbox.writeBusyMessage = function(jqueryid, msg){
	$(jqueryid).html("<div class='dacura-user-message-box dacura-info'><div class='dacura-busy-small'></div>" + msg + "</div>");
	$(jqueryid).show();
};

dacura.toolbox.clearBusyMessage = function(jqueryid){
	$(jqueryid).html("");
	$(jqueryid).hide();
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

