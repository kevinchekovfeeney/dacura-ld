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
var tooltipconf = <?php echo isset($params['tooltip_config']) ? $params['tooltip_config'] : "{}" ?>;
var history_datatable = <?=$params['history_datatable']?>;

//draw the empty table html for when there is no history 
function emptyHistoryTableHTML(key, tconfig){
	var pconfig = dacura.tool.subscreens[tconfig.screen];
	var mopts = $.extend(true, {}, pconfig.mopts);
	mopts.closeable = false;
	mopts.scrollTo = false;
	dacura.system.showInfoResult("When you update your " + ldtn + " records of its old versions will appear on this page", "Your " + ldtn + " has not been updated since it was created", pconfig.resultbox, null, mopts)
}

//initialise the table 
var initHistoryTable = function(data, pconf){
	if(typeof data.history == "undefined") data.history = [];
	dacura.tool.table.init("history_table", {
		"screen": "ldo-history", 
		"empty": emptyHistoryTableHTML,
		"dtsettings": history_datatable,
		"cellClick": function(event, entid, rowdata) {
			if(!ldtype){
				args = "?ldtype=" + "<?=isset($_GET['ldtype'])? $_GET['ldtype'] : ""?>" + "&version=" + rowdata.version;
			}
			else {
				args = "?version=" + rowdata.version;
			}
			window.location.href = dacura.system.pageURL() + "/" + "<?=$params['id']?>" + args;
		}				
	}, data.history);
	//$('#history_table').tooltip(tooltipconf);	
}

//refresh from api data
var refreshHistoryTable = function(data, pconf){
	if(typeof data.history == "undefined") data.history = [];
	dacura.tool.table.reincarnate("history_table", data.history);
}

if(typeof refreshfuncs == "object"){
	refreshfuncs['ldo-history'] = refreshHistoryTable;
}
if(typeof initfuncs == "object"){
	initfuncs["ldo-history"] = initHistoryTable;
}

</script>