<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />


<div id="show-candidate" class="">
	<div id='fragment-data' class="dch"></div>			
	<div id='update-data' class="dch"></div>			
	<div id='control-bar' class="dch">
		<table id='control-bar-table'>
		<tr>
			<td id="control-bar-format">
				<span class='view-mode' id="format">
				    <input type="radio" id="format_json" name="format" checked="checked"><label for="format_json">JSON</label>
				    <input type="radio" id="format_ttl" name="format"><label for="format_ttl">Turtle</label>
				    <input type="radio" id="format_triples" name="format"><label for="format_triples">Triples</label>
				    <input type="radio" id="format_html" name="format"><label for="format_html">HTML</label>
				</span>
				<span class='dch' id="edit-format">
				    <input type="radio" id="edit_json" name="eformat" checked="checked"><label for="edit_json">JSON</label>
				    <input type="radio" id="edit_html" name="eformat"><label for="edit_html">HTML</label>
				</span>
				<span class='update-mode dch' id="update-format">
					<input type="radio" id='update_triples' name="uformat"><label for="update_triples">Triples</label>
					<input type="radio" id="update_json" checked="checked" name="uformat"><label for="update_json">JSON</button>
				</span>
				<span class='update-mode dch' id="json-update-format">
					<input type="radio" id="update_forward" name="jformat"><label for="update_forward">Forward</label>
					<input type="radio" id="update_backward" name="jformat"><label for="update_backward">Backward</label>
					<input type="radio" id="update_full" checked="checked" name="jformat"><label for="update_full">Full</label>					
				</span>
				<span class='edit-mode' id="edit-status">
					Status: <select id='set-status'><?php echo $service->getCandidateStatusOptions();?></select>
				</span>
				
			</td>
			<td id="control-bar-edit">
				<span class='view-mode' id="setedit">
					<button id="display_edit" name="ebar">Edit</button>
				</span>
				<span class='edit-mode' id="editcancel">
				    <button class='edit-mode' id="cancel_edit">Finish Editing</button>
			    </span>
			    <span class='history-mode dch' id="setrestore">
					<button id="restore">Restore this version</button>
				</span>
			    <span class='update-mode dch' id="updatemod">
					<button id="modify">Modify this update</button>
				</span>
				</td>
			<td id="control-bar-options">
				<span id="editbar" class='edit-mode'>
				    <button class='edit-mode' id="test_edit">Test Changes</button>
				    <button class='edit-mode' id="save_edit" name="ebar">Save Changes</button>
				</span>
				<span id="display" class="view-mode">
					<input type="checkbox" id="display_ns" name="display" checked="checked"><label for="display_ns">Namespaces</label>
				    <input type="checkbox" id="display_links" name="display"><label for="display_links">Links</label>
				</span>
			</td>
			
			</tr>	
		</table>
	</div>
	<div id='version-data' class="dch">
		<table id='version-data-table'>
			<tr>
				<td class='version-meta' id='candidate-status'></td>
				<td class='version-meta' id="candidate-type-version"></span>
				<td class='version-meta' id='version-type'></td>
				<td class='version-meta' id='vcontrols'>
					<div id="version-controls">
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
	<div id="update-body-section" class="dch">
		<div id="update-body">
			<div class='update-view' id="update-edit"></div>
			<div class='update-view' id="update-triples"></div>
			<div class='update-view' id="update-json">
				<div class='update-view' id="update-forward"></div>
				<div class='update-view' id="update-backward"></div>
				<div class='update-view' id="update-full"></div>
			</div>
		</div>
	</div>
	<div id="candidate-body-section" class="dch">
		<div id="candidate-body"></div>
	</div>
	<div id="candidate-editor-section">
		<div id="candidate-editor"></div>
	</div>			
	<div id="history-section" class="dch">
		<div class="tool-section-header">
			<span class="section-title">Candidate History</span>
		</div>
		<div id="candidate-history">	
			<table id="history_table" class="display">
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
	</div>
	<div id="pending-section" class="dch">
		<div class="tool-section-header">
			<span class="section-title">Update Queue</span>
		</div>
			<div id="candidate-updates">
			<table id="updates_table" class="display">
				<thead>
				<tr>
					<th>From Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Status</th>
					<th>Change From</th>
					<th>Change To</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>			
		</div>
	</div>
