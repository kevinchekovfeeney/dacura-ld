//general notes:
//1. edit mode in individual candidate view doesn't work - where do we change modes?
//2. should probably factor out widget code into a separate section/module - dacura.frame.widget? dacura.widgets?
//3. add checks for when variable is of correct type
//4. should this be changed to insert widgets at generation?
//
////this needs to be set up to properly wait for correct loading
//and then call insertWidgets
var script = document.createElement("script");
script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDD_KgqQgwVDiXFFVFDiwypsBN_k9TLJD8";
document.getElementsByTagName('head')[0].appendChild(script);

dacura.frame = {};
dacura.frame.api = {};
dacura.frame.widgets = {}
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

//global variables for maps use
var marker = null;
var position;
var longitudeId, latitudeId; 


dacura.frame.widgets.createMap = function(jQueryObject, mode, latitude, longitude){
    //refactor to deal with multiple maps
    jQueryObject.next().append('<div id="googleMap" style="width:100%;height:380px;margin-top:5%;margin-bottom:5%;display:block;"></div>');
    dacura.frame.widgets.initMap(mode, latitude, longitude);
}
dacura.frame.widgets.deleteMap = function(){
    //need to generalise this
    marker = null;
    $("#googleMap").remove();
}

FrameViewer.prototype.draw = function(frames, mode){

    this.mode = mode;
    this.frames = frames;
    //$('#ldo-frame-contents').remove();
    var parent = document.getElementById(this.target);
    var container = document.createElement("div");
    container.setAttribute('id', "ldo-frame-contents" + "." + this.target);
    gensym = dacura.frame.Gensym("query");
    res = dacura.frame.frameGenerator(frames, container, gensym, mode);
    parent.appendChild(res);
    dacura.frame.insertWidgets(mode, this.cls);
    return;
};

FrameViewer.prototype.extract = function () {
    var y = dacura.frame.extractionConverter(this.frames, this.cls);
    console.log(y);
    return y;
}

dacura.frame.insertWidgets = function (mode, cls) {
    //this will go through the generated frame and insert complex widgets where necessary
    //shim for now, just adds in the map
    //for each property, check if it's one that has a complex widget and add it in
    //will need to recurse
    var widgets = dacura.frame.widgets.getWidgetList(cls);
    if(typeof widgets == "object"){
	    $("#ldo-frame-contents" + "." + this.target).children().each(function(){
	        //dacura.frame.widgets.insertMap(mode, cls);
	        for(var i = 0;i<widgets.length;i++){
	            if($(this).children().data("property") == widgets[i].label){
	                dacura.frame.widgets.insertMap(mode, cls);
	            }
	        }
	    });
    }
    //dacura.frame.widgets.insertMap(mode, cls)
    return;
}

dacura.frame.widgets.getWidgetList = function(cls){
    //shim for functionality - need to find out the best way to set this up for configurability
    switch(cls){
        case "http://dacura.cs.tcd.ie/data/seshattiny#Polity":
            return [{"label":'http://dacura.cs.tcd.ie/data/seshattiny#capitalCityLocation',
                "function": dacura.frame.widgets.insertMap}];
            break;
    }
}

