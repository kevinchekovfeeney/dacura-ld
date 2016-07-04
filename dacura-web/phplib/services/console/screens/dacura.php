<script>
var params = <?=json_encode($params)?>;
if(typeof dacura == "undefined"){
	var dacura = {};
}
if(typeof dacura.system == "undefined"){
	dacura.system = {};
}

dacura.system.ajax_url = "<?=$service->durl(true)?>";
dacura.system.install_url = "<?=$service->durl()?>";
dacura.system.apiURL = function(s, c){
	return dacura.system.ajax_url + s;
};

dacura.system.iconbase = "<?=$service->get_system_file_url('images', 'icons')?>";

dacura.system.getIcon = function(icon, config){
	var url = dacura.system.iconbase + "/" + icon + ".png";
	var cls = (typeof config == "object" && typeof config.cls != "undefined") ? config.cls : 'result-icon';
	var titstr = (typeof config == "object" && typeof config.title != "undefined") ? " title='" + config.title + "'" : "";
	return "<img class='" + cls + "' src='" + url + "' " + titstr + ">";	
}

dacura.system.writeResultMessage = function(type, title, jqueryid, msg, extra, opts){
	if(typeof opts == "undefined") opts = {};
	if(typeof opts.icon == "undefined") opts.icon = true;
	if(typeof opts.scrollTo == "undefined") opts.scrollTo = true;
	if(typeof opts.closeable == "undefined") opts.closeable = true;
	if(typeof opts.tprefix == "undefined") opts.tprefix = "";
	if(typeof opts.close_position == "undefined") opts.close_position = "body";
	if(typeof opts.more_html == "undefined") opts.more_html  = "More Details";
	if(typeof opts.less_html == "undefined") opts.less_html  = "Hide Details";
	var self = dacura.system;
	var cls = "dacura-" + type;
	var contents = "<div class='mtitle'>";
	if(typeof opts.icon != "undefined" && opts.icon){
		contents += "<span class='result-icon result-" + type + "'>" + self.getIcon(type) + "</span>";
	}
	contents += title;
	if(opts.close_position == "title" && typeof opts.closeable != "undefined" && opts.closeable){
		contents += "<span title='remove this message' class='user-message-close ui-icon-close ui-icon'></span>";
	}
	contents += "</div>";
	if(opts.close_position == "body" && typeof opts.closeable != "undefined" && opts.closeable){
		contents += "<span title='remove this message' class='user-message-close'>X</span>";
	}
	if(typeof extra != "undefined" && extra && !(typeof extra == 'object' && (size(extra)==0) && (extra.length == 0))){
		if(typeof extra == "object"){
			extra = JSON.stringify(extra, 0, 4);
		}
		self.isAnimating = false;
		var toggle_id = self.lasttoggleid++;
		if(typeof msg != "undefined" && msg){
			contents += "<div class='mbody'>" + msg; 
		}
		contents += "<div id='toggle_extra_" + toggle_id + "' class='toggle_extra_message'>" + opts.more_html + "</div></div>";
		
		if(opts.tprefix.length) contents = opts.tprefix + "<div class='dacura-test-message'>" + contents + "</div>";
		contents +=	"<div id='message_extra_" + toggle_id + "' class='message_extra dch'>" + extra + "</div>";
		var html = "<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>";
		$(jqueryid).html(html);
		var tgid = '#toggle_extra_' + toggle_id;
		$(tgid).click(function(event) {
			if(!self.isAnimating) {
				self.isAnimating = true;
		        setTimeout("dacura.system.isAnimating = false", 400); 
				$("#message_extra_" + toggle_id).toggle( "slow", function() {
					if($('#message_extra_' + toggle_id).is(":visible")) {
						$(tgid).html(opts.less_html);
					}
					else {
						$(tgid).html(opts.more_html);				
					}
				});
		    } 
			else {
				alert("animating");
		        event.preventDefault();
		    }
		});
	}
	else {
		if(typeof msg != "undefined" && msg){
			contents += "<div class='mbody'>" + msg + "</div>";
		}
		if(opts.tprefix.length) contents = opts.tprefix + "<div class='dacura-test-message'>" + contents + "</div>";
		$(jqueryid).html("<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>");
	}
	if(typeof opts.closeable != "undefined" && opts.closeable){
		$('.user-message-close').click(function(){
			$(jqueryid).html("");
		})
	}
	$(jqueryid).show();
};	


dacura.system.styleJSONLD = function(jqid) {
	if(typeof jqid == "undefined"){
		jqid = ".rawjson";
	}
	$(jqid).each(function(){
	    var text = $(this).html();
	    if(text){
	    	if(text.length > 50){
	    		presentation = text.substring(0, 50) + "...";
	    	}
	    	else {
	    		presentation = text;
	    	}
		    $(this).html(presentation);
	    	try {
	    		var t = JSON.parse(text);
	    		if(t){
	    			t = JSON.stringify(t, 0, 4);
	    		    $(this).attr("title", t);
	    		}
	    	}
	    	catch (e){
    		    $(this).attr("title", "Failure: " + e.message);	    		
	    	}
	    }
	});
};
function toTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