</div>			
<script>



dacura.candidate.currentCandidate = null;
dacura.candidate.viewArgs = {
	format: "<?=isset($params['format'])? $params['format'] : "json" ?>",
	display: "<?=isset($params['display'])? $params['display'] : 'ns' ?>",
	version: <?=isset($params['version'])? $params['version'] : 0 ?>,
	edit: <?=isset($params['edit'])? 1 : 0 ?>,
};

dacura.candidate.getArgString = function() {
	return "?version=" + dacura.candidate.viewArgs.version + "&format=" + dacura.candidate.viewArgs.format + "&display=" + dacura.candidate.viewArgs.display; 
}

dacura.candidate.getSettingString = function(){
	if(dacura.candidate.viewArgs.version == 0) {
		formatstr = "latest version";
	}
	else {
		formatstr = "version " + dacura.candidate.viewArgs.version; 
	}
	return "Loading " + dacura.candidate.viewArgs.format;
}

dacura.candidate.clearCandidate = function (){
	$('#candidate-history').html('<table id="history_table" class="display"><thead><tr><th>Version</th><th>Schema Version</th>' +
		'<th>Created</th><th>Sortable Created</th><th>Changed From</th><th>Changed To</th></tr></thead><tbody></tbody></table>');
	$('#candidate-updates').html('<table id="updates_table" class="display"><thead><tr><th>From Version</th><th>Created</th>' +
		'<th>Sortable Created</th><th>Status</th><th>Change From</th><th>Change To</th></tr></thead><tbody></tbody></table>');
	$('#candidate-body').html("");		
}

dacura.candidate.setTitle = function(data){
	dacura.toolbox.setToolSubtitle("<table class='candidate-invariants'><thead><tr><th>Type</th><th>Dataset</th><th>Created</th></tr>" +
		"</thead><tbody><tr><td id='cand_type'>" + data.type + "</td><td id='cand_owner'>" + data.cid + "/" + data.did + 
		"</td><td id='cand_created'>" + timeConverter(data.created) + "</td></tr></tbody></table>");
	$('.tool-title').html('Candidate  <span class="cand-id">' + data.id + '</span>');
}

dacura.candidate.drawUpdate = function(data, isfirst){	
	$('#update-forward').html("<div class='dacura-json-viewer'>" + JSON.stringify(data.forward, null, 4) + "</div>").hide();
	$('#update-backward').html("<div class='dacura-json-viewer'>" + JSON.stringify(data.backward, null, 4) + "</div>").hide();
	$('#update-full').html("<div class='dacura-json-viewer'>" + JSON.stringify(data.changed.display, null, 4) + "</div>").show();
	$('#update-triples').html(getChangeDetails(data.delta)).hide();
	dacura.candidate.drawCandidate(data.original, isfirst);
	$('.update-mode').show();
	$('.view-mode').hide();
    $('.history-mode').hide();
    $('.edit-mode').hide();
	var fdets = "Created: " + timeConverter(data.created) + ", Updated: " + timeConverter(data.modified);
	fdets += ", Status: " + data.status + ", From Version: " + data.from_version + ", To Version: " + data.to_version;
	fdets += getChangeSummary(data.delta);
	$('#update-data').html("<span class='fragment-title-label'>Update</span> <span class='fragment-title'>" + data.id + "</span><span class='fragment-details'>" + fdets + "</span>");
	$('#update-data').show();
	
	$( "#setrestore").hide();
	
}

