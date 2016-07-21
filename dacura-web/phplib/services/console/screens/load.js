if(typeof dacura_console_loaded == "undefined"){ //don't load script twice
	pre_dacura_body_html = "";
	dacura_console_loaded = true;
	if(!window.jQuery){
		var script = document.createElement('script');
		script.type = "text/javascript";
		script.src = "<?=$service->furl("js", "jquery-2.1.4.min.js")?>";
		document.getElementsByTagName('head')[0].appendChild(script);
	}
	if(typeof window.jQuery == "undefined" || !window.jQuery || !window.jQuery.ui){
		var script = document.createElement('script');
		script.type = "text/javascript";
		script.src = "<?=$service->furl("js", "jquery-ui.js")?>";
		document.getElementsByTagName('head')[0].appendChild(script);
		style=document.createElement("link");
		style.setAttribute("rel", "stylesheet");
		style.setAttribute("type", "text/css");
		style.setAttribute("href", "<?=$service->furl("css", "jquery-ui.css")?>");
		document.getElementsByTagName('head')[0].appendChild(style);
	}
    
    annotation_script = document.createElement('script');
    annotation_script.type = "text/javascript";
	annotation_script.src = "<?=$service->get_service_screen_url('annotation.js')?>";
	document.getElementsByTagName('head')[0].appendChild(annotation_script);

	style=document.createElement("link");	
	style.setAttribute("rel", "stylesheet");	
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->get_service_file_url('console.css')?>");	
	document.getElementsByTagName('head')[0].appendChild(style);

	function deferUntilLibsLoaded(method) {
		if (window.jQuery && window.jQuery.ui)
	    	method();
		else
	    	setTimeout(function() { deferUntilLibsLoaded(method) }, 50);
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
				pre_dacura_body_html = jQuery(dacura_params.jquery_body_selector).html();
    			try {
    				jQuery(dacura_params.jquery_body_selector).after(xhr.responseText);    	
    			}
    			catch(e){
    				alert("appending body failed: " + e.message + "\n" + xhr.responseText);
    			}
        	}
    		deferUntilLibsLoaded(func);
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

