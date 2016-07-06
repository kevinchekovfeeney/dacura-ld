if(typeof dacura == "undefined"){
	var dacura = {};
}
dacura.params = <?=json_encode($params)?>;

if(typeof dacura.system == "undefined"){
	dacura.system = {};
}
dacura.system.cid = function(){
	return (typeof dacura.params.context.collection == "string" ? dacura.params.context.collection : "") ;
}

dacura.system.ajax_url = "<?=$service->durl(true)?>";
dacura.system.install_url = "<?=$service->durl()?>";

dacura.system.apiURL = function(s, c){
	return dacura.system.ajax_url + this.getcds(s, c);
};

/**
 *  @function getcds
 *  @memberof dacura.system
 *  @summary Get the current string representing the collection & servicename context. A basic "where am I" for building links
 *  @param {string} [s=current service] - The service name .
 *  @param {string} [c=current collection] - The collection id.
 */
dacura.system.getcds = function(s, c){
	if(typeof c == "undefined" || c.length == 0){
		c = this.cid();
	}
	if(!c.length){
		return "";
	}
	if(typeof s == "undefined"){
		s = "";
	}
	if(c == "" || c == "all"){
		return s;
	}
	return c + "/" + s;
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
	if(typeof opts.closeable == "undefined") opts.closeable = true;
	if(typeof opts.tprefix == "undefined") opts.tprefix = "";
	if(typeof opts.more_html == "undefined") opts.more_html  = "More Details";
	if(typeof opts.less_html == "undefined") opts.less_html  = "Hide Details";
	var cls = "dacura-" + type;
	var contents = "<div class='mtitle'>";
	if(opts.icon){
		contents += "<span class='result-icon result-" + type + "'>" + dacura.system.getIcon(type) + "</span>";
	}
	contents += title;
	if(opts.closeable){
		contents += "<span title='remove this message' class='user-message-close'>X</span>";
	}
	if(typeof extra != "undefined" && extra && !(typeof extra == 'object' && (size(extra)==0) && (extra.length == 0))){
		if(typeof extra == "object"){
			extra = JSON.stringify(extra, 0, 4);
		}
		dacura.system.resultisAnimating = false;
		var toggle_id = dacura.system.lasttoggleid++;
		if(typeof msg != "undefined" && msg){
			contents += "<div class='mbody'>" + msg; 
		}
		contents += "<div id='toggle_extra_" + toggle_id + "' class='toggle_extra_message'>" + opts.more_html + "</div></div>";
		if(opts.tprefix.length) contents = opts.tprefix + "<div class='dacura-test-message'>" + contents + "</div>";
		contents +=	"<div id='message_extra_" + toggle_id + "' class='message_extra dch'>" + extra + "</div>";
		var html = "<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>";
		jQuery(jqueryid).html(html);
		var tgid = '#toggle_extra_' + toggle_id;
		jQuery(tgid).click(function(event) {
			if(!dacura.system.resultisAnimating) {
				dacura.system.resultisAnimating = true;
		        setTimeout("dacura.system.resultisAnimating = false", 400); 
				jQuery("#message_extra_" + toggle_id).toggle( "slow", function() {
					if(jQuery('#message_extra_' + toggle_id).is(":visible")) {
						jQuery(tgid).html(opts.less_html);
					}
					else {
						jQuery(tgid).html(opts.more_html);				
					}
				});
		    } 
			else {
		        event.preventDefault();
		    }
		});
	}
	else {
		if(typeof msg != "undefined" && msg){
			contents += "<div class='mbody'>" + msg + "</div>";
		}
		if(opts.tprefix.length) contents = opts.tprefix + "<div class='dacura-test-message'>" + contents + "</div>";
		jQuery(jqueryid).html("<div class='dacura-user-message-box " + cls + "'>" + contents + "</div>");
	}
	if(typeof opts.closeable != "undefined" && opts.closeable){
		$('.user-message-close').click(function(){
			jQuery(jqueryid).html("");
		});
	}
	jQuery(jqueryid).show();
};	

