<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<div id='fragment-header' class="dch">
	<span class='candidate-subhead fragment-title'></span>
	<span class='fragment-details'></span>
	<span class='fragment-path'></span>
</div>			
<div id='version-header' class="dch">
	<span class='candidate-subhead version-title'></span>
	<span class='version-created'></span>
	<span class='version-replaced'></span>
	<span class='version-details'></span>
</div>			

<div id='update-header' class="dch">
	<span class='candidate-subhead update-title'></span>
	<span class='update-created'></span>
	<span class='update-modified'></span>
	<span class='update-details'></span>
</div>			

<div class="dch" id="show-candidate">
	<div id='view-bar'>
		<table>
			<tr>
				<td id="view-bar-left">
					<span class='view-option view-mode dch' id="view-format">
					    <input type="radio" class='foption' id="format_json" name="format" checked="checked"><label for="format_json">JSON</label>
					    <input type="radio" class='foption' id="format_turtle" name="format"><label for="format_turtle">Turtle</label>
					    <input type="radio" class='foption' id="format_triples" name="format"><label for="format_triples">Triples</label>
					    <input type="radio" class='foption' id="format_typed_triples" name="format"><label for="format_typed_triples">Typed Triples</label>
					    <input type="radio" class='foption' id="format_html" name="format"><label for="format_html">HTML</label>
					</span>
					<span class='view-option view-update-mode dch' id="view-update-format">
						<input type="radio" class="ufoption" id='update_triples' name="uformat"><label for="update_triples">Triples</label>
						<input type="radio" class="ufoption" id="update_json" checked="checked" name="uformat"><label for="update_json">JSON</button>
					</span>
				</td>
				<td id="view-bar-centre">
					<span class='view-option view-update-mode dch' id="view-update-stage">
						<input type="radio" class="uview" id="stage_before" name="jformat"><label for="stage_before">Before</label>
						<input type="radio" class="uview" id="stage_forward" name="jformat"><label for="stage_forward">Forward</label>
						<input type="radio" class="uview" id="stage_full" checked="checked" name="jformat"><label for="stage_full">Full</label>
						<input type="radio" class="uview" id="stage_backward" name="jformat"><label for="stage_backward">Backward</label>
						<input type="radio" class="uview" id="stage_after" name="jformat"><label for="stage_after">After</label>					
					</span>
				</td>
				<td id="view-bar-right">
					<span id="display-options" class="view-option view-mode dch">
						<input type="checkbox" class="doption" id="display_ns" name="display" checked="checked"><label for="display_ns">Namespaces</label>
					    <input type="checkbox" class="doption" id="display_links" name="display"><label for="display_links">Links</label>
					    <input type="checkbox" class="doption" id="display_structure" name="display"><label for="display_structure">Structure</label>
					    <input type="checkbox" class="doption" id="display_problems" name="display"><label for="display_problems">Problems</label>
					</span>
				</td>
				
			</tr>	
		</table>
	</div>
	<div id='edit-bar' class='edit-mode dch'>
		<table id='edit-table'>
			<tr>
				<td id='edit-left'>
				<span id="edit-meta">
					Status: 
					<select class='edit-mode' id='set-status'><?php echo $service->getCandidateStatusOptions();?></select>
				</span>
				</td>
				<td id='edit-centre'>
					<span id="edit-update-meta" class="dch">
						Update Status: 
						<select class='edit-mode' id='set-updatestatus'><?php echo $service->getUpdateStatusOptions();?></select>
					</span>
				</td>
				<td id='edit-right'>
    	    		<button class='edit-mode' id="cancel_edit">Finish Editing</button>
		    		<button class='edit-mode' id="test_edit">Test Changes</button>
		    		<button class='edit-mode' id="save_edit">Submit Changes</button>
				</td>
			</tr>
		</table>
	</div>
	<div id='action-bar'>
		<table id='action-table'>
			<tr>
				<td id='action-left'>
					<button id="action-restore" class="dch">Restore this version</button>
					<button id="action-modify" class="dch">Modify this update</button>					
					<button id="action-edit" class="dch">Edit</button>
				</td>
				<td id='action-centre'>
				</td>
				<td id='vcontrols' class="dch">
					<div id="version-controls" class="dch">
						<span id="version-label">version</span>
						<button id="beginning">go to first</button>
						<button id="rewind">back</button>
						<span id="candidate-version"></span> 
						<button id="forward">forward</button>
						<button id="end">latest version</button>
					</div>
				</td>
			</tr>
		</table>
	</div>
	<div id="candidate-main-body">
		<div id="candidate-viewer"></div>
		<div id="candidate-json-editor"></div>
	</div>
	<div id="candidate-sub-sections" >
		<div id="history-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Candidate History</span>
			</div>
		</div>
		<div id="pending-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Candidate Update Queue</span>
			</div>
		</div>
	</div>
