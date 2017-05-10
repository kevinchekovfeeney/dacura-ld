var durl = "<?=$dacura_server->durl()?>";
if(typeof dacura_console_loaded == "undefined"){ //don't load script twice
	body_before_dacura = "";
	dacura_console_loaded = true;

	var jslibs = [durl + "phplib/services/core/dacura.utils.js", 
      durl + "phplib/services/ld/jslib/ldlibs.js", 
      durl + "phplib/services/candidate/gmap.js",
      durl + "phplib/services/candidate/big.js",
      durl + "phplib/services/candidate/range.js",
      durl + "phplib/services/candidate/year.js",
      durl + "phplib/services/candidate/duration.js",
      durl + "phplib/services/candidate/dacura.frame.js",
      durl + "phplib/services/console/dclient.js", 
      durl + "phplib/services/console/dconsole.js",
      durl + "phplib/services/console/dpagescanner.js"];

    var csslibs = [durl + "media/css/jquery-ui.css", durl + "phplib/services/console/files/console.css", 
                   "https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css", 
                   durl + "phplib/services/console/files/font-awesome-4.6.3/css/font-awesome.min.css"];
 	
	if(typeof jslibs == "object" && jslibs.length > 0){
		for(var i=0; i< jslibs.length; i++){
			var script = document.createElement('script');
			script.type = "text/javascript";
			script.src = jslibs[i];
			script.crossorigin = "anonymous";
			document.getElementsByTagName('head')[0].appendChild(script);		
		}
	}
	if(typeof csslibs == "object" && csslibs.length > 0){
		for(var i=0; i< csslibs.length; i++){
			var style=document.createElement("link");	
			style.setAttribute("rel", "stylesheet");	
			style.setAttribute("type", "text/css");
			style.setAttribute("href", csslibs[i]);	
			document.getElementsByTagName('head')[0].appendChild(style);
		}
	}

	var dacura_params = <?= (isset($params) ? json_encode($params) : "{}") ?>;
	var xhr = new XMLHttpRequest();
	if (!("withCredentials" in xhr)){
		alert("Your browser does not support dacura console - it needs to support authorised cross site requests (xhttp with credentials) ");
	}
	else {
		xhr.withCredentials = true;
	    xhr.open("GET", dacura_params.homeurl + "?source=" + encodeURIComponent(window.location.href));
  	  	xhr.onload = function(){
    		var func = function(){
    			body_before_dacura = jQuery(dacura_params.jquery_body_selector).html();
    			try {
    				var p = xhr.responseText;
    				jQuery(dacura_params.jquery_body_selector).after(p);
    				jQuery('#dacura-console img').each(function(){
    					jQuery(this).attr("src", durl + "phplib/services/console/" + jQuery(this).attr("src"));
    				});
    			}
    			catch(e){
    				alert("appending body failed: " + e.message + "\n" + xhr.responseText);
    			}
        	}
    		function launchConsole(){
    			jQuery.fn.select2.defaults.set('dropdownCssClass', 's2option');
    			dacuraConsole = new DacuraConsole(dacura.params.console_config);
    			if(dacura.params.context.tool  && dacura.params.context.tool == "data") {
    				dacura.params.context.entityclass = "http://dacura.scss.tcd.ie/seshat/ontology/seshat#Polity";
    			}
				dacuraConsole.init(dacura.params.context);
    			//dacuraConsole.init({collection: "seshat", tool: "data", candidate: "ddda", mode: "view"});
    		}
    		function deferUntilConsoleLoaded(method) {
    			if (typeof DacuraConsole === "function" && typeof DacuraClient === "function" ){
    		    	method();
    			}
    			else {
    		    	setTimeout(function() { deferUntilConsoleLoaded(method) }, 50);
    			}
    		}
    		func();
    		deferUntilConsoleLoaded(launchConsole);
    	};
    	xhr.onerror = function(){
    		console.log("error loading dacura console");
    	}
    	xhr.send();
	}
}
else {
	console.log("dacura console already loaded");
}

