<?php 
/*
 * % Required best practice
preTestSchema(classCycleSC).
preTestSchema(propertyCycleSC).
% best practice
testSchema(noImmediateDomainSC).
testSchema(noImmediateRangeSC).
testSchema(notUniqueClassLabelSC).
testSchema(notUniqueClassSC).
testSchema(notUniquePropertySC).
testSchema(schemaBlankNodeSC).
% OWL DL
testSchema(orphanClassSC).
testSchema(orphanPropertySC). 
testSchema(invalidRangeSC). 
testSchema(invalidDomainSC).
testSchema(domainNotSubsumedSC).
testSchema(rangeNotSubsumedSC).

%%%% Instance Tests
%%%% Local testing for violation of specific known elements in update.
%%%% must be pred/6 and have argument list (X,P,Y,Instance,Schema,Reason)
%% best Practice
edgeConstraints(noPropertyDomainIC).
edgeConstraints(noPropertyRangeIC).
edgeConstraints(instanceBlankNodeIC).
%% OWL DL (Constraint)
edgeConstraints(invalidEdgeIC).
edgeConstraints(edgeOrphanInstanceIC).
edgeConstraints(notFunctionalPropertyIC).
edgeConstraints(notInverseFunctionalPropertyIC).
edgeConstraints(localOrphanPropertyIC).


 */


$dqs = array();
$dqs["classCycleSC"] = array(
	"name" => "Class Cycle",
	"default" => "on",
	"type" => "foundation",
	"graph" => "schema",
	"text" => "The schema has cycles in the class hierarchy"
);
$dqs["propertyCycleSC"] = array(
	"default" => "on",
	"name" => "Property Cycle",
	"type" => "foundation",
	"graph" => "schema",
	"text" => "The schema has cycles in the property hierarchy"
);

$dqs["noImmediateDomainSC"] = array(
	"name" => "No Immediate Domain",
	"type" => "bp",
	"graph" => "schema",
	"text" => "There is no domain specified directly for the class"
);

$dqs["noImmediateRangeSC"] = array(
	"name" => "No Immediate Range",
	"type" => "bp",
	"graph" => "schema",
	"text" => "There is no range specified directly for the class"
);

$dqs["notUniqueClassLabelSC"] = array(
	"name" => "Not Unique Class Label",
	"type" => "bp",
	"graph" => "schema",
	"text" => "The class label is not unique"
);

$dqs["notUniqueClassSC"] = array(
	"name" => "Not Unique Class",
	"type" => "bp",
	"graph" => "schema",
	"text" => "The class is not unique"
);

$dqs["notUniquePropertySC"] = array(
	"name" => "Not Unique Property",
	"type" => "bp",
	"graph" => "schema",
	"text" => "The property is not unique"
);

$dqs["schemaBlankNodeSC"] = array(
	"default" => "on",
	"name" => "Schema Blank Node",
	"type" => "bp",
	"graph" => "schema",
	"text" => "The schema contains blank nodes"
);

$dqs["orphanClassSC"] = array(
		"default" => "on",
		"name" => "Orphan Class",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The parent class does not exist in the schema"
);

$dqs["orphanPropertySC"] = array(
		"default" => "on",
		"name" => "Orphan Property",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The parent property does not exist in the schema"
);

$dqs["invalidRangeSC"] = array(
		"default" => "on",
		"name" => "Invalid Range",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The range is invalid"
);

$dqs["invalidDomainSC"] = array(
		"default" => "on",
		"name" => "Invalid Domain",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The domain is invalid"
);

$dqs["domainNotSubsumedSC"] = array(
		"default" => "on",
		"name" => "Domain Not Subsumed",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The domain is not subsumed"
);

$dqs["rangeNotSubsumedSC"] = array(
		"default" => "on",
		"name" => "Range Not Subsumed",
		"type" => "owldl",
		"graph" => "schema",
		"text" => "The range is not subsumed"
);

$dqs["noPropertyDomainIC"] = array(
		"default" => "on",
		"name" => "No Property Domain",
		"type" => "bp",
		"graph" => "instance",
		"text" => "The domain of the property is not defined"
);

$dqs["noPropertyRangeIC"] = array(
		"default" => "on",
		"name" => "No Property Range",
		"type" => "bp",
		"graph" => "instance",
		"text" => "The range of the property is not defined"
);

$dqs["instanceBlankNodeIC"] = array(
		"default" => "on",
		"name" => "Instance Blank Node",
		"type" => "bp",
		"graph" => "instance",
		"text" => "Blank nodes are present in the instance data"
);

$dqs["invalidEdgeIC"] = array(
		"default" => "on",
		"name" => "Invalid Edge",
		"type" => "owldl",
		"graph" => "instance",
		"text" => "Invalid edge in instance data"
);

$dqs["edgeOrphanInstanceIC"] = array(
		"default" => "on",
		"name" => "Edge Orphan Instance",
		"type" => "owldl",
		"graph" => "instance",
		"text" => "Orphan edge in instance data"
);

