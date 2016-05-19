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
				<th id='dfn-printCreated'>Created</th>
				<th id='lde-createtime'>Sortable Created</th>
				<th id='dfn-printModified'>Modified</th>
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
<script>
//mapping php variables to js variables in advance to keep the code clean 
var lphp = {};
lphp.fetch_args = <?php echo isset($params['fetch_args']) && $params['fetch_args'] ? $params['fetch_args'] : "{}";?>;
lphp.ldo_datatable = <?php echo isset($params['ldo_datatable']) && $params['ldo_datatable'] ? $params['ldo_datatable'] : "{}";?>;
lphp.multiselect = <?php echo isset($params['ldo_multiselect_options']) && $params['ldo_multiselect_options'] ? $params['ldo_multiselect_options'] : "{}";?>;
lphp.canmulti = <?php echo isset($params['multi_ldo_update_allowed']) && $params['multi_ldo_update_allowed'] ? "true" : "false"; ?>;

function updateLDStatus(ids, status, cnt, pconf, rdatas){
	dacura.tool.clearResultMessages();
	var uconfig = $.extend(true, {}, pconf);
	if(typeof uconfig.bopts != "object"){
		uconfig.bopts = {};
	}
	uconfig.bopts.scrollTo = true;
	var upd = {"meta": {"status": status}, "editmode": "update", "format": "json"};
	dacura.ld.multiUpdateStatus(upd, ids, status, uconfig, rdatas, "ld_table", true);
}

function emptyLDOTableHTML(key, tconfig){
	if(typeof tconfig.multiselect == "object" && typeof tconfig.multiselect.container == "string"){
		$('#' + tconfig.multiselect.container).hide();	
	}
	var pconfig = dacura.tool.subscreens[tconfig.screen];
	var mopts = $.extend(true, {}, pconfig.mopts);
	mopts.closeable = false;
	mopts.scrollTo = false;
	dacura.system.showInfoResult("No " + ldtnp + " have been added to the your collection - once you create a new " + ldtn + " it will appear on this page", "No " + ldtnp + " in " + dacura.system.cid(), pconfig.resultbox, null, mopts)
}

var initLDOT = function(pconfig){
	var tabinit = {
		"screen": "ldo-list", 
		"cellClick": function(event, entid, rowdata) {
			var args = "";
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
		"empty": emptyLDOTableHTML,
		"refresh": {label: "Refresh " + ldtn + " List"},			
		"fetch": function(onwards, pconfig) { dacura.ld.fetchldolist(onwards, pconfig, dacura.ld.ldo_type, lphp.fetch_args);},
		"dtsettings": lphp.ldo_datatable
	};
	if(lphp.canmulti){
		tabinit["multiselect"] = {
			options: lphp.multiselect, 
			intro: "Update Selected " + ldtnp + ", set status to: ", 
			container: "ld-table-updates",
			label: "Update",
			update: updateLDStatus 
		};
	}
	dacura.tool.table.init("ld_table", tabinit);	
};

initfuncs["ldo-list"] = initLDOT;
</script>