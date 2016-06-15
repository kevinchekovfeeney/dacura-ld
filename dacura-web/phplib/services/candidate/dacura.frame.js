
dacura.frame = {};
dacura.frame.api = {};
dacura.frame.apiurl = dacura.system.apiURL();

dacura.frame.api.getFrame = function (cls) {
    var xhr = {};
    xhr.type = "POST";
    xhr.url = dacura.frame.apiurl + "/frame";
    xhr.data = {'class': cls};
    xhr.dataType = "json";
    return xhr;
};


function FrameViewer(cls, target, pconfig) {
    this.cls = cls;
    this.target = target;
    this.pconfig = pconfig;
}


//Loading google maps api
//window.onload 

// A $( document ).ready() block.
$( document ).ready(function() {
    console.log( "ready!" );
    var head = document.getElementsByTagName("head")[0];
	var script = document.createElement("script");
	script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDD_KgqQgwVDiXFFVFDiwypsBN_k9TLJD8";//&callback=createMap";
	head.appendChild(script);
});



//global variables for maps use
var marker = null;
var position;
var longitudeId, latitudeId; 

function initMap() {     
	  var myLatlng = {lat: -25.363, lng: 131.044};
	  var mapProp = {
	  center:myLatlng,
	  zoom: 8,
	  mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	var map=new google.maps.Map(document.getElementById("googleMap"), mapProp);
	  
	google.maps.event.addListener(map, 'click', function(event) {
		    position = event.latLng;
		    if(marker == null)
		    	placeMarker(position);
   		    else{
   		    	marker.setPosition(position);	
   		    } 		 
		    var long = document.getElementById("http://dacura.cs.tcd.ie/data/seshattiny#longitude");
		    long.value = marker.getPosition().lng();
		    var lat = document.getElementById("http://dacura.cs.tcd.ie/data/seshattiny#latitude");
		    lat.value = marker.getPosition().lat();
   		});

	function placeMarker(location) {
	     marker = new google.maps.Marker({
	        position: location, 
	        map: map
	    });
	}
}

function createMap(){
	$('#ldo-details').append('<div id="googleMap" style="width:200%;height:380px;margin-top:5%;margin-bottom:5%"></div>');
	initMap();	

}
function deleteMap(){
    marker = null;
 	$("#googleMap").remove();
}

FrameViewer.prototype.draw = function(frames, mode){
		$('br').remove();
		$('.html-create-form').remove();
		$('.dacura-html-viewer').remove();
		deleteMap();
		this.mode = mode;
		this.frames = frames;
		var insiderObj = "";
		var data = "";

		if(frames.length > 0){
			for (var i = 0; i < frames.length; i++) {
				var f = JSON.stringify(frames[i]);
				var parsed = JSON.parse(f);

				if(parsed.type == "objectProperty"){

					$('#ldo-contents').append("<br><div class='dacura-html-viewer'> " + parsed.label.data + "</div>");
					$('#ldo-details').append("<br><div class='html-create-form'> " + parsed.label.data + "</div>");
					if(((parsed.range).split('#'))[1] == "PointInSpace"){
						createMap();
					}
					for (var j = 0; j <  parsed.frame.length; j++) {
						if(parsed.frame[j].value != undefined)
							data = parsed.value.data;
						else
							 data = "";
						if(mode == "create"){
							$('#ldo-details').append("<div class='html-create-form'> " + parsed.frame[j].label.data + " <br> <input type='text' name='"+parsed.property+"' id=" +parsed.frame[j].property+">");
							if(parsed.frame[j].label.data == "Latitude")
								latitudeId = parsed.frame[j].label.data;
							else if(parsed.frame[j].label.data == "Longitude")
								longitudeId = parsed.frame[j].label.data;

						}else if(mode == "view"){
							$('#ldo-contents').append("<div class='dacura-html-viewer'> " +parsed.frame[j].label.data + " <br>" + data + "<br></div>");	
						}else{/*EDIT MODE HERE*/}
					}	

				}else if(parsed.type == "datatypeProperty"){

					//Since data can be empty, this is used to set in case it is not empty
					if(parsed.value != undefined){
						data = parsed.value.data;
					}
					else 
						data = "";
				
					if(mode == "create"){
						$('#ldo-details').append("<div class='html-create-form'> " + parsed.label.data + " <br> <input type='text' name='"+parsed.property+"'><br>");						
					}else if(mode == "view"){
						$('#ldo-contents').append("<br><div class='dacura-html-viewer'>" + parsed.label.data + " <br><p>" + data + "</p><br>");	
					}else{/*EDIT MODE HERE*/}

				}
			};
				$('#ldo-contents').append("</div> <br><br>");

		}
		else {
			
		}	

};


FrameViewer.prototype.extract = function () {
    var y = '{"rdf:type": "' + this.cls + '", ';
    y = y + dacura.frame.extractionConverter(this.frames);
    y = y + '}';
    return y;
}

dacura.frame.extractionConverter = function (frameArray){
    //how do we handle sub-properties of things?
    var tmpArray = [];
    for(var i = 0;i<frameArray.length;i++){
        var tmp = frameArray[i];
        var prop = tmp.property;
        var type = tmp.range;
        if(typeof tmp.frame !== 'undefined' && tmp.frame){
            var data = dacura.frame.extractionConverter(tmp.frame);
            var y = '"' + prop + '": {"data": {' + data + '}, "type": "' + type + '"}'
        }else if(typeof tmp.contents !== 'undefined' && tmp.contents){
            var data = tmp.contents;
            var y = '"' + prop + '": {"data": "' + data + '", "type": "' + type + '"}'
        }else{
            alert("something's gone wrong");
        }
        tmpArray.push(y);
    }
    return tmpArray.join(", ");
}

dacura.frame.draw = function (cls, resultobj, pconf, target) {
    //var framestr = resultobj.result;
    var frame = resultobj;//JSON.parse(framestr);
    var obj = document.createElement("div");
    obj.setAttribute('id', target);
    obj.setAttribute('data-class', cls);

    gensym = dacura.frame.Gensym("query");
    res = dacura.frame.frameGenerator(frame, obj, gensym);

    var elt = document.getElementById(pconf.busybox.substring(1));
    if (typeof elt != "undefined" && elt) {
        //need to wipe the div, before appending
        elt.appendChild(res);
    }
    return elt;
};

dacura.frame.initInteractors = function () {
    $('.queryInteractor').each(function (index) {
        var id = $(this).attr('id');
        // put class search interactor here.
    });

}

dacura.frame.Gensym = function (pref) {
    var prefix = pref + "-";
    var count = 0;
    return {
        next: function () {
            var result = prefix + count;
            count += 1;
            return result;
        }
    };
};

dacura.frame.bind = function(obj, prop, elt){
    Object.defineProperty(obj, prop, {
	get: function(){return elt.value;}, 
	set: function(newValue){elt.value = newValue;},
	configurable: true
    });
}

dacura.frame.frameGenerator = function (frame, obj, gensym) {
    //bind each part of the DOM to the relevant part of the JSON object
    //handler to automatically update the frame in memory
    //write this naively - don't worry about potential performance concerns
    //add in a function call that returns an input element
    //rewrite this for better structure
    ////make sure to add refresh option
    //alert(JSON.stringify(frame,null,4));
    /* list of frame options */
    if (frame.constructor == Array) {

        for (var i = 0; i < frame.length; i++) {
            var elt = frame[i];

            if (elt.type == "objectProperty" || elt.type == "datatypeProperty") {

                var propdiv = document.createElement("div");
                if (elt.domain) {
                    propdiv.setAttribute('data-class', elt.domain);
                } else {
                    alert("No domain for: " + (elt.label
                            || elt.property
                            || JSON.stringify(elt, null, 4)));
                }

                var labelnode = document.createElement("label");

                if (elt.label) {
                    var label = elt.label.data; // {'data' : someDataHere, 'type' : SomeTyping}
                    var textnode = document.createTextNode(label + ':');
                    /* if(label == 'Unit of Measure'){
                     alert(JSON.stringify(elt))
                     }*/
                } else {
                    var textnode = document.createTextNode(elt.property + ':');
                }
                var divlabel = document.createElement("div");
                divlabel.setAttribute('style', 'float: left');
                divlabel.appendChild(textnode);
                labelnode.appendChild(divlabel);
                labelnode.setAttribute('data-property', elt.property);

                propdiv.appendChild(labelnode);

                if (elt.type == 'objectProperty') {
                    var subframe = elt.frame;
                    var framediv = document.createElement("div");
                    framediv.setAttribute('class', 'embedded-object');
                    framediv.setAttribute('style', 'padding-left: 5px; display: inline-block;');

                    dacura.frame.frameGenerator(subframe, framediv, gensym);
                    labelnode.appendChild(framediv);
                } else if (elt.type == 'datatypeProperty') {
                    var input = document.createElement("input");
                    var ty = dacura.frame.typeConvert(elt.range);
                    input.setAttribute('type', ty);
                    dacura.frame.bind(elt, "contents", input);
                    elt.contents = elt.label.data;
                    
                    //console.log(elt);

                    labelnode.appendChild(input);
                } else {
                    alert("Impossible: must be either an object or datatype property.");
                }

                propdiv.appendChild(labelnode);
                obj.appendChild(propdiv);

            } else if (elt.type == 'failure') {
                //alert(elt.message + ' class :' + elt.domain);
                alert(JSON.stringify(elt, null, 4));
            } else {
                alert(JSON.stringify(elt, null, 4));
                //alert("What in gods name are we?");
            }
        } // for loop
    } else if (frame.constructor == Object && frame.type == 'entity' || frame.type == 'thing') {
        // we are an entity

        var input = document.createElement("input");
        input.setAttribute('class', 'entity-class');
        input.setAttribute('id', gensym.next());
        input.setAttribute('data-range', (frame.class || frame.type));
        // This should really be a specialised search box.
        input.setAttribute('type', 'text');
        obj.appendChild(input);
    } else {
        alert("We are neither a property nor an entity:" + JSON.stringify(frame, null, 4));
    }

    return obj;

};

dacura.frame.getId = function (obj, gs) {
    return obj.attr('data-id') ? obj.attr('data-id') : gs.next();
};

dacura.frame.entityExtractor = function (target) {
    var gs = dacura.frame.Gensym("_:oid");
    var frame = $(target);
    var id = dacura.frame.getId(frame, gs);
    var cls = frame.attr('data-class');
    var jsonobj = {};
    var res = dacura.frame.objectExtractor(frame, gs);
    res['rdf:type'] = cls;
    jsonobj[id] = res;
    return jsonobj;
};

dacura.frame.hasSubObject = function (obj) {
    if ($(obj).children('div.embedded-object').length > 0) {
        return true;
    } else {
        return false;
    }
}

dacura.frame.hasEntitySubObject = function (obj) {
    if ($(obj).children('div.embedded-object').children('input.entity-class').length > 0) {
        return true;
    } else {
        return false;
    }
}

dacura.frame.hasInputSubObject = function (obj) {
    if ($(obj).children('input').length > 0) {
        return true;
    } else {
        return false;
    }
}

// DDD debugging rubbish left on the ground by Gavin
ex = [];
sub = [];
empty = [];
current = undefined;

dacura.frame.objectExtractor = function (container, gs) {
    var obj = {};
    // Extracts the entity graph as JSON from a div.	
    $(container).children('div').children('label').each(function (elt) {
        var property = $(this).attr('data-property');
        if ('http://dacura.cs.tcd.ie/data/seshat#hasComponent' == property) {
            current = this;
        }
        var subobj = dacura.frame.subObjectExtractor(this, gs);
        obj[property] = subobj;
    });

    return obj;
};

dacura.frame.subObjectExtractor = function (container, gs) {
    var obj = {}

    if (dacura.frame.hasEntitySubObject(container)) {
        return $(container).children('div.embedded-object').children('input.entity-class').val();
    } else if (dacura.frame.hasInputSubObject(container)) {
        return $(container).children('div.embedded-object').children('input').val();
    } else {
        $(container).children('div.embedded-object').each(function (elt) {
            obj = dacura.frame.objectExtractor(this, gs);
            sub.push(obj);
        });
        return obj;
    }

};

dacura.frame.typeConvert = function (ty) {
    // This needs to be extended at each XSD type. 
    switch (ty) {
        case "http://www.w3.org/2001/XMLSchema#boolean" :
            return 'checkbox';
        case "http://www.w3.org/2000/01/rdf-schema#Literal" :
            return 'text';
        default:
            return 'text';
    }
};

dacura.frame.fillFrame = function (entity, target) {
    // do we need 'target'?

    var gs = dacura.frame.Gensym("_:oid");
    var frame = $(target);
    var cls = frame.attr('data-class');
    var jsonobj = {};

    //var id = entity.id;
    //for( var i ; i < entity.length ; i++){

    //}

};

/* 
 
 TODO: 
 
 1. Read / Write and Read-Write modes 
 2. Specific editorial comments to specific fields - add error / highlight of fields
 3. Get highlight to accept an RVO to signal the appropriate region of problem
 4. Take complete pconf object for rendering results / errors (dacura.js - DacuraPageConfig)
 
 
 Look at utilisation of LD dacura results.
 */

dacura.frame.init = function (entity, pconf) {
    // frame is in triple store. 
    //if(entity.meta.latest_status == 'accept'){
    //	alert("frame is in triple store");
    //}else{
    // frame is not in triple store and we have to get a class based frame.

    var eqn = dacura.system.pageURL(entity.ldtype, entity.meta.cid) + "/" + entity.id;
    var cls = entity.meta.type;

    var ajs = dacura.frame.api.getFrame(cls);
    msgs = {"success": "Retrieved frame for " + cls + " class from server", "busy": "retrieving frame for " + cls + " class from server", "fail": "Failed to retrieve frame for class " + cls + " from server"};
    //alert(cls);
    ajs.handleResult = function (resultobj, pconf) {
        var frameid = dacura.frame.draw(cls, resultobj, pconf, 'frame-container');
        //alert(JSON.stringify(entity, null, 4));
        console.log(resultobj);
        //dacura.frame.fillFrame(entity, pconf, 'frame-container'); 
        dacura.frame.initInteractors();
    }

    dacura.system.invoke(ajs, msgs, pconf);
}



