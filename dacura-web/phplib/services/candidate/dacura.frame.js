
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

dacura.frame.api.getFilledFrame = function (id) {
    var xhr = {};
    xhr.type = "GET";
    xhr.url = dacura.frame.apiurl + "/frame/" + id;
    xhr.dataType = "json";
    return xhr;
};

function FrameViewer(cls, target, pconfig) {
    this.cls = cls;
    this.target = target;
    this.pconfig = pconfig;
}

//Loading google maps api
window.onload = function loadScript() {
  var script = document.createElement("script");
  script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDD_KgqQgwVDiXFFVFDiwypsBN_k9TLJD8";//&callback=createMap";
  document.body.appendChild(script);
}

//global variables for maps use
var marker = null;
var position;
var longitudeId, latitudeId; 

function initMap(mode) {
      var myLatlng = {lat: -25.363, lng: 131.044};
      var mapProp = {
      center:myLatlng,
      zoom: 8,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };


    var map=new google.maps.Map(document.getElementById("googleMap"), mapProp);
    
    if(mode == "create"){ 
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
}

function createMap(jQueryObject, mode){
    jQueryObject.parent().append('<div id="googleMap" style="width:80%;height:380px;margin-top:5%;margin-bottom:5%"></div>');
    initMap(mode);  
}

function deleteMap(){
    marker = null;
    $("#googleMap").remove();
}

FrameViewer.prototype.draw = function(frames, mode){
    this.mode = mode;
    this.frames = frames;
	
    if(mode=="create"){
        var container = document.getElementById("ldo-create-contents");
    }else{
        var container = document.getElementById("ldo-contents");
    }
    gensym = dacura.frame.Gensym("query");
    res = dacura.frame.frameGenerator(frames, container, gensym, mode);
    dacura.frame.insertWidgets(mode);
    return res;
};

FrameViewer.prototype.extract = function () {
    var y = dacura.frame.extractionConverter(this.frames, this.cls);
    return y;
}

dacura.frame.insertWidgets = function (mode) {
    //this will go through the generated frame and insert complex widgets where necessary
    //shim for now, just adds in the map
    var x = $("div[data-property='http://dacura.cs.tcd.ie/data/seshattiny#capitalCityLocation']");
    console.log(x);
    createMap(x, mode);
    return;
}

dacura.frame.generateUUID = function(){
    var d = new Date().getTime();
    if(window.performance && typeof window.performance.now === "function"){
        d += performance.now(); //use high-precision timer if available
    }
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x3|0x8)).toString(16);
    });
    return uuid;
}

dacura.frame.extractionConverter = function (frameArray, cls){
    //this and subframeExtraction can probably be refactored to reduce duplication
    var tmpArray = [];
    tmpArray.push('"rdf:type": "' + cls + '"')
    for(var i = 0;i<frameArray.length;i++){
        var tmp = frameArray[i];
        var prop = tmp.property;
        var type = tmp.range;
        if(typeof tmp.frame !== 'undefined' && tmp.frame){
            var data = dacura.frame.subframeExtraction(tmp.frame, type);
            var y = '"' + prop + '": ' + data;
        }else if(typeof tmp.contents !== 'undefined' && tmp.contents){
            //is this the correct language type?
            var lang = tmp.label.lang;
            var data = tmp.contents;
            var y = '"' + prop + '": {"data": "' + data + '", "type": "' + type + '", "lang": "' + lang + '"}'
        }else{
            alert("something's gone wrong");
        }
        tmpArray.push(y);
    }
    return "{" + tmpArray.join(", ") + "}";
}