$dqs["notFunctionalPropertyIC"] = array(
		"default" => "on",
		"name" => "Not Functional Property",
		"type" => "owldl",
		"graph" => "instance",
		"text" => "Not a functional property in instance data"
);

$dqs["notInverseFunctionalPropertyIC"] = array(
		"default" => "on",
		"name" => "Not Inverse Functional Property",
		"type" => "owldl",
		"graph" => "instance",
		"text" => "Not inverse property in instance data"
);

$dqs["localOrphanPropertyIC"] = array(
		"default" => "on",
		"name" => "Local Orphan Property",
		"type" => "owldl",
		"graph" => "instance",
		"text" => "Not inverse property in instance data"
);


function getDQSOptions($dqs, $graph){
	$options = array();
	foreach($dqs as $id => $props){
		//if(in_array($graph, $props['graph'])){
		if($graph == $props['graph']){
			$options[$id] = $props;
		}
	}
	return $options;
}

function constraintTypeText($graph, $ct){
	if($graph == "schema"){
		if($ct == "foundation"){
			$ct = "Foundational Constraints";
		}
		elseif($ct == "owldl"){
			$ct = "Structural Rules";
		}
		elseif($ct == "bp"){
			$ct = "Schema best practice rules";
		}
	}
	else {
		if($ct == "owldl"){
			$ct = "Structural Rules";
		}
		elseif($ct == "bp"){
			$ct = "Instance data best practice rules";
		}
	}
	return $ct;
}

function getDQSCheckboxes($dqs, $graph){
	$boxes = array();
	$options = getDQSOptions($dqs, $graph);
	foreach($options as $id => $props){
		$checked = (isset($props['default']) && $props['default'] == "on") ? " checked" : "";
		$html = "<span class='dqs-input-field'><input type='checkbox' class='dqsoption dqsoption-$graph' id='$id' value='$id' title='" . $props['text'] . "' $checked><label for='$id'>".$props['name']."</label></span>";
		$boxes[$props['type']][] = $html;
	}
	$html = "";
	foreach($boxes as $id => $entries){
		$ct = constraintTypeText($graph, $id);
		$html .= "<div class='dqs-type dqs-$id'><div class='dqs-category-title'>".$ct."</div>".implode(" ", $entries)."</div>";
	}
	return $html;
}
?>
<?php if ($params['graph'] == "both"){?>
<table class='dqs-config'>
	<tr>
		<th>Schema Graph <span class='dqs-schema-all'><input type='checkbox' id='dqs-schema-all'><label for='dqs-schema-all'>Select All</label></span>
		</th>
		<td><?= getDQSCheckboxes($dqs, "schema");  ?></td>
	</tr>
	<tr>
		<th>Instance Graph <span class='dqs-instance-all'><input type='checkbox' id='dqs-instance-all'><label for='dqs-instance-all'>Select All</label></span></th>
		<td><?= getDQSCheckboxes($dqs, "instance");  ?></td>
	</tr>
</table>
<?php } else {?>
<div class='dqsopts'>
	<div class='dqs-opts-title'>Choose which constraints to apply</div>
	<span class='dqs-all'><input type='checkbox' id='dqs-all'> <label for='dqs-all'>Select All</label></span>
	<?php echo getDQSCheckboxes($dqs, $params['graph']);?>
</div>
<?php } ?>


<script>
dacura.dqs = {}
dacura.dqs.getSelection = function(graph){
	var tests = [];
	$('input:checkbox.dqsoption-' + graph).each(function () {
		if(this.checked){
			tests.push($(this).val());
		}
    });
	return tests;
};

$('.dqsoption').button();
$( "#dqs-all" ).button().click(function(event){
	if($('#dqs-all').is(":checked")){
		$("#dqs-instance-all").prop('checked', true).button("refresh");
		$("#dqs-schema-all").prop('checked', true).button("refresh");
		$("input:checkbox.dqsoption").prop('checked', true).button("refresh");
	}
	else {
		$("#dqs-instance-all").prop('checked', false).button("refresh");
		$("#dqs-schema-all").prop('checked', false).button("refresh");
		$("input:checkbox.dqsoption").prop('checked', false).button("refresh");
	}					
});
$( "#dqs-instance-all" ).button().click(function(event){
	if($('#dqs-instance-all').is(":checked")){
		$("input:checkbox.dqsoption-instance").prop('checked', true).button("refresh");
	}
	else {
		$("input:checkbox.dqsoption-instance").prop('checked', false).button("refresh");
	}					
});
$( "#dqs-schema-all" ).button().click(function(event){
	if($('#dqs-schema-all').is(":checked")){
		$("input:checkbox.dqsoption-schema").prop('checked', true).button("refresh");
	}
	else {
		$("input:checkbox.dqsoption-schema").prop('checked', false).button("refresh");
	}					
});


</script>

