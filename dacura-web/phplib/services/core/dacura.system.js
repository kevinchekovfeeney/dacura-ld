dacura.system = {};
dacura.system.pagecontext = {
		"collection_id": "<?=$service->getCollectionID()?>", 
		"dataset_id": "<?=$service->getDatasetID()?>", 
		"service" : "<?=$service->servicename?>"
};

dacura.system.getcds = function(c, d, s){
	if(typeof c == "undefined"){
		c = this.pagecontext.collection_id;
	}
	if(c == ""){
		c = "0";
	}
	if(typeof d == "undefined"){
		d = this.pagecontext.dataset_id;
	}
	if(d == ""){
		d = "0";
	}
	if(typeof s == "undefined"){
		s = this.pagecontext.service;
	}
	return c + "/" + d + "/" + s;	
};

dacura.system.apiURL = function(c, d, s){
	var url = "<?=$service->settings['ajaxurl']?>";
	return url + this.getcds(c, d, s);
};

dacura.system.pageURL = function(c, d, s){
	var url = "<?=$service->settings['install_url']?>";
	return url + this.getcds(c, d, s);
};

dacura.system.switchContext = function(c, d){
	window.location.href = this.pageURL(c, d, this.pagecontext.service);
};