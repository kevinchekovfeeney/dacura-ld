<style>
.dch { display: none }
</style>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>

<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id="pagecontent-nopadding">
	<div class="pctitle">Dataset Configuration Service <span id="screen-context"></span></div>
	<div class="pcbreadcrumbs">
		<?php echo $service->getBreadCrumbsHTML(false, '<span id="bcstatus" class="bcstatus"></span>');?>
	</div>
	<div class="user-message"></div>
	<br>
	<div id="dataset-pane-holder">
		<ul id="dataset-pane-list" class="dch">
			<li><a href="#dsdetails">Details</a></li>
			<li><a href="#dsschema">Schema</a></li>
		 	<li><a href="#dsjsonld">JSON-LD</a></li>
		 	<li><a href="#dscandidates">Candidates</a></li>
		 	<li><a href="#dsconfig">Configuration</a></li>
	 	</ul>
		 <div id="dsdetails" class="dataset-pane dch">
			<table class="dc-wizard" id="dataset_details">
				<tbody>
					<tr>		
						<th>ID</th><td id='datasetid'></td>
					</tr>
					<tr>
						<th>Title</th><td id='datasettitle'><input id="datasettitleip" size="50" value=""></td>
					</tr>
				</tbody>
			</table>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.deleteDataset()">Delete Dataset</a>
				<a class="button2" href="javascript:dacura.config.updateDetails()">Update Dataset</a>
			</div>
		</div>
	 	<div id="dsschema" class="dataset-pane dch pcdatatables">
	 		<p id="schema-description"></p>
	 		<textarea id="schemaip" style="width:98%; height: 100%"></textarea>
	 		<div class="pcsection pcbuttons">
				<a id="schema-update-button" class="button2" href="javascript:dacura.config.updateSchema()">Update Schema</a>
			</div>
	 	</div>
	 	<div id="dsjsonld" class="dataset-pane dch pcdatatables">
	 		<p id="json-description"></p>
	 		<textarea id="jsonip" style="width:98%; height: 100%"></textarea>
	 		<div class="pcsection pcbuttons">
				<a id="json-update-button" class="button2" href="javascript:dacura.config.updateJSON()">Update JSON</a>
			</div>	 	
	 	</div>
	 	<div id="dscandidates" class="dataset-pane dch pcdatatables"></div>
		<div id="dsconfig" class="dataset-pane dch pcdatatables">
			<div id='datasetconfig'></div>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.updateConfig()">Update Configuration</a>
			</div>
		</div>
	</div>
</div>
<script>
dacura.config.writeBusyMessage  = function(msg) {
	dacura.toolbox.writeBusyOverlay('#dataset-pane-holder', msg);
}

dacura.config.clearBusyMessage = function(){
	dacura.toolbox.removeBusyOverlay(false, 100);
};

dacura.config.writeSuccessMessage = function(msg){
	$('#bcstatus').html("<div class='dacura-user-message-box dacura-success'>"+ msg + "</div>").show();
	setTimeout(function(){$('#bcstatus').fadeOut(400)}, 1000);
}