</div>	
<div id="tabletemplates" style="display:none">
	<div id="header-template">
		<table class='candidate-invariants'>
			<thead>
				<tr>
					<th>Status</th>
					<th>Type</th>
					<th>Dataset</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class='cand_status'></td>
					<td class='cand_type'></td>
					<td class='cand_owner'></td>
					<td class='cand_created'></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="history-template">
		<table class="history_table display">
			<thead>
				<tr>
					<th>Version</th>
					<th>Schema Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Changed From</th>
					<th>Changed To</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>		
	</div>
	<div id="updates-template">
		<table class="updates_table display">
			<thead>
			<tr>
				<th>Status</th>
				<th>From Version</th>
				<th>To Version</th>
				<th>Created</th>
				<th>Sortable Created</th>
				<th>Updated</th>
				<th>Sortable Updated</th>
				<th>Change From</th>
				<th>Change To</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>			
	</div>	
</div>
<script>

/*
 * Called once per page load - sets the candidate context of the view page
 */
function setCandidateContext(cand){
	var txt = "Candidate ";
	if(cand.status == "accept"){
		txt = "Report ";
	}
	var tit = cand.id;
	if(typeof cand.title != "undefined"){
		tit = cand.title;
	}
	$('.tool-title').html(txt + tit);
    dacura.toolbox.setToolSubtitle($('#header-template').html());
    $('.cand_type').html(cand.type);
	$('.cand_owner').html(cand.cid + "/" + cand.did);
	$('.cand_created').html(timeConverter(cand.created));
	$('.cand_latest_version').html(cand.latest_version);
	$('.cand_status').html(cand.latest_status);
    dacura.toolbox.addServiceBreadcrumb("<?=$service->my_url()?>/" + cand.id , txt + tit);	
}

function showEditor(){
	$('#candidate-json-editor').append("<div class='dacura-json-editor'><textarea id='jsoninput_ta'>" + JSON.stringify(dacura.candidate.currentCandidate.ldprops, null, 4) + "</textarea></div>"); 
	$('.dacura-json-editor').show();
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
	JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
	dacura.candidate.jsoneditor = new JSONEditor($("#jsoninput_ta"), "100%", "400");
	dacura.candidate.jsoneditor.doTruncation(true);
	dacura.candidate.jsoneditor.showFunctionButtons();
}

function hideEditor(){
	$('.dacura-json-editor').remove();
}

function clearEditMode(){
	hideEditor();
	$('#edit-bar').hide();
	$('#action-bar').show();
	$('#view-bar').show();
	$('#candidate-viewer').show();
}

function setEditMode(){
	$('#edit-bar').show();
	$('#action-bar').hide();
	$('#view-bar').hide();
	$('#edit-save').show();
	$('#candidate-viewer').hide();
	showEditor();
}

function setViewMode(data){
	$('#edit-bar').hide();
	$('#action-bar').show();
	$('#view-bar').show();
	showVersionControls(data);
	if(typeof data.fragment_id != "undefined"){

	}
	if(typeof data.delta != "undefined"){
		$('#view-update-stage').show();
		$('#edit-update-meta').show();
		$('#set-updatestatus').val(data.delta.status);
		drawUpdateHeader();
	}
	else {
		$('#edit-update-meta').hide();
		$('#view-update-stage').hide();
	}
	$('#view-format').show();		
	$('#display-options').show();		
	dacura.candidate.display(data.display);
}

