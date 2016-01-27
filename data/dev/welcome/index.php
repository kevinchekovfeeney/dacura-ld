<?php 
include_once(path_to_snippet("header"));
include_once(path_to_snippet("topbar"));

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
	$service->handlePageLoad();
}
?>
</div>
<?php 
include_once(path_to_snippet("footer"));
?>