dacura.candidate.drawCandidate = function(data, isfirst){		
	dacura.candidate.currentCandidate = data; 
	dacura.candidate.setTitle(data)
	if(data.version == data.latest_version){
		$('#version-type').html("Latest version, created: " + timeConverter(data.modified));
		$( "#forward" ).button("disable");
		$( "#end" ).button("disable");
		$( "#setedit").show();
		$( "#setrestore").hide();
		dacura.toolbox.removeServiceBreadcrumb("bcversion");
	}
	else {
		$('#version-type').html("Version created: " + timeConverter(data.modified) + ", replaced: " + timeConverter(data.replaced));		
		$( "#forward" ).button("enable");
		$( "#end" ).button("enable");
		$( "#setedit").hide();
		$( "#setrestore").show();
		dacura.toolbox.addServiceBreadcrumb("<?=$service->my_url()?>/" +data.id + "?version=" + data.version, "Version " + data.version, "bcversion");
	}
	if(data.version == 1){
		$( "#rewind" ).button("disable");
		$( "#beginning" ).button("disable");
	}
	else {
		$( "#rewind" ).button("enable");
		$( "#beginning" ).button("enable");
	}
	$('#candidate-type-version').html("Schema: v" + data.type_version); 
	$('#candidate-status').html("Status: " + data.status); 
	$('#set-status').attr("selected");
	$('#candidate-version').html(data.version); 
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
	dacura.candidate.drawContents(data.display, data.ldprops, data.status);
	if(data.version > 1){
		$('#history-section').show();	
		dacura.candidate.drawHistoryTable(data.history);
	}
	else {
		$('#history-section').hide();	
	}
	if(typeof data.pending != "undefined" && data.pending.length > 0){
		$('#pending-section').show();	
		dacura.candidate.drawPendingTable(data.pending);	
	}
	else {
		$('#pending-section').hide();
	}
}

dacura.candidate.drawContents = function(contents, code, status){
	if(dacura.candidate.viewArgs.format == "json"){
		$('#candidate-body').html("<div class='dacura-json-viewer'>" + JSON.stringify(contents, null, 4) + "</div>");
	}
	else if(dacura.candidate.viewArgs.format == "ttl"){
		var html = "<div class='dacura-table-viewer'><table class='dacura-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for (var i in contents) {
			var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + contents[i][3] + "</td></tr>";
				html += row;
		}
		$('#candidate-body').html(html + "</table></div>");	
	}
	else if(dacura.candidate.viewArgs.format == "triples"){
		var html = "<div class='dacura-table-viewer'><table class='dacura-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for (var i in contents) {
			var row = "<tr><td>" + contents[i][0] + "</td>";
			row += "<td>" + contents[i][1] + "</td>";
			row += "<td>" + contents[i][2] + " .</td></tr>";
			html += row;
		}
		$('#candidate-body').html(html + "</table></div>");	
	}
	else if(dacura.candidate.viewArgs.format == "html"){
		var html = "<div class='dacura-html-viewer'>";
		html += contents;
		$('#candidate-body').html(html + "</table></div>");	
		//dacura.candidate.collapseAllEmbedded();
		$('.pidembedded').click(function(event){
			$('#'+ event.target.id + "_objrow").toggle();
		});
	}
}

var j;	

dacura.candidate.updateEditor = function(code, status){
	$('#set-status').val(status);
	j.json = code;
	j.rebuild();
}

	
dacura.candidate.addEditor = function(code, status, jq){
	if(typeof jq == "undefined"){
		jq = '#candidate-editor';
	}
	$('.dacura-json-editor').remove();
	$(jq).append("<div class='dacura-json-editor'><textarea id='jsoninput_ta'>" + JSON.stringify(code, null, 4) + "</textarea></div>"); 
	$('.dacura-json-editor').show();
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
	j = new JSONEditor($("#jsoninput_ta"), "100%", "400");
    j.doTruncation(true);
    //j.showText();
	j.showFunctionButtons();
	$('.dacura-json-editor').hide();
	$('#set-status').val(status);
}

function escapeHTML( string ){
    var pre = document.createElement('pre');
    var text = document.createTextNode( string );
    pre.appendChild(text);
    return pre.innerHTML;
}

