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
				<th id='dfu-printCreated'>Created</th>
				<th id='ldu-createtime'>Sortable Created</th>
				<th id='dfu-printModified'>Modified</th>
				<th id='ldu-modtime'>Sortable Modified</th>
				<th id='ldu-size'>Size</th>				
				<th id='dfu-rowselector'>Select</th>				
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class="subscreen-buttons" id='update-table-updates'></div>		
</div>
<script>
var uphp = {};
uphp.fetch_args = <?php echo isset($params['fetch_update_args']) && $params['fetch_update_args'] ? $params['fetch_update_args'] : "{}";?>;
uphp.update_datatable = <?php echo isset($params['update_datatable']) && $params['update_datatable'] ? $params['update_datatable'] : "{}";?>;
uphp.multiselect = <?php echo isset($params['updates_multiselect_options']) && $params['updates_multiselect_options'] ? $params['updates_multiselect_options'] : "{}";?>;
uphp.canmulti = <?php echo isset($params['multi_updates_update_allowed']) && $params['multi_updates_update_allowed'] ? "true" : "false"; ?>;
uphp.multiselect_text = "<?php echo isset($params['updates_multiselect_text']) ? $params['updates_multiselect_text'] : "Update status of selected updates to: " ?>";
uphp.multiselect_button_text = "<?php echo isset($params['updates_multiselect_button_text']) ? $params['updates_multiselect_button_text'] : "Update" ?>";

/* updates the status of an ld update */
function updateLDUpdateStatus(ids, status, cnt, pconf, rdatas){
	dacura.tool.clearResultMessages();
	var upd = {"umeta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.multiUpdateStatus(upd, ids, status, pconf, rdatas, "update_table");
}

/* draw empty updates table */
function emptyUpdatesTableHTML(key, tconfig){
	if(typeof tconfig.multiselect == "object" && typeof tconfig.multiselect.container == "string"){
		$('#' + tconfig.multiselect.container).hide();	
	}
	var pconfig = dacura.tool.subscreens[tconfig.screen];
	var mopts = $.extend(true, {}, pconfig.mopts);
	mopts.closeable = false;
	mopts.scrollTo = false;
	dacura.system.showInfoResult("No " + ldtnp + " have been updated in your collection - once you make changes to your " + ldtn + ", records of each update will appear on this page", "No " + ldtnp + " have been updated in " + dacura.system.cid(), pconfig.resultbox, null, mopts)
}
/* initialise updates table */
var initupds = function(pconfig){
	var tabinit = {
		"screen": "update-list", 
		"fetch": function(onwards, pconfig) { dacura.ld.fetchupdatelist(onwards, pconfig, dacura.ld.ldo_type, uphp.fetch_args);},
		"cellClick": function(event, entid, rowdata) {
			window.location.href = dacura.system.pageURL(dacura.system.pagecontext.service, rowdata.collectionid) + "/update/" + entid;
		},
		"refresh": {label: "Refresh " + ldtn + " Update List"},			
		"empty": emptyUpdatesTableHTML,
		"dtsettings": uphp.update_datatable
	};
	if(uphp.canmulti){
		tabinit["multiselect"] = {
			options: uphp.multiselect, 
			intro: uphp.multiselect_text, 
			container: "update-table-updates",
			label:  uphp.multiselect_button_text,
			update: updateLDUpdateStatus 
		};
	}
	dacura.tool.table.init("update_table", tabinit);	
}
/* add init function to initialisation array */
if(typeof initfuncs == "object"){
	initfuncs['update-list'] = initupds;
}
</script>