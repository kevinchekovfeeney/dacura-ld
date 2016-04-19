<div class='entity-header'>
	<span class='entity-label'><?php echo (isset($params['label']) ? $params['label'] : "~");?></span>
	<span class='entity-id'><?php echo (isset($params['id']) ? $params['id'] : "~");?></span>
	<span class='entity-type'><?php echo (isset($params['type']) ? $params['type'] : "~");?></span>
</div>
<?php if(isset($params['fragment_id'])) {?>
<div class='fragment-context'>
	<span class='fragment-label'>F Label</span>
	<span class='fragment-id'>F ID</span>
</div>

<?php }?>

<div class='candidate-meta'>
	<h2>Meta</h2>
	<?php echo (isset($params['meta']) ? $params['meta'] : "~");?>
</div>
<div class='candidate-contents'>
	<h2>Contents</h2>
	<?php echo (isset($params['contents']) ? $params['contents'] : "~");?>
</div>
<div class='candidate-provenance'>
	<h2>Provenance</h2>
	<?php echo (isset($params['provenance']) ? $params['provenance'] : "~");?>
</div>
<div class='candidate-annotation'>
	<h2>Annotation</h2>
	<?php echo (isset($params['annotation']) ? $params['annotation'] : "~");?>
</div>


