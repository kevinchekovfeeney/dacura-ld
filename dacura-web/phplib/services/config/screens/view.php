<div id="pagecontent">
	<div class="pctitle"></div>
	<div class="pcbusy"></div>
	<div id="collectionlisting">
		<div class="pcsection pcdatatables">
			<table id="collections_table">
				<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Config</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="<?=$service->settings['install_url'];?>0/0/config/create">Create New Collection</a>
		</div>
	</div>
	<div id="collectionview">
		<table id="collection_table">
			<thead>
			</thead>
			<tbody>
				<tr>
					<th>Name</th><td id='collectionname'><input type='text' id='collectionnameip' value=""></td>
				</tr>
			</tbody>
		</table>
		<div class="pcsection pcdatatables">
			<div class="pcsectionhead">Configuration</div>	
			<div id='collectionconfig'></div>
		</div>
		<div class="pcsection pcdatatables">
			<div class="pcsectionhead">Datasets</div>	
				<table id="datasets_table">
					<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Config</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
		</div>
		<div id="collectionhelp"></div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.config.deleteCollection()">Delete Collection</a>
			<a class="button2" href="<?=$service->get_service_url('config', array('create'))?>">Create New Dataset</a>
			<a class="button2" href="javascript:dacura.config.updateCollection()">Update Collection</a>
		</div>
	</div>
	<div id="datasetview">
		<table id="dataset_table">
			<thead>
			</thead>
			<tbody>
				<tr>
					<th>Name</th><td id='datasetname'><input type='text' id='datasetnameip' value=""></td>
				</tr>
			</tbody>
		</table>
		<div class="pcsection pcdatatables">
			<div class="pcsectionhead">Configuration</div>	
			<div id='datasetconfig'></div>
		</div>
		<div id="collectionhelp"></div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="javascript:dacura.config.deleteDataset()">Delete Dataset</a>
			<a class="button2" href="javascript:dacura.config.updateDataset()">Update Dataset</a>
		</div>
	</div>
</div>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.dataTables.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />


<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>

<script>
dacura.config.deleteDataset = function(){
	dacura.config.clearscreens();
	var ajs = dacura.config.api.del();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Deleting dataset");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		window.location.href = getAfterDeleteLink();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
	});	
};

var getAfterDeleteLink = function(){
	<?php if($service->getDatasetID()){?>
		return dacura.toolbox.getServiceURL("<?=$service->settings['install_url']?>", "", "<?=$service->getCollectionID()?>", "all", "config", "");
	<?php } else {?>
		return dacura.toolbox.getServiceURL("<?=$service->settings['install_url']?>", "", "all", "all", "config", "");
	<?php }?>
};



dacura.config.deleteCollection = function(){
	dacura.config.clearscreens();
	var ajs = dacura.config.api.del();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Deleting collection");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		window.location.href = getAfterDeleteLink();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
	});	
}

dacura.config.updateCollection = function(){
	dacura.config.clearscreens();
	var ds = {};
	ds.title = $('#collectionnameip').val();
	ds.payload = JSON.stringify(dacura.config.jsoneditor.getJSON());
	var ajs = dacura.config.api.update();
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating collection");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		self.viewcollection();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
	});	


	
}

dacura.config.updateDataset = function(){
	dacura.config.clearscreens();
	var ds = {};
	ds.title = $('#datasetnameip').val();
	ds.payload = JSON.stringify(dacura.config.jsoneditor.getJSON());
	var ajs = dacura.config.api.update();
	ajs.data = ds;
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Updating dataset");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');	
	};
	$.ajax(ajs)
	.done(function(data, textStatus, jqXHR) {
		self.viewdataset();
	})
	.fail(function (jqXHR, textStatus){
		dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
	});	

}


dacura.config.viewcollection = function(){
	dacura.config.clearscreens();
	var ajs = dacura.config.api.view();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving details for collection");
	};
	ajs.complete = function(){
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	//alert(id + " success " + data);
	     	dacura.toolbox.clearBusyMessage('.pcbusy');
			//dacura.toolbox.writeSuccessMessage ('#collectionhelp', "Retrieved collection " + id);
	     	dacura.config.drawViewTable(JSON.parse(data));
	    	$('#collectionview').show();    	
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
		}
	);	
	
};


