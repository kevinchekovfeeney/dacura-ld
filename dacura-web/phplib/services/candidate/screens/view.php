<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<style>
  #toolbar {
  	width: 98%;
    padding: 4px;
    display: inline-block;
  }
  /* support: IE7 */
  *+html #toolbar {
    display: inline;
  }
  
  #format {
  	float: right;
  }
 
dd, dt { float: left; } 


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
	   			<span class="tool-title">Candidate API Service</span>
				<span class="tool-description">Viewing Candidate <?php echo $params["id"];?>.</span>
	   		</div>
			<div id="show-candidate">
				<dl id='candidate-meta-details'>
					<dt class="dch">ID</dt><dd class="dch" id='candidate-id'></dd>
					<dt>Collection</dt><dd id='candidate-colid'></dd>
					<dt>Dataset</dt><dd id='candidate-dsid'></dd>
					<dt>Type</dt><dd id='candidate-type'><span id='candidate-type-name'></span> (v.<span id="candidate-type-version"></span>)</dd>
					<dt>Status</dt><dd id='candidate-status'></dd>
					<dt>Created</dt><dd id='candidate-created'></dd>
					<dt>Modified</dt><dd id='candidate-modified'></dd>
				</dl>			
				<div id="candidate-meta">
					<div id="toolbar" class="ui-widget-header ui-corner-all">
						<button id="beginning">go to first</button>
						<button id="rewind">back</button>
						Version: <span id="candidate-version"></span> 
						<button id="forward">forward</button>
						<button id="end">latest version</button>
						<span id="format">
						    <input type="radio" id="format_json" name="format" checked="checked"><label for="format_json">JSON</label>
						    <input type="radio" id="format_ttl" name="format"><label for="format_ttl">Turtle</label>
						    <input type="radio" id="format_triples" name="format"><label for="format_triples">N-Triples</label>
						    <input type="radio" id="format_html" name="format"><label for="format_html">HTML</label>
						</span>
					</div>
				</div>
				<div id="candidate-body">
				</div>			
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
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>		
				</div>
				<div class="tool-section-header">
	   				<span class="section-title">Pending Updates</span>
				</div>
				<div id="candidate-updates">
					<table id="updates_table" class="dch">
						<thead>
						<tr>
							<th>Version</th>
							<th>Schema Version</th>
							<th>Created</th>
							<th>Status</th>
							<th>Description</th>
						</tr>
						</thead>
						<tbody></tbody>
					</table>			
				</div>
			</div>
			<br>
		</div>
   	</div>
</div>
<script>

dacura.candidate.currentCandidate = null;
dacura.candidate.viewArgs = {
	format: 'json',
	display: 'default',
	version: 0,
};

dacura.candidate.drawCandidate = function(data){		
	$('#history_table').dataTable().show(); 
	$('#updates_table').dataTable().show();
	dacura.candidate.currentCandidate = data; 
	$('#candidate-id').html(data.id); 
	$('#candidate-type-name').html(data.type); 
	$('#candidate-type-version').html(data.type_version); 
	$('#candidate-status').html(data.status); 
	$('#candidate-colid').html(data.cid); 
	$('#candidate-dsid').html(data.did); 
	$('#candidate-created').html(data.created); 
	$('#candidate-modified').html(data.created); 
	$('#candidate-version').html(data.version); 
	$('#candidate-body').html("<textarea style='width: 100%; min-height: 100px;' id='candbody'>" + JSON.stringify(data.contents) + "</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#candbody"), "800", "400");
    j.doTruncation(true);
    j.showText();
	j.showFunctionButtons();
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
    dacura.candidate.makeRequest();
	
});
</script>