function size(obj){
	if(typeof obj != "object") return 0;
	return Object.keys(obj).length
}

function urlFragment(url){
	url = (typeof url == "undefined") ? window.location.href : url;
	url = url.split('#')[1];
	if(url){
		url = url.split("?")[0];		
	}
	return url;
}

function lastURLBit(url){
	url = (typeof url == "undefined") ? window.location.href : url;
	url = url.split('#')[0];
	url = url.split("?")[0];
	url = url.substring(url.lastIndexOf("/")+1);
	return url;
}

function jpr(obj){
	alert(JSON.stringify(obj));
}

/**
 * @function escapeQuotes
 * @param text the string
 * @returns a string with the quotes escaped
 */
function escapeQuotes(text) {
	if(!text) return text;
	var map = {
	    '"': '\\"',
	    "'": "\\'"
    };
	return text.replace(/"']/g, function(m) { return map[m]; });
}

/**
 * @function escapeHtml
 * @param text the string
 * @returns a string with the quotes escaped
 */
function escapeHtml(text) {
  if(!text) return text;
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * @function isEmpty
 * @summary is the object / associative array empty?
 * @param {Object} obj - the object 
 * @return {Boolean} - true if the object is empty
 */
function isEmpty(obj) {
    // null and undefined are "empty"
    if (obj == null) return true;
    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (obj.length > 0)    return false;
    if (obj.length === 0)  return true;
    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    for (var key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) return false;
    }
    return true;
}

if(typeof dacura.ld == "undefined"){
 dacura.ld = {}
}
 /**
  * @summary generate the html to display a triple table
  * @memberof dacura.ld
  * @param trips {Array} array of triples
  * @param tit {string} the optional table title
  * @returns {String} html 
  */
 dacura.ld.getTripleTableHTML = function(trips, tit){
 	var html = "";
 	if(trips.length > 0){
 		isquads = trips[0].length == 4;
 		if(typeof tit == "string" && tit.length){
 			html += "<div class='api-triplestable-title'>" + tit + "</div>";
 		}
 		html += "<table class='rbtable'>";
 		html += "<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th>";
 		if(isquads){
 			html += "<th>Graph</th>";
 		}
 		html += "</tr></thead><tbody>";
 		for(var i = 0; i < trips.length; i++){
 			if(typeof trips[i][2] == "object"){
 				trips[i][2] = JSON.stringify(trips[i][2]);
 			}
 			html += "<tr><td>" + trips[i][0] + "</td><td>" + trips[i][1] + "</td><td>" + trips[i][2] + "</td>";
 			if(isquads){
 				html += "<td>" + trips[i][3] + "</td>";
 			}
 			html += "</tr>";				
 		}
 		html += "</tbody></table>";
 	}
 	return html;
 };

 /**
  * @summary Generates the HTML to display a json update view 
  * @param def the object with the changes
  * @returns {String}
  */
 dacura.ld.getJSONUpdateViewHTML = function(def){
 	var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Value Before</th><th>Value After</th></tr></thead><tbody>";
 	for(var i in def){
 		html += "<tr><td>" + i + "</td>";
 		html += "<td class='table-json-viewer'>";
 		html += (typeof def[i][0] == "object" ? JSON.stringify(def[i][0], 0, 4) : def[i][0]);
 		html += "</td><td class='table-json-viewer'>";
 		html += (typeof def[i][1] == "object" ? JSON.stringify(def[i][1], 0, 4) : def[i][1]);
 		html += "</td></tr>";
 	}
 	html += "</tbody></table>";
 	return html;
 }

 /**
  * @summary shows the json view of an object
  * @param inserts the inserted json
  * @param deletes the delete json
  * @returns {String} html
  */
 dacura.ld.getJSONViewHTML = function(inserts, deletes){
 	if(!inserts || !deletes){
 		var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Value</th></tr></thead><tbody>";
 		var def = inserts ? inserts : deletes;
 		for(var i in def){
 			html += "<tr><td>" + i + "</td><td class='table-json-viewer'>";
 			html += (typeof def[i] == "object" ? JSON.stringify(def[i], 0, 4) : def[i]);
 			html += "</td></tr>";
 		}
 		html += "</tbody></table>";
 	}
 	else {
 		var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Before</th><th>After</th></tr></thead><tbody>";
 		if(typeof inserts == "object"){
 			for(var i in inserts){
 				html += "<tr><td>" + i + "</td><td class='dacura-json-viewer'>";
 				if(typeof deletes == "object" && typeof deletes[i] != "undefined"){
 					html += typeof deletes[i] == "object" ? JSON.stringify(deletes[i], 0, 4) : deletes[i];
 				}
 				else {
 					html += "not defined";
 				}
 				html += "</td><td class='dacura-json-viewer'>" + (typeof inserts[i] == "object" ? JSON.stringify(inserts[i], 0, 4) : inserts[i]);
 				html += "</td></tr>";
 			}
 		}
 		if(typeof deletes == "object"){
 			for(var i in deletes){
 				if(typeof inserts != "object" || typeof inserts[i] == "undefined"){
 					html += "<tr><td>" + i + "</td><td class='dacura-json-viewer'>undefined</td><td class='dacura-json-viewer'>";	
 					html += typeof deletes[i] == "object" ? JSON.stringify(deletes[i], 0, 4) : deletes[i];
 					html += "</td></tr>";
 				}
 			}
 		}
 		html += "</tbody></table>";
 	}
 	return html;
 };

 /**
  * @summary wraps the json in html to display it in
  * @param json {Object} the json object to be wrapped
  * @param mode {string} edit|view 
  * @returns {String} html
  */
 dacura.ld.wrapJSON = function(json, mode){
 	if(!mode || mode == "view"){
 		var html = "<div class='dacura-json-viewer'>" + JSON.stringify(json, null, 4) + "</div>";				
 	}
 	else {
 		var html = "<div class='dacura-json-editor'><textarea class='dacura-json-editor'>" + JSON.stringify(json, null, 4) + "</textarea></div>";			
 	}
 	return html;
 };

 /**
  * Returns true if the passed format uses json as its underlying encoding.
  * @param format {String} the format
  * @returns {Boolean} true if it is a json format
  */
 dacura.ld.isJSONFormat = function(format){
 	if(format == "json" || format == "jsonld" || format == "quads" || "format" == "triples"){
 		return true;
 	}
 	return false;
 };

 /**
  * @summary generates the html to show the mini ontology pane
  * @param ont {string} ontology id 
  * @param onttit {string} ontology title
  * @param onturl {string} ontology url
  * @param ontv {number} ontology version
  * @returns {String} html
  */
 dacura.ld.getOntologyViewHTML = function(ont, onttit, onturl, ontv){
 	var html = "<span class='ontlabel'";
 	if(onttit) html +=" title='" + onttit+ "'";
 	html += ">";
 	if(onturl){	
 		html += "<a href='" + onturl + "'>" + ont;
 		if(typeof ontv != "undefined"){
 			html += ontv == 0 ? " (latest)" : " (v" + ontv + ")";	
 		}
 		html += "</a>";
 	}
 	else {
 		html += ont;
 		if(typeof ontv != "undefined"){
 			html += ontv == 0 ? " (latest)" : " (v" + ontv + ")";	
 		}
 	}
 	html += "</span>";
 	return html;
 };

 /**
  * @summary get the select drop down for drawing the ontology select pane
  * @param ont {string} the id of the ontology
  * @param onttit {string} the title of the ontology
  * @param ontv {number} the version number of the ontology
  * @param ontlv {number} the latest version number of the ontology
  * @returns {String} html
  */
 dacura.ld.getOntologySelectHTML = function(ont, onttit, ontv, ontlv){
 	if(typeof ontv != "undefined"){
 		var html = "<span class='ontlabel ontlabelrem' id='imported_ontology_" + ont + "'>";
 		html += "<span class='ontid-label' title='" + onttit + "'>" + ont + "</span>";
 		html += "<span class='remove-ont' id='remove_ontology_" + ont + "'>" + dacura.system.getIcon('error') + "</span>";
 		html += "<span class='ont-version-selector'>";
 		html += "<select class='imported_ontology_version' id='imported_ontology_version_" + ont + "'><option value='0'";
 		if(ontv == 0) html += " selected";
 		html += ">latest version</option>";
 		if(typeof ontlv != "undefined"){
 			for(var k = ontlv; k > 0; k--){
 				html += "<option value='" + k + "'";
 				if(k == ontv){
 					html += " selected";
 				}
 				html += ">version " + k + "</option>";
 			}
 		}
 		html += "</select></span>";	
 	}
 	else {
 		var html = "<span class='ontlabel ontlabeladd' title='Click to add ontology " + onttit + "' id='add_ontology_" + ont + "'>";
 		html += ont;
 		html += " <span class='add-ont'>" + dacura.system.getIcon('add') + "</span>";
 	}
 	html += "</span>";
 	return html;
 };

/**
 * @function ucfirst
 * @summary Upper cases the first character of a string - added to prototype of string object
*/
String.prototype.ucfirst = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

</script>