function showUpdateStage(stage){
	if(stage == "backward"){
		dacura.candidate.display(dacura.candidate.currentCandidate.delta.backward);
	}
	else if(stage == "forward"){
		dacura.candidate.display(dacura.candidate.currentCandidate.delta.forward);		
	}
	else if(stage == "before"){
		dacura.candidate.display(dacura.candidate.currentCandidate.original);		
	}
	else if(stage == "after"){
		dacura.candidate.display(dacura.candidate.currentCandidate.display);			
	}
	else {
		dacura.candidate.display(dacura.candidate.currentCandidate.delta.display);				
	}
	//dacura.candidate.display(screen);
}

dacura.candidate.display = function (contents){
	
	if(dacura.candidate.viewArgs.format == "json"){
		$('#candidate-viewer').html("<div class='dacura-json-viewer'>" + JSON.stringify(contents, null, 4) + "</div>");
	}
	else if(dacura.candidate.viewArgs.format == "turtle"){
		var html = "<div class='dacura-table-viewer'><table class='dacura-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for (var i in contents) {
			var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + contents[i][3] + "</td></tr>";
				html += row;
		}
		$('#candidate-viewer').html(html + "</table></div>");	
	}
	else if(dacura.candidate.viewArgs.format == "triples" || dacura.candidate.viewArgs.format == "typed_triples"){
		var html = "<div class='dacura-table-viewer'><table class='dacura-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for (var i in contents) {
			var row = "<tr><td>" + contents[i][0] + "</td>";
			row += "<td>" + contents[i][1] + "</td>";
			row += "<td>" + contents[i][2] + " .</td></tr>";
			html += row;
		}
		$('#candidate-viewer').html(html + "</table></div>");	
	}
	else if(dacura.candidate.viewArgs.format == "html"){
		var html = "<div class='dacura-html-viewer'>";
		html += contents;
		$('#candidate-viewer').html(html + "</table></div>");	
		//dacura.candidate.collapseAllEmbedded();
		$('.pidembedded').click(function(event){
			$('#'+ event.target.id + "_objrow").toggle();
		});
	}
	$('#candidate-viewer').show();
}

function drawVersionHeader(data){
	
}

function drawFragmentHeader(data){
	if(typeof data.fragment_id != "undefined"){
		fids = data.fragment_id.split("/");
		fid = fids[fids.length -1];
		fdets = data.fragment_details;
		fpaths = data.fragment_paths;
		fpathhtml = "<div class='fragment-paths'>";
		for(i in fpaths){
			fpathhtml += "<span class='fragment-path'>";
			fpathhtml += "<span class='fragment-step'>" + data.id + "</span><span class='fragment-step'>";
			fpathhtml += fpaths[i].join("</span><span class='fragment-step'>");
			fpathhtml += "</span><span class='fragment-step'>" + data.fragment_id + "</span></span>";
		}
		fpathhtml += "</div>";
		$('#fragment-data').html("<span class='fragment-title-label'>Fragment</span> <span class='fragment-title'>" + fid + "</span><span class='fragment-details'>" + fdets + "</span>" + fpathhtml);
		$('#fragment-data').show();
	}	
}

function drawUpdateHeader(data){
	
}


function showVersionControls(data){
	if(!(data.version == 1 && data.version == data.latest_version)){
		if (data.version == 1){
			$("#rewind" ).button("disable");
			$("#beginning" ).button("disable");
		}
		else {
			$( "#rewind" ).button("enable");
			$("#beginning" ).button("enable");
		}
		if(data.version == data.latest_version){
			$( "#forward" ).button("disable");
			$( "#end" ).button("disable");
			$('#action-edit').show();
			$('#action-restore').hide();
			dacura.toolbox.removeServiceBreadcrumb("bcversion");							
		}
		else {
			drawVersionHeader(data);
			$('#action-edit').hide();
			$('#action-restore').show();
			$( "#forward" ).button("enable");
			$( "#end" ).button("enable");
			dacura.toolbox.addServiceBreadcrumb("<?=$service->my_url()?>/" +data.id + "?version=" + data.version, "Version " + data.version, "bcversion");									
		}
		$('#candidate-version').html(data.version); 	
		$('#vcontrols').show();
	}
	else {
		//hideVersionHeader();
		$('#vcontrols').hide();
		$('#action-restore').hide();
		$('#action-edit').show();
	}		
}

