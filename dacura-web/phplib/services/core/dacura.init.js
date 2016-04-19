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
	"error" : "<img class='result-icon result-error' src='<?=$service->furl("images", "icons/error.png");?>'>",		
	"success" : "<img class='result-icon result-success' src='<?=$service->furl("images", "icons/success.png");?>'>",
	"warning" : "<img class='result-icon result-warning' src='<?=$service->furl("images", "icons/warning.png");?>'>",
	"info" : "<img class='result-icon result-info' src='<?=$service->furl("images", "icons/info.png");?>'>",
	"help" : "<img class='result-icon result-info' src='<?=$service->furl("images", "icons/help_icon.png");?>'>",
	"accept" : "<img class='result-icon result-accept' src='<?=$service->furl("images", "icons/accept.png");?>'>",		
	"pending" : "<img class='result-icon result-pending' src='<?=$service->furl("images", "icons/pending.png");?>'>",		
	"reject" : "<img class='result-icon result-reject' src='<?=$service->furl("images", "icons/reject.png");?>'>",		
};

$(document).ready(function() {
	dacura.system.selects();
	dacura.system.selects("select.property-meta", {width: 100});
});