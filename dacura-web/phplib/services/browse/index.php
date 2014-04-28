<?php 
include_once("phplib/snippets/header.php");
include_once("phplib/snippets/topbar.php");
?>
<script>

</script>
<div id="pagecontent-container">
<?php 
/**
 * $servman 
 * $service_call
 * $service
 */
if(!$servman->isLoggedIn()){
	$servman->renderServiceScreen("core", "denied", array("message" => "You must be logged in to access this service"));	
}
else {
	$service->handleServiceCall();
}
?>
</div>
<?php 
include_once("phplib/snippets/footer.php");
?>