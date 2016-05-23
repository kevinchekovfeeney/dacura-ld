<div class="dacura-subscreen" id='ldo-contents' title="<?=$params['contents_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
	<div id="show-ldo"></div>
</div>

<script>
var show_contents_options = <?php echo isset($params['show_contents_options']) && $params['show_contents_options'] ? $params['show_contents_options'] : "{}";?>;
var refreshContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", show_contents_options, refreshLDOPage);
};

var initContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", show_contents_options, refreshLDOPage);
};

	
refreshfuncs["ldo-contents"] = refreshContents;

initfuncs["ldo-contents"] = initContents;


</script>