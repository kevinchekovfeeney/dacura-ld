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
<script>

function printCreated(obj){
	return timeConverter(obj.createtime);
}

function printModified(obj){
	return timeConverter(obj.modtime);
}


function initUpdatesTable(updates, screen){
	dacura.tool.table.init("updates_table", {
		"screen": screen, 
		"dtsettings": <?=$params['updates_datatable']?>,
		cellClick: function(event, entid, rowdata) {
			window.location.href = "update/" + entid;
		}, 
		"multiselect": {
			options: {"accept": "Accept", "pending": "Pending", "reject": "Reject"}, 
			intro: "Update status of selected updates to: ", 
			container: "updates-table-updates",
			label: "Update",
			update: updateUpdateStatus 
		},				
	}, updates);
	$('#updates-table-holder').show();
}


function updateUpdateStatus(ids, status, cnt, pconf, rdatas){
	var nid = ids.shift();
	var rdata = rdatas.shift();
	var upd = {"umeta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	var onwards = function(data, pconf){
		if(!isEmpty(ids)){
			updateUpdateStatus(ids, status, cnt, pconf, rdatas);
		}
		else {
			showUpdateStatusSuccess(status, cnt, pconf);
			//reset url to this context
			dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, dacura.system.cid());			
			refreshUpdateList();
		}
	}
	//for global scope we need to change api url...
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	dacura.ld.update("update/" + nid, upd, onwards, pconf, false);
	
}

function showUpdateStatusSuccess(status, cnt, targets){          
	dacura.system.showSuccessResult(cnt + "updates updated to status " + status, "Update OK", targets.resultbox, false, {'scrollTo': true, "icon": true, "closeable": true});
}

function refreshUpdateList(){
	dacura.tool.table.refresh("updates_table");
}

</script>