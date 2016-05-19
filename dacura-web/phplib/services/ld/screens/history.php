<div class='dacura-subscreen ld-list' id='ldo-history' title="<?=$params['history_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['history_intro_msg']?></div>
	<div class='tholder history-table-holder' id='history-table-holder'>
		<table id="history_table" class="history-table dacura-api-listing">
			<thead>
			<tr>
				<th id="hto-version" title="The Version Number">Version</th>
				<th id="hto-status" title="<?=isset($params['ldtype']) ? $params['ldtype'] : "linked data object"?> status">Status</th>
				<th id="hto-createtime">Sortable Created</th>
				<th id="dfh-printCreated" title="Date and time of version creation">Created</th>
				<th id="hto-created_by">Sortable Created By</th>
				<th id="dfh-printCreatedBy" title="Created by update">Update ID</th>
				<th id="hto-backward" title="Changed From" class="rawjson">Changed From</th>
				<th id="hto-forward" title="Changed To" class="rawjson">Changed to</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>
<script>
var history_datatable = <?=$params['history_datatable']?>;

var initHistoryTable = function(history, screen){
	dacura.tool.table.init("history_table", {
		"screen": screen, 
		"dtsettings": history_datatable,
		"cellClick": function(event, entid, rowdata) {
			var ldtype = ldt;
			if(!ldtype){
				args = "?ldtype=" + "<?=isset($_GET['ldtype'])? $_GET['ldtype'] : ""?>" + "&version=" + rowdata.version;
			}
			else {
				args = "?version=" + rowdata.version;
			}
			window.location.href = dacura.system.pageURL() + "/" + ldid + args;
		}				
	}, history);
}

initfuncs["ldo-history"] = initHistoryTable;



</script>