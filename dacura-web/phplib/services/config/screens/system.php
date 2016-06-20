<?php 
/** 
 * Platform / System level configuration page
 * 
 * @package config/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-screen' id='system-config'>
	<?php if(in_array("system-configuration", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="system-configuration" title="System Settings">
		<div class='subscreen-intro-message'><?= isset($params['system-configuration-intro']) ? $params['system-configuration-intro'] : ""?></div>
		<?php echo $service->getInputTableHTML("sysconfig", $params['sysconfig_fields'], $params['sysconfig_settings']);?>
		<div class="subscreen-buttons">
			<button id='configupdate' class='dacura-update subscreen-button'>Update Configuration</button>
		</div>		
	</div>
	<?php } if(in_array("list-collections", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='list-collections' title="Configure Collections">
		<div class='subscreen-intro-message'><?= isset($params['list-collections-intro']) ? $params['list-collections-intro'] : "" ?></div>
		<table id="collections-table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="dlo-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
				<th id="dlo-name" title="The title of the collection - expressed in natural language">Title</th>
				<th id="dlo-status" title="Only collections with status 'accept' are in use.">Status</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<?php } if(in_array("add-collection", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="add-collection" title="Create New Collection">
		<div class='subscreen-intro-message'><?= isset($params['add-collection-intro']) ? $params['list-collections-intro'] : "" ?></div>
		<?php echo $service->getInputTableHTML("collection-details", $params['create_collection_fields'], $params['create_collection_settings']);?>
		<div class="subscreen-buttons">
			<button id='collectioncreate' class='dacura-create subscreen-button'>Create New Collection</button>
		</div>
	</div>
	<?php } if(in_array("view-files", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-files" title="System Files">
		<div class='subscreen-intro-message'><?= isset($params['view-files-intro']) ? $params['view-files-intro'] : "" ?></div>
		<div id='kcfilebrowser'></div>
	</div>
	<?php } if(in_array("view-services", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-services" title="Configure Services">
		<div class='subscreen-intro-message'><?= isset($params['view-services-intro']) ? $params['view-services-intro'] : "" ?></div>
			<div id='servicelist'>
			<div id='srvrtable'>
			<table id="services-table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="dso-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
					<th id="dso-status" title="Only services with status 'accept' are enabled.">Status</th>
					<th id="dfn-rowselector" title="Update a group of services">Update</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
			</div>
			<div class="subscreen-buttons" id='multi-service-updates'>
				<div id="service-table-updates"></div>
			</div>
		</div>
	</div>
	<?php } if(in_array("view-logs", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-logs" title="View Server Logs">
		<div class='subscreen-intro-message'><?= isset($params['view-services-intro']) ? $params['view-logs-intro'] : "" ?></div>
		<?php echo RequestLog::getAsListingTable("logtable")?>
	</div>	
	<?php } ?>
</div>

<script>
/* Updates each of the selected services statuses in sequence */
function updateServicesStatus(ids, status, cnt, pconf){
	dacura.tool.clearResultMessages();
	var obj = {"services": {}};
	for(var i = 0; i < ids.length; i++){
		obj.services[ids[i]] = lconfig.services[ids[i]]; 
		obj.services[ids[i]].status = status;
	}
	var onwards = function(data, pconf){
		showUpdateSuccess(data, pconf, cnt + " services updated to status " + status);
	}
	dacura.config.updateCollection(obj, onwards, pconf);
}

/* to load a new collection, we just switch pages to it */
function loadCollection(e, id){
	dacura.system.switchContext(id);	
}

/* Loads an individual service following a click on it on the service configuration screen */
function loadService(e, id){
	var x = window.location.href.split("?")[0];
	x = x.split("#")[0];
	if(x.substring(x.length-1) != "/"){ x += "/";}
	window.location.href = x + id;
}

/* called to check for illegal and obvious problems with creating collections */
function inputError(obj){
	if(typeof obj.id == "undefined" || typeof obj.title == "undefined"){
		return "bad reading of object from input";
	}
	if(obj.id.length < 2 || obj.title.length < 5){
		return "The ID must be at least 2 characters long and the title must be at least 5";
	}
	return false;
}

/* marshalls the data extracted from the update configuration form for passing to the api */
function updateConfiguration(obj, result, pconf){
	obj.settings = obj.values;
	delete(obj.values);
	dacura.config.updateCollection(obj, result, pconf);
}

