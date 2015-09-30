<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />
<script>
function showDQSOptions(){
	$('#dqsopts').show();	
}

function validateSchema(id){
	var ajs = dacura.schema.api.validate_ontology(id);
	var msgs = { "busy": "Validating Ontology with Dacura Quality Service", "fail": "Schema validation failed"};
	var self = this;
	ajs.handleResult = function(data){ alert(JSON.stringify(data));};
	dacura.system.invoke(ajs, msgs);
}

$(function() {
	$('.dqsoption').button();
});
</script>
<div id='tab-holder'>
	 <ul id="ontology-pane-list" class="dch">
	 	<li><a href="#ontology-dependencies">Dependencies</a></li>
	 	<li><a href="#ontology-contents">Contents</a></li>
	 </ul>
	<div id="meta-holder">
		<div id="ontology-dependencies">
			<div id='dep-msgs'></div>
		
			<div id='deptable'>
			</div>
			<div class="tool-buttons">
	   			<button class="dacura-button depend-button dch" id="depend-button">Calulate Dependencies</button>
	      	</div>
			<div id="config-holder" class="dch">
				<div id="ontology-config">
					<div id='dqs' class="">
						<div class='title'>Dacura Quality Service</div>
						<a class='button2' href='javascript:validateSchema("<?=$params['id']?>");'>Validate Schema</a>
						<div id='dqs-setting'>Currently configured to run all tests <a href='javascript:showDQSOptions();'>Change</a></div>
						<div id='dqsopts' class='dch'>
							<?= $service->getDQSCheckboxes("schema"); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
 	<div id="contents-holder">
		<div id='ontology-contents' >
			<div id='lded-msgs'></div>
			<div id='viewont'>
				<?php echo $service->showLDEditor($params);?>
			</div>
		</div>
	</div>
</div>
<div id="tabletemplates" class='dacura-templates'>
	<?php echo $service->includeSnippet("ldentity-header")?>
	 <div id="depend-template">
	 	<table class='ont-depend'>
			<thead>
				<tr>
					<th>Shorthand</th>
					<th>URL</th>
					<th>Utilisation</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>

<script>
dacura.schema.showOntology = function(obj){
	dacura.system.setLDEntityToolHeader(obj);
	if(typeof obj.meta.dependencies != "undefined"){
		dacura.schema.showDependencies(obj.meta.dependencies);
	}
	else {
		dacura.system.writeWarningMessage("Dependencies have not been calculated", '#dep-msgs', 
				"Before an ontology can be deployed, Dacura must calculate the ontologies that it depends upon");
		$('#depend-button').show(); 
	}
	//$('#ontid').html(obj.id);
	//$('#onturl').val(obj.url);
	//$('#ontversion').val(obj.real_version);
	//$('#onttitle').val(obj.title);
	//$('#ontstatus').val(obj.status);
	//$('#ontdescr').val(obj.description);
	//$('#ontcreated').html("created " + timeConverter(obj.created));
	//$('#ontmodified').html("modified " + timeConverter(obj.modified));
}

depids = [];

dacura.schema.showDependencies = function(deps){
	var k = $('#depend-template').html();
	$('#deptable').html(k);
	for (var key in deps) {
	  	if (deps.hasOwnProperty(key)) {
			if(!isEmpty(deps[key])){
			 	$('#deptable table tbody').append(
					 	"<tr class='ontology-list' id='ontology_" + depids.length + "'>" + 
					 	"<td class='ontology_" + depids.length + "'>" + key + "</td><td>" + 
					 	deps[key]['url'] + "</td><td class='ontology_" + depids.length + "'>" + deps[key]['occurrences'] +
				  	"</td><td>" + deps[key]['status'] + "</tr>");
			  	$('.ontology_' + depids.length).click( function (event){
				  	window.location.href = "schema/" + depids[this.parentNode.id.substr(9)];
			    });
			  	depids[depids.length] = deps[key].id;		  	 		  	
				  	
			}
		}
	}
	$('.ontology-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	$('#ontology-table-holder .ontology_table').dataTable(<?=$dacura_server->getServiceSetting('ontology_datatable_init', "{}");?>);
}

dacura.schema.gatherOntologyDetails = function(){
	var details = {};
	details.url = $('#onturl').val();
	details.title = $('#onttitle').val();
	details.status = $('#ontstatus').val();
	details.description = $('#ontdescr').val();
	details.real_version = $('#ontversion').val();
	return details;
}


function clearResultMessage(){
	dacura.system.clearResultMessage();	
}
function initDecorations(){
	//view format choices
	$('#depend-button').button().click(function(event){
		dacura.schema.calculateDependencies("<?=$params['id']?>");
    });
}


$(function() {
	initDecorations();
	dacura.system.init({"mode": "tool", "targets": {resultbox: "#lded-msgs", errorbox: "#lded-msgs", busybox: "#tab-holder"}});
	dacura.editor.init({"entity_type": "ontology"});
	dacura.editor.load("<?=$params['id']?>", dacura.schema.fetchOntology, dacura.schema.updateOntology);
	$('#ontology-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
            clearResultMessage();
        }
    });
});
</script>
