<div id='ld-resultbox' class='dch'>
	<div class='ld-resultbox dacura-user-message-box'>
		<div class='mtitle'>
			<span class="result-icon"></span><span class="result-title"></span>
		</div>
		<div class="mbody"></div>
		<div class="mbuttons">
			<button class='cancel-update'>Return To Editor</button>
			<button class='confirm-update'>Confirm Update</button>
		</div>
	</div>

	<div class='ld-resultbox-extra dch'>
		<div class='ld-resultbox-title'>
			<h2 class='ld-extra ld-forward'>Change command</h2>
			<h2 class='ld-extra ld-backward'>Rollback command</h2>
			<h2 class='ld-extra ld-before'>State Before Update</h2>
			<h2 class='ld-extra ld-after'>State After Update</h2>
			<h2 class='ld-extra ld-change'>Changes Highlighted</h2>
			<h2 class='ld-extra ld-candidate'>Updates to the candidate / linked data graph</h2>
			<h2 class='ld-extra ld-report'>Updates to the report / triple-store graph</h2>
			<h2 class='ld-extra ld-updates'>Updates to the update graph</h2>
			<h2 class='ld-extra ld-dqs'>Dacura Quality Service Violations</h2>
		</div>
		
		<div class='ld-resultbox-options'>
			<span class='rb-options'>
			    <input type="radio" class='resoption roption' id="show_forward" name="rformat"><label class='resoption' title="Show forward delta to <?php echo $params['entity_type']?>" for="show_forward">Forward</label>
				<input type="radio" class='resoption roption' id="show_backward" name="rformat"><label class='resoption' title="Show backward delta for rolling back update to <?php echo $params['entity_type']?>" for="show_backward">Backward</label>
				<input type="radio" class='resoption roption' id="show_before" name="rformat"><label class='resoption' title="Show <?php echo $params['entity_type']?> before update" for="show_before">Before</label>
				<input type="radio" class='resoption roption' id="show_after" name="rformat"><label class='resoption' title="Show <?php echo $params['entity_type']?> after update" for="show_after">After</label>
				<input type="radio" class='resoption roption' checked="checked" id="show_change" name="rformat"><label class='resoption' title="Show what has changed in the <?php echo $params['entity_type']?>" for="show_change">Change</label>
				<input type="radio" class='candoption roption' id="show_candidate" name="rformat"><label class='candoption' title="Show updates to stored linked data version of <?php echo $params['entity_type']?> " for="show_candidate">Linked Data Updates</label>
				<input type="radio" class='metaoption roption' id="show_meta" name="rformat"><label class='metaoption' title="Show updates to <?php echo $params['entity_type']?> status" for="show_meta">Meta Updates</label>
				<input type="radio" class='repoption roption' id="show_report" name="rformat"><label class='repoption' title="Show updates to triple-store graph of <?php echo $params['entity_type']?> " for="show_report">Triplestore Updates</label>
				<input type="radio" class='updoptoin roption' id="show_updates" name="rformat"><label class='updoption' title="Show updates to triple-store graph of <?php echo $params['entity_type']?> " for="show_updates">Pending Updates</label>
				<input type="radio" class='dqs roption' id="show_dqs" name="rformat"><label class='dqs' title="Show results of Data Quality Service <?php echo $params['entity_type']?> Checks" for="show_dqs">Quality Violations</label>
			</span>
		</div>
	
		<div class='ld-resultbox-content'>
			<div class='ld-extra ld-forward'>
			</div>
			<div class='ld-extra ld-backward'>
			</div>
			<div class='ld-extra ld-before'>
			</div>
			<div class='ld-extra ld-after'>
			</div>
			<div class='ld-extra ld-change'>
			</div>
			<div class='ld-extra ld-candidate'>
			</div>
			<div class='ld-extra ld-report'>
			</div>
			<div class='ld-extra ld-updates'>
			</div>
			<div class='ld-extra ld-dqs error-details'>
				<table class='rbtable dqs-error-table'>
					<thead>
						<tr><td>#</td><td>Error</td><td>Property</td><td>Message</td></tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>

