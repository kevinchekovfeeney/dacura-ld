<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<div id='pagecontent-container'>
<div id='pagecontent'>
<div id="pagecontent-nopadding">
	<div class="pctitle">Users Service <span id="screen-context"></span></div>
	<div class="pcbreadcrumbs">
		<?php echo $service->getBreadCrumbsHTML(false, '<span id="bcstatus" class="bcstatus"></span>');?>
		 
	</div>
	<br>
	<div id="candidate-list" class="pcdatatables">
			<div class="tab-top-message-holder">
				<div id="clistmsg"></div>
			</div>
			<table id="candidate_table" class="dch">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<?php if (isset($params['show_collection']) && $params['show_collection']) echo "<th>Collection ID</th>"?>
					<?php if (isset($params['show_dataset']) && $params['show_dataset']) echo "<th>Dataset ID</th>"?>
					<th>Status</th>
					<th>Version</th>
					<th>Schema Version</th>
					<th>Created</th>
					<th>Modified</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
	</div>
</div>

<script>
dacura.candidate.writeBusyMessage  = function(msg) {
	dacura.toolbox.writeBusyOverlay('#candidate-list', msg);
}

dacura.candidate.clearBusyMessage = function(){
	dacura.toolbox.removeBusyOverlay(false, 100);
};

dacura.candidate.writeSuccessMessage = function(msg){
	$('#clistmsg').html("<div class='dacura-candidate-message-box dacura-success'>"+ msg + "</div>");
	setTimeout(function(){$('#clistmsg').fadeOut(400)}, 3000);
}

dacura.candidate.writeErrorMessage = function(msg){
	msg = "<div class='dacura-candidate-message-box dacura-error'>"+ msg + "</div>";
	dacura.toolbox.removeBusyOverlay(msg, 2000);
	$('#clistmsg').html(msg);
}


dacura.candidate.list = function(){
		var ajs = dacura.candidate.api.list();
		var self=this;
		ajs.beforeSend = function(){
			dacura.candidate.writeBusyMessage("Retrieving candidate list");
		};
		ajs.complete = function(){
			dacura.candidate.clearBusyMessage();
		};
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				if(data.length > 0 ){
					try {
						dacura.candidate.drawListTable(JSON.parse(data));
					}
					catch(e){
						dacura.candidate.writeErrorMessage("Error: " + e.message);
						dacura.candidate.drawListTable();
					}
				}
				else {
					dacura.candidate.drawListTable();
				}    	
				$('#candidate_table').show();
			})
			.fail(function (jqXHR, textStatus){
				dacura.candidate.writeErrorMessage("Error: " + jqXHR.responseText );
			}
		);	
};

dacura.candidate.drawListTable = function(data){		
	$('.pctitle').html("Candidates").show();
	if(typeof data == "undefined"){
		$('#candidate_table').dataTable(); 
		dacura.toolbox.writeErrorMessage('.dataTables_empty', "No Candidates Found");		
	}
	else {
		$('#candidate_table tbody').html("");
		for (var i in data) {
			var obj = data[i];
			$('#candidate_table tbody').append("<tr id='cand" + obj.id + "'>" + 
			"<td>" + obj.id + "</td>" + 
			"<td>" + obj.candidate_class + "</td>" + 
			<?php if (isset($params['show_collection']) && $params['show_collection']) echo '"<td>" + obj.collectionid + "</td>" + '?>
			<?php if (isset($params['show_dataset']) && $params['show_dataset']) echo '"<td>" + obj.datasetid + "</td>" + '?>
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.version + "</td>" + 
			"<td>" + obj.schema_version + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + obj.modtime + "</td>" + 
			"</tr>");
			$('#cand'+obj.id).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#cand'+obj.id).click( function (event){
				window.location.href = dacura.system.pageURL() + "/" + this.id.substr(4);
		    }); 
		}
		$('#candidate_table').dataTable(<?=$dacura_server->getServiceSetting('candidate_datatable_init_string', "{}");?>).show();
	}
}
		

	$(function() {
		dacura.candidate.list();
	});
	</script>
</script>