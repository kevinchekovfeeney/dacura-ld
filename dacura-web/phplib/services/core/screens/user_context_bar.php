<?php
$context_bits = array();
if($dacura_server->cid() != "all"){
	$col = $dacura_server->getCollection($dacura_server->cid());
	$context_bits[] = "<a href='".$service->settings['install_url'].$dacura_server->cid()."/all/'>".$col->name."</a>";
}
if($dacura_server->did() != "all"){
	$ds = $dacura_server->getDataset($dacura_server->did());
	$context_bits[] = "<a href='".$service->settings['install_url'].$dacura_server->cid()."/".$dacura_server->did()."/'>".$ds->name."</a>";
}
$x = $service->getServiceContextLinks();
if($x){
	$context_bits[] = $x;
}
?>
<ul id="utopbar-context">
<?php 
$i = 0;
foreach($context_bits as $cbit){
	$ext = (0 == $i++) ? 'class="ucontext first"' : 'class="ucontext"';
	echo "<li $ext>$cbit</li>";
}
?>
</ul>