dacura.candidate.drawHistoryTable = function(data){		
	if(typeof data == "undefined"){
		$('#history_table').dataTable(); 
		dacura.toolbox.writeErrorMessage('.dataTables_empty', "No Candidates Found");		
	}
	else {
		$('#history_table tbody').html("");
		for (var i in data) {
			var obj = data[i];
			var back = obj.backward.substring(0, 40);
			var fwd = obj.forward.substring(0, 40);
			$('#history_table tbody').append("<tr class='version_link' id='candv_" + obj.to_version + "'>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + obj.schema_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td title='" + obj.backward + "'>" + back + "</td>" + 
			"<td title='" + obj.forward + "'>" + fwd + "</td>" + 
			"</tr>"); 
		}
		$('.version_link').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		}).click( function (event){
	    	dacura.candidate.viewArgs.version = this.id.substr(6);
	    	dacura.candidate.fetchCandidate();
			//window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
	    });
		var init = <?=$dacura_server->getServiceSetting('history_datatable_init_string', "{}");?>;
	    $('#history_table').dataTable(init);
	}
}

dacura.candidate.drawPendingTable = function(data){		
	if(typeof data == "undefined"){
		$('#updates_table').dataTable(); 
		dacura.toolbox.writeErrorMessage('.dataTables_empty', "No Candidates Found");		
	}
	else {
		$('#updates_table tbody').html("");
		for (var i in data) {
			var obj = data[i];
			var back = obj.backward.substring(0, 40);
			var fwd = obj.forward.substring(0, 40);
			$('#updates_table tbody').append("<tr id='cur" + obj.curid + "'>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td title='" + obj.backward + "'>" + back + "</td>" + 
			"<td title='" + obj.forward + "'>" + fwd + "</td>" + 
			"</tr>");
			$('#cur'+obj.curid).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#cur'+obj.curid).click( function (event){
				dacura.candidate.showViewUpdatePage(this.id.substr(3), true);
				//alert(this.id.substr(3) + " " + obj.to_version);
				//window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
		    }); 
		}
		var init = <?=$dacura_server->getServiceSetting('opending_datatable_init_string', "{}");?>;
	    $('#updates_table').dataTable(init);
	}
}


dacura.candidate.makeCheckboxesBehave = function (){
    if(dacura.candidate.viewArgs.format == "json"){
    	$("#format_json").prop('checked', true);
    }
    else if(dacura.candidate.viewArgs.format == "ttl"){
    	$("#format_ttl").prop('checked', true);
    }
    else if(dacura.candidate.viewArgs.format == "html"){
    	$("#format_html").prop('checked', true);
    }
    else if(dacura.candidate.viewArgs.format == "triples"){
    	$("#format_triples").prop('checked', true);
    }
    if(dacura.candidate.viewArgs.display.indexOf("links") >= 0){
    	$("#display_links").prop('checked', true);
    }
    else {
    	$("#display_links").prop('checked', false);
    }
    if(dacura.candidate.viewArgs.display.indexOf("ns") >= 0){
    	$("#display_ns").prop('checked', true);
    }
    else {
    	$("#display_ns").prop('checked', false);
    }
    if(dacura.candidate.viewArgs.edit == 0){
    	dacura.candidate.clearEditMode();
    }
    else {
    	dacura.candidate.setEditMode();
    }
}

dacura.candidate.clearEditMode = function(cancel){
	$('.tool-info').html("");
	if(typeof cancel != "undefined" && cancel){
		var unsaved = true;
		var uobj = $('#jsoninput_ta').val();
		try {
			uobj = JSON.parse(uobj);
			if(JSON.stringify(uobj) === JSON.stringify(this.currentCandidate.ldprops) && this.currentCandidate.status === $('#set-status').val()){
				unsaved = false;
			} 
		}
		catch(e){
			
		}
		if(unsaved){
			if(confirm("You have unsaved edits that will be lost if you close editing mode. Hit cancel if you wish to save your work")){
				dacura.candidate.addEditor(this.currentCandidate.ldprops, this.currentCandidate.status);
			}
			else {
				return;
			}
		}
	}
	$("div#control-bar").removeClass('editmode');
	$("div#candidate-body-section").removeClass('editmode');
	$("div#version-data").show();//removeClass('editmode');
	$("#display_edit").prop('checked', false);
	if($("#edit_html").is(':checked')){
		dacura.candidate.viewArgs.format == "html";
    	$("#format_html").click();    		
	}
	$("div#history-section").show();
	$('.edit-mode').hide();
	$('.view-mode').show();
	$('.dacura-json-editor').hide();
	$('.dacura-json-viewer').show();
	$('.dacura-table-viewer').show();
}


