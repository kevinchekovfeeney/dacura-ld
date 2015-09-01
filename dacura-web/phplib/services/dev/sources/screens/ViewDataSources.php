<style type='text/css'>
@import
'<?=$service->url("css", "jquery.dataTables.css")?>'
</style>

<script
	src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script>

//This should be generalisable in a php file - do the same thing to get IDs to not-IDs as in x.php
$(document).ready(function() {
	var ct = $('#dataSourcesTable').dataTable( {
		"bProcessing": false,
		"bFilter": false,
		"bServerSide": false,
		"aoColumns": [
		{ "sTitle": "ID", "mData": "id" },
		{ "sTitle": "Name", "mData": "name" },
		{ "sTitle": "URL", "mData": "url" },
		{ "sTitle": "Status", "mData": "status" },
		{ "sTitle": "Type", "mData": "type" },
		{ "sTitle": "Dump Type", "mData": "dump_type" }
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

<?php
$user = "root";
$pass = "";
$db = "dacura";
$link = mysql_connect( "localhost", $user, $pass );
if ( ! $link ) {
	die( "Couldn't connect to MySQL: ".mysql_error() );
}

mysql_select_db( $db, $link ) or die ( "Couldn't open $db: ".mysql_error() );

$result = mysql_query( "SELECT * FROM candidate_source" );
$num_rows = mysql_num_rows( $result );

print "<p>$num_rows dataSources have added the table</p>\n";

print "<table id='dataSourcesTable'>";
while ( $a_row = mysql_fetch_row( $result ) ) {
	print "<tr>";
	print "<td><a href='sources/screens/DataSourceDetails?id=".$a_row[0]."'>".stripslashes($a_row[0])."</a></td>";
	print "<td>".stripslashes($a_row[1])."</td>";
	print "<td>".stripslashes($a_row[2])."</td>";
	
	if ($a_row[3]==1){
		print "<td>Alive</td>";
	}
	else if ($a_row[3]==0) {
		print "<td>Dead</td>";
	}

	if ($a_row[4]==1) {
		print "<td>Sparql</td>";
	}
	
	if ($a_row[5]==2) {
		print "<td>Once</td>";
	}else if ($a_row[5]==3) {
		print "<td>Constantly monitored</td>";
	}else if ($a_row[5]==4) {
		print "<td>Create Dump</td>";
	}

	print "</tr>";
}
print "</table>";
print "<br>";
print "<br>";
print "<br>";

mysql_close( $link );
print "<a href='sources/screens/AddDataSource'> Create New DataSource </a>";
?>


<div id="table-padder" style="height: 200px;">&nbsp;</div>

</div>
