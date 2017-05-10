/**
 * @function styleJSONLD
 * @memberof dacura.system
 * @summary dacura.system.styleJSONLD
 * @description apply a visual style to json ld elements in tables 
 * Shows the full json in the title attribute
 * and shows an abbreviated version in the regular html
 * @param {string} [jqid=.rawjson] - the jquery id of the element to be styled 
 */

dacura.system.styleJSONLD = function(jqid) {
	if(typeof jqid == "undefined"){
		jqid = ".rawjson";
	}
	jQuery(jqid).each(function(){
	    var text = $(this).html();
	    if(text){
	    	if(text.length == 53 && text.substring(50) == "..."){ //collision detection quick and dirty
		    	return;
		    }
	    	if(text.length > 50){
	    		presentation = text.substring(0, 50) + "...";
	    	}
	    	else {
	    		presentation = text;
	    	}
	    	jQuery(this).html(presentation);
	    	try {
	    		var t = JSON.parse(text);
	    		if(t){
	    			t = JSON.stringify(t, 0, 4);
	    			jQuery(this).attr("title", t);
	    		}
	    	}
	    	catch (e){
	    		jQuery(this).attr("title", "Failure: " + e.message + " " + text);	    		
	    	}
	    }
	});
}

dacura.utils = {};
dacura.utils.tidyDOMFromHTML = function(untidy, isStopper){
	isStopper = (typeof isStopper == "function" ? isStopper : function(){return false;});
	var appendNodeToTidy = function(domnode){
		if(jQuery(domnode).text().trim() == ""){
			return false;
		}
		else {
			return true;
		}	
	};
	
	var tdom = document.createElement("p");
	var udom = document.createElement("p");
	udom.innerHTML = untidy;
	var gotOne = false;
	var stopped = false;
	while(udom.childNodes.length && !stopped){
		if(isStopper(udom.childNodes[0])){
			stopped = true;
		}
		else if(appendNodeToTidy(udom.childNodes[0])){
			gotOne = true;
			tdom.appendChild(udom.childNodes[0]);
		}
		else {
			udom.removeChild(udom.childNodes[0]);
		}
	}
	if(gotOne){
		return tdom;
	}
	return false;
}

dacura.utils.tidyHTML = function(untidy, isStopper){
	if(fcdom = this.tidyDOMFromHTML(untidy, isStopper)){
		return jQuery(fcdom).html().trim();
	}
	return false;
}

/* some simple utility functions */
/**
 * @function validateURL
 * @summary basic url validation
 * @param {string} url - the url to be validated
 * @return {Boolean} - true if it is a valid url
 */
function validateURL(url){
	return /^(https|http):/.test(url);
};

/**
 * @function getMetaProperty
 * @summary gets a property from a meta array or a default if it is not present
 * @param {Object} meta - the meta array
 * @param {string} key - the key to use
 * @param {Object} def - the default value to use if the key is not present
 * @return {Object} - the value of meta.key or def if it does not exist
 */
function getMetaProperty(meta, key, def){
	if(typeof meta[key] == "undefined"){
		return def;
	}
	return meta[key];
}

/**
 * @function durationConverter
 * @summary prints out a duration in human readable form
 * @param {Number} secs - number of seconds
 */
function durationConverter(secs){
    var sec_num = parseInt(secs, 10); // don't forget the second param
    var hours   = Math.floor(sec_num / 3600);
    var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
    var seconds = sec_num - (hours * 3600) - (minutes * 60);

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    if (seconds < 10) {seconds = "0"+seconds;}
    var time    = hours+':'+minutes+':'+seconds;
    return time;
}

/**
 * @function timeConverter
 * @summary prints out a date time duration in human readable form
 * @param {Number} UNIX_timestamp - number of seconds since 1970
 */
function timeConverter(UNIX_timestamp, type){
	  var a = new Date(UNIX_timestamp*1000);
	  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	  var year = a.getFullYear() % 100;
	  var month = a.getMonth() + 1;
	  var mday = a.getUTCDate();
	  var date = a.getDate();
	  var hour = a.getHours();
	  var min = a.getMinutes();
	  var sec = a.getSeconds();
	  if(hour < 10) hour = "0" + hour;
	  if(min < 10) min = "0" + min;
	  if(sec < 10) sec = "0" + sec;
	  if(typeof type == "undefined" || !type){
		  var time = hour + ':' + min + ':' + sec + " " + date + '/' + month + '/' + year ;
	  }
	  else {
		  var time = hour + ':' + min + ':' + sec + " on " + mday + " " + months[month] + " " +  a.getFullYear();
	  }
	  return time;
}

/**
 * @function size
 * @summary gets the count of an associative array / object
 * @param {Object} obj - the object 
 * @return {Number} - the number of elements in the object
 */