dacura.ldresult = {
	update_type: "update",
	counts: { "errors" : 0, "warnings": 0, "dqs_errors": 0, "candidate_updates": 0, "report_updates": 0, "meta_updates": 0}	
};

dacura.ldresult.showDecision = function(dcm, jq, cancel, confirm, shortmode){
	dacura.ldresult.counts = { "errors" : 0, "warnings": 0, "dqs_errors": 0, "candidate_updates": 0, "report_updates": 0, "meta_updates": 0}
	var hasdepth = false;
	var hasextra = false;
	$(jq).html($('#ld-resultbox').html());
	$(jq + ' .result-title').html(this.getDecisionTitle(dcm));
	var cls = dacura.ldresult.getResultClass(dcm);
	$(jq + ' .result-icon').addClass("result-" + cls);
	$(jq + ' .result-icon').html(dacura.system.resulticons[cls]);	
	var msg = dacura.ldresult.getResultMessage(dcm, dacura.ldresult.update_type);
	$(jq + ' .ld-resultbox .mbody').html(msg);
	$(jq + ' .ld-resultbox').addClass("dacura-" + cls);
	$(jq + ' .ld-resultbox').show();
	if(typeof confirm != "undefined"){
		$(jq + ' .mbuttons button.confirm-update').button().click(confirm).show();
	}
	else {
		$(jq + ' .mbuttons button.confirm-update').hide();
	}
	if(typeof cancel != "undefined"){
		$(jq + ' .mbuttons button.cancel-update').button().click(cancel).show;
	}
	else {
		$(jq + ' .mbuttons button.cancel-update').hide();
	}
	$(jq + ' .mbuttons').show();
	
	if(dcm.result){
		hasdepth = true;
		hasextra = true;
		if(typeof dcm.result.forward != "undefined"){
			$(jq + ' div.ld-forward').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.forward, false, 4) + "</div>");
			$(jq + ' div.ld-backward').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.backward, false, 4)+ "</div>");
			if(dcm.format == "json" || dcm.format == "jsonld"){
				$(jq + ' div.ld-before').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.original.display, null, 4) + "</div>");
				$(jq + ' div.ld-after').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.changed.display, null, 4) + "</div>");
				$(jq + ' div.ld-change').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.display) + "</div>");
			}
			else if(dcm.format == 'html'){
				$(jq + ' div.ld-before').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.original.display + "</div>");		
				$(jq + ' div.ld-after').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.changed.display + "</div>");		
				$(jq + ' div.ld-change').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.display + "</div>");			
			}
			else {
				$(jq + ' div.ld-before').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.original.display + "</div>");		
				$(jq + ' div.ld-after').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.changed.display + "</div>");		
				$(jq + ' div.ld-change').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.display + "</div>");		
			}
		}
		else {
			if(dcm.format == "json" || dcm.format == "jsonld"){
				$(jq + ' div.ld-change').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.display) + "</div>");
			}
			else if(dcm.format == 'html'){
				$(jq + ' div.ld-change').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.display + "</div>");			
			}
			else {
				$(jq + ' div.ld-change').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.display + "</div>");		
			}
		}
	}
	else {
		$(jq + ' .resoption').hide();
	}
	if(typeof(dcm.report_graph_update) != "undefined" && dcm.report_graph_update != null){
	 	for (var name in dcm.report_graph_update.errors) {
	 		if(typeof dcm.report_graph_update.errors[name] != "undefined" && dcm.report_graph_update.errors[name].length > 0){
	 			$(jq + " .dqs-error-table tbody").append(this.getErrorDetailsHTML(dcm.report_graph_update.errors[name], dcm.action));
	 	 	}
	 	}
	 	if(dacura.ldresult.counts.dqs_errors > 0){
		 	hasextra = true;
			$(jq + ' label.dqs').html(dacura.ldresult.counts.dqs_errors + " Quality Violations"); 	
	 	}
	 	else {
			$(jq + ' .dqs').hide(); 		 	
		 }	 	
	}
	else {
		$(jq + ' .dqs').hide(); 	
	} 	
	if(typeof dcm.update_graph_update != "undefined" && dcm.update_graph_update != null){
		var x = this.getUpdateGraphUpdateHTML(dcm);
		if(x != ""){
	 		$(jq + ' div.ld-updates').append(x);
	 		hasextra = true;
		}
 		else {
 			$(jq + ' .updoption').hide(); 	 		
 	 	} 	
	}
 	else {
		$(jq + ' .updoption').hide(); 	
 	}
 	if(typeof dcm.candidate_graph_update != "undefined" && dcm.candidate_graph_update != null){
 		$(jq + ' div.ld-candidate').append(this.getCandidateGraphUpdateHTML(dcm));
 		if(dacura.ldresult.counts.candidate_updates > 0){
			$(jq + ' label.candoption').html(dacura.ldresult.counts.candidate_updates + " Linked Data Updates"); 
			hasextra = true;
 		}
 		else {
 			$(jq + ' .candoption').hide(); 		
 	 	}		
 	}
 	else {
		$(jq + ' .candoption').hide(); 	
 	}
 	if(typeof dcm.report_graph_update != "undefined" && dcm.report_graph_update != null){
 		$(jq + ' div.ld-report').append(this.getReportGraphUpdateHTML(dcm));
 		if(dacura.ldresult.counts.report_updates > 0){
			$(jq + ' label.repoption').html(dacura.ldresult.counts.report_updates + " Triplestore Updates"); 
			hasextra = true;
 		}
 		else {
 			$(jq + ' .repoption').hide(); 	
 	 	}			
	}
 	else {
		$(jq + ' .repoption').hide(); 	
 	}
	$(jq + ' .metaoption').hide(); 	
	$(jq + " .ld-extra").hide();
	if(dacura.ldresult.counts.dqs_errors > 0){
		$(jq + ' #show_dqs').attr("checked", "checked");		
		$(jq + ' .ld-dqs').show(); 
	}
	else if(hasdepth){
		$(jq + ' #show-change').attr("checked", "checked"); 		
		$(jq + ' .ld-change').show(); 
	}
	if(hasextra){
		$(jq + ' .ld-resultbox-extra').show(); 	
	 	$(jq + " .rb-options").buttonset();
		$(jq + " .roption").button().click(function(event){
			$(jq + " .ld-extra").hide();	
			$(jq + " .ld-" + this.id.substring(5)).show();				
		});			
	}
	$(jq).show();	
}


