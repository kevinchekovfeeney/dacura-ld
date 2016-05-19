<div class="dacura-subscreen" id='ldo-contents' title="<?=$params['contents_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
	<div id="show-ldo"></div>
</div>

<script>
var initContents = function(data, pconf){
	var ldo = new LDO(data);
	var ldov = new LDOViewer(ldo, pconf);
	var ldovconfig = {target: "#show-ldo", emode: "view"};
	ldovconfig.view_formats = <?= json_encode($params['valid_view_formats']);?>;
	ldovconfig.edit_formats = <?= json_encode($params['valid_input_formats']);?>;
	ldovconfig.view_options = <?= json_encode($params['default_view_options']);?>;
	ldovconfig.view_actions = <?= json_encode($params['view_actions']);?>;
	ldovconfig.view_graph_options = <?= json_encode($params['view_graph_options']);?>;
	ldovconfig.result_options = <?= json_encode($params['update_result_options']);?>;
	ldovconfig.editmode_options = <?= json_encode($params['editmode_options']);?>;
	ldov.init(ldovconfig);
	ldov.show();
}
	
initfuncs["ldo-contents"] = initContents;


</script>