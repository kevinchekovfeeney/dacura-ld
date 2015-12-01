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
	
dacura.system.ajax_url = "<?=$service->settings['ajaxurl']?>";
dacura.system.install_url = "<?=$service->settings['install_url']?>";
dacura.system.pagecontext = {
		"collection_id": "<?=$service->getCollectionID()?>", 
		"dataset_id": "<?=$service->getDatasetID()?>", 
		"service" : "<?=$service->servicename?>"
};
dacura.system.resulticons = {
		"error" : "<img class='result-icon result-error' src='<?=$service->url("image", "capi/error.png");?>'>",		
		"success" : "<img class='result-icon result-success' src='<?=$service->url("image", "capi/success.png");?>'>",
		"warning" : "<img class='result-icon result-warning' src='<?=$service->url("image", "capi/warning.png");?>'>",
		"info" : "<img class='result-icon result-info' src='<?=$service->url("image", "capi/info.png");?>'>",
};

$(document).ready(function() {
	$('select.dacura-select').selectmenu({"width": "200"});
});