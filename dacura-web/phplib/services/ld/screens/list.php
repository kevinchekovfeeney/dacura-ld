<div class='dacura-screen' id='ld-tool-home'>
	<?php if(in_array("ldo-list", $params['subscreens'])) { ?>
	<div class='dacura-subscreen ld-list' id="ldo-list" title="<?=$params['ld_list_title']?>">
		<div class='subscreen-intro-message'><?=$params['objectlist_intro_msg']?></div>
		<div class='tholder' id='ld-table-container'>
			<table id="ld_table" class="dcdt display dacura-api-listing">
				<thead>
				<tr>
					<th id='lde-id'>ID</th>
					<th id='lde-meta-title'>Title</th>
					<th id='lde-type'>Type</th>
					<th id='lde-collectionid'>Collection</th>
					<th id='lde-status'>Status</th>
					<th id='lde-version'>Version</th>
					<th id='dfn-getPrintableCreated'>Created</th>
					<th id='lde-createtime'>Sortable Created</th>
					<th id='dfn-getPrintableModified'>Modified</th>
					<th id='lde-modtime'>Sortable Modified</th>
					<th id='lde-size'>Size</th>
					<th id='dfn-rowselector'>Select</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="subscreen-buttons" id='ld-table-updates'></div>
	</div>
	
	<?php } if(in_array("update-list", $params['subscreens'])) { ?>
	<div class='dacura-subscreen ld-list' id="update-list" title="<?=$params['ld_updates_title']?>">
		<div class='subscreen-intro-message'><?=$params['updates_intro_msg']?></div>
		<div class='tholder' id='update-table-container'>
			<table id="update_table" class="dcdt dacura-api-listing display">
				<thead>
				<tr>
					<th id='ldu-eurid'>ID</th>
					<th id='ldu-targetid'>Target</th>
					<th id='ldu-type'>Type</th>
					<th id='ldu-collectionid'>Collection</th>
					<th id='ldu-status'>Status</th>
					<th id='ldu-from_version'>From Version</th>
					<th id='ldu-to_version'>To Version</th>
					<th id='dfu-getPrintableCreated'>Created</th>
					<th id='ldu-createtime'>Sortable Created</th>
					<th id='dfu-getPrintableModified'>Modified</th>
					<th id='ldu-modtime'>Sortable Modified</th>
					<th id='ldu-size'>Size</th>				
					<th id='dfx-rowselector'>Select</th>
					
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="subscreen-buttons" id='update-table-updates'></div>		
	</div>
	<?php } if(in_array("ldo-create", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="ldo-create" title="<?=$params['ld_create_title']?>">
		<div class='subscreen-intro-message'><?=$params['create_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("ldo-details", $params['create_ldo_fields'], array("display_type" => "create"));?>
		<div class="subscreen-buttons">
			<button id='ldotestcreate' class='dacura-test-create subscreen-button'><?=$params['testcreate_button_text']?></button>		
			<?php if(isset($params['direct_create_allowed']) && $params['direct_create_allowed']) { ?>
			<button id='ldocreate' class='dacura-create subscreen-button'><?=$params['create_button_text']?></button>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</div>
<script>

function updateLDUpdateStatus(ids, status, cnt, pconf, rdatas){
	var nid = ids.shift();
	var rdata = rdatas.shift();
	var upd = {"umeta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	var onwards = function(data, pconf){
		if(!isEmpty(ids)){
			updateLDUpdateStatus(ids, status, cnt, pconf, rdatas);
		}
		else {
			showUpdateStatusSuccess(status, cnt, pconf);
			//reset url to this context
			dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, dacura.system.cid());			
			refreshLDUpdateList();
		}
	}
	//for global scope we need to change api url...
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	dacura.ld.update("update/" + nid, upd, onwards, pconf, false);
}

function refreshLDUpdateList(){
	dacura.tool.table.refresh("update_table");
}

function updateLDStatus(ids, status, cnt, pconf, rdatas){
	dacura.tool.clearResultMessages();
	var nid = ids.shift();
	var rdata = rdatas.shift();
	var upd = {"meta": {"status": status}, "ldtype": rdata.type, "editmode": "update", "format": "json"};
	var onwards = function(data, pconf){
		if(!isEmpty(ids)){
			updateLDStatus(ids, status, cnt, pconf, rdatas);
		}
		else {
			showUpdateStatusSuccess(status, cnt, pconf);
			//reset url to this context
			dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, dacura.system.cid());			
			refreshLDList();
		}
	}
	//for global scope we need to change api url...
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	dacura.ld.update(nid, upd, onwards, pconf, false);
}

function showUpdateStatusSuccess(status, cnt, targets){          
	dacura.system.showSuccessResult(cnt + " ldos updated to status " + status, "LDO Update OK", targets.resultbox, false, {'scrollTo': true, "icon": true, "closeable": true});
}

function refreshLDList(){
	dacura.tool.table.refresh("ld_table");
}

writeLDImportToForm = function(fieldid){
	var ldov = new LDOViewer(ldo, $params['create_ldoviewer_config']);
	var ldov = new LDOViewer(false, dacura.tool.subscreens[screen]);
	ldov.show("create", fieldid);	
}


$(function() {
	var initarg = {
		"tabbed": 'ld-tool-home',
	};
	<?php if(in_array("ldo-create", $params['subscreens'])) { ?>
	initarg.forms = { 
		ids: ['ldo-details'], 			
		icon: "<?= $service->get_system_file_url("images", "icons/help-icon.png")?>",
		actions: { 
			"ldurl-download": function(){
				dacura.ld.viewer.loadURL("ldo-details-ldurl", 'ldo-details-contents');
			},
			"ldfile-upload": function(){
				dacura.ld.viewer.loadFile('ldo-details-ldfile', 'ldo-details-contents');
			}
		},
		fburl: "<?= $service->getFileBrowserURL()?>"			
	};
	<?php } ?>
	dacura.tool.init(initarg);
	dacura.tool.table.init("ld_table", {
		"screen": "ldo-list", 
		"cellClick": function(event, entid, rowdata) {
			var args = "";
			var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
			if(!ldtype){
				args = "?ldtype=" + rowdata.type;
			}
			if(dacura.system.cid() == "all" && rowdata.collectionid != "all"){
				window.location.href = dacura.system.pageURL(dacura.system.pagecontext.service, rowdata.collectionid) + "/" + entid + args;
			}
			else {
				window.location.href = dacura.system.pageURL() + "/" + entid + args;
			}
		},
		"multiselect": {
			options: {"accept": "Accept", "reject": "Reject", "pending": "Pending"}, 
			intro: "Update Selected <?=isset($params['ldtype']) ? $params['ldtype'] :  "object"?>, Set Status to ", 
			container: "ld-table-updates",
			label: "Update",
			update: updateLDStatus 
		},
		"refresh": {label: "Refresh <?=isset($params['ldtype']) ? $params['ldtype'] :  "object"?> List"},			
		//"ajax": dacura.ld.apiurl,//
		"fetch": function(onwards, pconfig) { dacura.ld.fetchldolist(onwards, pconfig, dacura.ld.ldo_type<?php if(isset($params['fetch_args']) && $params['fetch_args']) echo ", ".$params['fetch_args'];?>);},
		"dtsettings": <?=$params['ldo_datatable']?>
	});		
	dacura.tool.table.init("update_table", {
		"screen": "update-list", 
		"fetch": function(onwards, pconfig) { dacura.ld.fetchupdatelist(onwards, pconfig, dacura.ld.ldo_type<?php if(isset($params['fetch_update_args']) && $params['fetch_update_args']) echo ", ".$params['fetch_update_args'];?>);},
		"cellClick": function(event, entid, rowdata) {
			window.location.href = dacura.system.pageURL(dacura.system.pagecontext.service, rowdata.collectionid) + "/update/" + entid;
		},
		"multiselect": {
			options: {"accept": "Accept", "pending": "Pending", "reject": "Reject"}, 
			intro: "Update status of selected updates to: ", 
			container: "update-table-updates",
			label: "Update",
			update: updateLDUpdateStatus 
		},
		"dtsettings": <?=$params['update_datatable']?>
	});	
	dacura.tool.button.init("ldotestcreate", {
		"screen": "ldo-create",			
		"source": "ldo-details",
		"validate": dacura.ld.viewer.validateNew,		
		"submit": testCreateLDO,
		"result": function(data, pconf) { createSuccess(data, pconf, "System Configuration Updated OK");}
	});	
	<?php if(isset($params['direct_create_allowed']) && $params['direct_create_allowed']) { ?>
	dacura.tool.button.init("ldocreate", {
		"screen": "ldo-create",			
		"source": "ldo-details",
		"validate": dacura.ld.viewer.validateNew,		
		"submit": createLDO,
		"result": function(data, pconf) { createSuccess(data, pconf, "System Configuration Updated OK");}
	});	
	<?php } ?>			
	dacura.ld.viewer.initCreate('ldo-details');
	//dacura.editor.init({"editorheight": "300px", "targets": {resultbox: "#create-ldo-msgs", busybox: "#create-ldo"}});
	//dacura.editor.load(false, false, dacura.ld.create);

});


function createSuccess(data, pconf, msg){
	//obj = createFormToAPI(data);
	var x = new LDResult(data, pconf);
	x.show();
}



function testCreateLDO(data, result, pconf){
	<?php if(count($params['create_options']) > 0){
		echo "options = ".json_encode($params['create_options']).";";
	}?>
	var demand_id_token = "<?php echo $params['demand_id_token'];?>";
	obj = dacura.ld.viewer.parseCreateForm(data, demand_id_token, options);
	if(obj){
		//dacura.system.showModal("Testing Linked Data Object Creation", "info");
		pconf.onmessage = function(msgs){
			for(var i = 0; i < msgs.length; i++){
				dacura.system.updateModal(msgs[i]);
			}
		}
		dacura.ld.create(obj, result, pconf, true);
	}
}


function createLDO(data, result, pconf){
	<?php if(count($params['create_options']) > 0){
		echo "options = ".json_encode($params['create_options']).";";
	}?>
	var demand_id_token = "<?php echo $params['demand_id_token'];?>";
	obj = dacura.ld.viewer.parseCreateForm(data, demand_id_token, options);
	if(obj){
		dacura.ld.create(obj, result, pconf);
	}
}

function getPrintableCreated(obj){
	return timeConverter(obj.createtime);
}

function getPrintableModified(obj){
	return timeConverter(obj.modtime);
}

</script>

