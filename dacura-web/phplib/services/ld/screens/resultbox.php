
dacura.ldresult = {};

dacura.ldresult.getDecisionBasicText = function (dcm, test, type){
	var dtxt = "<div class='api-decision-decision " + dcm.decision + "'>";
	var dtit = "";
	var dsubtit = "";
	var did = "";
	if(dcm.decision == "accept"){
		if(test){
			dtit = type + " Approved";
			dsubtit = "If this " + type + " request is submitted, it will be accepted and published.";
		}
		else {
			dtit = type + " Successful";	
			dsubtit = "The " + type + " request has been accepted and published.";
			did = dcm.result.id;
			durl = dcm.result.cwurl;
		}
	}
	else if(dcm.decision == "pending"){
		if(test){
			dtit = type + " requires approval";
			dsubtit = "If submitted, this " + type + " request will be scheduled for approval by the data-set managers.";
		}
		else {
			dtit = type + " successfully submitted for approval";
			dsubtit = "The " + type + " request has been successfully submitted to the dataset managers.";
			did = dcm.result.id;
			durl = dcm.result.cwurl;
		}	
	}
	else if(dcm.decision == "reject"){
		dtit = type + " rejected";
		dsubtit = "The " + type + " request was not accepted by the Dacura API";		
	}
	dtxt += "<span class='api-decision-title'>" + dtit + "</span>" + 
		"<span class='api-decision-subtitle'>" + dsubtit + "</span>";
	if(did.length > 0){
		dtxt +="<span class='api-decision-id'>ID: <a href='"+ durl + "'>" + did + "</a></span>";
		dtxt +="<span class='api-decision-url'>URL: <a href='"+ durl + "'>" + durl + "</a></span>";
	}
	dtxt += "</div>";
	return dtxt;
}

dacura.ldresult.getErrorDetailsHTML = function(dcm){
	var html = "<div class='api-error-details'>";
	if(typeof dcm.errors != "undefined"){
		var errhtml = "<h3>Errors</h3><table class='api-error-table'>";
		for (var key in dcm.errors) {
			  if (dcm.errors.hasOwnProperty(key)) {
					errhtml += "<tr><td>" + key + "</td><td>" + JSON.stringify(dcm.errors[key], 0, 4) + "</td></tr>";
			  
			  }
		}
		html += errhtml + "</table>";
	}
	html += "</div>";
	return html
}	

dacura.ldresult.getWarningsHTML = function(dcm){
	var html = "";
	if(typeof dcm.warnings != "undefined" && dcm.warnings.length > 0){
		var errhtml = "";
		for(var i = 0; i < dcm.warnings.length; i++){
			errhtml += "<div class='api-one-warning'>Warning: <span class='action'>" + dcm.warnings[i].action +
				"</span><span class='title'>" + dcm.warnings[i].msg_title + "</span><span class='body'>" + 
				dcm.warnings[i].msg_body + "</span></div>";
		}
		if(errhtml.length > 0){
			html = "<h3>Warnings</h3><div class='api-warning-details'>" + errhtml + "</div>";
		}	
	}
	return html	
}


dacura.ldresult.getUpdateDetailsHTML = function(dcm, test){
	var html = "<div class='api-decision-triples'>";
	if(dcm.inserts.length > 0){
		if(typeof test != "undefined" && test){
			html += "<h3>" + dcm.inserts.length + " triples will be added</h3>"; 
		}
		else {
			html += "<h3>" + dcm.inserts.length + " triples were added</h3>";	
		}
		html += "<table class='change-details' id='change-add'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Graph</th></tr>";
		for(var i = 0; i< dcm.inserts.length; i++){
			if(typeof dcm.inserts[i][2] == "object"){
				dcm.inserts[i][2] = JSON.stringify(dcm.inserts[i][2]);
			}
			html += "<tr><td>" + dcm.inserts[i][0] + "</td><td>" + dcm.inserts[i][1] + "</td><td>" + 
			dcm.inserts[i][2] + "</td><td>" + dcm.inserts[i][3] + "</td></tr>";
		}
		html += "</table>";
	}
	if(dcm.deletes.length > 0){
		if(typeof test != "undefined" && test){
			html += "<h3>" + dcm.deletes.length + " triples will be deleted</h3>"; 
		}
		else {
			html += "<h3>" + dcm.deletes.length + " triples were deleted</h3>";	
		}
		html += "<table class='change-details' id='change-del'>";
		html += "<tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Graph</th></tr>";
		for(var i = 0; i<dcm.deletes.length; i++){
			if(typeof dcm.deletes[i][2] == "object"){
				dcm.deletes[i][2] = JSON.stringify(dcm.deletes[i][2]);
			}
			html += "<tr><td>" + dcm.deletes[i][0] + "</td><td>" + dcm.deletes[i][1] + "</td><td>" +
			 dcm.deletes[i][2] + "</td><td>" + dcm.deletes[i][3] +"</td></tr>";
		}
		html += "</table>";
	}
	html += "</div>";
	return html;			
}

