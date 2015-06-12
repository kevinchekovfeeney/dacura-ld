<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<style>
    
  #version-data {
	margin-top: -10px;
	width: 102.5%;
	margin-bottom: 6px;
	margin-right: 0px;
	margin-left: -10px;
	padding: 0;
	font-size: 0.8em;
	color: #222;
    display: inline-block;
    border-bottom: 1px solid #888;
 }
 
 #version-data-table {
 	border-collapse: collapse;
 	width: 100%;
 	border-bottom: 1px solid #888;
 	border-top: 1px solid white;
 }
 
  #version-data-table td {
 	padding: 2px 4px; 
 	border: 0;
 	text-align: center;
 	border-right: 1px dotted #eee;
 	border-bottom: 1px solid white;
  }
 
 td#vcontrols {
 	text-align: right;
 }
 
  #version-controls {
    display: inline-block;
    font-size: 0.7em;
  }
  
  #fragment-data {
  	width: 100%;
  	background-color: #fafaff;
  	margin: 0px 0 10px 0;
  }
  .fragment-title {
  	font-weight: bold;
  	margin-right: 20px;
  }
  
  .fragment-paths {
  	font-size: 0.65em;
  }
  
  .fragment-path {
  	display: block;
  }
  
  .fragment-step {
  	padding-left: 6px;
  	padding-right: 6px;
  	border-right: 1px solid #888;
  }
  
  div#control-bar {
  	background-color: #d5f9e9;
	margin-bottom: 0;
	font-size: 0.6em;
	border-radius: 3px 3px 0 0;
	border-bottom: 1px solid #888;
  }
      
  div#control-bar table {
  	width: 100%;
  }
  
  div#control-bar table td {
  	width: 33%;
  }
  
  td#control-bar-options {
  	text-align: right;
  }

  td#control-bar-edit {
  	text-align: center;
  }
  
  div#candidate-body {
 	border: 1px solid #aaa;
 	background-color: #fdfdfd;
 	padding-left: 4px;
 	padding-top: 2px;
  }
  
  
 .dacura-json-viewer {
 	white-space: pre;
 	unicode-bidi: embed;
 	font-size: 0.85em;
 }

.dacura-table-viewer {
 	unicode-bidi: embed;
 	font-size: 0.85em;
 }

 #version-label {
  	font-size: 1.3em;
  	font-weight: bold;
 	vertical-align: middle;
 	padding: 0px 4px;	
}
 
 #candidate-version {
 	font-size: 1.4em;
 	padding: 0px 6px;
 	vertical-align: middle;
 }
 


.version-meta {
	padding: 6px 15px 8px 5px;
	margin-bottom: 0px;
	border-left: 1px solid #aaa;
	border-right: 1px solid #aaa;
	background-color: #e4e4e4;
	border-top: #fafafa 1px solid;
	margin-right: -4px;
	
}

#version-data dl,
#version-data dt,
#version-data dd {
	display: inline;
}

#candidate-meta-details dt {
	font-size: 14px;
	padding-left: 4px;
	padding-top: 2px;
	font-weight: bold;

}

#candidate-meta-details dd {
	font-size: 14px;
	background-color: white;
	padding: 2px 2px;
	margin-left: 2px;
	margin-right: 6px;
}

dl#candidate-meta-details {
	width: 100%;
	background-color: black;
	margin-top: 0;
}

div#candidate-meta {
	padding-top: 10px;
}

table.candidate-invariants {
	margin-top: -8px;
	margin-right: -12px;
}

table.candidate-invariants th {
	font-size: 0.75em;
	padding-left: 2px;
	font-weight: normal;
}

table.candidate-invariants td {
font-size: 0.8em;
padding-top: 2px;
padding-bottom: 3px;
padding-right: 20px;
padding-left: 10px;
border-left: #888 solid 1px;
	font-weight: bold;
	color: #111;
}

#display-bar {
	font-size: 0.6em;
}

</style>
<script>
	dacura.candidate = {}
	dacura.candidate.apiurl = "<?=$service->get_service_url('candidate', array(), true)?>";
	dacura.candidate.api = {};
	dacura.candidate.api.view = function (id, xhr){
		if(typeof xhr == "undefined"){
			xhr = {};
			xhr.data ={};
		}
		xhr.url = dacura.candidate.apiurl + "/" + id;
		return xhr;
	}