/* just some result reporting */
function showCreateSuccess(txt, targets){
	dacura.system.showSuccessResult("You will now be able to configure and activate this collection", "Collection with id: " + txt + " successfully created", targets.resultbox, false, {'scrollTo': true, "icon": true});
	setTimeout(dacura.system.switchContext(txt), 3000);
}

function showUpdateSuccess(data, pconf, msg){
	dacura.system.showSuccessResult(msg, "Updates successfully saved", pconf.resultbox, false, {'scrollTo': true, "icon": true});
	drawCollection(data);
}

/* called when settings are received from a call to draw the page */
function drawCollection(obj){
	if(typeof lconfig == "object"){
		for(var k in obj){
			lconfig[k] = obj[k];
		}
	}
	else {
		lconfig = obj;
	}
	if((typeof obj.collection == "object") && (typeof obj.collection.config == "object") && obj.collection.config && typeof obj.collection.config.meta == "object"){
		//dacura.tool.form.populate("sysconfig", lconfig.settings, obj.collection.config.meta);
	}
	else if(typeof obj.settings == "object"){
		dacura.tool.form.populate("sysconfig", lconfig.settings);
	}
	if(typeof obj.services == "object"){
		drawServiceTable(obj.services);
	}
}

var sloaded = false;//is the service table loaded
/* draws the table listing the various services */ 
function drawServiceTable(services){
	var service_rows = dacura.config.getServiceTableRows(services);
	if(!sloaded){
		dacura.tool.table.init("services-table", {
			"screen": "view-services", 
			"container": "srvrtable",
			"cellClick": loadService,
			"multiselect": {
				options: <?=json_encode($params['selection_options'])?> , 
				intro: "Update selected services: ", 
				container: "service-table-updates",
				label: "Update",
				update: updateServicesStatus 
			},		
			"dtsettings": <?=$params['service_table_settings']?>
		}, service_rows);
		sloaded = true;
	}
	else {
		dacura.tool.table.reincarnate("services-table", service_rows, dacura.tool.tables["services-table"]); 	
	}
}

var fbloaded = false;//is the configuration loaded
var lconfig;//the most recent configuration received from the api

/* page initialisation - forms, tables, etc */
$(function() {	
	dacura.tool.init({
		"tabbed": 'system-config', 
		forms: {
			ids:['sysconfig','collection-details'], 
			icon: "<?= $service->furl("images", "icons/help-icon.png")?>"}
		}
	); 
	dacura.tool.table.init("collections-table", {
		"screen": "list-collections", 
		"fetch": dacura.config.getCollections,
		"refresh": {label: "Refresh Collection List"},
		"rowClick": loadCollection,
		"dtsettings": <?=$params['dacura_table_settings']?>
	});		
	dacura.tool.table.init("logtable", {
		"screen": "view-logs", 
		"fetch": dacura.config.getLogs,
		"refresh": {label: "Refresh Log Listing"},
		"dtsettings": <?=$params['log_table_settings']?>
	});		
	dacura.tool.button.init("configupdate", {
		"screen": "system-configuration",			
		"source": "sysconfig",
		"submit": updateConfiguration,
		"result": function(data, pconf) { showUpdateSuccess(data, pconf, "System Configuration Updated OK");}
	});	
	dacura.tool.button.init("collectioncreate", {
		"screen": "add-collection",
		"source": "collection-details",
		"validate": inputError, 
		"submit": dacura.config.addCollection, 
		"result": showCreateSuccess
	});
    if(!fbloaded && $("#kcfilebrowser").is(':visible')){
		dacura.tool.openKCFinder("#kcfilebrowser", "<?php echo $service->getFileBrowserURL()?>", dacura.system.cid(), "images");	
    }
	$('#system-config').tabs( {
        "activate": function(event, ui) {
        	 if($("#kcfilebrowser").is(':visible')){
        		dacura.tool.openKCFinder("#kcfilebrowser", "<?php echo $service->getFileBrowserURL()?>", dacura.system.cid(), "images");	
        	 }
        	 else {
				$("#kcfilebrowser").html("");
           	}
     	 }
    });
	var pconf = { resultbox: ".tool-info", busybox: "#system-config"};
	dacura.config.fetchCollection(dacura.system.cid(), drawCollection, pconf);
    fbloaded = true;
});
</script>