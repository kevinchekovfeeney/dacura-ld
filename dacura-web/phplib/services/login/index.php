<?php 
include_once("phplib/snippets/header.php");
include_once("phplib/snippets/topbar.php");
?>
<script>
dacura.login = {}
dacura.login.apiurl = "<?=$dacura_settings['ajaxurl']?>login/";
dacura.login.mode = "local";
<?php include_once("dacura.login.js");?>
</script>
<div id='maincontrol'>
<?php 
/**
 * $servman = new ServiceManager($dacura_settings);
 * $service_call
 * $service
 */
$service->handleServiceCall($service_call);

?>
</div>
<?php 
include_once("phplib/snippets/footer.php");
?>