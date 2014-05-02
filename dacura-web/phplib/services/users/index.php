<?php 
include_once("phplib/snippets/header.php");
include_once("phplib/snippets/topbar.php");
?>
<script>
<?php include_once("dacura.users.js");?>
</script>
<div id="pagecontent-container">
<?php 
/**
 * $servman 
 * $service_call
 * $service
 */
if(!$servman->isLoggedIn()){
	$servman->renderServiceScreen("core", "denied", array("message" => "You must be logged in to see this page"));	
}
else {
	$service->handlePageLoad($service_call);
}
?>
</div>
<?php 
include_once("phplib/snippets/footer.php");
?>