dacura.ldresult.getDecisionTitle = function (dcm){
	if(dcm.decision == "accept"){
		if(dcm.test){
			return dcm.msg_title + " " + dcm.action + " was tested and approved ";
		}
		else {
			return dcm.msg_title + " " + dcm.action + " was accepted and published";
		}	
	}	
	else if(dcm.decision == "pending"){
		if(dcm.test){
			return dcm.msg_title + " " + dcm.action + " will require approval";
		}
		else {
			return dcm.msg_title + " " + dcm.action + " was submitted for approval";
		}	
	}
	else if(dcm.decision == "reject"){
		if(dcm.test){
			return "Test " + dcm.action + " - " + dcm.msg_title;
		}
		else {
			return dcm.action + " - " + dcm.msg_title;
		}
	}
	else {
		return dcm.msg_title + " (? " + dcm.decision + " ?)";
	}
}

dacura.ldresult.getResultClass = function (dcm){
	if(dcm.decision == 	'reject'){
		return "error";
	}
	else if(dcm.errcode > 200){
		return "error";
	}
	else if(dcm.decision == 'pending'){
		return "warning";
	}
	else if(dcm.decision == 'confirm'){
		return "success";
	}
	else if(dcm.decision == 'accept'){
		return "success";
	}
};


dacura.ldresult.getResultMessage = function (dcm, update_type){
	if(typeof update_type == "undefined"){
		update_type = "update";
	}
	if(typeof(dcm.msg_body) != "undefined" && dcm.msg_body != ""){
		msg = dcm.msg_body;
	}
	else if(dcm.decision == "reject"){
		if(dcm.test){
			msg = "This " + update_type + " would not be permitted. ";
		}
		else {	
			msg = "This " + update_type + " is not permitted. ";
		}
	}
	else {
		if((dcm.test && dcm.decision == "accept") || dcm.decision == "confirm"){
			msg = "This " + update_type + " will be accepted if confirmed";
		}
		else if(dcm.test && dcm.decision == "pending"){
			msg = "This " + update_type + " would need to be approved";			
		}
		else if(dcm.decision == "pending"){
			msg = "The " + update_type + " is awaiting approval";
		}
		else if(dcm.decision == "accept"){
			msg = "The  " + update_type + " has been accepted.";
		}	
		else {
			msg = "Accessing Unknown thing(*^&(^";
		}
	}
	msg += dacura.ldresult.getErrorsHTML(dcm);	
	msg += dacura.ldresult.getWarningsHTML(dcm);
	msg += dacura.ldresult.getStatusChangeWarningsHTML(dcm);
	return msg;
}	

