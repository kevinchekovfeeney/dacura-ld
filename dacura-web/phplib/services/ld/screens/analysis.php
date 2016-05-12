<div id='analysis-screen' class='dch'>
	<?php include("dqs.php");?>
	<?php include("dependencies.php");?>
</div>
<script>

function initAnalysis(data, pconf){
	pconf.subscreen = '#analysis-subscreen';
	showAnalysisBasics(data, "#analysis-main");
	if(typeof data.analysis.validation == "object"){
		initDQS(data, pconf);
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}		
	$('#analysis-screen').show();
	$('.subscreen-close').html(dacura.system.getIcon('error'));
	$('.subscreen-close').click(function(){
  		$('.dacsub').hide("slide", { direction: "up" }, "slow");
		dacura.system.goTo(pconf.busybox);
	});
	$('#analysis-screen').tooltip({
        content: function () {
            return $(this).prop('title');
        },
    	show: {delay: 1000}
    });
}


function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function initDecorations(){
	$('#singleont-dependencies').hide();
}


var subscreens = {};

function analyse(data, pconf){
	initDecorations();
	if(typeof data.analysis == "object"){
		showAnalysisBasics(data);
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}
	$('.summary-entry').hover(function(){
		$(this).addClass('divhover');
	}, function() {
	    $(this).removeClass('divhover');
	});
	$('.summary-entry').click(function(){
		var act = this.id.substring(3);
		if(act == "warnings" || act == "errors" || act == "tests" || act == "imports" || act == "triples" || act == "structural"){
			showSubscreen(act, subscreens[act]);
		}
		else if(act == "dependencies" || act == "schema_dependencies"){
			showDependenciesControls();
		}
		else {
			showNSUsageControls();			
		}
	});
}

function getControlTableRow(rowdata){
	var html ="<tr class='control-table";
	if(typeof rowdata.unclickable != "undefined" && rowdata.unclickable){
		html += " unclickable-row";
	}
	else {
		html += " control-table-clickable";
	}
	html +="' id='row_" + rowdata.id + "'>";
	if(typeof rowdata.icon != "undefined"){
		html += "<td class='control-table-icon' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.icon + "</td>";
	}
	else {
		//html += "<td class='control-table-empty'>" + "</td>";
	}
	html += "<td class='control-table-number' id='" + rowdata.id + "-count'>" + rowdata.count + "</td>" +
	"<td class='control-table-variable' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.variable + "</td>" +
	"<td class='control-table-value'>" + rowdata.value + "</td></tr>";
	return html;
}

function getSummaryTableEntry(rowdata){
	var html = "<div class='summary-entry";
	if(typeof rowdata.unclickable != "undefined" && rowdata.unclickable){
		html += " unclickable-summary";
	}
	else {
		html += " clickable-summary";
	}
	html += "'";
	if(rowdata.id){
		html += " id='sum_" + rowdata.id + "'";
	}
	if(typeof rowdata.icon != "undefined"){
		html += "><span class='summary-icon' title='" + rowdata.help + "'>" + rowdata.icon + "</span>";
	}
	else {
		html += ">";
	}
	html +=	"<span class='summary-value' title='" + escapeHtml(rowdata.value) + "'>"  + rowdata.count + "</span> " +
	"<span class='summary-variable' title='" + escapeHtml(rowdata.value) + "'>" + rowdata.variable + "</span></div>";
	return html;
}

function getTreeEntries(tree){
	var ents = [];
	for(var i in tree){
		if(ents.indexOf(i) == -1){
			ents.push(i);
		}
		if(typeof(tree[i]) == 'object'){
			var ments = getTreeEntries(tree[i]);
			for(var j = 0; j<ments.length; j++){
				if(ents.indexOf(ments[j]) == -1){
					ents.push(ments[j]);
				}
			}
		}
	}
	return ents;
}


</script>