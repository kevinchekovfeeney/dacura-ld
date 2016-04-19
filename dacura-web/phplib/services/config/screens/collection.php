<?php 
/** 
 * Collection configuration page
 * 
 * @package config/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-screen' id='collection-config'>
	<?php if(in_array("collection-configuration", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="collection-configuration" title="Settings">
		<div class='subscreen-intro-message'></div>
		<?php echo $service->getInputTableHTML("sysconfig", $params['sysconfig_fields'], $params['sysconfig_settings']);?>
		<div id="kcfinder_div" class='dch'></div>
		<div class="subscreen-buttons">
			<?php if(isset($params['candelete']) && $params['candelete']){?>
			<button id='deletecollection' class='dacura-delete subscreen-button'>Delete Collection</button>
			<?php } if(isset($params['sysconfig_settings']['display_type']) && $params['sysconfig_settings']['display_type'] == "update"){?>
				<button id='configupdate' class='dacura-update subscreen-button'>Update Configuration</button>
			<?php } ?>
		</div>		
	</div>
	<?php } if(in_array("view-files", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-files" title="Files">
		<div class='subscreen-intro-message'></div>
		<div id='kcfilebrowser'></div>
	</div>
	<?php } if(in_array("view-services", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-services" title="Services">
		<div class='subscreen-intro-message'></div>
		<div id='servicelist'>
			<div id='srvrtable'>
				<table id="services-table" class="dacura-api-listing">
					<thead>
					<tr>
						<th id="dso-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
						<th id="dso-status" title="Only services with status 'accept' are enabled.">Status</th>
						<th id="dso-selector" title="Update a group of services">Update</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
		<div id='servicebox' class='subsubscreen dch'>
			<div id='servicebox-contents'></div>
			<?php if(isset($params['service_config_settings']['display_type']) && $params['service_config_settings']['display_type'] == "update"){?>
				<div class="subscreen-buttons">
					<button id='serviceupdate' class='dacura-update subscreen-button'>Update Service Configuration</button>
				</div>
			<?php } ?>
		</div>
	</div>
	<?php } if(in_array("view-logs", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-logs" title="Logs">
		<div class='subscreen-intro-message'></div>
		<?php echo RequestLog::getAsListingTable("logtable")?>
	</div>	
	<?php } ?>
</div>
<script>

/* result reporting */

function showUpdateSuccess(data, pconf, msg){
	opts = {};
	if(typeof data.collection == "object" && typeof data.collection.name == "string"){
		opts.cname = data.collection.name; 
	}
	if(typeof data.settings == "object" && typeof data.settings.icon == "string"){ 
		opts.cicon = data.settings.icon;
	}
	if(!isEmpty(opts)){
		dacura.system.updateTopbar(opts);
	}
	if(typeof data.settings == "object" && typeof data.settings.background == "string"){
		dacura.system.updateHeader({background: data.settings.background});
	}
	dacura.system.showSuccessResult("Updates successfully saved", msg, pconf.resultbox, false, {'scrollTo': true, "icon": true, "closeable": true});
	drawCollection(data);
}

/* updates the current settings of a collection - called by collection configuration button */
function updateSettings(obj, result, pconf){
	data = {settings: obj};
	dacura.config.updateCollection(data, result, pconf);
}

/* Draws the collection screens - including all the subscreens*/
function drawCollection(obj){
	if(typeof lconfig == "object"){
		for(var k in obj){
			lconfig[k] = obj[k];
		}
	}
	else {
		lconfig = obj;
	}
	if(typeof obj.settings == "object"){
		obj.settings.name = obj.collection.name;
		obj.settings.id = obj.collection.id;
		obj.settings.status = obj.collection.status;
		dacura.tool.form.populate("sysconfig", obj.settings);
	}
	<?php if(in_array("view-services", $params['subscreens'])) { ?>
	
	if(typeof obj.services == "object"){
		drawServiceTable(obj.services, obj.collection);
	}
	$('button.update-service').button().click(function(){
		var bits = this.id.split("-");
		var sid = bits[0];
		if(typeof lconfig.services[sid] == "object"){
			nobj = lconfig.services[sid];
			nobj.status = bits[1];
		}
		else {
			nobj = {"status": bits[1]};
		}
		var ndata = {"services": {}};
		ndata.services[sid] = nobj;
		var onwards = function(data, pconf){
			showUpdateSuccess(data, pconf, sid + " updated: " + bits[1] + "d");
		}
		pconf = typeof service_subpage_conf == "object" ? service_subpage_conf : dacura.tool.subscreens['view-services'];
		dacura.config.updateCollection(ndata, onwards, pconf);
	});
	<?php } ?>
}

