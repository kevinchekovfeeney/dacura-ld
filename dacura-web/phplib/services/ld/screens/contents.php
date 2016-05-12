<div id="show-ldo"></div>
<script>
function initContents(data, screen){
	var ldo = new LDO(data);
	var ldov = new LDOViewer(ldo, dacura.tool.subscreens[screen]);
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
	
function drawFragmentHeader(data){
	if(typeof data.fragment_id != "undefined"){
		fids = data.fragment_id.split("/");
		fid = fids[fids.length -1];
		fdets = data.fragment_details;
		fpaths = data.fragment_paths;
		fpathhtml = "<div class='fragment-paths'>";
		for(i in fpaths){
			fpathhtml += "<span class='fragment-path'>";
			fpathhtml += "<span class='fragment-step'>" + data.id + "</span><span class='fragment-step'>";
			fpathhtml += fpaths[i].join("</span><span class='fragment-step'>");
			fpathhtml += "</span><span class='fragment-step'>" + data.fragment_id + "</span></span>";
		}
		fpathhtml += "</div>";
		$('#fragment-data').html("<span class='fragment-title-label'>Fragment</span> <span class='fragment-title'>" + fid + "</span><span class='fragment-details'>" + fdets + "</span>" + fpathhtml);
		$('#fragment-data').show();
	}	
}


</script>