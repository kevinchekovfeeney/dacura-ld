<!DOCTYPE html>
<html>
<head>
<title>Add Sparql Source</title>
<!-- Meta Tags -->
<meta charset="utf-8">
<meta name="generator" content="Wufoo">
<meta name="robots" content="index, follow">
<!-- CSS -->
<link href="http://localhost/dacura/phplib/services/sources/screens/css/structure.css" rel="stylesheet">
<link href="http://localhost/dacura/phplib/services/sources/screens/css/form.css" rel="stylesheet">
<!-- JavaScript -->
<script src="http://localhost/dacura/phplib/services/sources/screens/scripts/wufoo.js"></script>
<!--[if lt IE 10]>
<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>


<body id="public">
<div id="container" class="ltr">
<form id="form4" name="form4" class="wufoo leftLabel page" method="post" action="sources/screens/AddDataSource"><header id="header" class="info">
<h2>Register Sparql Source</h2>
</header>
<ul>
	<li id="foli108" class="notranslate"><label class="desc" id="title108" for="Field108"> Name <span id="req_108" class="req">*</span> </label>
	<div><input id="Field108" name="Field108" type="text" class="field text medium" value="" maxlength="50" tabindex="1" onkeyup="validateRange(108, 'character');" required /> <label for="Field108">Maximum Allowed: <var id="rangeMaxMsg108">50</var>
	characters.&nbsp;&nbsp;&nbsp; <em class="currently">Currently Used: <var id="rangeUsedMsg108">0</var> characters.</em></label></div>
	</li>
	<li id="foli3" class="notranslate"><label class="desc" id="title3" for="Field3"> Link <span id="req_3" class="req">*</span> </label>
	<div><input id="Field3" name="Field3" type="url" class="field text medium" value="" maxlength="255" tabindex="2" /></div>
	</li>
	<li id="foli214" class="notranslate  notStacked     ">
	<fieldset><![if !IE | (gte IE 8)]> <legend id="title214" class="desc"> Source Type <span id="req_214" class="req">*</span> </legend> <![endif]> <!--[if lt IE 8]>
<label id="title214" class="desc">
Source Type
<span id="req_214" class="req">*</span>
</label>
<![endif]-->
	<div><input id="radioDefault_214" name="Field214" type="hidden" value="" /> <span> <input id="Field214_0" name="Field214" type="radio" class="field radio" value="1" tabindex="3" checked="checked" required /> <label class="choice" for="Field214_0">Sparql</label>
	</span> </div>
	</fieldset>
	</li>
	<li id="foli213" class="notranslate  notStacked     ">
	<fieldset><![if !IE | (gte IE 8)]> <legend id="title213" class="desc"> Dump Type <span id="req_213" class="req">*</span> </legend> <![endif]> <!--[if lt IE 8]>
<label id="title213" class="desc">
Source Type
<span id="req_213" class="req">*</span>
</label>
<![endif]-->
	<div><input id="radioDefault_213" name="Field213" type="hidden" value="" /> <span> <input id="Field213_0" name="Field213" type="radio" class="field radio" value="2" tabindex="3" checked="checked" required /> <label class="choice" for="Field213_0"> Once</label>
	</span> <span> <input id="Field213_1" name="Field213" type="radio" class="field radio" value="3" tabindex="4" required /> <label class="choice" for="Field213_1"> Constantly monitored</label> </span> <span> <input id="Field213_2" name="Field213"
		type="radio" class="field radio" value="4" tabindex="5" required /> <label class="choice" for="Field213_2"> Create Dump</label> </span></div>
	</fieldset>
	</li>
	<div><input id="saveForm" name="saveForm" class="btTxt submit" type="submit" value="Submit" /></div>
	</li>
	<li class="hide"><label for="comment">Do Not Fill This Out</label> <textarea name="comment" id="comment" rows="1" cols="1"></textarea> <input type="hidden" id="idstamp" name="idstamp" value="mWPmHj6hW60RT8TjEGV7HI0d1Y2a8oh2crLJbKBidJA=" /></li>
</ul>
</form>

<?php
require_once("phplib/sparqllib.php");

if(isset($_POST['Field3'])){

	$url=$_POST['Field3'];
	$db = sparql_connect($url);
	if( !$db ) { echo sparql_errno() . ": " . sparql_error(). "\n"; exit; }
	else {

		mysql_connect("localhost","root","");
		mysql_select_db("dacura");

			
		$name = (isset($_POST['Field108']) ? $_POST['Field108'] : '');
		$type = (isset($_POST['Field214']) ? $_POST['Field214'] : '');
		$dump_type = (isset($_POST['Field213']) ? $_POST['Field213'] : '');

		$query = "INSERT INTO CANDIDATE_SOURCE SET name='$name', url='$url' ,status=1 ,type=$type , dump_type=$dump_type ";
		$result = mysql_query($query);

		// Display an appropriate message
		if ($result){
			print "<a href='sources/screens/ViewDataSources'>Product successfully inserted!</a>";
		}
		else{
			echo "<p>There was a problem inserting the Employee!</p>";
		}

		mysql_close();
	}
}
?>

</div>
<!--container-->
</body>
</html>
