
// grab and display code for the seshat wiki scraper
// part of dacura
// Copyright (C) 2014 Dacura Team
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.



//for now, a bookmarklet which throws in a button
var dacura = {
		grabber: {}
};

dacura.grabber.insertModal = function (){
	var modal = "<div id='modal-dim'></div><div id='modal'>";
	modal += "<p id='modal-text'>Scanning...</p>";
	modal += "<button id='modal-close'>Close</button>";
	modal += "</div>";
	$("body").append(modal);
	$("#modal-close").click(function(){
		$("body").css("overflow", "auto");
		$("#modal-dim").hide();
		$("#modal-results").hide();
		$("#modal").hide();
		$("#modal-next").hide();
		$("#modal-prev").hide();
		$('#ca-grab').show()
		$("#modal").css("position", "absolute");
		$("#modal").css("top", "0");
		$("#modal").css("right", "0");
		$("#modal").css("left", "0");
		$("#modal").css("bottom", "0");
		$("#modal").css("margin", "auto");
	});
};

//https://developer.mozilla.org/en-US/docs/Using_XPath
dacura.grabber.getXPathForElement = function(el, xml){
	var xpath = '';
	var pos, tempitem2;
	while(el !== xml.documentElement){
		pos = 0;
		tempitem2 = el;
		while(tempitem2) {
			if (tempitem2.nodeType === 1 && tempitem2.nodeName === el.nodeName){ // If it is ELEMENT_NODE of the same name
				pos += 1;
			}
			tempitem2 = tempitem2.previousSibling;
		}
		xpath = el.nodeName + "[" + pos + ']' + '/' +xpath;
		el = el.parentNode;
	}
	xpath = '//' + xml.documentElement.nodeName + '/' + xpath;
	xpath = xpath.replace(/\/$/, '');
	return xpath;
};

dacura.grabber.grab = function(page){
	//this function grabs all the facts on the page
	factCollection = page.evaluate('//*[text()[contains(., "♠")]]', page, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null)
	facts = []
	for(var i = 0;i < factCollection.snapshotLength;i++){
		node = factCollection.snapshotItem(i);
		xpath = this.getXPathForElement(node, page);
		text = node.innerHTML;
		factParts = {"contents": text, "location": xpath};
		facts[facts.length] = factParts;
	}
	return facts;
};

dacura.grabber.display = function (json){
	var good = 0;
	var bad = 0;
	for(var i = 0;i < json.length;i++){
		xpath = json[i]["location"];
		node = document.evaluate(xpath, document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null).snapshotItem(0);
		if(json[i]["error"] === true){
			node.style.color = "red";
			node.classList.add("mistake");
			content = this.makeContent(json[i]["errorMessage"]);
			$(node).append(content);
			bad += 1;
			errorName = "error" + bad;
			$(node).prepend("<a id='" + errorName + "'></a><img src='<?=$service->get_service_file_url("error.png")?>' alt='error' class='error'>");
		}else if(json[i]["error"] === false){
			node.style.color = "green";
			$(node).prepend("<img src='<?=$service->get_service_file_url("correct.png")?>' alt='correct' class='correct'>");
			good += 1;
		}else{
			node.style.color = "blue";
		}
	}
	return [good, bad, json.length]
};

dacura.grabber.makeContent = function (contents){
	var x = '<span class="pop">' + contents + '</span>';
	return x;
};

var css = ".pop{border:1px #f00 solid;background:#fbc;padding:3px;visibility:hidden;position:absolute;left:1.6em;margin:1.6em 0;color:#000;}"
	+ ".mistake:hover span{visibility:visible;}"
	+ "#modal-dim{width:100%;height:100%;background:rgba(127,127,127,0.5);position:absolute;left:0;top:0;display:block;}"
	+ "#modal{width:25em;height:10em;background:#fff;position:absolute;left:0;"
		+ "right:0;top:0;bottom:0;margin:auto;border:1px solid #000;border-radius:1em;padding:1em;}"
var style = document.createElement("style");
style.type = 'text/css';
if (style.styleSheet){
	style.styleSheet.cssText = css;
}else{
	style.appendChild(document.createTextNode(css));
}
document.head.appendChild(style);
if($("#ca-grab").length){
	//do nothing
}else{
	$("<li id='ca-grab'><span><a>Validate</a></span></li>").insertBefore("#ca-view");
};
$('#ca-grab').click(function(){
	var errorNumber = 0;
	$('#ca-grab').hide();
	$("body").css("overflow", "hidden");
	$(".correct").remove();
	$(".error").remove();
	$("#modal-next").remove();
	$("#modal-prev").remove();
	var user = $("#pt-userpage").text();
	var nga = "";
	var polity = $.trim($("#firstHeading").text())
	x = {"nga": nga, "polity": polity, "user": user};
	y = [{"metadata": x, "data": dacura.grabber.grab(document)}];
	if($("#modal").length){
		$("#modal-dim").show();
		$("#modal-text").html("Analysing variables on page...");
		$("#modal").show();
	}else{
		dacura.grabber.insertModal();
	}
	xhr = {};
	xhr.data = {data: JSON.stringify(y)};
	xhr.url = "<?=$service->my_url("rest")?>/parse";
	xhr.type = "POST";
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		errorNumber = 0;
		if($("#modal-results").length){
			$("#modal-results").show();
		}else{
			$("#modal").append("<button id='modal-results'>View results</button>");
		}
		$("#modal-results").click(function(){
			errorNumber = 0;
			$("body").css("overflow", "auto");
			$("#modal-dim").hide();
			$("#modal-results").hide();
			$("#modal").css("position", "fixed");
			$("#modal").css("top", "2em");
			$("#modal").css("right", "2em");
			$("#modal").css("left", "auto");
			$("#modal").css("bottom", "auto");
			$("#modal").css("margin", "none");
			if($("#modal-next").length){
				$("#modal-next").show();
			}else{
				$("#modal").append("<button id='modal-next'>Next error</button>");
				$("#modal-next").click(function(){
					errorNumber = errorNumber + 1;
					if(errorNumber > errorLength){
						errorNumber = 1;
					}
					var anchor = "#error" + errorNumber;
					$('html, body').animate({
						scrollTop: $(anchor).offset().top
					}, 1000);
				});
			}
			if($("#modal-prev").length){
				$("#modal-prev").show();
			}else{
				$("#modal").append("<button id='modal-prev'>Previous error</button>");
				$("#modal-prev").click(function(){
					errorNumber = errorNumber - 1;
					if(errorNumber < 1){
						errorNumber = errorLength;
					}
					var anchor = "#error" + errorNumber;
					$('html, body').animate({
						scrollTop: $(anchor).offset().top
					}, 1000);
				});
			}
		});
		y = JSON.parse(response);
		x = dacura.grabber.display(y[0]["data"]);
		$("#modal-text").html("Analysis completed successfully.<br>Results:<br>" + x[2] + " results<br>" + x[0] + " correct<br>" + x[1] + " syntax errors");
		var errorLength = x[1];

	})
	.fail(function (jqXHR, textStatus){
		alert("Scan failed. Error: " + jqXHR.responseText);
	});
});
