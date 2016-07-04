var params = <?=json_encode($params)?>;

dconsole = {}
dconsole.insertPane = function (){
	var pane = "<div id='dacura-console'><div class='console-branding'>"; 
	pane += "<img height='24' src='<?=$service->furl('image', 'dacura-logo-simple.png')?>'></div>";
	pane += "<div class='console-context'><span class='collection'></span><span class='entitytype'></span><span class='entities'></span><span class='properties'></span></div>";
	pane += "<div class='console-stats'></div>";
	pane += "<div class='console-controls'>";
	pane += "</div>";
	pane += "<div class='console-user'>";
	pane += "</div>";
	pane += "<div id='console-extra'></div>";
	pane += "</div>";
	$("body").append(pane);
	$("#dacura-console").hide();	
};

dconsole.initPane = function(){
	style=document.createElement("link");
	style.setAttribute("rel", "stylesheet");
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->furl('css', 'jquery-ui.css')?>");
	document.body.appendChild(style);
	style=document.createElement("link");
	style.setAttribute("rel", "stylesheet");
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->get_service_file_url('console.css')?>");
	document.body.appendChild(style);
	this.insertPane();
	$("#dacura-console").show();
};

dconsole.showLoginBox = function(){
	var html = "<span class='login-topbar'>email: <input class='login-email' type='text'> password: <input class='login-pass' type='password'> <button class='logingo'>Login</button></span>";
	$('#dacura-console .console-user').html(html);
	$('.logingo').button().click(function(){
		var pass = $('.login-topbar .login-pass').val();
		var email = $('.login-topbar .login-email').val();
		xhr = {};
		xhr.xhrFields = {
		    withCredentials: true
		};
		xhr.data ={};
		xhr.url = params.loginurl;
		xhr.type = "POST";
		xhr.data['login-email'] = email;
		xhr.data['login-password'] = pass;
		$.ajax(xhr).done(function(response, textStatus, jqXHR) {
			dconsole.reload();
			alert("logged in - loading console");
		}).fail(function (jqXHR, textStatus, errorThrown){
			//if(jqXHR.responseText && jqXHR.responseText.length > 0){
			alert("login failed");
		});
	});
}

dconsole.reload = function(){
	$('#dacura-console').remove();
	xhr = {};
	xhr.url = params.reloadurl;	
	xhr.xhrFields = {
	    withCredentials: true
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		$("body").append(response);
	})
	.fail(function(response){
		alert(response);
	});
}

dconsole.getUserMenuHTML = function(){
	var html = '<div class="console-user-context">';
	html += '<a href="' + params.profileurl + '">';
	html += '<span class="username" title="' + params.username + '"><img class="uicon" src="' + params.usericon + '" />';
	html += "</span></a>";
	html += "<div class='console-user-menu dch'>";
	html += '<ul id="console-user-actions">';
	if(typeof params.current_collection != "undefined"){
		for(var i in params.collection_choices){
			if(i == params.current_collection){
			 	html += "<li class='ui-state-disabled'>" + params.collection_choices[i].title + "</li>";				
			}
			else {
			 	html += '<li><a href="javascript:dconsole.switchCollectionContext(\"' + i + '\")">' + params.collection_choices[i].title + '</a></li>';				
			}
		}
	}
	html += "<li>--</li><li>";
	html += '<span class="ui-icon ui-icon-disk"></span>';
	html += "<a href='" + params.dacuraurl + "'>Dacura Home</a></li><li>"; 
	html += '<span class="ui-icon ui-icon-disk"></span>';
	html += "<a href='" + params.logouturl + "'>Logout</a></li>"; 
	html += "</ul></div></div>";
	return html;
};

dconsole.getEntityTypeSelectorHTML = function(){
	var html = "<select class='console-entity-type'><option value=''>Entity Type</option>";
	for(var i = 0; i < params.collection_contents.entity_classes.length; i++){
		var clsname = params.collection_contents.entity_classes[i].substring(params.collection_contents.entity_classes[i].lastIndexOf('#')+1);
		if(clsname != "Nothing"){
			html += "<option value='" + params.collection_contents.entity_classes[i] + "'>" + clsname + "</option>";
		}
	}
	html += "</select><span id='createentity'>" + params.new_entity_icon + "</span>";
	return html;
};

dconsole.getEntitySelectorHTML = function(cls, clsname){
	if(typeof params.collection_contents.entities[cls] == "undefined" || params.collection_contents.entities[cls].length == 0){
		return "";
	}
	var html = "<select class='console-entity-list'><option value=''>Select " + clsname + "</option>";
	for(var i = 0; i < params.collection_contents.entities[cls].length; i++){
		html += "<option value='" + params.collection_contents.entities[cls][i] + "'>" + params.collection_contents.entities[cls][i] + "</option>";
	}
	html += "</select>";
	return html;
};

