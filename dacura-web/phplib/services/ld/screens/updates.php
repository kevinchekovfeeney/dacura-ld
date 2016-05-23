<div class='dacura-subscreen ld-list' id='ldo-updates' title="<?=$params['updates_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['updates_intro_msg']?></div>
	<div class='tholder updates-table-holder' id='updates-table-holder'>
		<table id="updates_table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="uto-eurid" title='Update ID'>ID</th>
				<th id="uto-status" title='current status of this update'>Status</th>
				<th id="uto-targetid" title='current status of this update'>Target ID</th>
				<th id="uto-from_version" title='Version that this update was applied to'>Applied to version</th>
				<th id="uto-to_version" title='Version that this update created'>created version</th>
				<th id="uto-createtime">Sortable Created</th>
				<th id="dfg-printCreated" title="Date and time of update creation">Created</th>
				<th id="uto-modtime">Sortable modified</th>
				<th id="dfg-printModified" title="Date and time of last update modification">Last Modified</th>
				<th id='uto-size'>Size</th>				
				<th id='dfg-rowselector'>Select</th>
				
				</tr> 
		</thead>
		<tbody>
		</tbody>
		</table>
		<div class="subscreen-buttons" id='updates-table-updates'></div>				
	</div>
</div>

<script>
var xphp = {};
xphp.update_datatable = <?php echo isset($params['updates_datatable']) && $params['updates_datatable'] ? $params['updates_datatable'] : "{}";?>;
xphp.multiselect = <?php echo isset($params['updates_multiselect_options']) && $params['updates_multiselect_options'] ? $params['updates_multiselect_options'] : "{}";?>;
xphp.canmulti = <?php echo isset($params['multi_updates_update_allowed']) && $params['multi_updates_update_allowed'] ? "true" : "false"; ?>;

var initLDOUpdatesTable = function(data, pconf){
	var tabinit = {
		"screen": "ldo-updates", 
		"dtsettings": xphp.update_datatable,
		cellClick: function(event, entid, rowdata) {
			window.location.href = "update/" + entid;
		},
		"fetch": function(onwards, pconfig) { dacura.ld.fetch("<?=$params['id']?>", ldov.ldo.getAPIArgs(), refreshLDOPage, pconf);},
		empty: emptyUpdateTableHTML
	}
	if(xphp.canmulti){
		tabinit.multiselect = {
			options: xphp.multiselect, 
			intro: "Update status of selected updates to: ", 
			container: "updates-table-updates",
			label: "Update",
			update: updateUpdateStatus 
		};
	}			
	if(typeof data.updates == "undefined") data.updates = [];
	dacura.tool.table.init("updates_table", tabinit, data.updates);
};

function emptyUpdateTableHTML(key, tconfig){
	if(typeof tconfig.multiselect == "object" && typeof tconfig.multiselect.container == "string"){
		$('#' + tconfig.multiselect.container).hide();	
	}
	var pconfig = dacura.tool.subscreens[tconfig.screen];
	var mopts = $.extend(true, {}, pconfig.mopts);
	mopts.closeable = false;
	mopts.scrollTo = false;
	dacura.system.showInfoResult("There have been no updates to this " + ldtn + " since it was created", "Once you make changes to your " + ldtn + ", records of each update will appear on this page", pconfig.resultbox, null, mopts)
}

var refreshLDOUpdatesTable = function(data, pconf){
	if(typeof data.updates == "undefined") data.updates = [];
	dacura.tool.table.reincarnate("updates_table", data.updates);
}

refreshfuncs["ldo-updates"] = refreshLDOUpdatesTable;
initfuncs["ldo-updates"] = initLDOUpdatesTable;

function updateUpdateStatus(ids, status, cnt, pconf, rdatas){
	dacura.tool.clearResultMessages();
	var upd = {"umeta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.multiUpdateStatus(upd, ids, status, pconf, rdatas, "updates_table");
}

</script>