dacura.candidate.setEditMode = function(){
	$('.tool-info').html("");
	$("div#control-bar").addClass('editmode');
	$("div#candidate-body-section").addClass('editmode');
	$("div#version-data").hide();//addClass('editmode');
	$("div#history-section").hide();
	$('.dacura-table-viewer').hide();
	$('.dacura-json-viewer').hide();
	$('.dacura-json-editor').show();
	$('.edit-mode').show();
	$('.view-mode').hide();
	$('#jsoninput_ta').css("width", "100%");
	//if($("#format_html").is(':checked')){
		//$("#edit_html").prop('checked', true); 
	//	$('#edit_html').click();
	//	alert("editing html");
	//}
	//else {
		$('#edit_json').click();
	//}
}
dacura.candidate.showEditUpdate = function(){
	$('#update-edit').show();
	$('#update-json').hide();
	$('#update-triples').hide();
	dacura.candidate.currentUpdate.forward["updatemeta"] = dacura.candidate.currentUpdate.meta;
	dacura.candidate.addEditor(dacura.candidate.currentUpdate.forward, dacura.candidate.currentUpdate.status, '#update-edit');
	$("div#control-bar").addClass('editmode');
	$("div#candidate-body-section").addClass('editmode');
	$(".edit-mode").show();
	$(".update-mode").hide();
	//dacura.candidate.currentUpdate.forward.updatemeta = null;
	$('.dacura-json-editor').show();
}


dacura.candidate.showViewUpdatePage = function(id, isfirst){
	$('.tool-info').html("");
	var ajs = dacura.candidate.api.viewUpdate(id, {data: dacura.candidate.viewArgs});
	ajs.beforeSend = function(){
		dacura.candidate.writeBusyMessage(dacura.candidate.getSettingString());
		if(typeof isfirst != "undefined" && isfirst){
			$('#show-candidate').show();
		    $('#version-data').hide();
		    $('#control-bar').show();
		    $('#update-body-section').show();
		}	
	};
	ajs.complete = function(){
		dacura.candidate.clearBusyMessage();
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {    		
		try {
			//dacura.candidate.clearBusyMessage();
			var data = JSON.parse(response);
			dacura.candidate.currentUpdate = data;
			if(typeof isfirst != "undefined" && isfirst){
			    dacura.toolbox.addServiceBreadcrumb("<?=$service->my_url()?>/" +data.original.id , "Candidate " + data.original.id);
			}
			dacura.candidate.clearCandidate();
		    //dacura.candidate.viewArgs.version = data.candidate.version;
			//
			dacura.candidate.drawUpdate(data);	
			//dacura.candidate.addEditor(data.contents, data.status);
		}
		catch(e) 
		{
			$('#show-candidate').hide();
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Error - Could not interpret the server response</strong><br>" + e.message, response); 
		}
	})
	.fail(function (jqXHR, textStatus){
		$('#show-candidate').hide();		   
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to retrieve update</strong><br>" + jqXHR.responseText);
		dacura.candidate.clearBusyMessage();		
	});
}

