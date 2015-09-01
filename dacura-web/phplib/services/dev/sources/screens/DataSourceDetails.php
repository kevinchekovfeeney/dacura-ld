<!DOCTYPE html>
<html>
<head>
<title>Sparql Source Details</title>
<!-- Meta Tags -->
<meta charset="utf-8">
<meta name="generator" content="Wufoo">
<meta name="robots" content="index, follow">
<!-- CSS -->
<link href="http://localhost/dacura/phplib/services/sources/screens/css/structure.css" rel="stylesheet">
<link href="http://localhost/dacura/phplib/services/sources/screens/css/form.css" rel="stylesheet">
<!-- JavaScript -->
<script src="http://localhost/dacura/phplib/services/sources/screens/scripts/wufoo.js"></script>
<script>



<?php

require_once("phplib/sparqllib.php");

if(isset($_GET['id'])){

$id=$_GET['id'];
	

$query = "SELECT candidate_source.id , candidate_source.name, candidate_source.url, candidate_source.type, candidate_source.dump_type, candidate_source_queries.query FROM candidate_source LEFT OUTER JOIN candidate_source_queries on candidate_source.id = candidate_source_queries.candidate_source_id where candidate_source.id=$id" ; 



$user = "root";
$pass = "";
$db = "dacura";
$link = mysql_connect( "localhost", $user, $pass );
if ( ! $link ) {
	die( "Couldn't connect to MySQL: ".mysql_error() );
}

mysql_select_db( $db, $link ) or die ( "Couldn't open $db: ".mysql_error() );

$result = mysql_query($query);
$num_rows = mysql_num_rows( $result );

print "function init(){";

while ( $a_row = mysql_fetch_row( $result ) ) {

print "document.getElementById('SourceId').value = $a_row[0];";
print "document.getElementById('Field108').value = '$a_row[1]';";
print "document.getElementById('Field3').value = '$a_row[2]';";
//print "document.getElementById('Field108').value = $a_row[3];";

if ($a_row[4]==2) {
print "document.getElementById('Field213').value = 'Once';";
	}else if ($a_row[4]==3) {
print "document.getElementById('Field213').value = 'Constantly monitored';";
	}else if ($a_row[4]==4) {
print "document.getElementById('Field213').value = 'Create Dump';";
	}

print "document.getElementById('Field318').value = '$a_row[5]';";

}
print "}";
mysql_close( $link );

}



?>

</script>
<!--[if lt IE 10]>
<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body id="public" onload="init();">
<div id="container" class="ltr">
<form id="form4" name="form4" class="wufoo leftLabel page" accept-charset="UTF-8" autocomplete="off" enctype="multipart/form-data" method="post" novalidate action="sources/screens/DataSourceDetails"><header id="header" class="info"> <input type="hidden"
	name="SourceId" id="SourceId" />
<h2>Sparql Source Details</h2>
</header>
<ul>
	<li id="foli108" class="notranslate      "><label class="desc" id="title108" for="Field108"> Name </label>
	<div><input id="Field108" name="Field108" type="text" class="field text medium" tabindex="1" readonly /></div>
	</li>
	<li id="foli3" class="notranslate       "><label class="desc" id="title3" for="Field3"> Link </label>
	<div><input id="Field3" name="Field3" type="url" class="field text medium" value="" tabindex="2" readonly /></div>
	</li>
	<li id="foli213" class="notranslate  notStacked     ">
	<fieldset><![if !IE | (gte IE 8)]> <legend id="title213" class="desc"> Source Type </legend> <![endif]> <!--[if lt IE 8]>
<label id="title213" class="desc">
Source Type
</label>
<![endif]-->
	<div><input id="Field213" name="Field213" type="text" value="" class="field text medium" readonly /></div>
	</fieldset>
	</li>
	<li id="foli215" class="notranslate section      "><section>
	<h3 id="title215">Registered Queries</h3>
	</section></li>
	<li id="foli318" class="notranslate      "><label class="desc" id="title318" for="Field318"> Query </label>
	<div><textarea id="Field318" name="Field318" class="field textarea medium" spellcheck="true" rows="10" cols="50" tabindex="6" onkeyup=""></textarea></div>
	</li>
	<li class="buttons ">
	<div><input id="saveForm" name="saveForm" class="btTxt submit" type="submit" value="Submit" /></div>
	</li>
</ul>
</form>
<?php

if(isset($_POST['SourceId'])){

	$candidate_source_id=$_POST['SourceId'];

	mysql_connect("localhost","root","");
	mysql_select_db("dacura");


	$sparql_query = (isset($_POST['Field318']) ? $_POST['Field318'] : '');

	$query = "INSERT INTO CANDIDATE_SOURCE_QUERIES SET query='$sparql_query' , candidate_source_id=$candidate_source_id ";
	$result = mysql_query($query);

	// Display an appropriate message
	if ($result){
		print "<a href='sources/screens/ViewDataSources'>Query successfully inserted!</a>";
	}
	else{
		echo "<p>There was a problem inserting the Query!</p>";
	}

	mysql_close();
}
?></div>
<!--container-->
</body>
</html>
