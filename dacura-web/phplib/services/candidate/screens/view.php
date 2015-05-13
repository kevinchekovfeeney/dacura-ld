<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<style>
  #toolbar {
  	width: 99%;
    padding: 4px;
    display: inline-block;
    font-size: 0.7em;
    margin-top: -6px;
    margin-bottom: -20px;
  }
  /* support: IE7 */
  *+html #toolbar {
    display: inline;
  }
  
 
  
  #format {
  	float: right;
  }
  
  #toolbar dl {
  	float: right;
  	border: 2px solid red;
  	margin-top: -2px;
  	margin-bottom: -2px;
  	width: 565px;
  	padding-top: 6px;
  	padding-bottom: 4px;
  }
 
 .dacura-json-viewer {
 	white-space: pre;
 	unicode-bidi: embed;
 	font-size: 0.85em;
 	border-top: 1px solid #aaa;
 	border-left: 1px solid #aaa;
 	border-right: 1px solid #aaa;
 	background-color: #fdfdfd;
 	padding-left: 4px;
 	padding-top: 2px;
}

 #version-label {
  	font-size: 1.1em;
 	vertical-align: middle;
 	padding: 0px 4px;	
}
 
 #candidate-version {
 	font-size: 1.4em;
 	padding: 0px 6px;
 	vertical-align: middle;
 }
 
#version-data {
	margin-top: 18px;
	margin-bottom: -6px;
	margin-right: -4px;
	margin-left: -6px;
	padding: 4px 6px 4px 6px;
	font-size: 0.8em;
	border-radius: 3px 3px 0px 0px;
	color: #222;
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

div#candidate-body {
	margin-top: 10px;
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
				<div id="candidate-meta">
					<div id="toolbar" class="ui-widget-header ui-corner-all">
						<span id="version-label">version</span>
						<button id="beginning">go to first</button>
						<button id="rewind">back</button>
						<span id="candidate-version"></span> 
						<button id="forward">forward</button>
						<button id="end">latest version</button>
						<span id="format">
							    <input type="radio" id="format_json" name="format" checked="checked"><label for="format_json">JSON</label>
							    <input type="radio" id="format_ttl" name="format"><label for="format_ttl">Turtle</label>
							    <input type="radio" id="format_triples" name="format"><label for="format_triples">Triples</label>
							    <input type="radio" id="format_html" name="format"><label for="format_html">HTML</label>
						</span>
					</div>
				</div>
				<div id='version-data'>
					<span class='version-meta' id='version-type'></span>
					<span class='version-meta' id='candidate-status'></span>
					<span class='version-meta' id="candidate-type-version"></span>
					<span class='version-meta' id='candidate-modified'></span>
				</div>
				
				<div id="candidate-format">
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
	display: "<?=isset($params['display'])? $params['display'] : 'default' ?>",
	version: <?=isset($params['version'])? $params['version'] : 0 ?>,
};

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
		$('#version-type').html("Latest Version");
		$( "#forward" ).button("disable");
		$( "#end" ).button("disable");
	}
	else {
		$('#version-type').html("Replaced: " + timeConverter(data.modified));		
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
	$('#candidate-modified').html("Created: " + timeConverter(data.modified)); 
	$('#candidate-version').html(data.version); 
	
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
		$('#candidate-body').html(JSON.stringify(contents, null, 4)).addClass("dacura-json-viewer");
	}
	else if(dacura.candidate.viewArgs.format == "triples"){
		var html = "<table class='dacura-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
		for (var i in contents) {
			var row = "<tr><td>" + escapeHTML("<"+ contents[i][0] + "> ") + "</td>";
			row += "<td>" + escapeHTML("<"+ contents[i][1] + "> ") + "</td>";
			row += "<td>" + escapeHTML("<"+ contents[i][2] + "> .") + "</td></tr>";
			html += row;
		}
		$('#candidate-body').html(html + "</table>");	
	}
	
	else if(dacura.candidate.viewArgs.format == "turtle"){
		//since we have no blank nodes in the triplestore only have to care about directives..
		//@base
		//
		
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
    $( "#format" ).buttonset();
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