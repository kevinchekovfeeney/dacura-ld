<?php $hsds = new DacuraServer($service->settings);
$choices = $hsds->getUserAvailableContexts("admin", true);
$workflowid = isset($params['workflowid']) ? $params['workflowid'] : false;
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
	<?php echo $service->getBreadCrumbsHTML();?>
	</div>
	<div class="pcbusy"></div>
	<div class="dch" id="workflowedit">
		<table class="dc-wizard" id="workflow_table">
			<thead>
			</thead>
			<tbody>
				<tr>
					<th>Name</th><td id='wfname'><input type='text' id='wfnameip' value=""></td>
				</tr>
				<tr>
					<th>Description</th><td id='wfdescr'><input type='text' id='wfdescrip' value=""></td>
				</tr>
				<tr>
					<th>Type</th><td id='wftype'><input type='input' id='wftypeip' value=""></td>
				</tr>
			</tbody>
		</table>
		<div id="wfinput" class="pcsection">
			<div class="pcsectionhead">Input</div>	
		</div>
		<div id="wfoutput" class="pcsection">
			<div class="pcsectionhead">Output</div>	
		</div>
		<div id="wfprocess" class="pcsection">
			<div class="pcsectionhead">Process</div>	
		</div>
	</div>
<script>
dacura.workflow.drawNewWorkflowForm = function(){
	$('.pctitle').html("Create New Workflow Process").show();
	$('.pcbreadcrumbs').show();
	$('#workflowedit').show();
};

dacura.workflow.drawEditWorkflowForm = function(x){
	alert(x);
}

$(function() {
	<?php if(!$workflowid){
		echo "dacura.workflow.drawNewWorkflowForm();";
	} 
	else {
		echo "dacura.workflow.drawEditWorkflowForm('$workflowid');";
	}?>
});
</script>