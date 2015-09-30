<?php $hsds = new DacuraServer($service);
$choices = $hsds->getUserAvailableContexts("admin", true);
?>
<style>
.dch { display: none;}
</style>
<div id="pagecontent">
	<div class="pctitle dch"></div>
	<div class="pcbreadcrumbs dch">
		<div class="pccon">
		<?php $service->renderScreen("available_context", array("type" => "admin"), "core");?>
		</div>
	<?php echo $service->getBreadCrumbsHTML($arg);?>
	</div>
	<div class="pcbusy"></div>
	<div class="dch" id="workflowlisting">
		<div class="pcsection pcdatatables">
			<table id="worklow_table">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Description</th>
					<th>Status</th>
					<th>Candidates In</th>
					<th>Candidates Out</th>
					<th>Candidates being processed</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="userhelp"></div>
		<div class="pcsection pcbuttons">
			<a class="button2" href="<?=$service->get_service_url('workflow', array('create'))?>">Create New Workflow Step</a>
		</div>
	</div>
</div>
<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.dataTables.css")?>" />
<script>
dacura.workflow.listworkflow = function(){
	var ajs = dacura.workflow.api.listing();
	var self=this;
	ajs.beforeSend = function(){
		dacura.system.showBusyMessage("Retrieving Workflow List", "", '.pcbusy');
	};
	ajs.complete = function(){
		dacura.system.clearBusyMessage('.pcbusy');
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			if(data.length > 0 ){
				dacura.workflow.drawListTable(JSON.parse(data));
			}
			else {
				dacura.workflow.drawListTable();
			}    	
			$('#workflowlisting').show();
		})
		.fail(function (jqXHR, textStatus){
			dacura.system.writeErrorMessage("", '#userhelp', "", "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.workflow.drawListTable = function(data){
	$('.pctitle').html("List of processes in workflow").show();
	$('.pcbreadcrumbs').show();
	if(typeof data == "undefined"){
		$('#workflow_table').hide(); 
		dacura.system.writeErrorMessage("", '.pcbusy', "", "No Workflow Processes Found");		
	}
	else {
		$('#workflow_table tbody').html("");
		$.each(data, function(i, obj) {
			if(obj.status != "deleted"){
				//url='javascript:alert("hello world")';
				$('#worklfow_table tbody').append("<tr id='workflow" + obj.id + "'><td>" + obj.id + "</td><td>" + obj.type 
						+ "</td><td>" + obj.description + "</td><td>" + obj.status + "</td><td>" + obj.counts.input + 
						 "</td><td>" + obj.counts.output + "</td><td>" + obj.counts.processing + "</td></tr>");
				$('#workflow'+obj.id).hover(function(){
					$(this).addClass('userhover');
				}, function() {
				    $(this).removeClass('userhover');
				});
				$('#workflow'+obj.id).click( function (event){
					window.location.href = dacura.system.pageURL() + "/" + this.id.substr(8);
			    }); 
			}
		});
		$('#workflow_table').dataTable();
	}
}

$(function() {
	dacura.workflow.listworkflow();
});
</script>

