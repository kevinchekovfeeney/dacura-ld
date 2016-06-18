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

/* result reporting */ 
function showDeleteResult(obj, targets){
	dacura.system.showWarningResult("This collection has been deleted", "Collection " + dacura.system.cid() + " deleted", targets.resultbox, false, targets.mopts);
}

/* Loads the service with the passed id into the screen */
function loadService(id){
	var x = window.location.href.split("?")[0];
	x = x.split("#")[0];
	if(x.substring(x.length-1) != "/"){ x += "/";}
	window.location.href = x + id;
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