<?php 
/** 
 * Service configuration page
 * 
 * @package config/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-screen' id='service-config'>
	<?php echo $service->getInputTableHTML("service-config", $params['serviceconfig_fields'], $params['serviceconfig_settings']);?>		
	<?php if(isset($params['serviceconfig_settings']['display_type']) && $params['serviceconfig_settings']['display_type'] == "update"){?>
		<div class="subscreen-buttons">
			<button id='serviceupdate' class='dacura-update subscreen-button'>Update Service Configuration</button>
		</div>
	<?php } ?>
</div>
<script>

var sets = <?php echo (isset($params['smeta']) ? json_encode($params['smeta']) : "{}") ?>;
/* result reporting */
function showUpdateSuccess(data, pconf, msg){
	opts = {};
	dacura.system.showSuccessResult("Updates successfully saved", msg, pconf.resultbox, false, {'scrollTo': true, "icon": true, "closeable": true});
	showService(data);
}

/* Storing various data in javascript variables for access by screens */
var allroles = <?=isset($params['all_roles']) ? json_encode($params['all_roles']) : "{}"?>;
var pconf = { resultbox: ".tool-info", errorbox: ".tool-info", busybox: "#service-config"};
var cservice = <?=isset($params['service_settings']) ? json_encode($params['service_settings']) : "{}"?>;

/* Called when user clicks 'remove' link of facet page */
function facet_remove(divid, r, f){
	var nfacets = [];
	for(var i = 0; i < cservice.facets.length; i++){
		if(typeof cservice.facets[i] == "object" && cservice.facets[i]){
			if(cservice.facets[i].facet == f && cservice.facets[i].role == r){
				//break;
			}
			else{
				nfacets.push(cservice.facets[i]);
			}
		}
	}
	cservice.facets = nfacets;
	var ndata = {"services": {}};
	ndata.services["<?=$params['id']?>"] = cservice;
	var onwards = function(data, pconf){
		$('#'+divid).remove();
		showUpdateSuccess(data, pconf, "<?=$params['id']?> updated");
	}
	dacura.config.updateCollection(ndata, onwards, pconf);	
}

/* Called when user updates list of facets for a service */
function updateFacetList(key, facets, pconf, allfacets){
	var html = "";
	for(var i = 0; i < facets.length; i++){
		if(facets[i]){
			var rtitle = allroles[facets[i].role];
			if(typeof allfacets == "object" && typeof allfacets[facets[i].facet] == "string"){
				fac = allfacets[facets[i].facet];
			}
			else {
				fac = facets[i].facet;
			}
			html += dacura.config.getFacetButtonHTML(key+"-facet-" + i, facets[i].role, rtitle, facets[i].facet, fac);
		}
	}
	if(html.length == 0){
		html = "<span>no access currently configured</span>";
	}
	$('#' + key + ' .dacura-facets-listing').html(html);
}

/* Loads the service with the passed id into the screen */
function drawService(id, service, conf){
	dacura.tool.clearResultMessages();
	dacura.tool.form.populate('service-config', service, conf);		
	initAddFacetsButton(service, id);	
}

function showService(data){
	var sid = "<?=$params['id']?>";
	if(typeof data.services == "object"){
		cservice = data.services[sid];
		if(typeof data.collection == "object" && typeof data.collection.config == "object" && typeof data.collection.config.servicesmeta == "object"){
			var sets = data.collection.config.servicesmeta[sid];
		}	
		drawService(sid, cservice, sets);
	}
}

function initAddFacetsButton(service, id){
	$('.addfacet').button().click(function(event){
		var f = $('.facet-maker select.facets').val();
		var r = $('.facet-maker select.roles').val();
		if(typeof service.facets == "object"){
			service.facets.push({"facet": f, "role": r});
		}
		var ndata = {"services": {}};
		ndata.services[id] = service;
		var onwards = function(data, pconf){
			updateFacetList("service-config", service.facets, pconf, service["facet-list"]); 
			showUpdateSuccess(data, pconf, id + " updated");
		}
		dacura.config.updateCollection(ndata, onwards);	
	});
	updateFacetList("service-config", service.facets, pconf, service["facet-list"]); 	
}

/* loads data into the object from the form */
function loadDataFromObj(obj){
	var sid = "<?=$params['id']?>";
	if(dacura.system.cid() == "all"){
		var data = {"services": {}, "servicesmeta": {}};
		data.services[sid] = obj.values;
		data.servicesmeta[sid] = obj.meta;			
	}
	else {
		var data = {"services": {}};
		data.services[sid] = obj;
	}
	return data;
}

/* updates the config of a particular service */
function updateServiceConfig(obj, result){
	data = loadDataFromObj(obj);
	dacura.config.updateCollection(data, result, pconf);
}

/* reads the data from the form, marshalls it and sends it to the update api */
function readServiceUpdate(){
	//get id of currently loaded serviced
	var obj = dacura.tool.form.gather("service-config");
	if(dacura.system.cid() == "all"){
		obj.values.facets = cservice.facets;
	}
	else {
		obj.facets = cservice.facets;
	}
	return obj;
}

 /* page initialisation - forms, tables, etc */
 $(function() {
	dacura.tool.button.init("serviceupdate", {
		"screen": "view-service",			
		"gather": readServiceUpdate,
		"submit": updateServiceConfig,
		"result": function(data, pconf) { showUpdateSuccess(data, pconf, "Service Configuration Updated OK");}
	});
	dacura.tool.subscreens['view-service'] = pconf;
	dacura.tool.header.addBreadcrumb("", "<?= $params['service-title']?>");
	dacura.tool.form.init('service-config', {initselects: true, icon: "<?= $service->furl("images", "icons/help-icon.png")?>"});
	$('#service-config').show();
	//drawService("<?php echo $params['id']?>", cservice, sets);	
    dacura.config.fetchCollection(dacura.system.cid(), showService, pconf);
});
</script>