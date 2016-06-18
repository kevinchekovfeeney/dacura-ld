<div class="dacura-subscreen" id='ldo-contents' title="<?=$params['contents_screen_title']?>">
	<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
	<div id="show-ldo"></div>
</div>

<script>
//just load the configuration and the object and call show...
var refreshContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", refreshLDOPage);
};
//just load the configuration and the object and call show...
var initContents = function(data, pconf){
	ldov.pconf = pconf;
	ldov.show("#show-ldo", "view", refreshLDOPage);
};
if(typeof initfuncs == "object"){
	initfuncs["ldo-contents"] = initContents;
}
if(typeof refreshfuncs == "object"){
	refreshfuncs["ldo-contents"] = refreshContents;
}
</script>