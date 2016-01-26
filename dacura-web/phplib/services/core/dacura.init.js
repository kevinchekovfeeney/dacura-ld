dacura = {};
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
	
dacura.system.ajax_url = "<?=$service->durl(true)?>";
dacura.system.install_url = "<?=$service->durl()?>";
dacura.system.pagecontext = {
		"collection_id": "<?=$service->cid()?>", 
		"service" : "<?=$service->name()?>"
};

dacura.system.resulticons = {
		"error" : "<img class='result-icon result-error' src='<?=$service->furl("image", "capi/error.png");?>'>",		
		"success" : "<img class='result-icon result-success' src='<?=$service->furl("image", "capi/success.png");?>'>",
		"warning" : "<img class='result-icon result-warning' src='<?=$service->furl("image", "capi/warning.png");?>'>",
		"info" : "<img class='result-icon result-info' src='<?=$service->furl("image", "capi/info.png");?>'>",
};

$(document).ready(function() {
	dacura.system.selects()
});