function size(obj){
	if(obj){
		return Object.keys(obj).length
	}
	return false;
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

dacura.utils.sloppyMatch = function(a, b){
	if(a.length > b.length){
		if(a.substring(0, b.length) == b) return true;
	}
	else if(b.length > a.length){
		if(b.substring(0, a.length) == a) return true;		
	}
	return this.isTypo(a, b);
}

dacura.utils.isStringType = function(type){
	return (!type || (urlFragment(type) == "string"));
}

dacura.utils.isTypo = function(a, b){
	var dis = this.getEditDistance(a, b);
	if(dis < 4 && a.length > 12) return true;
	if(dis < 3 && a.length > 8) return true;
	if(dis < 2 && a.length > 4) return true;
	return false;
}

dacura.utils.getEditDistance = function(a, b){
	  if(a.length == 0) return b.length; 
	  if(b.length == 0) return a.length; 

	  var matrix = [];

	  // increment along the first column of each row
	  var i;
	  for(i = 0; i <= b.length; i++){
	    matrix[i] = [i];
	  }

	  // increment each column in the first row
	  var j;
	  for(j = 0; j <= a.length; j++){
	    matrix[0][j] = j;
	  }

	  // Fill in the rest of the matrix
	  for(i = 1; i <= b.length; i++){
	    for(j = 1; j <= a.length; j++){
	      if(b.charAt(i-1) == a.charAt(j-1)){
	        matrix[i][j] = matrix[i-1][j-1];
	      } else {
	        matrix[i][j] = Math.min(matrix[i-1][j-1] + 1, // substitution
	                                Math.min(matrix[i][j-1] + 1, // insertion
	                                         matrix[i-1][j] + 1)); // deletion
	      }
	    }
	  }

	  return matrix[b.length][a.length];
};

dacura.utils.getXSDDateString = function(date){
	var date = (date ? date : new Date());
	var fmt = date.toISOString();
	var ofz = (date.getTimezoneOffset() / 60);
	if(ofz > 0){
		fmt += "+";
	}
	fmt += ":00";
	return fmt;
}


/**
 * @function toggleCheckbox
 * @summary Toggles the state of a checkbox
 * @param cbox jquery checkbox object
 */
function toggleCheckbox(cbox){
	if(cbox.is(':checked')) {
		cbox.prop( "checked", false)
	} 
	else {
		cbox.prop('checked', 'checked'); 
	}	
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

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

/**
 * @function isJSONObjectLiteral
 * @summary determines whether a json structure is a dacura object literal. 
 * @param json
 * @returns {Boolean}
 */
function isJSONObjectLiteral(json){
	if(typeof json.data == "undefined") return false;
	if((typeof json.type == "undefined" || json.type.length == 0) && typeof json.lang == "undefined" || json.lang.length) return false;
    for(var i in json){
		if(i != "lang" && i != "data" && i != "type") return false;
    }
    return true;
}

function urlFragment(url){
	url = (typeof url == "undefined") ? window.location.href : url;
	url = url.split('#')[1];
	if(url){
		url = url.split("?")[0];		
	}
	return url;
}



function isBNID(url){
	if(url.substring(0, 2) == "_:"){
		return true;
	}
	return false;
}

function bareURL(url){
	url = (typeof url == "undefined") ? window.location.href : url;
	url = url.split("?")[0];		
	url = url.split('#')[0];
	return url;
}

function lastURLBit(url){
	url = (typeof url == "undefined") ? window.location.href : url;
	url = url.split('#')[0];
	url = url.split("?")[0];
	url = url.substring(url.lastIndexOf("/")+1);
	return url;
}

function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

/**
 * @function nvArrayToOptions
 * @summary Produces a list of html options to populate a select from a passed name-value array
 * @param {Object} nv - the name-value array object
 * @param {string} [selected] - the id of the element that is selected by default
 * @return {string} - the html string 
 */
function nvArrayToOptions(nv, selected){
	var html = "";
	for(i in nv){
	    var selhtml = "";
	    if (typeof selected == "string" && i == selected) selhtml = " selected"; 
	    opthtml = "<option value='" + i + "'" + selhtml + ">" + nv[i] + "</option>";
	    if(i == ""){
	    	html = opthtml + html;
	    }
	    else {
	    	html += opthtml;
	    }
	}
	return html;
}

/**
 * @function jpr
 * @summary a short cut to alerting a json stringified version of a javascript object - basic debugging 
 * @param obj - the object to be show in the alert box
 */
function jpr(obj){
	alert(JSON.stringify(obj));
}

function toTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

function firstKey(obj) {
    for (var a in obj) return a;
    return false;
} 

function first(obj) {
    for (var a in obj) return obj[a];
    return false;
} 

/**
 * @function ucfirst
 * @summary Upper cases the first character of a string - added to prototype of string object
*/
String.prototype.ucfirst = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

String.prototype.after = function(char) {
	return this.substring(this.lastIndexOf(char) + 1);
}

/**
 * @function freezeElement
 * @param jq - jquery selector of element to freeze
 * @param hidden_degree - transparency percentage of overlay
 */
function freezeElement(jq, cls, hidden_degree){
	var cls = (typeof cls == "string" ? cls : "busy-overlay");
	var css = {
	    position: "absolute",
	    width: "100%",
	    height: "100%",
	    left: 0,
	    top: 0,
	    zIndex: 99999  // to be on the safe side
	}
	if(hidden_degree){
		css.background = "rgba(255, 255, 255, " +  hidden_degree + ")"
	}
	jQuery("<div class='" + cls + "'/>").css(css).appendTo(jQuery(jq).css("position", "relative"));
}




function isElement(obj) {
  try {
    //Using W3 DOM2 (works for FF, Opera and Chrom)
    return obj instanceof HTMLElement;
  }
  catch(e){
    //Browsers not supporting W3 DOM2 don't have HTMLElement and
    //an exception is thrown and we end up here. Testing some
    //properties that all elements have. (works on IE7)
    return (typeof obj==="object") &&
      (obj.nodeType===1) && (typeof obj.style === "object") &&
      (typeof obj.ownerDocument ==="object");
  }
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function numberWithCommas(x) {
    var parts = x.toString().split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
}

function isNumericType(type){
	var nums = ["decimal", "integer", "float", "double", "integerRange", "decimalRange"];
	var x = urlFragment(type);
	if(x && nums.indexOf(x) != -1) return true;
	return false;
	
}
