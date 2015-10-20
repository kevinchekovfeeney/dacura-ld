<style>
.dch { display: none }
</style>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>

<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id="pagecontent-nopadding">
	<div class="pctitle">Collection Configuration Service <span id="screen-context"></span></div>
	<div class="pcbreadcrumbs">
		<?php echo $service->getBreadCrumbsHTML();?>
		 
	</div>
	<div class="user-message"></div>
	<br>
	<div id="collection-pane-holder">
		<ul id="collection-pane-list" class="dch">
			<li><a href="#collection-details">Details</a></li>
			<li><a href="#datasets">Datasets</a></li>
		 	<li><a href="#dataset-add">Add Dataset</a></li>
		 	<li><a href="#collection-config">Configuration</a></li>
	 	</ul>
		 <div id="collection-details" class="collection-pane dch">
			<table class="dc-wizard" id="collection_details">
				<tbody>
					<tr>		
						<th>ID</th><td id='collectionid'></td>
					</tr>
					<tr>
						<th>Title</th><td id='collectiontitle'><input id="collectiontitleip" size="50" value=""></td>
					</tr>
				</tbody>
			</table>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.deleteCollection()">Delete Collection</a>
				<a class="button2" href="javascript:dacura.config.updateCollection()">Update Collection</a>
			</div>
		</div>
		
	 	<div id="datasets" class="collection-pane dch pcdatatables"></div>
		<div id="dataset-add" class="collection-pane dch">
			<div id="dsadd"></div>
			<table class="dc-wizard" id="dataset_add_details">
				<tbody>
					<tr>		
						<th>ID</th><td id='datasetid'><input id="datasetidip" size="24" value=""></td>
					</tr>
					<tr>
						<th>Title</th><td id='datasettitle'><input id="datasettitleip" size="50" value=""></td>
					</tr>
				</tbody>
			</table>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.addDataset()">Add New Dataset</a>
			</div>
		</div>
		<div id="collection-config" class="collection-pane dch pcdatatables">
			<div id='collectionconfig'></div>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.deleteCollection()">Delete Collection</a>
				<a class="button2" href="javascript:dacura.config.updateCollection()">Update Collection</a>
			</div>
		</div>
	</div>
</div>
<script>
dacura.config.writeBusyMessage  = function(msg) {
	dacura.system.showBusyMessage(msg, "", '#collection-pane-holder');
}

dacura.config.clearBusyMessage = function(){
	dacura.system.removeBusyOverlay();
};

dacura.config.writeSuccessMessage = function(msg){
	$('#bcstatus').html("<div class='dacura-user-message-box dacura-success'>"+ msg + "</div>").show();
	setTimeout(function(){$('#bcstatus').fadeOut(400)}, 1000);
}

dacura.config.fetchCollection = function(cid){
	var ajs = dacura.config.api.getCollection(cid);
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
				dacura.config.current_collection = JSON.parse(data);
		     	dacura.config.drawCollection(dacura.config.current_collection);
				dacura.config.writeSuccessMessage("Retrieved collection details");
				
			}
			catch(e){
				dacura.system.writeErrorMessage("", "#bcstatus", "", "Error: " + e.message);
			}
		})
		.fail(function (jqXHR, textStatus){
	     	dacura.config.clearBusyMessage();
			dacura.system.writeErrorMessage("", "#bcstatus", "", "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.config.drawCollection = function(data){		
	if(typeof data == "undefined"){
		dacura.system.writeErrorMessage("", "#bcstatus", "", "Failed to load dataset");		
	}
	else {
		$('.pctitle').html("Configuration for Collection " + data.name + " (ID: "+data.id + " - " + data.status + ")").show();
		//details section
		$('#collectionid').html(data.id);
		$('#collectiontitleip').val(data.name);
		//datasets section
		$('#datasets').html('<table id="datasets_table" class="dch"><thead><tr><th>ID</th><th>Title</th><th>Status</th></tr></thead><tbody></tbody></table>');
		for (var i in data.datasets) {
			var obj = data.datasets[i];
			<?php if(!$dacura_server->getServiceSetting('show_deleted_datasets', false)){
				echo 'if(obj.status == "deleted") {continue;}';
			}?>
			$('#datasets_table tbody').append("<tr id='ds" + obj.id + "'><td>" + obj.id + "</td><td>" + obj.name 
			+ "</td><td>" + obj.status + "</td></tr>");
			$('#ds'+obj.id).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#ds'+obj.id).click( function (event){
				dacura.system.switchContext(dacura.config.current_collection.id, this.id.substr(2));
		    }); 
		}
		$('#datasets_table').dataTable(<?=$dacura_server->getServiceSetting('datasets_datatable_init_string', "{}");?>).show();
		//config section
		$('#collectionconfig').html("<textarea id='collectionconfig_ta'>" + JSON.stringify(data.config) + "</textarea>");
		JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
	    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
	    var j = new JSONEditor($("#collectionconfig_ta"), "700", "300");
	    j.doTruncation(true);
		j.showFunctionButtons();
		dacura.config.jsoneditor = j;
	}
}