dacura.frame.subframeExtraction = function(frameArray, cls){
    var tmpArray = []
    for(var i = 0;i<frameArray.length;i++){
        var uniqueID = "_:" + dacura.frame.generateUUID();
        var tmp = frameArray[i];
        var prop = tmp.property;
        var type = tmp.range;
        if(typeof tmp.frame !== 'undefined' && tmp.frame){
            var data = dacura.frame.subframeExtraction(tmp.frame, type);
            var y = '"' + prop + '": ' + data;
        }else if(typeof tmp.contents !== 'undefined' && tmp.contents){
            var data = tmp.contents;
            var lang = tmp.label.lang;
            var y = '"' + uniqueID + '": {"rdf:type": "' + cls + '", ';
            y = y + '"' + prop + '": {' + '"type": "' + type + '", "data": "' + data + '"}}'
        }else{
            alert("something's gone wrong");
        }
        tmpArray.push(y);
    }
    return "{" + tmpArray.join(", ") + "}";
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

dacura.frame.frameGenerator = function (frame, obj, gensym, mode) {
    //console.log(JSON.stringify(frame));
    if (frame.constructor == Array) {
        for (var i = 0; i < frame.length; i++) {
            var elt = frame[i];
            if (elt.type == "objectProperty" || elt.type == "datatypeProperty") {
                //create container
                var contentDiv = document.createElement("div");
                if (elt.domain) {
                    contentDiv.setAttribute('data-class', elt.domain);
                } else {
                    alert("No domain for: " + (elt.label
                            || elt.property
                            || JSON.stringify(elt, null, 4)));
                }

                //create left hand side
                var labelDiv = document.createElement("div");
                labelDiv.setAttribute('background-color', '#f00');
                if (elt.label) {
                    var label = elt.label.data; // {'data' : someDataHere, 'type' : SomeTyping}
                    var textnode = document.createTextNode(label + ':');
                } else {
                    var textnode = document.createTextNode(elt.property + ':');
                }
                labelDiv.appendChild(textnode);
                labelDiv.setAttribute('data-property', elt.property);
                contentDiv.appendChild(labelDiv);

                //create right hand side
                if (elt.type == 'objectProperty') {
                    var subframe = elt.frame;
                    var subframeDiv = document.createElement("div");
                    subframeDiv.setAttribute('class', 'embedded-object');
                    dacura.frame.frameGenerator(subframe, subframeDiv, gensym, mode);
                    contentDiv.appendChild(subframeDiv);
                } else if (elt.type == 'datatypeProperty') {
                    var input = dacura.frame.inputSelector(mode);
                    var ty = dacura.frame.typeConvert(elt.range);
                    input.setAttribute('type', ty);
                    if(mode =="create"){
                        dacura.frame.bind(elt, "contents", input);
                        elt.contents = elt.label.data;
                    }else{
                        test = elt.label.data;
                        if(typeof elt.value != "undefined"){
                            test = elt.value.data;
                        }
                        var value = document.createTextNode(test);
                        input.appendChild(value);
                    }
                    contentDiv.appendChild(input);
                } else {
                    alert("Impossible: must be either an object or datatype property.");
                }
                obj.appendChild(contentDiv);
            } else if (elt.type == 'failure') {
                //don't think we should hit this, but check again
                //alert(elt.message + ' class :' + elt.domain);
                alert(JSON.stringify(elt, null, 4));
                var failnode = document.createTextNode(elt.message);
                obj.appendChild(failnode);
            } else {
                //console.log("Element - restriction");
                //console.log(elt);
                //alert(JSON.stringify(elt,null,4));
            }
        }
    } else if (frame.constructor == Object && frame.type == 'entity' || frame.type == 'thing') {
        // we are an entity
        var input = document.createElement("input");
        input.setAttribute('class', 'entity-class');
        input.setAttribute('id', gensym.next());
        input.setAttribute('data-range', (frame.class || frame.type));
        // This should really be a specialised search box.
        input.setAttribute('type', 'text');
        obj.appendChild(input);
    } else if (frame.constructor == Object && frame.type == 'failure') {
        // log to console
        console.log("Entity - failure");
        console.log(frame);
    } else if (frame.constructor == Object && frame.type == 'restriction') {
        // log to console
        console.log("Entity - restriction");
        console.log(frame);
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

dacura.frame.inputSelector = function (mode){
    //for now, just mode - will include the type of input later
    if(mode == "create"){
        var input = document.createElement("input");
    }else if(mode == "edit"){
        var input = document.createElement("div");
    }else{
        var input = document.createElement("div");
    }
    return input;
}

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



