<script>

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

function printCreated(obj){
	return timeConverter(obj.createtime);
}

function printModified(obj){
	return timeConverter(obj.modtime);
}

function printCreatedBy(obj){
	return "<a href='update/" + obj.created_by + "'>" + obj.created_by + "</a>";
}


</script>