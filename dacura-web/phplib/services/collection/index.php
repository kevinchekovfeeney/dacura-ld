<?php 
include_once("phplib/snippets/header.php");
include_once("phplib/snippets/topbar.php");
?>
<script>
<?php include_once("dacura.collection.js");?>
dacura.collection.mode = "local";
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
	$service->handleServiceCall($service_call);
}
?>
</div>
<?php 
include_once("phplib/snippets/footer.php");
?>