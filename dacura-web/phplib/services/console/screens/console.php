<div id='dacura-console'> 
	<div class='console-spacer'></div>
	<div class='console-branding'><img height='24' src='<?=$service->furl('image', 'dacura-logo-simple.png')?>'></div>
	<div class='console-context'>
		<span class='context-element collection'></span>
		<span class='context-element entitytype'></span>
		<span class='context-element entities'></span>
		<span class='context-element properties'></span>
	</div>
	<div class='console-stats'></div>
	<div id='dacura-console-menu-message'></div>
	<div class='console-user'></div>
	<div class='console-controls'></div>
	<div class='console-extra'></div>
</div>
<script>
//the params array contains all the variable information in the console and is included directly into it. 
if(typeof dacura.params.jslibs == "object" && dacura.params.jslibs.length > 0){
	for(var i=0; i< dacura.params.jslibs.length; i++){
		var script = document.createElement('script');
		script.type = "text/javascript";
		script.src = dacura.params.jslibs[i];
		document.getElementsByTagName('head')[0].appendChild(script);		
	}
}

jQuery(document).ready(function(){
	function launchConsole(){
		dconsole.init();
	}

	function deferUntilConsoleLoaded(method) {
    	if (typeof dconsole != "undefined"){
        	method();
    	}
    	else {
        	setTimeout(function() { deferUntilConsoleLoaded(method) }, 50);
    	}
	}
	deferUntilConsoleLoaded(launchConsole);
});

</script>