function initialiseDecorations(){
	//the version controls
	$( "#beginning" ).button({
      	text: false,
      	icons: {
        	primary: "ui-icon-seek-start"
      	}
    }).click(function(){
    	dacura.candidate.viewArgs.version = 1;
    	dacura.candidate.fetchCandidate("version");
    });
	$( "#rewind" ).button({
      	text: false,
	    icons: {
	        primary: "ui-icon-seek-prev"
	    }
    }).click(function(){
    	dacura.candidate.viewArgs.version--;
    	dacura.candidate.fetchCandidate("version");
    });
    $( "#forward" ).button({
      	text: false,
      	icons: {
        	primary: "ui-icon-seek-next"
    	}
    }).click(function(){
    	dacura.candidate.viewArgs.version++;
    	dacura.candidate.fetchCandidate("version");
    });
    $( "#end" ).button({
      	text: false,
      	icons: {
        primary: "ui-icon-seek-end"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version = 0;
    	dacura.candidate.fetchCandidate("version");
    });
	//The editing buttons
    $( "#test_edit" ).button({
      	icons: {
        	primary: "ui-icon-check"
      	}
  	}).click(function(){
		dacura.candidate.updateCandidate(true);
	});
	$( "#save_edit" ).button({
      	icons: {
        	primary: "ui-icon-arrowthickstop-1-n"
      	}
  	}).click(function(){
		dacura.candidate.updateCandidate();
	});
	$('#action-edit').button({
      	icons: {
        	primary: "ui-icon-pencil"
      	}
  	}).click(function(){
		setEditMode();
	});
	$('#action-restore').button({
      	icons: {
        	primary: "ui-icon-refresh"
      	}
  	}).click(function(){
		//setEditMode();
	});
	
    $('#cancel_edit').button({
      	icons: {
        	primary: "ui-icon-closethick"
      	}
  	}).click(function(){
		clearEditMode();
        //setViewMode(true);
	});
	
	//view format choices
	$( "#view-format" ).buttonset();
	$( ".foption" ).click(function(event){
		dacura.candidate.viewArgs.format = event.target.id.substr(7);
		dacura.candidate.fetchCandidate("format");
    });

	//view update  format choices
	$( "#view-update-format" ).buttonset();
	$( ".ufoption" ).click(function(event){
		dacura.candidate.viewArgs.format = event.target.id.substr(7);
		dacura.candidate.fetchCandidate("format");
    });

	$( "#view-update-stage" ).buttonset();
	$( ".uview" ).click(function(event){
		showUpdateStage(event.target.id.substr(6));
		//alert(event.target.id.substr(6));
	});
	
	//display options
	$( "#display-options" ).buttonset();
	$( ".doption" ).click(function(e){
		dacura.candidate.fetchCandidate("display");
	});
	
}

function loadOptions(){
	var options = [];
	if($("#display_links").is(':checked')){
		options.push("links");
	}
	if($("#display_ns").is(':checked')){
		options.push("ns");
	}
	if($("#display_problems").is(':checked')){
		options.push("problems");
	}
	if($("#display_structure").is(':checked')){
		options.push("structure");
	}
	
	dacura.candidate.viewArgs.display = options.join("_");
	return options;
}

function getArgString() {
	var options = loadOptions();
	return "?version=" + dacura.candidate.viewArgs.version + "&format=" + dacura.candidate.viewArgs.format + "&display=" + options.join("_"); 
}

function getBusyString(prompt){
	return "loading candidate (prompt)";	
} 

function writeBusyMessage(msg, cls) {
	if(typeof cls == "undefined") { cls = '#show-candidate';}
	dacura.toolbox.writeBusyOverlay(cls, msg);
}

function clearBusyMessage(){
	dacura.toolbox.removeBusyOverlay('', 0);
}

function clearResultMessage(){
	$('.tool-info').html("");	
}

dacura.candidate.fetchCandidate = function(prompt, isfirst, id){
	if(typeof id == "undefined") id = "<?=$params['id']?>"; 
	type = "Candidate";
	if(isUpdateID(id)){ type = "Update";}
	loadOptions();
	var ajs = dacura.candidate.api.view(id, {data: dacura.candidate.viewArgs});
	ajs.beforeSend = function(){
		clearResultMessage();
		writeBusyMessage(getBusyString(prompt));
	};
	ajs.complete = function(){
		clearBusyMessage();
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {    		
		try {
			var data = JSON.parse(response);
			if(jqXHR.status == 200){
				if(typeof isfirst != "undefined" && isfirst){
					setCandidateContext(data);
				}
				dacura.candidate.currentCandidate = data;
				dacura.candidate.viewArgs.version = data.version;
				$('#set-status').val(data.status);
				setViewMode(data);
			}
			else if(jqXHR.status == 202){
				$('#show-candidate').hide();
				dacura.candidate.showFetchFailure(data, '.tool-info', type);											
			}
			else {
				$('#show-candidate').hide();				
				dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Unknown response code</strong><br>" + jqXHR.responseText);				
			}
		}
		catch(e) 
		{
			$('#show-candidate').hide();
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Error - Could not interpret the server response</strong><br>" + e.message, response); 
		}
	})
	.fail(function (jqXHR, textStatus){
		$('#show-candidate').hide();		   
		try {
			data = JSON.parse(jqXHR.responseText);
			if(data){
				dacura.candidate.showFetchFailure(data, '.tool-info', type);								
			}
			else {
				dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to retrieve candidate</strong><br>" + jqXHR.responseText);				
			}
		}
		catch(e){
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to retrieve candidate</strong><br>" + e.message, jqXHR.responseText);							
		}	
		clearBusyMessage();		
	});
}

function isUpdateID(id){
	return id.substr(0,7) == "update/";
}

dacura.candidate.updateCandidate = function(test){
	loadOptions();
	var uobj = $('#jsoninput_ta').val();
	try {
		uobj = JSON.parse(uobj);
		uobj.options = jQuery.extend({}, dacura.candidate.viewArgs);
		if(typeof test != "undefined"){
			uobj.test = true;
		}
		if($('#set-status').val() != this.currentCandidate.status){
			uobj.meta = { "status" : $('#set-status').val()};
		}
		if(typeof this.currentCandidate.delta != "undefined" && $('#set-updatestatus').val() != this.currentCandidate.delta.status){
			uobj.updatemeta = { "status": $('#set-updatestatus').val() };
		}
	}
	catch(e){
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>JSON Parsing Error</strong><br>" + e.message);
		return;
	}
	var ajs = dacura.candidate.api.update("<?=$params['id']?>", uobj);
	ajs.beforeSend = function(){
		clearResultMessage();
		writeBusyMessage("Submitting Update to Candidate API");
	};
	ajs.complete = function(){
		clearBusyMessage();		
		$("body, html").animate({ 
            scrollTop: $('.tool-info').offset().top - 50
        }, 600);		
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			try{
				if(data){
					dacura.candidate.showUpdateDecision(data, test, '.tool-info');								
				}
				else {
					dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to read response from server </strong><br>" . jqXHR.responseText);				
				}
			}
			catch(e){
				dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to parse response from server </strong><br>" + e.message, jqXHR.responseText);								
			}
		}).fail(function (jqXHR, textStatus){
			try {
				data = JSON.parse(jqXHR.responseText);
				if(data){
					dacura.candidate.showUpdateDecision(data, test, '.tool-info');								
				}
				else {
					dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to read error response from server </strong><br>" + jqXHR.responseText);				
				}
			}
			catch(e){
				dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to parse error response from server </strong><br>" + e.message, jqXHR.responseText);							
			}	
		});
}



dacura.candidate.viewArgs = {
	format: "<?=isset($params['format'])? $params['format'] : "json" ?>",
	display: "<?=isset($params['display'])? $params['display'] : 'ns' ?>",
	version: <?=isset($params['version'])? $params['version'] : 0 ?>,
	edit: <?=isset($params['edit'])? 1 : 0 ?>,	
};

dacura.candidate.activeID = "<?=$params['id']?>";
dacura.candidate.pagetype = "candidate";

$('document').ready(function(){
	$( document ).tooltip();
	initialiseDecorations();
	dacura.toolbox.initTool({});
	$('#show-candidate').show();
   	dacura.candidate.fetchCandidate("init", true, "<?=$params['id']?>");
});
</script>


