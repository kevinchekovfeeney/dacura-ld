<?php 
session_start();
?>


<html>
<head>
  <script src="jquery-1.9.1.min.js"></script>
  <script src="jquery-ui-1.10.2.custom.min.js"></script>
  <script src="json2.js"></script>
  <script src="prettyprint.js"></script>
  <script src="widget.js"></script>
  <link rel="stylesheet" type="text/css" href="jquery-ui-1.10.2.custom.min.css" />
  <link rel="stylesheet" type="text/css" href="widget.css" />
  </head>
<body>
<div id='dc-content'>
<input id='show-login' type='submit' value='Log in'>
<input id='show-adduser' type='submit' value='Add user'>
<input id='show-allocate' type='submit' value='Allocate'>
<input id='show-status' type='submit' value='Status'>
</div>
<div id="loginbox" >
	<table>
		<tr><th>Username</th><td><input id="login-email" type="text" value=""></td>
		<tr><th>Password</th><td><input id="login-password" type="password" value=""></td>
		<tr><td colspan="2" id="loginbox-status" class="dacura-status"></td></tr>
		<tr><td colspan="2"><input type="submit" id='login-submit' value="Login"></td></tr>
	</table>
</div>

<div id="userbox">
	<table>
		<tr><th>Stuff</th><td>stuff value</td>
		<tr><th>More Stuff</th><td>more stuff value</td>
		<tr><td colspan="2" id="userbox-status" class="dacura-status"></td></tr>
		<tr><td colspan="2"><input type="submit" value="stuff"></td></tr>
	</table>
</div>
<div id="adduserbox">
		<tr><th>Username</th><td><input id="adduser-email" type="text" value=""></td>
		<tr><th>Password</th><td><input id="adduser-password" type="password" value=""></td>
		<tr><td colspan="2" id="adduserbox-status" class="dacura-status"></td></tr>
		<tr><td colspan="2"><input type="submit" id='adduser-submit' value="Add"></td></tr>		
</div>
<div id="allocatebox">
		<tr><th>Username</th><td><input id="allocate-email" type="text" value=""></td>
		<tr><th>Chunk</th><td><input id="allocate-year" type="text" value=""></td>
		<tr><td colspan="2" id="allocatebox-status" class="dacura-status"></td></tr>
		<tr><td colspan="2"><input type="submit" id='allocate-submit' value="Add"></td></tr>		
</div>
<div id="statusbox">
		<tr><th>Status</th><td><input id="status-x" type="text" value=""></td>
		<tr><th>Chunk</th><td><input id="status-y" type="text" value=""></td>
		<tr><td colspan="2" id="statusbox-status" class="dacura-status"></td></tr>
		
</div>


<script>

$('#show-login').button().click(function(e){
	$('#loginbox').show();
	
});

function showloginbox(){
	hideall();
	$('#loginbox').show().dialog({"title": "Log in", 'width' : 350});
}

function showallocatebox(){
	hideall();
	$('#allocatebox').show().dialog({"title": "Allocate Chunk"});
}


function showuserbox(u){
	hideall();
	$('#userbox').show().dialog({"title": u.name});
}

function showstatusbox(){
	hideall();
	$('#statusbox').show().dialog({"title": "Status Box"});
}

function showadduserbox(){
	hideall();
	$('#adduserbox').show().dialog({"title": "Add User"});	
}

function hideall(){
	$('#loginbox').hide();
	$('#userbox').hide();
	$('#adduserbox').hide();
	$('#statusbox').hide();
	$('#allocatebox').hide();
}

function resetadduser(){
	$('#adduser-email').empty();
	$('#adduser-password').empty();
}

function resetlogin(){
	$('#login-email').empty();
	$('#loginr-password').empty();
}


$('#show-login').button().click(function(e){
	e.preventDefault();
	showloginbox();
});

$('#show-adduser').button().click(function(e){
	e.preventDefault();
	showadduserbox();
});

$('#show-allocate').button().click(function(e){
	e.preventDefault();
	showallocatebox();
});

$('#show-status').button().click(function(e){
	e.preventDefault();
	showstatusbox();
});

$('#show-allocate').button().click(function(e){
	e.preventDefault();
	showallocatebox();
});

/*
 * Submit buttons
 */

$('#login-submit').button().click(function(e){
	e.preventDefault();
	$.post("workapi.php", {"action": "login", "login-email": $('#login-email').val(), "login-password": $('#login-password').val()})
	.done(function(data, textStatus, jqXHR) {
		hideall();
		resetlogin();
		showuserbox(JSON.parse(data));
	})
	.fail(function (jqXHR, textStatus){
		alert("fail" + jqXHR.status + textStatus);
	});
	
});

$('#adduser-submit').button().click(function(e){
	e.preventDefault();
	$.post("workapi.php", {"action": "adduser", "adduser-email": $('#adduser-email').val(), "adduser-password": $('#adduser-password').val()})
	.done(function(data, textStatus, jqXHR) {
		$('#adduserbox-status').html("Success -  " + $('#adduser-email').val() + " added");
		alert(data);
		$('#adduser-email').empty();
		$('#adduser-password').empty();
	})
	.fail(function (jqXHR, textStatus){
		alert("fail " + jqXHR.status + " " + textStatus + " " + jqXHR.responseText );
	});
});


$('#allocate-submit').button().click(function(e){
	e.preventDefault();
	$.post("workapi.php", {"action": "allocate", "allocate-email": $('#allocate-email').val(), "allocate-year": $('#allocate-year').val()})
	.done(function(data, textStatus, jqXHR) {
		$('#allocatebox-status').html("Success -  " + $('#allocate-year').val() + " allocated to " + $('#allocate-email').val());
		alert(data);
		$('#allocate-email').empty();
		$('#allocate-year').empty();
	})
	.fail(function (jqXHR, textStatus){
		alert("fail " + jqXHR.status + " " + textStatus + " " + jqXHR.responseText );
	});
});


$(function(){
	hideall();
	
});
</script>

<?php  ?>


</body>