dacura.candidate.updateCandidate = function(test){
	$('.tool-info').html("");
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
	}
	catch(e){
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>JSON Parsing Error</strong><br>" + e.message);
		return;
	}
	var ajs = dacura.candidate.api.update("<?=$params['id']?>", uobj);
	ajs.beforeSend = function(){
		dacura.candidate.writeBusyMessage("Submitting Update to Candidate API");
	};
	ajs.complete = function(){
		dacura.candidate.clearBusyMessage();		
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {
		try{
			var msg = getChangeSummary(response.delta);
			var dets = getChangeDetails(response.delta);
			if(typeof test != "undefined"){
				dacura.toolbox.writeSuccessMessage('.tool-info', "<strong>Update test was accepted</strong><br>" + msg, dets);			
			}
			else {
				var data = response;
				dacura.candidate.viewArgs.version = data.version;
				dacura.candidate.clearCandidate();
				dacura.candidate.drawCandidate(data);
				dacura.candidate.updateEditor(data.ldprops, data.status);		
				dacura.candidate.setEditMode();
				dacura.toolbox.writeSuccessMessage('.tool-info', "<strong>Update was successful</strong><br>" + msg, dets);				
			}	
		}
		catch(e){
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Error - Could not interpret the server response</strong><br>" + e.message, response);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Updating Failed</strong><br>" + jqXHR.responseText);
	});	
}

dacura.candidate.fetchCandidate = function(isfirst){
	$('.tool-info').html("");
	var ajs = dacura.candidate.api.view("<?=$params['id']?>", {data: dacura.candidate.viewArgs});
	ajs.beforeSend = function(){
		dacura.candidate.writeBusyMessage(dacura.candidate.getSettingString());
		if(typeof isfirst != "undefined" && isfirst){
			$('#show-candidate').show();
		    $('#version-data').show();
		    $('#control-bar').show();
		    $('#candidate-body-section').show();
		}	
	};
	ajs.complete = function(){
		dacura.candidate.clearBusyMessage();
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {    		
		try {
			dacura.candidate.clearBusyMessage();
			var data = JSON.parse(response);
			if(typeof isfirst != "undefined" && isfirst){
			    dacura.toolbox.addServiceBreadcrumb("<?=$service->my_url()?>/" +data.id , "Candidate " + data.id);
			}		
		    dacura.candidate.viewArgs.version = data.version;
			dacura.candidate.clearCandidate();
			dacura.candidate.drawCandidate(data, isfirst);	
			dacura.candidate.addEditor(data.ldprops, data.status);
		}
		catch(e) 
		{
			$('#show-candidate').hide();
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Error - Could not interpret the server response</strong><br>" + e.message, response); 
		}
	})
	.fail(function (jqXHR, textStatus){
		$('#show-candidate').hide();		   
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Failed to retrieve candidate</strong><br>" + jqXHR.responseText);
		dacura.candidate.clearBusyMessage();		
	});
}

