
<div id='tab-holder'>
	 <ul id="ld-pane-list" class="dch">
	 	<li><a href="#ld-list">User Interface Widgets</a></li>
	 	<!-- <li><a href="#update-list">LD Entity Update Queue</a></li> -->
	 	<li><a href="#create-widget">Create New UI Widget</a></li>
	 </ul>
	<div id="create-widget">
		<div class="tab-top-message-holder">
			<div class="tool-create-info tool-tab-info" id="create-msgs"></div>
		</div>
		<div id="create-holder" class="dch">
			<div id='entity-graphs'>
			</div>
			<div id='entity-classes' class='dch'>				
			</div>
			<div id='class-properties' class='ld-list dch'>
				<table id="entity-property-table" class="dcdt display">
					<thead>
					<tr>
						<th>Name</th>
						<th>Range</th>
						<th>Version</th>
						<th>Created</th>
						<th>Sortable Created</th>
						<th>Modified</th>
						<th>Sortable Modified</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
	<div id="ld-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="ld-msgs"></div>
		</div>
		<div id="ld-holder" class='ld-list dch'>
			<table id="ld_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Status</th>
					<th>Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Modified</th>
					<th>Sortable Modified</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<div id="update-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="update-msgs"></div>
		</div>
		<div id="update-holder" class="ld-list dch">
			<table id="update_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Target</th>
					<th>Type</th>
					<th>Collection</th>
					<th>Status</th>
					<th>From Version</th>
					<th>To Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Modified</th>
					<th>Sortable Modified</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
<div class='dch'>
	<div id="class-template">
		<table class="class-table display">
			<thead>
			<tr>
				<th>Name</th>
				<th>Label</th>
				<th>Type</th>
				<th>Parent</th>
				<th>Select</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
<script>



var entity_urls = [];
var update_urls = [];
var entity_classes = [];

dacura.widget.drawClassPropertyTable = function(data){
	dacura.system.showSuccessResult("received stub from server", data, "Success", "#create-msgs");
};

dacura.widget.drawEntityClassTable = function(data){
	for (var g in data){
		var obj = data[g];
		var tjqid = "graph-table-" + g;
		var djqid = "graph-" + g;
		$('#entity-classes').append("<div class='graph-classes-list' id='" + djqid + "'><h3>Graph " + g + "</h3>"
			+ "<div id='" + tjqid + "'></div></div>");
		dacura.widget.drawClassTable("#" + tjqid, obj, g); 
	}
	$('.select-class').button().click( function(event){
		var rec = entity_classes[this.id.substring(7)];
		dacura.widget.fetchClassProperties(rec.graph, rec.name, dacura.widget.drawClassPropertyTable, {resultbox: "#create-msgs", errorbox: "#create-msgs", busybox: "#create-widget"});
	});
	$('#entity-classes').show();
}

dacura.widget.drawClassTable = function(jqid, data, g){
	$(jqid).html($('#class-template').html());
	for (var i in data) {
		var obj = data[i];
		$(jqid + " .class-table tbody").append("<tr>" +
			"<td>" + obj.name + "</td>" + 
			"<td>" + obj.label + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.subclass + "</td>" + 
			"<td><input type='checkbox' class='select-class' id='entity_" + entity_classes.length + "'><label for='entity_" + entity_classes.length + "'>Select</label></td>" +
			"</tr>");
		entity_classes[entity_classes.length] = { graph: g, name: obj.name};
	}
}


dacura.widget.drawEntityListTable = function(data){		
	if(typeof data == "undefined" || data.length == 0){
		$('#ld-holder').show();	
		$('#ld_table').dataTable(dacura.widget.getTableInitStrings()).show(); 
		dacura.system.showErrorResult("No Entities Found", false, "", '#ld_table .dataTables_empty');		
	}
	else {
		$('#ld_table tbody').html("");
		entity_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('#ld_table tbody').append("<tr class='entityrow' id='ent_" + entity_urls.length + "'>" + 
			"<td>" + obj.id + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");
			if(obj.type == "candidate"){
				s = obj.type;
			}	
			else if(obj.type == "graph" || obj.type == "ontology"){
				s = "schema";
			}
			else {
				s = "ld";
			}
			entity_urls[entity_urls.length] = dacura.system.pageURL(s, obj.collectionid) + "/" + obj.id;
		}
		$('.entityrow').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.entityrow').click( function (event){
			window.location.href = entity_urls[this.id.substr(4)]
	    }); 		
		$('#ld-holder').show();	
		$('#ld_table').dataTable(<?=$params['widget_datatable']?>);
	}
}

dacura.widget.drawUpdateListTable = function(data){		
	if(typeof data == "undefined" || data.length == 0){
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.widget.getTableInitStrings(true)); 
		dacura.system.showErrorResult("No Updates Found", false, "", '#update_table .dataTables_empty');		
	}
	else {
		$('#update_table tbody').html("");
		update_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('#update_table tbody').append("<tr class='updaterow' id='update_" + update_urls.length + "'>" + 
			"<td>" + obj.eurid + "</td>" + 
			"<td>" + obj.targetid + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.collectionid + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");
			if(obj.type == "candidate"){
				s = obj.type;
			}	
			else if(obj.type == "graph" || obj.type == "ontology"){
				s = "schema";
			}
			else {
				s = "ld";
			}
			update_urls[update_urls.length] = dacura.system.pageURL(s, obj.collectionid) + "/" + obj.targetid + "/update/" + obj.eurid;			
		}
		$('.updaterow').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.updaterow').click( function (event){
			window.location.href = update_urls[this.id.substr(7)]
	    }); 
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.widget.getTableInitStrings(true)).show();
	}
}

$(function() {
	dacura.system.init({"mode": "tool"});
	dacura.widget.fetchentitylist(dacura.widget.drawEntityListTable, {resultbox: "#ld-msgs", errorbox: "#ld-msgs", busybox: "#ld-list"});
	dacura.widget.fetchClasses(dacura.widget.drawEntityClassTable, {resultbox: "#create-msgs", busybox: "#create-widget"});
	//dacura.ld.fetchupdatelist(dacura.ld.drawUpdateListTable, {resultbox: "#update-msgs", errorbox: "#update-msgs", busybox: "#update-list"}); 
	$('#ld-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
           $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
    $('#create-holder').show();
});
	
</script>