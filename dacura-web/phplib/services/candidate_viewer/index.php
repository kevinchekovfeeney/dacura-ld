<?php 
include_once("header.php");
require_once("Candidate_viewerDacuraServer.php");
$cds = new Candidate_viewerDacuraServer($dacura_settings);
/**
 * $servman 
 * $service_call
 * $service
 */
?>

<?php 

if(!$servman->isLoggedIn()){
	$servman->renderServiceScreen("core", "denied", array("message" => "You must be logged in to see this page"));	
}
else {
	$service->handlePageLoad();	
}
?>
<script>
<?php include_once("dacura.candidate_viewer.js");?>
dacura.candidate_viewer.mode = "local";
$(function(){
	dacura.candidate_viewer.init();
	dacura_widget.ajax_url = dacura.candidate_viewer.apiurl + "/widget";
	<?php 
	$u = $cds->sm->getUser();
	if($u->hasLiveSession("candidate_viewer")){
		echo "dacura.candidate_viewer.continueDacuraSession();";
	}
	else {
		echo "dacura.candidate_viewer.loadTool();dacura.candidate_viewer.doWork();";
	}
?>
	//echo "$('#work-session-username').html('".$u->getName()."');";
	/*	if($u->isadmin) {
			echo "showControlPanel(2);";
		}
		else {
			echo "showControlPanel(1);";
		}
	}
	if($u && $u->session->hasLiveLocalSession()){
		echo "dacura_system.continueDacuraSession();";
	}
	elseif($u) {
		echo "dacura_system.loadTool();";
	}*/
});
</script>
</body>
</html>
