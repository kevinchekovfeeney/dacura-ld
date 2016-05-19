<div class='dacura-subscreen' id='ldo-updates' title="<?=$params['updates_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['updates_intro_msg']?></div>
	<div class='tholder dch updates-table-holder' id='updates-table-holder'>
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

var initUpdatesTable = function(data, pconf){
	dacura.tool.table.init("updates_table", {
		"screen": "ldo-updates", 
		"dtsettings": xphp.update_datatable,
		cellClick: function(event, entid, rowdata) {
			window.location.href = "update/" + entid;
		},
		empty: emptyUpdateTableHTML,
		"multiselect": {
			options: {"accept": "Accept", "pending": "Pending", "reject": "Reject"}, 
			intro: "Update status of selected updates to: ", 
			container: "updates-table-updates",
			label: "Update",
			update: updateUpdateStatus 
		},				
	}, data.updates);
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

initfuncs["ldo-updates"] = initUpdatesTable;

function updateUpdateStatus(ids, status, cnt, pconf, rdatas){
	dacura.tool.clearResultMessages();
	var upd = {"umeta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.multiUpdateStatus(upd, ids, status, pconf, rdatas, "update_table");
}

</script>