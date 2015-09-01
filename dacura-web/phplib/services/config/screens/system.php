<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id="collection-pane-holder">
		<ul id="collection-pane-list" class="dch">
			<li><a href="#collections-list">List Collections</a></li>
		 	<li><a href="#collection-add">Create Collection</a></li>
 		</ul>
		<div id="collections-list" class="collection-pane dch pcdatatables">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="clistmsg"></div>
			</div>
			<table id="collections_table" class="display dch">
				<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>Status</th>
					<th>Datasets</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div id="collection-add" class="collection-pane dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="caddmsg"></div>
			</div>
			<table class="dc-wizard" id="collection_add">
				<thead><tr><th class='left'></th><th class='right'></th></tr></thead>
				<tbody>
					<tr>		
						<th>ID</th><td id='collectionid'><input id="collectionidip" size="24" value=""></td>
					</tr>
					<tr>
						<th>Title</th><td id='collectiontitle'><input id="collectiontitleip" size="50" value=""></td>
					</tr>
				</tbody>
			</table>
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.config.addCollection()">Create New Collection</a>
			</div>
		</div>
</div>

<script>
	writeBusyMessage  = function(msg) {
		dacura.toolbox.writeBusyOverlay('#collection-pane-holder', msg);
	}
	
	clearBusyMessage = function(){
		dacura.toolbox.removeBusyOverlay(false, 100);
	};

	function updateBusyMessage(msg){
		dacura.toolbox.updateBusyMessage(msg);
	}
	
	dacura.config.listCollections = function(){
		var ajs = dacura.config.api.listing();
		var self=this;
		ajs.beforeSend = function(){
			writeBusyMessage("Retrieving collection list");
		};
		ajs.complete = function(){
			clearBusyMessage();
		};
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				try {
					dacura.config.drawListTable(JSON.parse(data));
				}
				catch(e){
					dacura.toolbox.writeErrorMessage('#clistmsg', "Error: " + e.message);
				}
			})
			.fail(function (jqXHR, textStatus){
				clearBusyMessage();
				dacura.toolbox.writeErrorMessage('#clistmsg', "Error: " + jqXHR.responseText );
				$('#collections_table').dataTable().show(); 
			});	
	};

	dacura.config.drawListTable = function(data){	
		$('#collections_table tbody').html("");
		for (var i in data) {
			var obj = data[i];
			<?php if(!$dacura_server->getServiceSetting('show_deleted_collections', false)){
				echo 'if(obj.status == "deleted") {continue;}';
			}?>
			var datasets = 0;
			if(typeof obj.datasets != "undefined"){
				for(key in obj.datasets) datasets++;
			}
			$('#collections_table tbody').append("<tr id='col" + obj.id + "'><td>" + obj.id + "</td><td>" + obj.name 
					+ "</td><td>" + obj.status + "</td><td>" + datasets + "</td></tr>");
			$('#col'+obj.id).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#col'+obj.id).click( function (event){
				dacura.system.switchContext(this.id.substr(3));
		    }); 
		};
		$('#collections_table').dataTable(<?=$dacura_server->getServiceSetting('collections_datatable_init_string', "{}");?>).show();	
	};

	dacura.config.addCollection = function(){
		var ds = {};
		ds.id = $('#collectionidip').val();
		ds.title = $('#collectiontitleip').val();
		if(ds.id.length < 2 || ds.title.length < 5){
			return dacura.toolbox.writeErrorMessage('#caddmsg', "ID must be at least 2 characters and title must be at least 5.");
		}
		var ajs = dacura.config.api.createCollection(ds.id);
		var self=this;
		ajs.data = ds;
		ajs.beforeSend = function(){
			writeBusyMessage("Creating New Collection");
		};
		ajs.complete = function(){
		};
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				if(data.length > 0 ){
					try {
						var colid = JSON.parse(data);
						dacura.toolbox.writeSuccessMessage('#caddmsg', "Created Collection " + colid);
						updateBusyMessage("Creating collection " + colid + " configuration");
						dacura.system.switchContext(colid);
					}
					catch(e){
						dacura.toolbox.writeErrorMessage('#caddmsg', "Error: " + e.message, jqXHR.responseText );
						clearBusyMessage();
					}
				}
				else {
					clearBusyMessage();
					dacura.toolbox.writeErrorMessage('#caddmsg', "Error: server response was empty");
				}    	
			})
			.fail(function (jqXHR, textStatus){
				clearBusyMessage();
				dacura.toolbox.writeErrorMessage('#caddmsg', "Error: " + jqXHR.responseText );
			}
		);	
	};

	$(function() {
		$("#collection-pane-holder").tabs( {
	        "activate": function(event, ui) {
	            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
	        }
	    });
		$("#collection-pane-list").show();
		dacura.config.listCollections();
	});
	</script>
</script>