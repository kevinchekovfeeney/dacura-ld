<?php
$hsds = new DacuraServer($service->settings);
$choices = $hsds->getUserAvailableContexts($params['type']);
?>
<select class='dccontextchanger' id='dccollectioncontext'>
<?php 
foreach($choices as $i => $choice){
	echo "<option value='$i'"; 
	if($i == $service->getCollectionID() or ($i == "0" && !$service->getCollectionID())) echo " selected";
	echo ">".$choice['title']."</option>";
}
?>
</select>
<?php 
foreach($choices as $i => $choice){
	?>
	<select class='dch dccontextchanger dcdatasetselect' id='dcdatasetcontext_<?=$i?>'>
	<?php 
		foreach($choice['datasets'] as $j => $t){
			echo "<option value='$j'";
			if($j == $service->getDatasetID() or ($j == "0" && !$service->getDatasetID())) echo " selected";
			echo ">$t</option>";
		}
	?>
	</select>
<?php }?>
<input id='dcchangecontext' type='submit' value="go">	
<script>

	var updateDS = function(){
		$('.dcdatasetselect').hide();
		$('#dcdatasetcontext_' + $('#dccollectioncontext').val()).show();
	};
	
	$('#dccollectioncontext').change(updateDS);
	$('#dcchangecontext').click(function(){
		dacura.system.switchContext($('#dccollectioncontext').val(), $('#dcdatasetcontext_' + $('#dccollectioncontext').val()).val());
	});
	
	updateDS();
	

</script>