</script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<div id='pagecontent-container'>
	<div id='pagecontent'>
		<div id="pagecontent-nopadding">
			<div class="tool-header">
	   			<span class="tool-title">Candidate  <span class="cand-id"></span></span>
				<span class="tool-description">
					<table class='candidate-invariants'>
						<thead>
							<tr><th>Type</th><th>Dataset</th><th>Created</th></tr>
						</thead>
						<tbody>
							<tr><td id="cand_type"></td><td id="cand_owner"></td><td id="cand_created"></td></tr>
						</tbody>						
					</table>
				</span>
	   		</div>
			<div id="show-candidate">
				<div id='version-data'>
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
				<div id='fragment-data'></div>			
				<div id='control-bar'>
					<table id='control-bar-table'>
					<tr>
						<td id="control-bar-format">
							<span class='view-mode' id="format">
							    <input type="radio" id="format_json" name="format" checked="checked"><label for="format_json">JSON</label>
							    <input type="radio" id="format_ttl" name="format"><label for="format_ttl">Turtle</label>
							    <input type="radio" id="format_triples" name="format"><label for="format_triples">Triples</label>
							    <input type="radio" id="format_html" name="format"><label for="format_html">HTML</label>
							</span>
							<span class='edit-mode' id="edit-format">
							    <input type="radio" id="edit_json" name="eformat" checked="checked"><label for="edit_json">JSON</label>
							    <input type="radio" id="edit_html" name="eformat"><label for="edit_html">HTML</label>
							</span>
						</td>
						<td id="control-bar-edit">
							<span class='view-mode' id="setedit">
								<button id="display_edit" name="ebar">Edit</button>
							</span>
							<span class='edit-mode' id="editcancel">
							    <button class='edit-mode' id="cancel_edit">Cancel Edit</button>
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
				<div id="candidate-body">
				</div>			
				<div id="history-section">
					<div class="tool-section-header">
		   				<span class="section-title">Candidate History</span>
					</div>
					<div id="candidate-history">	
						<table id="history_table" class="dch">
							<thead>
								<tr>
									<th>Version</th>
									<th>Schema Version</th>
									<th>Created</th>
									<th>Changed From</th>
									<th>Changed To</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>		
					</div>
				</div>
				<div id="pending-section">
					<div class="tool-section-header">
		   				<span class="section-title">Pending Updates</span>
					</div>
					<div id="candidate-updates">
						<table id="updates_table" class="dch">
							<thead>
							<tr>
								<th>From Version</th>
								<th>Created</th>
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
			<br>
		</div>
   	</div>
</div>
<script>

function timeConverter(UNIX_timestamp){
  var a = new Date(UNIX_timestamp*1000);
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var year = a.getFullYear();
  var month = months[a.getMonth()];
  var date = a.getDate();
  var hour = a.getHours();
  var min = a.getMinutes();
  if(min < 10) min = "0" + min;
  if(sec < 10) sec = "0" + sec;
  var sec = a.getSeconds();
  var time = date + ' ' + month + ' ' + year + ' ' + hour + ':' + min + ':' + sec ;
  return time;
}

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

dacura.candidate.clearCandidate = function (){
	$('#candidate-history').html('<table id="history_table" class="dch"><thead><tr><th>Version</th><th>Schema Version</th>' +
			'<th>Created</th><th>Changed From</th><th>Changed To</th></tr></thead><tbody></tbody></table>');
	$('#candidate-updates').html('<table id="updates_table" class="dch"><thead><tr><th>From Version</th><th>Created</th>' +
	'<th>Status</th><th>Change From</th><th>Change To</th></tr></thead><tbody></tbody></table>');
	$('#candidate-body').html("");		
}

dacura.candidate.drawCandidate = function(data){		
	dacura.candidate.currentCandidate = data; 
	$('#cand_owner').html(data.cid + "/" + data.did);
	$('#cand_type').html(data.type);
	$('#cand_created').html(timeConverter(data.created));
	$('.cand-id').html(data.id); 
	if(data.version == data.latest_version){
		$('#version-type').html("Latest Version, Created: " + timeConverter(data.modified));
		$( "#forward" ).button("disable");
		$( "#end" ).button("disable");
	}
	else {
		$('#version-type').html("Created: " + timeConverter(data.modified) + ". Replaced: " + timeConverter(data.modified));		
		$( "#forward" ).button("enable");
		$( "#end" ).button("enable");
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
	dacura.candidate.drawContents(data.contents);
	/*JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#candbody"), "800", "400");
    j.doTruncation(true);
    j.showText();
	j.showFunctionButtons();*/
	if(data.version > 1){
		dacura.candidate.drawHistoryTable(data.history);
		$('#history-section').show();	
		//$('#history_table').dataTable().show(); 
	}
	else {
		$('#history-section').hide();	
	}
	if(typeof data.pending != "undefined" && data.pending.length > 0){
		dacura.candidate.drawPendingTable(data.pending);	
		//
		$('#pending-section').show();	
	}
	else {
		$('#pending-section').hide();
	}
}

dacura.candidate.drawContents = function(contents){
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
			var row = "<tr><td>&lt;" + contents[i][0] + "&gt; </td>";
			row += "<td>&lt;" + contents[i][1] + "&gt; </td>";
			row += "<td>&lt;" + contents[i][2] + "&gt; .</td></tr>";
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

function escapeHTML( string )
{
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
			$('#history_table tbody').append("<tr class='version_link' id='candv_" + obj.to_version + "'>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + obj.schema_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.backward + "</td>" + 
			"<td>" + obj.forward + "</td>" + 
			"</tr>"); 
		}
		$('.version_link').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		}).click( function (event){
	    	dacura.candidate.viewArgs.version = this.id.substr(6);
	    	dacura.candidate.makeRequest();
			//window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
	    });
		$('#history_table').dataTable(<?=$dacura_server->getServiceSetting('history_datatable_init_string', "{}");?>).show();
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