dacura.frame.widgets.insertMap = function(mode, cls){
    //refactor this to take a DOM object as input
    var latitude = 0;
    var longitude = 0;
    var x = $("div[data-property='http://dacura.cs.tcd.ie/data/seshattiny#capitalCityLocation']");
    var y = x.next().children();
    for(var i = 0;i<y.length;i++){
        z = $(y[i]).children()
        if(mode!="create"){
            if($(z[0]).data("property") == "http://dacura.cs.tcd.ie/data/seshattiny#longitude"){
                longitude = $(z[1]).data("value")
            }else if($(z[0]).data("property") == "http://dacura.cs.tcd.ie/data/seshattiny#latitude"){
                latitude = $(z[1]).data("value")
            }
        }
    }
    dacura.frame.widgets.createMap(x, mode, latitude, longitude);
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
            var data = "{ ";
             if(polygon != null){ 
              var vertices = polygon.getPath();
           
              for (var j =0; j < vertices.getLength(); j++) {
                var xy = vertices.getAt(j);
                if(prop == "http://dacura.scss.tcd.ie/ontology/seshat#latitude"){
                    if(data == "{ ")
                        data = "[ " + xy.lat();
                    else
                        data = data + " , " + xy.lat();
                }                 
                else if(prop == "http://dacura.scss.tcd.ie/ontology/seshat#longitude")
                    if(data == "{ ")
                        data = "[ " + xy.lng();
                    else
                        data = data + " , " + xy.lng();
                
              }
              data = data + " ]";

            }else{
                data = dacura.frame.subframeExtraction(tmp.frame, type);
            }
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

    //deprecated?
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


createSelectBox = function(inputdata){
    var select = document.createElement( 'select' );
                         var option;
                         var inp = "A.C.||B.C.";
                        inp.split( '||' ).forEach(function( item ) {
                            option = document.createElement( 'option' );
                            option.value = option.textContent =   item;
                            select.appendChild( option );
                        });
    return select;
}


createInput = function(elt, contentDiv, mode, inputDiv){
        inputDiv.setAttribute('class', 'property-value');
        var input = dacura.frame.inputSelector(mode);
        var ty = dacura.frame.typeConvert(elt.range);
        inputDiv.setAttribute('id', elt.range);
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
        return input;

}

/********************************************************************************************************
***********************************CODE FOR THE MAP IN TERRITORY AND MAP CREATION FOR ANY PURPOSE ********
*********************************************************************************************************/
var polylineArray = [];
var map;
var lastPoly;

 
function initMap(mapId, mapLat, mapLong, listenerFunction, mode) {
  //In case the latitude and logitude not setted, put some value in it
  if(mapLat == 0)
    mapLat = '41.9010004'; 
  if(mapLong == 0)
    mapLong = '12.500061500000015';

  map = new google.maps.Map(mapId, {
    zoom: 2,
    center: {lat: parseFloat(mapLat), lng: parseFloat(mapLong)}  
  });

 
  createPolyline();

  if(listenerFunction == "addLatLng"){ //In case there is some function being passed
      map.addListener('click', addLatLng);

  }
  if(mode == "create"){   
        google.maps.event.addListener(map, 'click', function(event) {
                position = event.latLng;
                if(marker == null)
                    placeMarker(position);
                else{
                    marker.setPosition(position);   
                }
                console.log(marker.getPosition().lat());
                console.log(marker.getPosition().lng());
                var long = $("div[data-property='http://dacura.cs.tcd.ie/data/seshattiny#longitude']").next().children("input")[0];
                long.value = marker.getPosition().lng();
                var lat = $("div[data-property='http://dacura.cs.tcd.ie/data/seshattiny#latitude']").next().children("input")[0];
                lat.value = marker.getPosition().lat();
            });
   }
   function placeMarker(location) {
         marker = new google.maps.Marker({
            position: location, 
            map: map
        });
   }

}

function createPolyline(){
 var poly = new google.maps.Polyline({
      strokeColor: '#000000',
      strokeOpacity: 1.0,
      strokeWeight: 3
    });
  poly.setMap(map);
  lastPoly = poly;
  polylineArray.push(poly);

}

var firstMarker = null;
var markersArray = [];
var polygon= null;

var coords = [];
var polygonsArray = [];


function clearMarkers(markersArray) {
  for (var i = 0; i < markersArray.length; i++ ) {
    markersArray[i].setMap(null);
  }
  markersArray.length = 0;
}

// Handles click events on a map, and adds a new point to the Polyline.
function addLatLng(event) {
  if(polygon == null){
         var path = lastPoly.getPath();

          path.push(event.latLng);

          // Add a new marker at the new plotted point on the polyline.
          var marker = new google.maps.Marker({
            position: event.latLng,
            title: '#' + path.getLength(),
            map: map
          });

          markersArray.push(marker);
          coords.push(marker.position);

          if(firstMarker == null){
              firstMarker = marker;
          }
          
           firstMarker.addListener('click', function() {
              lastPoly.setMap(null);

              polygon = new google.maps.Polygon({
                paths: coords,
                strokeColor: '#FF0000',
                strokeOpacity: 0.2,
                strokeWeight: 3,
                fillColor: '#FF0000',
                fillOpacity: 0.35
              });
              polygon.setMap(map);
              clearMarkers(markersArray);
              polygonsArray.push(polygon);

          });
  }

}

 //To fix: polygon is still appearing even when clearMap. But if the polygon is not complete, when it is just polylines, it works.
 function clearMap(){
    
    clearMarkers(markersArray);
    lastPoly.setMap(null);
   
      for (var i = polygonsArray.length - 1; i >= 0; i--) {
         polygonsArray[i].setMap(null);
      };
      polygon = null;
    
     polylineArray = [];
   // lastPoly = null;
   // poly = null;
    coords = [];
    createPolyline();
    firstMarker = null;

 }


/********************************************************************************************
********************************************************************************************/



dacura.frame.frameGenerator = function (frame, obj, gensym, mode) {

    //this should probably insert the data into the DOM for redundancy and ease of access
    //console.log(JSON.stringify(frame));
    if (frame.constructor == Array) {
        for (var i = 0; i < frame.length; i++) {
            var elt = frame[i];

            if (elt.type == "objectProperty" || elt.type == "datatypeProperty") {
                //create container
                var contentDiv = document.createElement("div");
                contentDiv.setAttribute('id', "content-div");

                if (elt.domain) {
                    contentDiv.setAttribute('data-class', elt.domain);
                } else {
                    alert("No domain for: " + (elt.label
                            || elt.property
                            || JSON.stringify(elt, null, 4)));
                }

                //create left hand side
                var labelDiv = document.createElement("div");
                labelDiv.setAttribute('class', 'property-label');
                if (elt.label) {
                    var label = elt.label.data; // {'data' : someDataHere, 'type' : SomeTyping}
                    var textnode = document.createTextNode(label + ': ');
                } else {
                    var textnode = document.createTextNode(elt.property + ':');
                }
                labelDiv.appendChild(textnode);
                labelDiv.setAttribute('data-property', elt.property);
                contentDiv.appendChild(labelDiv);





                //create right hand side- BEGIN OF INPUTS
                if (elt.type == 'objectProperty' && elt.frame != "") {
                    if(elt.range == "http://dacura.scss.tcd.ie/ontology/seshat#Territory"){


                        //REMOVE BUTTON
                        var btnDiv = document.createElement("div");
                        var btn = document.createElement("button");        
                        var t = document.createTextNode("clear map");       
                        btn.appendChild(t);
                        btnDiv.appendChild(btn);   
                        btnDiv.style.width = "10%";
                        btnDiv.style.height = "20px";                           
                        contentDiv.appendChild(btnDiv);

                        btn.addEventListener('click', function() {
                                clearMap();
                        }, false)

                        var mapDiv = document.createElement("div");

                        mapDiv.setAttribute('id', 'mapTerritory');
                        mapDiv.style.width = "100%";
                        mapDiv.style.height = "400px";

                       
                        contentDiv.appendChild(mapDiv);
                       
                        initMap(mapDiv, 0, 0, 'addLatLng', "");
                    }
                    else{
                        var subframe = elt.frame;
                        var subframeDiv = document.createElement("div");
                        subframeDiv.setAttribute('class', 'embedded-object');
                        dacura.frame.frameGenerator(subframe, subframeDiv, gensym, mode);
                        contentDiv.appendChild(subframeDiv);
                    }
                    
                    
                } else if (elt.type == 'datatypeProperty' || (elt.type == 'objectProperty' && elt.frame == "")) {
                  
                    var inputDiv = document.createElement("div");
                    inputDiv.setAttribute('class', 'property-value');
                    var input = dacura.frame.inputSelector(mode);
                    var ty = dacura.frame.typeConvert(elt.range);
                    inputDiv.setAttribute('id', elt.range);
                    if(mode =="create"){
                        dacura.frame.bind(elt, "contents", input);
                        elt.contents = elt.label.data;
                    }else{
                        test = elt.label.data;
                        if(typeof elt.value != "undefined"){
                            test = elt.value.data;
                        }
                        var value = document.createTextNode(test);
                        inputDiv.setAttribute('data-value', test);
                        input.appendChild(value);
                    } 
                    inputDiv.appendChild(input);
                    contentDiv.appendChild(inputDiv);
                    var ty = dacura.frame.typeConvert(elt.range);
                    var inputdata = "CE||BCE";
                    if(ty == "text" || ty == "checkbox")
                       input.setAttribute('type', ty);
                    else if(ty == "select"){
                        var select = createSelectBox(inputdata);
                        inputDiv.appendChild(select);
                    }else if(ty == "duration"){
                        var select = createSelectBox(inputdata);
                        var input1 = createInput(elt, contentDiv, mode, inputDiv);                         
                        inputDiv.appendChild(input1);
                        inputDiv.appendChild(select);

                        var input2 = createInput(elt, contentDiv, mode, inputDiv);                         
                        var select2 = createSelectBox(inputdata);

                        inputDiv.appendChild(input);
                        inputDiv.appendChild(select2);
                        contentDiv.appendChild(inputDiv);
                    }
                }/*else if(elt.type == 'objectProperty' && elt.frame == "" ){
                    if(elt.property == "http://dacura.scss.tcd.ie/ontology/seshat#precedingQuasipolity" || elt.property == "http://dacura.scss.tcd.ie/ontology/seshat#succeedingQuasipolity"){
                    //add code here. use the api to get the list of polities for succeding and preceding polities

                    
                    }
                }*/
            } else {
                    alert("Impossible: must be either an object or datatype property.");
              }

                obj.appendChild(contentDiv);
            /*} else*/ if (elt.type == 'failure') {
                //don't think we should hit this, but check again
                //alert(elt.message + ' class :' + elt.domain);
                alert(JSON.stringify(elt, null, 4));
                var failnode = document.createTextNode(elt.message);
                obj.appendChild(failnode);
            } //else {
                //console.log("Element - restriction");
                //console.log(elt);
                //alert(JSON.stringify(elt,null,4));
            //}
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
    alert("entity extractor");
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
        case "http://www.w3.org/2001/XMLSchema#gYear" :
            return 'select';
        case "http://dacura.scss.tcd.ie/ontology/seshat#Duration":
            return 'duration';
        case "http://dacura.scss.tcd.ie/ontology/seshat#Territory":
            return territory;
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