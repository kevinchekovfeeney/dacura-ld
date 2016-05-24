<div class="dacura-subscreen" id='ldo-contents' title="<?=$params['contents_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
	<div id="show-ldo"></div>
</div>

<script>
var refreshContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", refreshLDOPage);
};

var initContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", refreshLDOPage);
};

	
refreshfuncs["ldo-contents"] = refreshContents;

initfuncs["ldo-contents"] = initContents;


</script>