if(typeof dacura_console_loaded == "undefined"){ //don't load script twice
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

	style=document.createElement("link");	
	style.setAttribute("rel", "stylesheet");	
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->get_service_file_url('console.css')?>");	
	document.getElementsByTagName('head')[0].appendChild(style);
	var params = <?= (isset($params) ? json_encode($params) : "{}") ?>;
	var xhr = new XMLHttpRequest();
	if (!("withCredentials" in xhr)){
		alert("Your browser does not support cross site requests");
	}
	else {
		xhr.withCredentials = true;
	    xhr.open("GET", params.homeurl + "?source=" + encodeURIComponent(window.location.href));
  	  	xhr.onload = function(){
    		function deferUntilLibsLoaded(method) {
    	    	if (window.jQuery && window.jQuery.ui)
    	        	method();
    	    	else
    	        	setTimeout(function() { deferUntilLibsLoaded(method) }, 50);
    		}
    		var func = function(){
    			jQuery('body').append(xhr.responseText);    	
        	}
    		deferUntilLibsLoaded(func);
    	};
    	xhr.onerror = function(){
    		alert("error");
    	}
    	xhr.send();
	}
}
else {
	alert("dacura console already loaded")
}