dacura.config.viewdataset = function(){
	dacura.config.clearscreens();
	var ajs = dacura.config.api.view();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving details for dataset");
	};
	ajs.complete = function(){
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	//alert(id + " success " + data);
	     	dacura.toolbox.clearBusyMessage('.pcbusy');
			//dacura.toolbox.writeSuccessMessage ('#collectionhelp', "Retrieved collection " + id);
	     	dacura.config.drawViewDSTable(JSON.parse(data));
	    	$('#datasetview').show();    	
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
		}
	);	
	
};



dacura.config.clearscreens = function(){
	$('#collectionlisting').hide();
	$('#collectionview').hide();
	$('#datasetview').hide();
	$('.pctitle').html("").hide();
}

dacura.config.drawListTable = function(data){
	$('.pctitle').html("List of all dataset collections on this server").show();
	var urlbase = "<?=$service->settings['install_url'];?>"; 
	if(typeof data == "undefined"){
		$('#collections_table').hide(); 
		dacura.toolbox.writeBusyMessage('.pcbusy', "No Collections currently configured");		
	}
	else {
		$('#collections_table tbody').html("");
		$.each(data, function(i, obj) {
			if(obj.status == "active"){
				var url = urlbase + i+"/0/config";
				$('#collections_table tbody').append("<tr><td><a href='" + url + "'>" + i + "</a></td><td>" + obj.name + "</td><td>" + JSON.stringify(obj.config) + "</td></tr>");
			}
		});
		$('#collections_table').dataTable();
	}
}

dacura.config.drawViewTable = function(data){
	$('.pctitle').html("Collection "+data.id + " [" + data.status + "]").show();
	$('#collectionnameip').val(data.name);
	$('#collectionconfig').html("<textarea id='collectionconfig_ta'>" + JSON.stringify(data.config) + "</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#collectionconfig_ta"), "790", "300");
    j.doTruncation(true);
	j.showFunctionButtons();
	dacura.config.jsoneditor = j;
	var urlbase = "<?=$service->settings['install_url'];?>"; 
	$('#datasets_table tbody').html("");
	$.each(data.datasets, function(i, obj) {
		if(obj.status == "active"){
			var url = urlbase + data.id+"/" + i + "/config";
			$('#datasets_table tbody').append("<tr><td><a href='" + url + "'>" + i + "</a></td><td>" + obj.name + "</td><td>" + JSON.stringify(obj.config) + "</td></tr>");
		}
	});
	$('#datasets_table').dataTable();
}

dacura.config.drawViewDSTable = function(data){
	$('.pctitle').html("Dataset "+data.id+ " [" + data.status + "]").show();
	$('#datasetnameip').val(data.name);
	$('#datasetconfig').html("<textarea id='datasetconfig_ta'>" + JSON.stringify(data.config) + "</textarea>");
	JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
    JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
    var j = new JSONEditor($("#datasetconfig_ta"), "790", "300");
    j.doTruncation(true);
	j.showFunctionButtons();
	dacura.config.jsoneditor = j;
}


dacura.config.listcollection = function(){
	dacura.config.clearscreens();
	
	var ajs = dacura.config.api.listing();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('.pcbusy', "Retrieving Collection Listing");
	};
	ajs.complete = function(){
		dacura.toolbox.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				dacura.config.drawListTable(JSON.parse(data));
			}
			else {
				dacura.config.drawListTable();
			}    	
	    	$('#collectionlisting').show();
			//dacura.toolbox.writeSuccessMessage ('#collectionhelp', "Retrieved list of collections");
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#collectionhelp', "Error: " + jqXHR.responseText );
		}
	);	
	
}



$(function() {
	<?php 
	if(!$service->getCollectionID()){
		echo "dacura.config.listcollection();";
	}
	else {
		if(!$service->getDatasetID()){
			echo "dacura.config.viewcollection();";	
		}
		else {
			echo "dacura.config.viewdataset();";
		}
	}?>
	
});


</script>