dacura.candidate.restore = function(test){
	$('.tool-info').html("");
	var uobj = {};
	uobj.options = jQuery.extend({}, dacura.candidate.viewArgs);
	uobj.options.restore = dacura.candidate.viewArgs.version;
	if(typeof test != "undefined"){
		uobj.test = true;
	}
	var ajs = dacura.candidate.api.update("<?=$params['id']?>", uobj);
	ajs.beforeSend = function(){
		dacura.candidate.writeBusyMessage("Analysing Restore Consequences");
	};
	ajs.complete = function(){
		dacura.candidate.clearBusyMessage();		
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {
		try{
			var msg = getChangeSummary(response.delta);
			var dets = getChangeDetails(response.delta);
			if(typeof test != "undefined"){
				msg += "<div class='confirm-restore-buttons'><button id='confirm-restore'>Confirm Restore</button> <button id='cancel-restore'>Cancel</button></div>";
				dacura.toolbox.writeSuccessMessage('.tool-info', "<strong>Restore Analysis Completed</strong><br>" + msg, dets);
				$('#confirm-restore').button().click(function(){
					dacura.candidate.restore();
				});	
				$('#cancel-restore').button().click(function(){
					$('.tool-info').html("");
				});	
			}
			else {
				var data = response;
				dacura.candidate.viewArgs.version = data.version;
				dacura.candidate.clearCandidate();
				dacura.candidate.drawCandidate(data);
				dacura.candidate.addEditor(data.ldprops, data.status);
				dacura.toolbox.writeSuccessMessage('.tool-info', "<strong>Restore completed successfully</strong><br>" + msg, dets);				
			}	
		}
		catch(e){
			dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Error - Could not interpret the server response</strong><br>" + e.message, response);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('.tool-info', "<strong>Restore Failed</strong><br>" + jqXHR.responseText);
	});	
	
}

function getChangeSummary(changes){
	var added = changes.triples_added.length;
	var deleted = changes.triples_removed.length;
	var updated = changes.triples_updated.length;
	tc = changes.type_changes.length;
	var html = tc + " structural change"; 
	if(tc != 1) html +="s";
	html += ". " + added + " triple";
	if(added != 1) html += "s";
	html += " added. " + deleted + " triple";
	if(deleted != 1) html += "s";
	html += " deleted. " + updated + " triple";
	if(updated != 1) html += "s";
	html += " updated";
	return html;
}

function getChangeDetails(changes){
	var html = "<div class='change-details'>";
	if(typeof changes.tc != "undefined" && changes.type_changes.length > 0){
		html += "<h3>Structural Changes</h3>"; 
		html += "<table class='change-details' id='change-tc'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>From</th><th>To</th></tr>";
		for(var i = 0; i<changes.type_changes.length; i++){
			var ftrips = changes.type_changes[i].del.length;
			if(ftrips==1){
				ftrips = "1 triple";
			}
			else {
				ftrips += " triples";
			}
			var ttrips = changes.type_changes[i].add.length;
			if(ttrips==1){
				ttrips = "1 triple";
			}
			else {
				ttrips += " triples";
			}
			html += "<tr><td>" + changes.type_changes[i].subject + "</td><td>" + changes.type_changes[i].predicate + "</td><td>" + changes.type_changes[i].from + " (" + ftrips + ")</td><td>" + changes.type_changes[i].to + " (" + ttrips + ")</td></tr>";
		}
		html += "</table>";
	}
	
	if(changes.triples_added.length > 0){
		html += "<h3>Triples Added</h3>"; 
		html += "<table class='change-details' id='change-add'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for(var i = 0; i<changes.triples_added.length; i++){
			if(typeof changes.triples_added[i][2] == "object"){
				changes.triples_added[i][2] = JSON.stringify(changes.triples_added[i][2]);
			}
			html += "<tr><td>" + changes.triples_added[i][0] + "</td><td>" + changes.triples_added[i][1] + "</td><td>" + changes.triples_added[i][2] + "</td></tr>";
		}
		html += "</table>";
	}
	if(changes.triples_removed.length > 0){
		html += "<h3>Triples Deleted</h3>"; 
		html += "<table class='change-details' id='change-del'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for(var i = 0; i<changes.triples_removed.length; i++){
			if(typeof changes.triples_removed[i][2] == "object"){
				changes.triples_removed[i][2] = JSON.stringify(changes.triples_removed[i][2]);
			}
			html += "<tr><td>" + changes.triples_removed[i][0] + "</td><td>" + changes.triples_removed[i][1] + "</td><td>" + changes.triples_removed[i][2] + "</td></tr>";
		}
		html += "</table>";
	}
	if(changes.triples_updated.length > 0){
		html += "<h3>Triples Updated</h3>"; 
		html += "<table class='change-details' id='change-upd'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>Old Object</th><th>New Object</th></tr>";
		for(var i = 0; i<changes.triples_updated.length; i++){		
			if(typeof changes.triples_updated[i][2] == "object"){
				changes.triples_updated[i][2] = JSON.stringify(changes.triples_updated[i][2]);
			}
			if(typeof changes.triples_updated[i][3] == "object"){
				changes.triples_updated[i][3] = JSON.stringify(changes.triples_updated[i][3]);
			}
			html += "<tr><td>" + changes.triples_updated[i][0] + "</td><td>" + changes.triples_updated[i][1] + "</td><td>" + changes.triples_updated[i][2] + "</td><td>" + changes.triples_updated[i][3] + "</td></tr>";
		}
		html += "</table>";
	}
	return html;
}