dconsole.getChoosePropertiesHTML = function(frame){
	var html = "<select class='console-properties'><option value=''>Choose a property</option>";
	for(var i = 0; i < frame.length; i++){
		html += "<option value='" + frame[i]['property'] + "'>" + frame[i]['label']['data'] + "</option>";
	}
	html += "</select><span id='viewproperty'>" + params.view_property_icon + "</span>";
	return html;
}

dconsole.showProperty = function(){};
dconsole.createEntity = function(){};


dconsole.getFilledFrame = function(id, callback){
	xhr = {};
	xhr.url = params.apiurl + "candidate/frame/" + id;
	xhr.xhrFields = {
	    withCredentials: true
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var res = JSON.parse(response);
			if(res.status == "accept" && typeof res.result != "undefined"){				
				var frame = (typeof res.result == "object") ? res.result : JSON.parse(res.result);
				callback(frame);
			}
			else{
				alert("failed to read frame for " + type);
			}
		}
		catch(e){
			alert("Failed to contact server to parse candidate: " + e.message);
		}
	})
	.fail(function(response){
			alert("Failed to retrieve class " + type + " class frame from " + xhr.url);
	});
};

dconsole.getEmptyFrame = function(cls, callback){
	xhr = {};
	xhr.url = params.apiurl + "candidate/frame";
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.type = "POST";
	xhr.data = {"class": cls};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var res = JSON.parse(response);
			if(res.status == "accept" && typeof res.result != "undefined"){				
				var frame = (typeof res.result == "object") ? res.result : JSON.parse(res.result);
				callback(frame);
			}
			else{
				alert("failed to read frame for " + type);
			}
		}
		catch(e){
			alert("Failed to contact server to parse candidate: " + e.message);
		}
	})
	.fail(function(response){
		alert("Failed to retrieve class " + type + " class frame from " + xhr.url);
	});
};

dconsole.loadEntityType = function(cls) {
	var clsname = cls.substring(cls.lastIndexOf('#')+1);
	var callback = function(frame){
		$('#dacura-console .console-context .properties').html(dconsole.getChoosePropertiesHTML(frame));
		$('#dacura-console .console-context .properties select.console-properties').selectmenu();
	};
	$('#dacura-console .console-context .entities').html(this.getEntitySelectorHTML(cls, clsname));
	$('#dacura-console .console-context select.console-entity-list').selectmenu({
	  change: function( event, ui ) {
		  if(this.value.length){
			  dconsole.getFilledFrame(this.value, callback);
		  }
	  }
	});
	dconsole.getEmptyFrame(cls, callback);
};

dconsole.showUserOptions = function(){
	$('#dacura-console .console-context .collection').html(params.context_title);
	$('#dacura-console .console-context .entitytype').html(this.getEntityTypeSelectorHTML());
	$('#dacura-console .console-user').html(this.getUserMenuHTML());
	$('#console-user-actions').menu({
		  icons: { submenu: "ui-icon-circle-triangle-w" }
	});
	$('#dacura-console select.console-entity-type').selectmenu({
		  change: function( event, ui ) {
			  if(this.value.length){
				  dconsole.loadEntityType(this.value);
			  }
		  }
	});
	this.grabWikiFacts();
	$('.console-user-context').hover(
	  	function(){ 
	      	$(this).addClass('ui-state-focus');
	      	$('.console-user-menu').show("blind");
	      	$('#console-user-actions').menu("refresh"); 
	    },
	  	function(){ 
	      	$(this).removeClass('ui-state-focus'); 
	      	$('.console-user-menu').hide("fade", "slow");
	    }
	);
}

dconsole.getEntityClasses = function(handler){
	xhr = {};
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.url = params.apiurl + "candidate/entities";
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			x = JSON.parse(response);
		}
		catch(e){
			dconsole.showErrorMessage("Failed to parse entity classes response from server: " + e.message);
		}
	})
	.fail(function(){
		alert("failed to find any entity classes for " + candurl);
	});
};