dacura.config.fetchDataset = function(cid, did){
	var ajs = dacura.config.api.getDataset(cid, did);
	var self=this;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Retrieving details for collection");
	};
	ajs.complete = function(){
     	dacura.config.clearBusyMessage();
    };
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			try {
				dacura.config.current_dataset = JSON.parse(data);
		     	dacura.config.drawDataset(dacura.config.current_dataset);
				dacura.config.writeSuccessMessage("Retrieved dataset details");			
			}
			catch(e){
				dacura.toolbox.writeErrorMessage("#bcstatus", "Error: " + e.message);
			}
		})
		.fail(function (jqXHR, textStatus){
	     	dacura.config.clearBusyMessage();
			dacura.toolbox.writeErrorMessage("#bcstatus", "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.config.drawDataset = function(data){		
	if(typeof data == "undefined"){
		dacura.toolbox.writeErrorMessage("#bcstatus", "Failed to load dataset");		
	}
	else {
		$('.pctitle').html("Dataset Configuration for " + data.name + " (ID: "+data.id + " - " + data.status + ")").show();
		//details section
		$('#datasetid').html(data.id);
		$('#datasettitleip').val(data.name);
		if(typeof data.schema == "object" && data.schema != null ){
			$('#schemaip').val(data.schema.contents);
			$('#schema-description').html("Schema Version " + data.schema.version);
			$('#schema-update-button').html("Update Schema");
		}
		else {
			$('#schema-description').html("No schema has been created yet - enter the schema in turtle notation in the box below");
			$('#schema-update-button').html("Create Dataset Schema");
		}
		if(typeof data.json == "object" && data.json != null ){
			$('#jsonip').val(data.json.contents);
			$('#json-description').html("JSON Version " + data.json.version);
			$('#json-update-button').html("Update JSON");
		}
		else {
			$('#json-description').html("No JSON-LD has been created yet - enter it in the box below");
			$('#json-update-button').html("Create JSON-LD Schema");
		}
		$('#dscandidates').html("<h4>This is where we will specify widgets and different ways of viewing entities.</h4>");
		$('#datasetconfig').html("<textarea id='dsconfig_ta'>" + JSON.stringify(data.config) + "</textarea>");
		JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
	    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
	    var j = new JSONEditor($("#dsconfig_ta"), "740");
	    j.doTruncation(true);
		j.showFunctionButtons();
		dacura.config.jsoneditor = j;
	}
}

dacura.config.updateSchema = function(){
	var schema = {};
	schema.version = "0";
	if(typeof dacura.config.current_dataset.schema == "object" && dacura.config.current_dataset.schema != null){
		schema.version = dacura.config.current_dataset.schema.version;
	}
	schema.contents = $('#schemaip').val();
	schema.update_type = "minor";
	dacura.config.updateDataset({"schema" : JSON.stringify(schema) }); 
}

dacura.config.updateDetails = function(){
	var data = {};
	data.title = $('#datasettitleip').val();	
	dacura.config.updateDataset(data); 
}

dacura.config.updateJSON = function(){
	var json = {};
	json.version = "0";
	if(typeof dacura.config.current_dataset.json == "object" && dacura.config.current_dataset.json != null){
		json.version = dacura.config.current_dataset.json.version;
	}
	json.contents = $('#jsonip').val();
	json.update_type = "minor";
	dacura.config.updateDataset({"json" : JSON.stringify(json)}); 
}

dacura.config.updateConfig = function(){
	var data = {};
	data.config = JSON.stringify(dacura.config.jsoneditor.getJSON());
	dacura.config.updateDataset(data); 	
}


dacura.config.updateDataset = function(obj){
	$('.user-message').html("");
	var ajs = dacura.config.api.updateDataset();
	ajs.data = obj;
	var self=this;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Updating dataset");
	};
	ajs.complete = function(){
     	dacura.config.clearBusyMessage();
    };
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			try {
				dacura.config.current_dataset = JSON.parse(data);
		     	dacura.config.drawDataset(dacura.config.current_dataset);
				dacura.config.writeSuccessMessage("Updated dataset " + dacura.config.current_dataset.id);
			}
			catch(e){
				dacura.toolbox.writeErrorMessage(".user-message", "Error. Update failed " + e.message);
			}
		})
		.fail(function (jqXHR, textStatus){
	     	dacura.config.clearBusyMessage();
			dacura.toolbox.writeErrorMessage(".user-message", "Error. Update Failed " + jqXHR.responseText );
		}
	);	
};

dacura.config.deleteDataset = function(){
	var ajs = dacura.config.api.del();
	var self=this;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Deleting dataset");
	};
	ajs.complete = function(){
		dacura.config.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.config.writeSuccessMessage("Deleted dataset");
		dacura.system.switchContext("<?=$params['cid']?>", "all");
	})
	.fail(function (jqXHR, textStatus){
		dacura.config.clearBusyMessage();	
		dacura.toolbox.writeErrorMessage("Error. Failed to delete dataset " + jqXHR.responseText );
	});	
};


$(function() {
	$("#dataset-pane-list").show();
	$("#dataset-pane-holder").tabs();
	dacura.config.fetchDataset("<?=$params['cid']?>", "<?=$params['did']?>");
});


</script>
