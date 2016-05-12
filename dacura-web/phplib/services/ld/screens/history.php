<div class='tholder dch history-table-holder' id='history-table-holder'>
	<table id="history_table" class="history-table dacura-api-listing">
		<thead>
		<tr>
			<th id="hto-version" title="The Version Number">Version</th>
			<th id="hto-status" title="<?=isset($params['ldtype']) ? $params['ldtype'] : "linked data object"?> status">Status</th>
			<th id="hto-createtime">Sortable Created</th>
			<th id="dfn-printCreated" title="Date and time of version creation">Created</th>
			<th id="hto-created_by">Sortable Created By</th>
			<th id="dfn-printCreatedBy" title="Created by update">Update ID</th>
			<th id="hto-backward" title="Changed From" class="rawjson">Changed From</th>
			<th id="hto-forward" title="Changed To" class="rawjson">Changed to</th>
		</tr>
		</thead>
		<tbody></tbody>
	</table>
</div>
<script>


function initHistoryTable(history, screen){
	dacura.tool.table.init("history_table", {
		"screen": screen, 
		"dtsettings": <?=$params['history_datatable']?>,
		"cellClick": function(event, entid, rowdata) {
			var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
			if(!ldtype){
				args = "?ldtype=" + "<?=isset($_GET['ldtype'])? $_GET['ldtype'] : ""?>" + "&version=" + rowdata.version;
			}
			else {
				args = "?version=" + rowdata.version;
			}
			window.location.href = dacura.system.pageURL() + "/<?=$params['id']?>" + args;
		}				
	}, history);
	$('#history-table-holder').show();
}

</script>