dconsole.grabWikiFacts = function(){
	var page = $('#bodyContent').html();
	this.originalpage = page;
	this.calculatePageContexts(page);
	var regex = /(♠([^♠♥]*)♣([^♠♥]*)♥)([^♠]*)/gm;
	var i = 0;
	var facts = [];
	while(matches = regex.exec(page)){
		factParts = {
			"id": (i+1),
			"location": matches.index, 
			"full": matches[1],
			"length" : matches[1].length, 
			"varname": matches[2].trim(),
			"contents": matches[3].trim(),
			"notes": matches[4].trim().substring(4).trim()
		};
		factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
		if(factParts.notes.substring(factParts.notes.length - 3) == "<b>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 3) == "<p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 4) == "</p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 4).trim();
		}
		//factParts.notes = factParts.notes.substring(0, factParts.notes.length - 6);
		if(factParts.varname.length == 0){
			factParts.parsed = { "result_code" : "error", "result_message" : "Variable name is missing"}				
		}
		else {
			if(factParts.contents.length == 0){
				factParts.parsed = { "result_code" : "empty", "result_message" : "No value entered yet"}				
			}
			if(typeof varnames[factParts.varname] == "undefined"){
				varnames[factParts.varname] = {};
			}
			varnames[factParts.varname][factParts.id] = factParts;
		}
		var locator = this.getFactContextLocator(factParts);
		if(locator){
			locid = locator.id;
			var flocid = locid + factParts.varname;
			if(typeof locators[flocid] != "undefined"){
				locid += "#" + (++locators[flocid]);
			}
			else {
				locators[flocid] = 1;
			}
			factParts.pattern = locid;
		}
		else {
			factParts.pattern = "";
		}
		facts[i++] = factParts;
	}
	return facts;
}

dconsole.sendFactsToParser = function(){
	var pfacts = [];
	var fact_ids = [];
	if(this.pageFacts.length == 0){
		//alert("No Seshat facts were found in the page. Seshat facts are encoded as ♠ VAR ♠ VALUE ♥");	
		return;
	}
	for(i in this.pageFacts){
		if(typeof this.pageFacts[i].parsed != "object"){
			pfacts[pfacts.length] = this.pageFacts[i].contents;
			fact_ids[fact_ids.length] = i;			
		}
	}
	if(pfacts.length == 0){
		//whole page parsed already
		this.displayFacts();
		return;
	}
	xhr = {};
	xhr.xhrFields = {
	   withCredentials: true
	};
		
	xhr.data = { "data" : JSON.stringify(pfacts)};
    //xhr.dataType = "json";
    //xhr.data = JSON.stringify(pfacts);
	xhr.url = "<?=$service->my_url('rest')?>/validate";
	xhr.type = "POST";
	xhr.beforeSend = function(){
		this.grabison = true;
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				this.pageFacts[fact_ids[i]].parsed = results[i];
			}
			this.displayFacts();
			$('button#validator-ontologize-button').button().click(function(){
				$('#ontologize').toggle();
			});
			$('button#validator-close-button').button().click(function(){
				this.clear();
			});
			this.getOntology(onturl);
			this.grabison = false;
		}
		catch(e){
			alert("Failed to contact server to parse variables: " + e.message);
			this.grabison = false;
		}
	})
	.fail(function (jqXHR, textStatus){
		alert("Failed to contact server to parse variables: " + jqXHR.responseText);
		alert(JSON.stringify(jqXHR));
		this.grabison = false;
	});
};	

var varnames = {};

var pagecontexts = {};
var locators = {};

dconsole.calculatePageContexts = function(page){
	var headerids = {};
	var pheaders = $(':header span.mw-headline');
	for(var j = 0; j< pheaders.length; j++){
		var hid = pheaders[j].id;
		if(hid.length){
			var htext = $(pheaders[j]).text();
			if(htext.substring(htext.length - 6) == "[edit]"){
				htext = htext.substring(htext.length - 6); 
			}
			var regexstr = "id\\s*=\\s*['\"]" + escapeRegExp(hid) + "['\"]";
			var re = new RegExp(regexstr, "gmi");
			var hids = 0;
			var hmatch;
			while(heads = re.exec(page)){
				hmatch = heads.index;
				hids++;
			}
			if(hids != 1){
				alert("failed to find unique header id for header " + hid + " " + hids);
			}
			else {
				pagecontexts[hmatch] = {id: hid, text: htext};
			}
		}
	}	
}

dconsole.uniqifyFacts = function(){
	for(var i = 0; i< this.pageFacts.length; i++){
		var factoid = this.pageFacts[i];
		if(size(varnames[factoid.varname]) == 1){
			this.pageFacts[i].uniqid = factoid.varname;
		}
		else {
			this.pageFacts[i].uniqid = factoid.varname + "_" + "repeated"; 
			var seq = 1;
			for(var id in varnames[factoid.varname]){
				if(varnames[factoid.varname][id].id == factoid.id){
					this.pageFacts[i].uniqid = factoid.varname + "_" + seq;	
					break;
				}
				seq++;	
			}
		}
	}
}

dconsole.getFactContextLocator = function(factoid){
	//most recent header text....
	//locate the nearest header
	var hloc = 0;
	for(var loc in pagecontexts){
		if(loc < factoid.location && loc > hloc){
			hloc = loc;
		}
	}
	return pagecontexts[hloc];
}

function toTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

function size(obj){
	return Object.keys(obj).length
}

function jpr(obj){
	alert(JSON.stringify(obj));
}