/* Storing various data in javascript variables for access by screens */
<?php if(in_array("view-services", $params['subscreens'])) { ?>
var allroles = <?=isset($params['all_roles']) ? json_encode($params['all_roles']) : "{}"?>;
var service_tables = <?= json_encode($params['service_tables']); ?>;

/* result reporting */ 
function showDeleteResult(obj, targets){
	dacura.system.showWarningResult("This collection has been deleted", "Collection " + dacura.system.cid() + " deleted", targets.resultbox, false, targets.mopts);
}

/* Called when user clicks 'remove' link of facet page */
function facet_remove(divid, r, f){
	id = current_service;
	var nfacets = [];
	for(var i = 0; i < lconfig.services[id].facets.length; i++){
		if(lconfig.services[id].facets[i].facet == f && lconfig.services[id].facets[i].role == r){
			
		}
		else {
			nfacets.push(lconfig.services[id].facets[i]);
		}
	}
	lconfig.services[id].facets = nfacets;
	var ndata = {"services": {}};
	ndata.services[id] = lconfig.services[id];
	var onwards = function(data, pconf){
		$('#'+divid).remove();
		showUpdateSuccess(data, pconf, id + " updated");
	}
	dacura.config.updateCollection(ndata, onwards, service_subpage_conf);	
}

/* Called when user updates list of facets for a service */
function updateFacetList(key, facets, pconf, allfacets){
	var html = "";
	for(var i = 0; i < facets.length; i++){
		var rtitle = allroles[facets[i].role];
		if(typeof allfacets == "object" && typeof allfacets[facets[i].facet] == "string"){
			fac = allfacets[facets[i].facet];
		}
		else {
			fac = facets[i].facet;
		}
		html += dacura.config.getFacetButtonHTML(key+"-facet-" + i, facets[i].role, rtitle, facets[i].facet, fac);
	}
	$('#' + key + ' .dacura-facets-listing').html(html);
}

/* Loads the service with the passed id into the screen */
function loadService(id){
	current_service = id;
	dacura.tool.clearResultMessages();
	if(!isEmpty(service_tables[id])){
		$('#servicebox-contents').empty().append(service_tables[id].body);
		dacura.tool.form.init('service-'+id, {initselects: true, icon: "<?= $service->furl("images", "icons/help-icon.png")?>"});
		$('.addfacet').button().click(function(event){
			var f = $('.facet-maker select.facets').val();
			var r = $('.facet-maker select.roles').val();
			if(typeof lconfig.services[id].facets == "object"){
				lconfig.services[id].facets.push({"facet": f, "role": r});
			}
			var ndata = {"services": {}};
			ndata.services[id] = lconfig.services[id];
			var onwards = function(data, pconf){
				updateFacetList("servicebox-contents", lconfig.services[id].facets, pconf, lconfig.services[id]["facet-list"]); 
				showUpdateSuccess(data, pconf, id + " updated");
			}
			dacura.config.updateCollection(ndata, onwards, service_subpage_conf);	
		});
		dacura.tool.form.populate('service-'+id, lconfig.services);		
	}
	else {
		dacura.system.showErrorResult("Found no service configuration information for " + id, "Error loading service", '#servicebox-contents');
	}
	
	service_subpage_conf = dacura.tool.loadSubscreen('servicelist', 'servicebox', "return to list of services", service_tables[id].header);
}

/* updates the config of a particular service */
function updateServiceConfig(obj, result, pconf){
	var sid = obj.id;
	delete(obj.id);
	var data = {"services": {}};
	data.services[sid] = obj;
	pconf = typeof service_subpage_conf == "object" ? service_subpage_conf : pconf;
	dacura.config.updateCollection(data, result, pconf);
}

/* reads the data from the form, marshalls it and sends it to the update api */
function readServiceUpdate(screen){
	//get id of currently loaded serviced
	if($('#servicebox-contents table').length){
		var tid = $("#servicebox-contents table").attr("id");
		var obj = dacura.tool.form.gather(tid);
		if(typeof obj.meta == "object" && typeof obj.values == "object"){

		}
		obj.id = tid.substring(8);
		return obj;
	}
	else {
		alert("No input data available to update service configuration");
	}
	return {};
}

/* draws the table which lists the services available */
function drawServiceTable(services, col){
	var service_rows = dacura.config.getServiceTableRows(services, col);
	if(!sloaded){
		dacura.tool.table.init("services-table", {
			"screen": "view-services",
			"nohover": true, 
			"container": "srvrtable",
			"dtsettings": <?=$params['service_table_settings']?>
		}, service_rows);
		sloaded = true;
	}
	else {
		dacura.tool.table.reincarnate("services-table", service_rows, dacura.tool.tables["services-table"]); 	
	}
	for(sid in services){
		if(services[sid].status == 'enable'){
			$('#services-table td.dso-id:contains("' + sid + '")').hover(function(){
				$(this).closest('tr').addClass('userhover');
			}, function() {
			    $(this).closest('tr').removeClass('userhover');
			}).click( function(event){
				loadService($(event.target).html());
			});					
		}
	}

}

<?php } ?>
var fbloaded = false;//is the configuration loaded
var lconfig;//the most recent configuration received from the api
var sloaded = false; // the currently loaded service

 /* page initialisation - forms, tables, etc */
 $(function() {
	dacura.tool.init({
		"tabbed": 'collection-config', 
		"forms": {
			ids: ['sysconfig'], 
			icon: "<?= $service->furl("images", "icons/help-icon.png")?>",
			fburl: "<?php echo $service->getFileBrowserURL()?>"
		}
	}); 
	
    <?php if(in_array("view-logs", $params['subscreens'])) { ?>
    dacura.tool.table.init("logtable", {
		"screen": "view-logs", 
		"fetch": dacura.config.getLogs,
		"refresh": {label: "Refresh Log Listing"},
		"dtsettings": <?=$params['log_table_settings']?>
	});		
    <?php } if(in_array("collection-configuration", $params['subscreens']) && isset($params['sysconfig_settings']['display_type']) && $params['sysconfig_settings']['display_type'] == "update"){?>
    dacura.tool.button.init("configupdate", {
		"screen": "collection-configuration",			
		"source": "sysconfig",
		"submit": updateSettings,
		"result": function(data, pconf){showUpdateSuccess(data, pconf, "Configuration settings updated ok");}
	});
    <?php } if(in_array("view-services", $params['subscreens']) && isset($params['service_config_settings']['display_type']) && $params['service_config_settings']['display_type'] == "update") { ?>
	dacura.tool.button.init("serviceupdate", {
		"screen": "view-services",			
		"gather": readServiceUpdate,
		"submit": updateServiceConfig,
		"result": function(data, pconf) { showUpdateSuccess(data, pconf, "System Configuration Updated OK");}
	});
    <?php } if(isset($params['candelete'])){?>
	dacura.tool.button.init("deletecollection", {
		"screen": "collection-configuration",
		"gather": function(){ return dacura.system.cid();},
		"submit": dacura.config.deleteCollection, 
		"result": showDeleteResult
	});
	<?php } if(in_array("view-files", $params['subscreens'])) { ?>
    if(!fbloaded && $("#kcfilebrowser").is(':visible')){
		dacura.tool.openKCFinder("#kcfilebrowser", "<?php echo $service->getFileBrowserURL()?>", dacura.system.cid(), "images");	
    }
	$('#collection-config').tabs( {
        "activate": function(event, ui) {
        	 if($("#kcfilebrowser").is(':visible')){
        		dacura.tool.openKCFinder("#kcfilebrowser", "<?php echo $service->getFileBrowserURL()?>", dacura.system.cid(), "images");	
        	 }
        	 else {
				$("#kcfilebrowser").html("");
           	}
     	 }
    });
	<?php } ?>	
        
	var pconf = { resultbox: ".tool-info", errorbox: ".tool-info", busybox: "#collection-config"};
    dacura.config.fetchCollection(dacura.system.cid(), drawCollection, pconf);
    fbloaded = true;
});
</script>