dacura.ldresult.getStatusChangeWarningsHTML = function(dcm){
	var html = "";
	if(typeof dcm.candidate_graph_update != "undefined" && dcm.candidate_graph_update != null && typeof dcm.candidate_graph_update.meta != "undefined"){
		html = "<div class='rb-status-change'>";
		for (var key in dcm.candidate_graph_update.meta) {
			html += "<span class='rb-status-key'>" + key + " changed </span>" + 
				"<span class='rb-status-orig'>from " + dcm.candidate_graph_update.meta[key][0] + "</span>" + 
				"<span class='rb-status-changed'>to " + dcm.candidate_graph_update.meta[key][1] + "</span>";
		}	
		html += "</div>";
	}
	return html;
}

dacura.ldresult.getWarningsHTML = function(dcm){
	var html = "";
	if(typeof dcm.warnings != "undefined" && dcm.warnings.length > 0){
		var errhtml = "";
		for(var i = 0; i < dcm.warnings.length; i++){
			dacura.ldresult.counts.warnings++;
			errhtml += "<div class='rbwarning'>Warning: <span class='action'>" + dcm.warnings[i].action +
				"</span><span class='title'>" + dcm.warnings[i].msg_title + "</span><span class='body'>" + 
				dcm.warnings[i].msg_body + "</span></div>";
		}
		if(errhtml.length > 0){
			html = "<div class='api-warning-details'>" + errhtml + "</div>";
		}	
	}
	return html;	
}

dacura.ldresult.getErrorsHTML = function(dcm){
	var html = "";
	if(typeof dcm.errors != "undefined" && dcm.errors.length > 0){
		var errhtml = "";
		for(var i = 0; i < dcm.errors.length; i++){
			dacura.ldresult.counts.errors++;
			errhtml += "<div class='rberror'>Error: <span class='action'>" + dcm.errors[i].action +
				"</span><span class='title'>" + dcm.errors[i].msg_title + "</span><span class='body'>" + 
				dcm.errors[i].msg_body + "</span></div>";
		}
		if(errhtml.length > 0){
			html = "<div class='api-warning-details'>" + errhtml + "</div>";
		}	
	}
	return html;	
}

dacura.ldresult.getErrorDetailsTable = function(errors){
	var html = "<table class='rbtable dqs-error-table'><thead><tr><th>#</th><th>Error</th><th>Property</th><th>Message</th><th>Raw</th></tr></thead></tbody>";
	html += this.getErrorDetailsHTML(errors);
	html += "</tbody></table>";
	return html;
}

dacura.ldresult.getErrorDetailsHTML = function(errors){
	if(typeof errors != "undefined"){
		var errhtml = "";
		for (var key in errors) {
			dacura.ldresult.counts.dqs_errors++;
			  if (errors.hasOwnProperty(key)) {
					//errhtml += "<tr><td>" + key + "</td><td>" + JSON.stringify(errors[key], 0, 4) + "</td></tr>";
					errhtml += "<tr><td>"+key+"</td><td>"+errors[key].error+"</td><td>"+errors[key].property+"</td><td>"+errors[key].message+"</td><td class='rawjson'>" + JSON.stringify(errors[key], 0, 4) + "</td></tr>";
			  }
		}
	}
	return errhtml;
}	

