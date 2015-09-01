<style type='text/css'>
@import '<?=$service->url("css", "jquery.dataTables.css")?>'
</style>

<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script>

//This should be generalisable in a php file - do the same thing to get IDs to not-IDs as in x.php
$(document).ready(function() {
	var ct = $('#candidates_table').dataTable( {
		"bProcessing": true,
		"bFilter": false,
		"bServerSide": true,
		"sAjaxSource": "<?=$service->get_service_url("candidates", array("datatable"), true)?>",
		"aoColumns": [
		{ "sTitle": "ID", "mData": "candid" },
		{ "sTitle": "Submitted", "mData": "submitted" },
		{ "sTitle": "Source Date", "mData": "sourcedate" },
		{ "sTitle": "Client", "mData": "client" },
		{ "sTitle": "Source ID", "mData": "permid" },
		{ "sTitle": "Actions", "mData": "actions" },
		{ "sTitle": "Status", "mData": "status" },
		{ "sTitle": "Cached", "mData": "cached" }
		], 
		"fnRowCallBack" : function (){
			alert("x");
		}
		/* "aoColumns": [
		 { "sTitle": "Start Date",   "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#startDate" },
		{ "sTitle": "End Date",  "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#endDate" },
		{ "sTitle": "Category", "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#category" },
		{ "sTitle": "Motivation",  "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#motivation" },
		{ "sTitle": "Location",    "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#location" },
		{ "sTitle": "Fatalities",    "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#fatalities" },
		{ "sTitle": "Source",    "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#source" },
		{ "sTitle": "Description",    "mData": "http://tcdfame.cs.tcd.ie/data/politicalviolence#description" }
		] */
	});

} );


</script>
<div id="pagecontent">

	<table id="candidates_table">
	<thead></thead>
	<tbody></tbody>
	</table>
<div id="table-padder" style="height: 200px;">
&nbsp;
</div>
	
</div>