dacura.candidate.clearEditMode = function(){
	$("#display_edit").prop('checked', false);
	$("div#candidate-body").css("border", "1px solid #aaa");
	$("div#control-bar").css("border", "1px solid #aaa");
	$("div#control-bar").css("background-color", "#d5f9e9");
	$("div#candidate-body").css("background-color", "#fdfdfd");
	if($("#edit_html").is(':checked')){
		dacura.candidate.viewArgs.format == "html";
    	$("#format_html").click();    		
	}
	$('.edit-mode').hide();
	$('.view-mode').show();
	dacura.candidate.makeRequest();
}


dacura.candidate.setEditMode = function(){
	$('.edit-mode').show();
	$('.view-mode').hide();
	$("div#candidate-body").css("border", "1px solid #88f");
	$("div#control-bar").css("border", "1px solid blue");
	$("div#control-bar").css("background-color", "#d5e9f9");
	$("div#candidate-body").css("background-color", "#f0f0ff");
	
	if($("#format_html").is(':checked')){
		//$("#edit_html").prop('checked', true); 
		$('#edit_html').click();
	}
	else {
		$('#edit_json').click();
	}
	//$("#editmode").hide();
	//$("#editbar").show();
	//$("#control-bar-options").hide();
	//$("#format").hide();
	//$("#edit-format").show();
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
			$('#updates_table tbody').append("<tr id='cur" + obj.curid + "'>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.backward + "</td>" + 
			"<td>" + obj.forward + "</td>" + 
			"</tr>");
			$('#cur'+obj.curid).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#cur'+obj.curid).click( function (event){
				alert(this.id.substr(3) + " " + obj.to_version);
				//window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
		    }); 
		}
		$('#updates_table').dataTable(<?=$dacura_server->getServiceSetting('candidate_datatable_init_string', "{}");?>).show();
	}
}

dacura.candidate.makeRequest = function(){
	var ajs = dacura.candidate.api.view("<?=$params['id']?>", {data: dacura.candidate.viewArgs});
	ajs.beforeSend = function(){
	};
	ajs.complete = function(){
		
	};
	$.ajax(ajs)
	.done(function(response, textStatus, jqXHR) {
		var data = JSON.parse(response);
		dacura.candidate.viewArgs.version = data.version;
		dacura.candidate.clearCandidate();
		dacura.candidate.drawCandidate(data);
	});	
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
    	dacura.candidate.makeRequest();
	}
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
    	dacura.candidate.makeRequest();
    });
    $( "#rewind" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-prev"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version--;
    	dacura.candidate.makeRequest();
    });
    $( "#forward" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-next"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version++;
    	dacura.candidate.makeRequest();
    });
    $( "#end" ).button({
      text: false,
      icons: {
        primary: "ui-icon-seek-end"
      }
    }).click(function(){
    	dacura.candidate.viewArgs.version = 0;
    	dacura.candidate.makeRequest();
    });
    dacura.candidate.makeCheckboxesBehave();
    $( "#format" ).buttonset();
	$( "#test_edit" ).button().click(function(){
		alert("test");
	});
	$( "#save_edit" ).button().click(function(){
		alert("save");
	});
	$( "#display" ).buttonset();
	$( "#display_ns").button().click(function(){
		loadDisplaySetting();
	});
	$( "#display_links").button().click(function(){
		loadDisplaySetting()
	});

    $('#edit-format').buttonset();
    $('#display_edit').button().click(function(){
       	dacura.candidate.setEditMode();
    });

    $('#cancel_edit').button().click(function(){
       dacura.candidate.clearEditMode();
	});
	
    $('#format_json').click(function(){
    	dacura.candidate.viewArgs.format = "json";    
    	dacura.candidate.makeRequest();
	});
    $('#format_ttl').click(function(){
    	dacura.candidate.viewArgs.format = "ttl";    
    	dacura.candidate.makeRequest();
	});
    $('#format_triples').click(function(){
    	dacura.candidate.viewArgs.format = "triples";    
    	dacura.candidate.makeRequest();
	});
    $('#format_html').click(function(){
    	dacura.candidate.viewArgs.format = "html";    
    	dacura.candidate.makeRequest();
	});
    dacura.candidate.makeRequest();
});
</script>