dacura.ldresult.getReportGraphUpdateHTML = function(dcm){
	var rupdates = dcm.report_graph_update;
	var html ="<div class='api-graph-testresults report-graph'>";
	if(rupdates.hypothetical || (rupdates.inserts.length == 0 && rupdates.deletes.length == 0)){
		html += "<div class='info'>No changes to report graph</div>";		
	}
	if((rupdates.inserts.length > 0 || rupdates.deletes.length > 0)){
		dacura.ldresult.counts.report_updates = rupdates.inserts.length + rupdates.deletes.length; 
		var insword = "inserted";
		var delword = "deleted"
		if(dcm.test || dcm.decision != "accept"){
			if(rupdates.hypothetical){
				insphrase = "would be " + insword;
				delphrase = "would be " + delword;
			}
			else {
				insphrase = "will be " + insword;
				delphrase = "will be " + delword;
			}
		}
		else {
			insphrase = insword;
			delphrase = delword;		
		}
		var instext = rupdates.inserts.length + " quad";
		if(rupdates.inserts.length != 1) instext += "s" 
		instext += " " + insphrase;
		var deltext = rupdates.deletes.length + " quad";
		if(rupdates.deletes.length != 1) deltext += "s";
		deltext += " " + delphrase;
		if(rupdates.hypothetical){
			html += "<div class='api-report-hypotheticals'>";	
		}
		else {
			html += "<div class='title'>Report Graph " + instext + " " + deltext + "</div>";
			html += "<div class='api-report-updates'>";
		}
		if(rupdates.inserts.length > 0){
			html += this.getTripleTableHTML(rupdates.inserts, "Quads " + insword, true, "report-insert-triples"); 
		}
		if(rupdates.deletes.length > 0){
			html += this.getTripleTableHTML(rupdates.deletes, "Quads " + delword, true, "report-delete-triples"); 
		}
		return html + "</div>";
	}
}

dacura.ldresult.getUpdateGraphUpdateHTML = function(dcm){
	var html ="<div class='api-graph-testresults update-graph'>";
	//if(typeof dcm.update_graph_update.meta != "undefined"){
	//	html += this.getMetaUpdatesHTML(dcm.update_graph_update.meta);
	//}
	if((typeof dcm.update_graph_update.inserts.forward == "undefined" || dcm.update_graph_update.inserts.forward == "") &&
		(typeof dcm.update_graph_update.inserts.backward == "undefined" || dcm.update_graph_update.inserts.backward == "") && 
		(typeof dcm.update_graph_update.deletes.forward == "undefined" || dcm.update_graph_update.deletes.forward == "") &&
		(typeof dcm.update_graph_update.deletes.backward == "undefined" || dcm.update_graph_update.deletes.backward == "")){
		return "";		
	}
	else {
		html += "<div class='info'>Changes to update graph</div>";		
		html += getJSONUpdateTableHTML(dcm.update_graph_update);
	}	
	return html + "</div>";
};