dacura.candidate.getReportGraphUpdateHTML = function(rupdates, done){
	var html ="<div class='api-graph-testresults report-graph'>";
	if(rupdates.hypothetical || (rupdates.inserts.length == 0 && rupdates.deletes.length == 0)){
		html += "<div class='title'>No changes to report graph</div>";		
	}
	if((rupdates.inserts.length > 0 || rupdates.deletes.length > 0)){
		var insword = "inserted";
		var delword = "deleted"
		if(!done){
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

dacura.candidate.getMetaUpdatesHTML = function(meta){
	var thtml = "";
	for (var key in meta) {
		  if (meta.hasOwnProperty(key)) {
			  thtml += "<tr><td>" + key + "</td><td>"; 
			  if(typeof meta[key] == "object"){
				  thtml += meta[key][0] + "</td><td>" + meta[key][1] + "</td></tr>";
			  }
			  else {
				  thtml += "</td><td>" + meta[key] + "</td></tr>";					  
			  }
		  }
	}
	if(thtml.length > 0){
		thtml = "<table class='update-meta-state'><tr><th>State</th><th>Before</th><th>After</th></tr>" + thtml + "</table>";
	}
	return thtml;	
}

dacura.candidate.getUpdateGraphUpdateHTML = function(cupdates, done){
	var html ="<div class='api-graph-testresults update-graph'>";
	if(typeof cupdates.meta != "undefined"){
		html += this.getMetaUpdatesHTML(cupdates.meta);
	}
	if((typeof cupdates.inserts.forward == "undefined" || cupdates.inserts.forward == "") &&
		(typeof cupdates.inserts.backward == "undefined" || cupdates.inserts.backward == "") && 
		(typeof cupdates.deletes.forward == "undefined" || cupdates.deletes.forward == "") &&
		(typeof cupdates.deletes.backward == "undefined" || cupdates.deletes.backward == "")){
		html += "<div class='title'>No changes to update graph</div>";		
	}
	else {
		html += "<div class='title'>Changes to update graph</div>";		
		html += getJSONUpdateTableHTML(cupdates);
	}	
	return html + "</div>";
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
	html = "";
	if(af != "" || ab != "" || df != "" || db != ""){
		html += "<table class='dacura-update-json'><thead><tr><th></th><th>Forward Graph</th><th>Backward Graph</th></tr></thead><tbody>";
		if(af != "" || ab != ""){
			html += "<tr><th>Added</th><td class='json-frag'>" + af + "</td><td>" + ab + "</td></tr>";
		}
		if(df != "" || db != ""){
				html += "<tr><th>Deleted</th><td class='json-frag'>" + df + "</td><td>" + db + "</td></tr>";
		}
		html += "</tbody></table>";
	}
	return html;
}

dacura.ldresult.getJSONFragmentHTML = function(frag, title){
	html = "<div class='json-fragment-title'>"+ title + "</div><div class='dacura-json-viewer'>" + JSON.stringify(frag, 0, 4) + "</div>";
	return html;
}

dacura.ldresult.getCandidateGraphUpdateHTML = function(cupdates, done){
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
		var insword = "inserted";
		var delword = "deleted"
		if(!done){
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
}

dacura.ldresult.getTripleTableHTML = function(trips, tit, isquads, cls){
	var html = "";
	if(trips.length > 0){
		html += "<div class='api-triplestable-title cls'>" + tit + "</div>";
		html += "<table class='change-details " + cls + "'>";
		html += "<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th>";
		if(isquads){
			html += "<th>Graph</th>";
		}
		html += "</tr></thead><tbody>";
		for(var i = 0; i < trips.length; i++){
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

dacura.ldresult.getGraphTestResultsHTML = function(res, dcm, type){
	var html ="<div class='api-graph-testresults'>";
	if(type == "Update Candidate" && typeof dcm.result != "undefined" && dcm.result != null){
		if(res.decision == "reject"){
			html += "<div class='title'>Quality Control Problems</div>";		
			html += "The " + type + " request would produce the following graph updates and errors: ";
			html += dacura.candidate.getUpdateDetailsHTML(res, true); 
			html += this.getErrorDetailsHTML(res);		
		}
		else {
			if(dcm.result.status == "accept" && dcm.result.original.status == "accept"){
				html += "<div class='title'>Updating Published Report</div>";		
				html += "If the " + type + " request is accepted, it will produce the following updates to the graph:";
			}
			else if(dcm.result.status == "accept"){
				html += "<div class='title'>Publishing New Report</div>";
				html += "If the " + type + " request is accepted, it will cause a new report to be published, consisting of the following graph updates:";		
			}
			else if(dcm.result.original.status == "accept"){
				html += "<div class='title'>Removing Published Report</div>";
				html += "If the " + type + " request is accepted, it will cause the report to be unpublished, consisting of the following graph updates:";		
			}
			else {
				html += "<div class='title'>Editing Unpublished Candidate</div>";
				html += "If the " + type + " request is accepted, it will have no impact on the graph. Below are the changes that would appear if the candidate is ever accepted";				
			}
			html += dacura.candidate.getUpdateDetailsHTML(res, true); 
		}
	}
	else {
		if(res.decision != "reject"){
			html += "<div class='title'>Quality Tests Passed OK</div>";
			if(res.deletes.length > 0 || res.inserts.length > 0){
				html += "If the submitted candidate is accepted, it will produce the following updates ";
				html += dacura.candidate.getUpdateDetailsHTML(res, true); 
			}
			else {
				html += "If the submitted candidate is accepted, it will not produce any updates to the graph";
			}
		}
		else {
			html += "<div class='title'>Cannot Currently Be Published due to Quality Control Problems</div>";
			html += "The submitted candidate would produce the following graph updates: ";
			html += dacura.candidate.getUpdateDetailsHTML(res, true); 
			html += this.getErrorDetailsHTML(res);
			//show errors in the graph test...
		}
	}
	return html;
}

dacura.ldresult.showUpdateDecision = function(dcm, test, jq){
	return this.showDecision(dcm, test, jq, "Update Candidate");
}

dacura.ldresult.showCreateDecision = function(dcm, test, jq){
	return this.showDecision(dcm, test, jq, "Create Candidate");
}


dacura.ldresult.showDecision = function(dcm, test, jq, type){
	var html = this.getDecisionBasicText(dcm, test, type);
	var subhtml = ""; 
	if(typeof dcm.msg_title != "undefined" && dcm.msg_title != null && dcm.msg_title.length > 0) {
		subhtml = "<span class='api-msg-title'>" + dcm.msg_title + "</span>";
	}
	if(typeof dcm.msg_body != "undefined" && dcm.msg_body.length > 0){
		subhtml += "<span class='api-msg-body'>" + dcm.msg_body + "</span>";
	}
	if(subhtml.length > 0){
		html += "<div class='api-decision-subtext'>" + subhtml + "</div>";
	}
	if(typeof dcm.errors != "undefined" && dcm.errors.length > 0){
		html += this.getErrorDetailsHTML(dcm, type);
	}
	if(typeof dcm.warnings != "undefined" && dcm.warnings.length > 0){
		html += this.getWarningsHTML(dcm, type);	
	}
	if(typeof dcm.update_graph_update != "undefined" && dcm.update_graph_update != null){
		html += this.getUpdateGraphUpdateHTML(dcm.update_graph_update, !test && (dcm.decision == "accept" || dcm.decision == "pending"));
	}
	if(typeof dcm.candidate_graph_update != "undefined" && dcm.candidate_graph_update != null){
		html += this.getCandidateGraphUpdateHTML(dcm.candidate_graph_update, !test && (dcm.decision == "accept" || dcm.decision == "pending"));
	}
	if(typeof dcm.report_graph_update != "undefined" && dcm.report_graph_update != null){
		html += this.getReportGraphUpdateHTML(dcm.report_graph_update, !test && dcm.decision == "accept");
	}
	
	if(type == "Update Candidate"){
		if(typeof dcm.result != "undefined" && dcm.result != null){
			if(dcm.result.status != dcm.result.original.status){
				html += "<div class='api-decision-statechange'>Candidate Status changed from " + dcm.result.original.status +
				" to " + dcm.result.status + "</div>";			
			}
		}
	}

	var cls; 
	if(dcm.decision == 	'reject'){
		cls = "dacura-error rejected";
	}
	else if(dcm.errcode > 200){
		cls = "dacura-error";
	}
	else if(dcm.decision == 'pending'){
		if(dcm.warnings.length > 0){
			cls = "dacura-warning";
		}
		else {
			cls = "dacura-info";
		}
		if(typeof dcm.graph_test != "undefined") {
			html += this.getGraphTestResultsHTML(dcm.graph_test, dcm, type);
		}
	}
	else if(dcm.decision == 'confirm'){
		if(dcm.warnings.length > 0){
			cls = "dacura-confirm dacura-warning";
		}
		else {
			cls = "dacura-confirm";
		}
	}
	else if(dcm.decision == 'accept'){
		if(dcm.warnings.length > 0){
			cls = "dacura-success dacura-warning";
		}
		else {
			cls = "dacura-success";
		}
	}
	$(jq).html("<div class='dacura-user-message-box " + cls + "'>"+ html + "</div>").show();
}