function loadDisplaySetting(){
	var options = [];
	if($("#display_links").is(':checked')){
		options.push("links");
	}
	if($("#display_ns").is(':checked')){
		options.push("ns");
	}
	optstr = options.join("_");
	if(optstr != dacura.candidate.viewArgs.display){
		dacura.candidate.viewArgs.display = optstr;
    	dacura.candidate.fetchCandidate();
	}
}

dacura.candidate.clearBusyMessage = function(){
	dacura.toolbox.removeBusyOverlay('', 0);
}

dacura.candidate.writeBusyMessage  = function(msg, cls) {
	if(typeof cls == "undefined") { cls = '#show-candidate';}
	dacura.toolbox.writeBusyOverlay(cls, msg);
}

dacura.candidate.collapseAllEmbedded = function(){
	$('.embedded-object').css("display",  "none");
}

$('document').ready(function(){
 	$( "#beginning" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-start"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version = 1;
    	dacura.candidate.fetchCandidate();
    });
    $( "#rewind" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-prev"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version--;
    	dacura.candidate.fetchCandidate();
    });
    $( "#forward" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-next"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version++;
    	dacura.candidate.fetchCandidate();
    });
    $( "#end" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-end"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version = 0;
    	dacura.candidate.fetchCandidate();
    });
  	$( "#test_edit" ).button().click(function(){
		dacura.candidate.updateCandidate(true);
	});
	$( "#save_edit" ).button().click(function(){
		dacura.candidate.updateCandidate();
	});
	$( "#display_ns").button().click(function(){
		loadDisplaySetting();
	});
	$( "#display_links").button().click(function(){
		loadDisplaySetting()
	});

	$('#json-update-format').buttonset();
	$('#update_triples').button().click(function(){
		$('#json-update-format').hide();
		$('#update-json').hide();
		$('#update-triples').show();
	});
	$('#update_json').button().click(function(){
		$('#json-update-format').show();
		$('#update-triples').hide();
		$('#update-json').show();
	});
	$('#update-format').buttonset();
	$('#update_forward').button().click(function(){
		$('#update-backward').hide();
		$('#update-full').hide();
		$('#update-forward').show();
	});
	$('#update_backward').button().click(function(){
		$('#update-backward').show();
		$('#update-full').hide();
		$('#update-forward').hide();
	});
	$('#update_full').button().click(function(){
		$('#update-backward').hide();
		$('#update-full').show();
		$('#update-forward').hide();
	});
	
    $('#edit-format').buttonset();
    $('#display_edit').button().click(function(){
       	dacura.candidate.setEditMode();
    });

    $('#modify').button().click(function(){
        dacura.candidate.showEditUpdate();
    });

    $('#restore').button().click(function(){
        dacura.candidate.restore(true);
 	});
    
    $('#cancel_edit').button().click(function(){
       dacura.candidate.clearEditMode(true);
	});
	
    $('#format_json').click(function(){
    	dacura.candidate.viewArgs.format = "json";    
    	dacura.candidate.fetchCandidate();
	});
    $('#format_ttl').click(function(){
    	dacura.candidate.viewArgs.format = "ttl";    
    	dacura.candidate.fetchCandidate();
	});
    $('#format_triples').click(function(){
    	dacura.candidate.viewArgs.format = "triples";    
    	dacura.candidate.fetchCandidate();
	});
    $('#format_html').click(function(){
    	dacura.candidate.viewArgs.format = "html";    
    	dacura.candidate.fetchCandidate();
	});
    dacura.candidate.makeCheckboxesBehave();
    $( "#format" ).buttonset();
    $( "#display" ).buttonset();

    <?php if(isset($params['update_view'])){?>
	    dacura.candidate.showViewUpdatePage("<?= $params['id']?>", true);
	<?php } else {?>
    	dacura.candidate.fetchCandidate(true);
	<?php } ?>    
});
</script>