dacura.ldresult.getCandidateGraphUpdateHTML = function(dcm){
	var cupdates = dcm.candidate_graph_update;
	var html ="<div class='api-graph-testresults candidate-graph'>";
	if(cupdates.hypothetical || (cupdates.inserts.length == 0 && cupdates.deletes.length == 0)){
		html += "<div class='title'>No changes to candidate graph</div>";		
	}
	if(typeof cupdates.meta != "undefined"){
		var mhtml = this.getMetaUpdatesHTML(cupdates.meta);
		if(cupdates.hypothetical){
			html += "<div class='api-candidate-meta api-candidate-hypotheticals'>" + mhtml + "</div>";
		}
		else {
			html += "<div class='api-candidate-meta'>" + mhtml + "</div>";			
		}
	}
	if(!(cupdates.inserts.length == 0 && cupdates.deletes.length == 0)){
		dacura.ldresult.counts.candidate_updates = cupdates.inserts.length + cupdates.deletes.length; 
		var insword = "inserted";
		var delword = "deleted"
		if(dcm.test || dcm.decision != "accept"){
			if(cupdates.hypothetical){
				insphrase = "would be " + insword;
				delphrase = "would be " + delword;
			}
			insphrase = "will be " + insword;
			delphrase = "will be " + delword;
		}
		else {
			insphrase = insword;
			delphrase = delword;		
		}
		var instext = cupdates.inserts.length + " triple";
		if(cupdates.inserts.length != 1) instext += "s" 
		instext += " " + insphrase;
		var deltext = cupdates.deletes.length + " triple";
		if(cupdates.deletes.length != 1) deltext += "s";
		deltext += " " + delphrase;
		if(cupdates.hypothetical){
			html += "<div class='api-candidate-hypotheticals'>";	
		}
		else {
			html += "<div class='title'>Candidate Graph " + instext + " " + deltext + "</div>";
			html += "<div class='api-candidate-updates'>";		
		}
		if(cupdates.inserts.length > 0){
			html += this.getTripleTableHTML(cupdates.inserts, "Triples " + insword, false, "candidate-insert-triples"); 
		}
		if(cupdates.deletes.length > 0){
			html += this.getTripleTableHTML(cupdates.deletes, "Triples " + delword, false, "candidate-delete-triples"); 
		}
		return html + "</div>";
	}
	else {
		return html + "</div>";	
	}
}

dacura.ldresult.getMetaUpdatesHTML = function(meta){
	var thtml = "";
	for (var key in meta) {
		  if (meta.hasOwnProperty(key)) {
			  dacura.ldresult.counts.meta_updates++; 
			  thtml += key + ": "; 
			  if(typeof meta[key] == "object" && meta[key] != null){
				  thtml += meta[key][0] + " " + meta[key][1] + "<br>";
			  }
			  else {
				  thtml += meta[key] + "<br>";					  
			  }
		  }
	}
	if(thtml.length > 0){
		thtml = "<div class='rbdecision info'><h3>State</h3>" + thtml + "</div>";
	}
	return thtml;	
}


dacura.ldresult.getTripleTableHTML = function(trips, tit, isquads, cls){
	var html = "";
	if(trips.length > 0){
		html += "<div class='api-triplestable-title cls'>" + tit + "</div>";
		html += "<table class='rbtable'>";
		html += "<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th>";
		if(isquads){
			html += "<th>Graph</th>";
		}
		html += "</tr></thead><tbody>";
		for(var i = 0; i < trips.length; i++){
			dacura.ldresult.numtriples++;
			if(typeof trips[i][2] == "object"){
				trips[i][2] = JSON.stringify(trips[i][2]);
			}
			html += "<tr><td>" + trips[i][0] + "</td><td>" + trips[i][1] + "</td><td>" + trips[i][2] + "</td>";
			if(isquads){
				html += "<td>" + trips[i][3] + "</td>";
			}
			html += "</tr>";				
		}
		html += "</tbody></table>";
	}
	return html;
} 

function getJSONUpdateTableHTML(cupdates){
	var af = "";
	if(typeof cupdates.inserts.forward != "undefined" && cupdates.inserts.forward != ""){
		af = JSON.stringify(cupdates.inserts.forward);
	}
	var ab = "";
	if(typeof cupdates.inserts.backward != "undefined" && cupdates.inserts.backward != ""){
		ab = JSON.stringify(cupdates.inserts.backward);
	}
	var df = "";
	if(typeof cupdates.deletes.forward != "undefined" && cupdates.deletes.forward != ""){
		df = JSON.stringify(cupdates.deletes.forward);
	}
	var db = "";
	if(typeof cupdates.deletes.backward != "undefined" && cupdates.deletes.backward != ""){
		db = JSON.stringify(cupdates.deletes.backward);
	}
	var html = "";
	if(af != "" || ab != "" || df != "" || db != ""){
		html = "<div class='info'>";
		if(af != "" || ab != ""){
			html += "Added: <td class='json-frag'>" + af + " (Forward Graph) - " + ab + " (Backward Graph)";
		}
		if(df != "" || db != ""){
				html += "Deleted: <td class='json-frag'>" + df + " (Forward Graph)" + db + " (Backward Graph)";
		}
		html += "</div>";
	}
	return html;
}


</script>