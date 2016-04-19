<?php 
/**
 * Status pane in the menu on dacura home page
 * 
 * Shows various statistics about the collection
 * @package browse/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<table class='dacura-status'>
	<tr><th>Users</th><td class='user-count'><?=$params['user-count']?></td>
	<tr><th>Schema Triples</th><td class='schema-count'><?=$params['schema-count']?></td>
	<tr><th>Instance Triples</th><td class='instance-count'><?=$params['instance-count']?></td>	
	<tr><th>Graphs</th><td class='graph-count'><?=$params['graph-count']?></td>
	<?php if($params['type'] == "system"){ ?>
		<tr><th>Collections</th><td class='collection-count'><?=$params['collection-count']?></td>
		<tr><th>Ontologies</th><td class='ontology-count'><?=$params['ontology-count']?></td>
	<?php } elseif($params['type'] == "admin"){ ?>	
		<tr><th>Imported Ontologies</th><td class='ontology-count'><?=$params['ontology-count']?></td>
	<?php } ?>
</table>