dacura.config.deleteCollection = function(){
	var ajs = dacura.config.api.del();
	var self=this;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Deleting collection");
	};
	ajs.complete = function(){
		dacura.config.clearBusyMessage();	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		dacura.system.showSuccessResult("Deleted Collection", "", "", '#bcstatus');
		dacura.system.switchContext("all");
	})
	.fail(function (jqXHR, textStatus){
		dacura.config.clearBusyMessage();	
		dacura.system.writeErrorMessage("", "#bcstatus", "", "Error: " + jqXHR.responseText );
	});	
}

dacura.config.updateCollection = function(){
	var data = {};
	data.id = $('#collectionid').html();
	data.title = $('#collectiontitleip').val();
	data.payload = JSON.stringify(dacura.config.jsoneditor.getJSON());
	var ajs = dacura.config.api.updateCollection(data.id);
	ajs.data = data;
	var self=this;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Updating collection " + data.id);
	};
	ajs.complete = function(){
     	dacura.config.clearBusyMessage();
    };
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			try {
				dacura.config.current_collection = JSON.parse(data);
		     	dacura.config.drawCollection(dacura.config.current_collection);
				dacura.config.writeSuccessMessage("Updated collection " + dacura.config.current_collection.id);
				
			}
			catch(e){
				dacura.system.writeErrorMessage("", "#bcstatus", "", "Error: " + e.message);
			}
		})
		.fail(function (jqXHR, textStatus){
	     	dacura.config.clearBusyMessage();
			dacura.system.writeErrorMessage("", "#bcstatus", "", "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.config.addDataset = function(){
	var ds = {};
	ds.id = $('#datasetidip').val();
	ds.title = $('#datasettitleip').val();
	if(ds.id.length < 2 || ds.title.length < 5){
		return dacura.system.writeErrorMessage("", "#dsadd", "", "ID must be at least 2 characters and title must be at least 5.");
	}
	var ajs = dacura.config.api.createDataset(dacura.config.current_collection.id, ds.id);
	var self=this;
	ajs.data = ds;
	ajs.beforeSend = function(){
		dacura.config.writeBusyMessage("Creating New dataset");
	};
	ajs.complete = function(){
		dacura.config.clearBusyMessage();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				try {
					var dsid = JSON.parse(data);
					dacura.system.switchContext(dacura.config.current_collection.id, dsid);
				}
				catch(e){
					dacura.system.writeErrorMessage("", "#dsadd", "", "Error: " + e.message);
				}
			}
			else {
				dacura.system.writeErrorMessage("", "#dsadd", "", "Error: server response was empty");
			}    	
		})
		.fail(function (jqXHR, textStatus){
			dacura.system.writeErrorMessage("", "#dsadd", "", "Error: " + jqXHR.responseText );
		}
	);	
};

$(function() {
	$("#collection-pane-list").show();
	$("#collection-pane-holder").tabs();
	dacura.config.fetchCollection("<?=$params['cid']?>");
});


</script>
